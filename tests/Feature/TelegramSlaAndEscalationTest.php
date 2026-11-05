<?php

namespace Tests\Feature;

use App\Models\CustomerModel;
use App\Models\Laporan;
use App\Models\LaporanLog;
use App\Models\LaporanPesan;
use App\Models\Pembelian;
use App\Models\Produk;
use App\Models\SettingWebsite;
use App\Models\TelegramSession;
use App\Models\TipeLayanan;
use App\Models\Toko;
use App\Models\User;
use App\Models\VarianLayanan;
use App\Services\Laporan\LaporanService;
use App\Services\Telegram\CallbackRouter;
use App\Services\Telegram\FlowHandler;
use App\Services\Telegram\TelegramClient;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mockery;
use Tests\TestCase;

class TelegramSlaAndEscalationTest extends TestCase
{
    use DatabaseTransactions;

    protected int $adminGroupId = -1009999888;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.telegram.enabled' => true]);
        config(['services.telegram.admin_group_id' => $this->adminGroupId]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_authorization_matrix_for_seller_and_admin_actions(): void
    {
        $sellerChatId = 20001;
        $adminChatId = 20002;
        $strangerChatId = 20003;

        $seller = User::factory()->seller()->withTelegram($sellerChatId, 'seller_tg_auth')->create();
        $admin = User::factory()->admin()->withTelegram($adminChatId, 'admin_tg_auth')->create();
        $stranger = User::factory()->withTelegram($strangerChatId, 'stranger_tg_auth')->create();
        $customer = User::factory()->create();

        $toko = Toko::create([
            'user_id' => $seller->id,
            'nama_toko' => 'Toko Auth Test',
            'no_telp' => '081234567891',
            'akun_telegram' => '@tokoauthtest',
            'saldo' => 0,
            'status' => 'aktif',
        ]);

        $laporan = Laporan::factory()->create([
            'user_id' => $customer->id,
            'assigned_to' => null,
            'status' => 'pending',
            'first_response_at' => null,
        ]);

        $mockClient = Mockery::mock(TelegramClient::class);
        $this->app->instance(TelegramClient::class, $mockClient);

        $callbackRouter = new CallbackRouter($mockClient, null, app(LaporanService::class));

        // 1. Stranger mencoba 'ambil' tiket -> Ditolak
        $mockClient->shouldReceive('answerCallbackQuery')
            ->once()
            ->with('cb_stranger', Mockery::on(fn($params) => str_contains($params['text'], 'tidak memiliki hak akses') && $params['show_alert'] === true));

        $callbackRouter->handle([
            'id' => 'cb_stranger',
            'from' => ['id' => $strangerChatId],
            'message' => ['chat' => ['id' => $strangerChatId], 'message_id' => 101],
            'data' => "lp:{$laporan->id}:ambil",
        ]);

        $this->assertNull($laporan->fresh()->assigned_to);

        // 2. Seller pemilik toko 'ambil' tiket -> Diterima, assigned_to = seller.id, status = proses, first_response_at terisi
        $laporan->assigned_to = null;
        $laporan->save();

        // Hubungkan tiket dengan toko seller melalui assigned_to atau dummy order
        $laporan->update(['assigned_to' => $seller->id]); // set initial assignment or assign directly

        $mockClient->shouldReceive('answerCallbackQuery')
            ->once()
            ->with('cb_seller_ambil', Mockery::on(fn($params) => str_contains($params['text'], 'Tiket berhasil diambil')));
        $mockClient->shouldReceive('editMessageText')->once()->andReturn(['ok' => true]);

        $callbackRouter->handle([
            'id' => 'cb_seller_ambil',
            'from' => ['id' => $sellerChatId],
            'message' => ['chat' => ['id' => $sellerChatId], 'message_id' => 102],
            'data' => "lp:{$laporan->id}:ambil",
        ]);

        $laporan->refresh();
        $this->assertSame($seller->id, $laporan->assigned_to);
        $this->assertSame('proses', $laporan->status);
        $this->assertNotNull($laporan->first_response_at);

        // Verifikasi log audit tercatat
        $this->assertDatabaseHas('tbl_laporan_log', [
            'laporan_id' => $laporan->id,
            'user_id' => $seller->id,
            'sumber_perubahan' => 'telegram_seller',
            'status_baru' => 'proses',
        ]);

        // 3. Admin 'tetapkan' kembali tiket ke seller
        $mockClient->shouldReceive('answerCallbackQuery')
            ->once()
            ->with('cb_admin_tetapkan', Mockery::on(fn($params) => str_contains($params['text'], 'Tiket berhasil ditetapkan')));
        $mockClient->shouldReceive('editMessageText')->once()->andReturn(['ok' => true]);

        $callbackRouter->handle([
            'id' => 'cb_admin_tetapkan',
            'from' => ['id' => $adminChatId],
            'message' => ['chat' => ['id' => $this->adminGroupId], 'message_id' => 103],
            'data' => "lp:{$laporan->id}:tetapkan",
        ]);

        // 4. Stranger mencoba 'tetapkan' -> Ditolak (Hanya admin)
        $mockClient->shouldReceive('answerCallbackQuery')
            ->once()
            ->with('cb_stranger_tetapkan', Mockery::on(fn($params) => str_contains($params['text'], 'tidak memiliki hak akses') && $params['show_alert'] === true));

        $callbackRouter->handle([
            'id' => 'cb_stranger_tetapkan',
            'from' => ['id' => $strangerChatId],
            'message' => ['chat' => ['id' => $strangerChatId], 'message_id' => 104],
            'data' => "lp:{$laporan->id}:tetapkan",
        ]);
    }

    public function test_callback_validation_rejects_action_if_ticket_already_selesai(): void
    {
        $adminChatId = 20004;
        $admin = User::factory()->admin()->withTelegram($adminChatId, 'admin_tg_done')->create();

        $laporan = Laporan::factory()->create([
            'status' => 'selesai',
        ]);

        $mockClient = Mockery::mock(TelegramClient::class);
        $this->app->instance(TelegramClient::class, $mockClient);

        $callbackRouter = new CallbackRouter($mockClient, null, app(LaporanService::class));

        $mockClient->shouldReceive('answerCallbackQuery')
            ->once()
            ->with('cb_done_test', Mockery::on(fn($params) => str_contains($params['text'], 'sudah selesai') && $params['show_alert'] === true));

        $callbackRouter->handle([
            'id' => 'cb_done_test',
            'from' => ['id' => $adminChatId],
            'message' => ['chat' => ['id' => $adminChatId], 'message_id' => 201],
            'data' => "lp:{$laporan->id}:ambil",
        ]);

        $this->assertSame('selesai', $laporan->fresh()->status);
    }

    public function test_escalation_is_strictly_idempotent(): void
    {
        $sellerChatId = 20005;
        $seller = User::factory()->seller()->withTelegram($sellerChatId, 'seller_tg_eskalasi')->create();
        Toko::create([
            'user_id' => $seller->id,
            'nama_toko' => 'Toko Eskalasi',
            'no_telp' => '081234567895',
            'akun_telegram' => '@tokoeskalasi',
            'saldo' => 0,
            'status' => 'aktif',
        ]);

        $laporan = Laporan::factory()->create([
            'assigned_to' => $seller->id,
            'status' => 'pending',
            'escalated_at' => null,
            'telegram_topic_id' => null,
        ]);

        $mockClient = Mockery::mock(TelegramClient::class);
        $mockClient->shouldReceive('isEnabled')->andReturn(true);

        // Expect createForumTopic called EXACTLY ONCE
        $mockClient->shouldReceive('createForumTopic')
            ->once()
            ->with($this->adminGroupId, Mockery::type('string'))
            ->andReturn(['ok' => true, 'result' => ['message_thread_id' => 771122]]);

        // Expect summary message sent into the topic thread
        $mockClient->shouldReceive('sendMessage')
            ->once()
            ->with($this->adminGroupId, Mockery::on(fn($t) => str_contains($t, 'ESKALASI TIKET LAPORAN')), Mockery::on(fn($opt) => ($opt['message_thread_id'] ?? null) === 771122))
            ->andReturn(['ok' => true]);

        $mockClient->shouldReceive('answerCallbackQuery')
            ->once()
            ->with('cb_eskalasi_1', Mockery::on(fn($p) => str_contains($p['text'], 'berhasil dieskalasi')));

        $mockClient->shouldReceive('editMessageText')->once()->andReturn(['ok' => true]);

        $this->app->instance(TelegramClient::class, $mockClient);
        $service = new LaporanService(app(\App\Services\Telegram\NotificationRouter::class), $mockClient);
        $callbackRouter = new CallbackRouter($mockClient, null, $service);

        // 1. Eksekusi eskalasi pertama
        $callbackRouter->handle([
            'id' => 'cb_eskalasi_1',
            'from' => ['id' => $sellerChatId],
            'message' => ['chat' => ['id' => $sellerChatId], 'message_id' => 301],
            'data' => "lp:{$laporan->id}:eskalasi",
        ]);

        $laporan->refresh();
        $this->assertNotNull($laporan->escalated_at);
        $this->assertSame(771122, $laporan->telegram_topic_id);

        // 2. Eksekusi eskalasi kedua (harus diabaikan / tidak panggil createForumTopic lagi)
        $mockClient->shouldReceive('answerCallbackQuery')
            ->once()
            ->with('cb_eskalasi_2', Mockery::on(fn($p) => str_contains($p['text'], 'sudah pernah dieskalasi') && $p['show_alert'] === true));

        $callbackRouter->handle([
            'id' => 'cb_eskalasi_2',
            'from' => ['id' => $sellerChatId],
            'message' => ['chat' => ['id' => $sellerChatId], 'message_id' => 301],
            'data' => "lp:{$laporan->id}:eskalasi",
        ]);

        // Verifikasi createForumTopic tidak dipanggil ulang
        $this->assertSame(771122, $laporan->fresh()->telegram_topic_id);
    }

    public function test_sla_command_sends_reminder_sixty_minutes_before_due(): void
    {
        $sellerChatId = 20006;
        $seller = User::factory()->seller()->withTelegram($sellerChatId, 'seller_tg_sla')->create();
        Toko::create([
            'user_id' => $seller->id,
            'nama_toko' => 'Toko SLA Reminder',
            'no_telp' => '081234567896',
            'akun_telegram' => '@tokoslareminder',
            'saldo' => 0,
            'status' => 'aktif',
        ]);

        // Tiket pending, batas waktu 40 menit lagi (<= 60 menit)
        $laporan = Laporan::factory()->create([
            'assigned_to' => $seller->id,
            'status' => 'pending',
            'first_response_at' => null,
            'sla_due_at' => now()->addMinutes(40),
            'sla_reminder_sent_at' => null,
            'escalated_at' => null,
        ]);

        $mockClient = Mockery::mock(TelegramClient::class);
        $mockClient->shouldReceive('sendMessage')
            ->once()
            ->with($sellerChatId, Mockery::on(fn($t) => str_contains($t, 'PENGINGAT BATAS WAKTU SLA')), Mockery::any())
            ->andReturn(['ok' => true]);
        $this->app->instance(TelegramClient::class, $mockClient);

        // Jalankan artisan laporan:check-sla
        $this->artisan('laporan:check-sla')
            ->assertExitCode(0);

        $laporan->refresh();
        $this->assertNotNull($laporan->sla_reminder_sent_at);

        // Jalankan kedua kali: reminder tidak boleh dikirim ulang (idempoten)
        $this->artisan('laporan:check-sla')
            ->assertExitCode(0);
    }

    public function test_sla_command_escalates_overdue_ticket_once(): void
    {
        $sellerChatId = 20007;
        $seller = User::factory()->seller()->withTelegram($sellerChatId, 'seller_tg_overdue')->create();
        Toko::create([
            'user_id' => $seller->id,
            'nama_toko' => 'Toko SLA Overdue',
            'no_telp' => '081234567897',
            'akun_telegram' => '@tokoslaoverdue',
            'saldo' => 0,
            'status' => 'aktif',
        ]);

        // Tiket telah lewat batas waktu SLA (overdue)
        $laporan = Laporan::factory()->create([
            'assigned_to' => $seller->id,
            'status' => 'pending',
            'first_response_at' => null,
            'sla_due_at' => now()->subMinutes(15),
            'escalated_at' => null,
        ]);

        $mockClient = Mockery::mock(TelegramClient::class);
        $mockClient->shouldReceive('isEnabled')->andReturn(true);
        $mockClient->shouldReceive('createForumTopic')
            ->once()
            ->with($this->adminGroupId, Mockery::type('string'))
            ->andReturn(['ok' => true, 'result' => ['message_thread_id' => 998877]]);

        $mockClient->shouldReceive('sendMessage')
            ->once()
            ->with($this->adminGroupId, Mockery::on(fn($t) => str_contains($t, 'ESKALASI TIKET LAPORAN')), Mockery::any())
            ->andReturn(['ok' => true]);

        $this->app->instance(TelegramClient::class, $mockClient);

        // Jalankan perintah
        $this->artisan('laporan:check-sla')
            ->assertExitCode(0);

        $laporan->refresh();
        $this->assertNotNull($laporan->escalated_at);
        $this->assertSame(998877, $laporan->telegram_topic_id);

        // Verifikasi log tercatat dengan sumber system_sla
        $this->assertDatabaseHas('tbl_laporan_log', [
            'laporan_id' => $laporan->id,
            'sumber_perubahan' => 'system_sla',
        ]);

        // Jalankan perintah sekali lagi: tidak menduplikasi eskalasi
        $this->artisan('laporan:check-sla')
            ->assertExitCode(0);
    }

    public function test_admin_topic_message_saved_to_pesan_and_forwarded_to_counterparts(): void
    {
        $adminChatId = 20008;
        $customerChatId = 20009;
        $sellerChatId = 20010;

        $admin = User::factory()->admin()->withTelegram($adminChatId, 'admin_topic_sender')->create();
        $customer = User::factory()->withTelegram($customerChatId, 'cust_topic_target')->create();
        CustomerModel::create([
            'user_id' => $customer->id,
            'nomor_telepon' => '081234567899',
        ]);

        $seller = User::factory()->seller()->withTelegram($sellerChatId, 'seller_topic_target')->create();
        Toko::create([
            'user_id' => $seller->id,
            'nama_toko' => 'Toko Topic Target',
            'no_telp' => '081234567898',
            'akun_telegram' => '@tokotopictarget',
            'saldo' => 0,
            'status' => 'aktif',
        ]);

        $topicId = 554433;
        $laporan = Laporan::factory()->create([
            'user_id' => $customer->id,
            'assigned_to' => $seller->id,
            'status' => 'pending',
            'telegram_topic_id' => $topicId,
            'first_response_at' => null,
        ]);

        $mockClient = Mockery::mock(TelegramClient::class);

        // Notifikasi ke Customer
        $mockClient->shouldReceive('sendMessage')
            ->once()
            ->with($customerChatId, Mockery::on(fn($t) => str_contains($t, 'Balasan Baru')), Mockery::any())
            ->andReturn(['ok' => true]);

        $this->app->instance(TelegramClient::class, $mockClient);

        $flowHandler = new FlowHandler($mockClient, app(LaporanService::class));

        // Pesan dikirim admin di dalam topic grup admin
        $flowHandler->handle([
            'message_id' => 9055,
            'message_thread_id' => $topicId,
            'chat' => ['id' => $this->adminGroupId, 'type' => 'supergroup'],
            'from' => ['id' => $adminChatId, 'username' => 'admin_topic_sender'],
            'text' => 'Halo tim, kendala customer ini sedang kami ambil alih perbaikannya.',
        ]);

        // Verifikasi tersimpan di tbl_laporan_pesan
        $this->assertDatabaseHas('tbl_laporan_pesan', [
            'laporan_id' => $laporan->id,
            'sender_user_id' => $admin->id,
            'sender_role' => 'admin',
            'sumber' => 'telegram',
            'body' => 'Halo tim, kendala customer ini sedang kami ambil alih perbaikannya.',
        ]);

        // Status tiket berubah ke proses & first_response_at terisi
        $laporan->refresh();
        $this->assertSame('proses', $laporan->status);
        $this->assertNotNull($laporan->first_response_at);
    }
}

