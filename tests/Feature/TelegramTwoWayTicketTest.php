<?php

namespace Tests\Feature;

use App\Enums\PembelianStatus;
use App\Models\CustomerModel;
use App\Models\Laporan;
use App\Models\LaporanPesan;
use App\Models\Pembelian;
use App\Models\Produk;
use App\Models\TelegramMessageMap;
use App\Models\TelegramSession;
use App\Models\TipeLayanan;
use App\Models\Toko;
use App\Models\User;
use App\Models\VarianLayanan;
use App\Services\Laporan\LaporanService;
use App\Services\Telegram\CallbackRouter;
use App\Services\Telegram\CommandRouter;
use App\Services\Telegram\FlowHandler;
use App\Services\Telegram\TelegramClient;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\RateLimiter;
use Mockery;
use Tests\TestCase;

class TelegramTwoWayTicketTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.telegram.enabled' => true]);
        config(['services.telegram.admin_group_id' => -1009999888]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_telegram_reply_to_notification_is_saved_to_ticket_thread(): void
    {
        $chatId = 10001;
        $customer = User::factory()->withTelegram($chatId, 'customer_tg_1')->create();
        CustomerModel::create([
            'user_id' => $customer->id,
            'nomor_telepon' => '081234567891',
        ]);

        $laporan = Laporan::factory()->create([
            'user_id' => $customer->id,
            'judul' => 'Kendala Akses Premium',
            'status' => 'pending',
        ]);

        TelegramMessageMap::create([
            'chat_id' => $chatId,
            'telegram_message_id' => 9999,
            'laporan_id' => $laporan->id,
            'user_id' => $customer->id,
        ]);

        $mockClient = Mockery::mock(TelegramClient::class);
        $mockClient->shouldReceive('sendMessage')
            ->once()
            ->with($chatId, Mockery::on(fn($text) => str_contains($text, 'Pesan Terkirim')))
            ->andReturn(['ok' => true]);
        $this->app->instance(TelegramClient::class, $mockClient);

        $handler = new FlowHandler($mockClient, app(LaporanService::class));

        $updateMessage = [
            'message_id' => 1001,
            'chat' => ['id' => $chatId, 'type' => 'private'],
            'from' => ['id' => $chatId, 'username' => 'customer_tg_1'],
            'text' => 'Halo admin, apakah sudah ada tindak lanjut?',
            'reply_to_message' => [
                'message_id' => 9999,
            ],
        ];

        $handler->handle($updateMessage);

        $this->assertDatabaseHas('tbl_laporan_pesan', [
            'laporan_id' => $laporan->id,
            'sender_user_id' => $customer->id,
            'sender_role' => 'customer',
            'body' => 'Halo admin, apakah sudah ada tindak lanjut?',
            'sumber' => 'telegram',
            'telegram_message_id' => 1001,
        ]);
    }

    public function test_telegram_reply_rejected_if_user_not_authorized(): void
    {
        $chatOwner = 10002;
        $chatIntruder = 10003;

        $owner = User::factory()->withTelegram($chatOwner, 'owner_tg_2')->create();
        $intruder = User::factory()->withTelegram($chatIntruder, 'intruder_tg_2')->create();

        $laporan = Laporan::factory()->create([
            'user_id' => $owner->id,
            'judul' => 'Kendala Tiket Pribadi',
        ]);

        TelegramMessageMap::create([
            'chat_id' => $chatIntruder,
            'telegram_message_id' => 8888,
            'laporan_id' => $laporan->id,
            'user_id' => $owner->id,
        ]);

        $mockClient = Mockery::mock(TelegramClient::class);
        $mockClient->shouldReceive('sendMessage')
            ->once()
            ->with($chatIntruder, Mockery::on(fn($text) => str_contains($text, 'tidak memiliki hak akses')))
            ->andReturn(['ok' => true]);
        $this->app->instance(TelegramClient::class, $mockClient);

        $handler = new FlowHandler($mockClient, app(LaporanService::class));

        $updateMessage = [
            'message_id' => 2002,
            'chat' => ['id' => $chatIntruder, 'type' => 'private'],
            'from' => ['id' => $chatIntruder, 'username' => 'intruder_tg_2'],
            'text' => 'Saya mau intip tiket orang lain',
            'reply_to_message' => [
                'message_id' => 8888,
            ],
        ];

        $handler->handle($updateMessage);

        $this->assertDatabaseMissing('tbl_laporan_pesan', [
            'body' => 'Saya mau intip tiket orang lain',
        ]);
    }

    public function test_credential_filter_blocks_password_in_chat(): void
    {
        $chatId = 10004;
        $customer = User::factory()->withTelegram($chatId, 'customer_tg_3')->create();
        $laporan = Laporan::factory()->create([
            'user_id' => $customer->id,
            'judul' => 'Akun Netflx',
        ]);

        TelegramMessageMap::create([
            'chat_id' => $chatId,
            'telegram_message_id' => 7777,
            'laporan_id' => $laporan->id,
            'user_id' => $customer->id,
        ]);

        $mockClient = Mockery::mock(TelegramClient::class);
        $mockClient->shouldReceive('sendMessage')
            ->once()
            ->with($chatId, Mockery::on(function ($text) {
                return str_contains($text, 'Peringatan Keamanan') &&
                       str_contains($text, 'tidak menerima kredensial akun melalui Telegram');
            }))
            ->andReturn(['ok' => true]);
        $this->app->instance(TelegramClient::class, $mockClient);

        $handler = new FlowHandler($mockClient, app(LaporanService::class));

        $credentialMessage = [
            'message_id' => 3003,
            'chat' => ['id' => $chatId, 'type' => 'private'],
            'from' => ['id' => $chatId],
            'text' => 'Halo ini email saya: user@domain.com password: RahasiaKredensial123 tolong diganti',
            'reply_to_message' => ['message_id' => 7777],
        ];

        $handler->handle($credentialMessage);

        $this->assertDatabaseMissing('tbl_laporan_pesan', [
            'laporan_id' => $laporan->id,
        ]);
    }

    public function test_first_response_at_and_status_update_when_seller_or_admin_replies(): void
    {
        $chatSeller = 10005;
        $customer = User::factory()->create();
        $seller = User::factory()->seller()->withTelegram($chatSeller, 'seller_tg_4')->create();
        $toko = Toko::create([
            'user_id' => $seller->id,
            'nama_toko' => 'Toko Mantap 4',
            'no_telp' => '081234567894',
            'akun_telegram' => '@tokomantap4',
            'saldo' => 0,
            'status' => 'aktif',
        ]);

        $laporan = Laporan::factory()->create([
            'user_id' => $customer->id,
            'assigned_to' => $seller->id,
            'status' => 'pending',
            'first_response_at' => null,
        ]);

        $mockClient = Mockery::mock(TelegramClient::class);
        $mockClient->shouldReceive('sendMessage')->andReturn(['ok' => true]);
        $this->app->instance(TelegramClient::class, $mockClient);

        // Seller membalas tiket via LaporanService
        $pesan = app(LaporanService::class)->addMessage(
            $laporan,
            $seller,
            'Halo customer, kendala sedang kami investigasi.',
            'web'
        );

        $laporan->refresh();
        $this->assertNotNull($laporan->first_response_at);
        $this->assertSame('proses', $laporan->status);
        $this->assertSame('seller', $pesan->sender_role);
    }

    public function test_wizard_lapor_full_flow_success(): void
    {
        $chatId = 10006;
        $customer = User::factory()->withTelegram($chatId, 'customer_tg_5')->create();
        $customerData = CustomerModel::create([
            'user_id' => $customer->id,
            'nomor_telepon' => '081234567895',
        ]);

        $seller = User::factory()->seller()->create();
        $toko = Toko::create([
            'user_id' => $seller->id,
            'nama_toko' => 'Toko Digital Jaya 5',
            'no_telp' => '081234567895',
            'akun_telegram' => '@tokodigitaljaya5',
            'saldo' => 0,
            'status' => 'aktif',
        ]);

        $produk = Produk::create([
            'nama_produk' => 'Canva Pro Lifetime 5',
            'id_toko' => $toko->id_toko,
        ]);

        $tipe = TipeLayanan::create([
            'id_produk' => $produk->id_produk,
            'nama_tipe' => 'Invite Member 5',
        ]);

        $varian = VarianLayanan::create([
            'id_tipe' => $tipe->id_tipe,
            'nama_varian' => '1 Tahun',
            'harga' => 25000,
            'durasi_hari' => 30,
        ]);

        $pembelian = Pembelian::create([
            'order_id' => 'ORD-TEST-99999',
            'id_customer' => $customerData->id,
            'id_varian' => $varian->id_varian,
            'harga_saat_beli' => 25000,
            'status' => PembelianStatus::SUCCESS,
        ]);

        $mockClient = Mockery::mock(TelegramClient::class);
        $mockClient->shouldReceive('answerCallbackQuery')->andReturn(['ok' => true]);
        $mockClient->shouldReceive('sendMessage')->andReturn(['ok' => true]);
        $mockClient->shouldReceive('editMessageText')->andReturn(['ok' => true]);
        $this->app->instance(TelegramClient::class, $mockClient);

        // 1. /lapor
        $cmdRouter = new CommandRouter($mockClient);
        $cmdRouter->handle([
            'chat' => ['id' => $chatId, 'type' => 'private'],
            'from' => ['id' => $chatId],
            'text' => '/lapor',
        ]);

        $this->assertDatabaseHas('tbl_telegram_sessions', [
            'chat_id' => $chatId,
            'flow' => 'lapor',
            'step' => 'select_order',
        ]);

        // 2. Callback pilih order
        $callbackRouter = new CallbackRouter($mockClient);
        $callbackRouter->handle([
            'id' => 'cb_1',
            'message' => ['chat' => ['id' => $chatId], 'message_id' => 501],
            'data' => 'lapor_order_' . $pembelian->order_id,
        ]);

        $this->assertDatabaseHas('tbl_telegram_sessions', [
            'chat_id' => $chatId,
            'step' => 'select_category',
        ]);

        // 3. Callback pilih kategori (Kategori 1: Akun Tidak Bisa Login)
        $callbackRouter->handle([
            'id' => 'cb_2',
            'message' => ['chat' => ['id' => $chatId], 'message_id' => 501],
            'data' => 'lapor_cat_1',
        ]);

        $this->assertDatabaseHas('tbl_telegram_sessions', [
            'chat_id' => $chatId,
            'step' => 'input_kronologi',
        ]);

        // 4. Input kronologi via teks
        $flowHandler = new FlowHandler($mockClient, app(LaporanService::class));
        $flowHandler->handle([
            'message_id' => 502,
            'chat' => ['id' => $chatId, 'type' => 'private'],
            'from' => ['id' => $chatId],
            'text' => 'Akun saya tiba-tiba logout dan muncul password salah.',
        ]);

        $this->assertDatabaseHas('tbl_telegram_sessions', [
            'chat_id' => $chatId,
            'step' => 'input_bukti',
        ]);

        // 5. Lewati foto bukti
        $callbackRouter->handle([
            'id' => 'cb_3',
            'message' => ['chat' => ['id' => $chatId], 'message_id' => 503],
            'data' => 'lapor_skip_photo',
        ]);

        $this->assertDatabaseHas('tbl_telegram_sessions', [
            'chat_id' => $chatId,
            'step' => 'confirm',
        ]);

        // 6. Submit laporan
        $callbackRouter->handle([
            'id' => 'cb_4',
            'message' => ['chat' => ['id' => $chatId], 'message_id' => 504],
            'data' => 'lapor_submit',
        ]);

        // Verifikasi tiket berhasil dibuat di database
        $this->assertDatabaseHas('tbl_laporan', [
            'user_id' => $customer->id,
            'status' => 'pending',
        ]);

        $laporanDibuat = Laporan::where('user_id', $customer->id)->latest()->first();
        $this->assertNotNull($laporanDibuat);
        $this->assertStringContainsString('Akun Tidak Bisa Login', $laporanDibuat->judul);
        $this->assertStringContainsString('ORD-TEST-99999', $laporanDibuat->judul);
        $this->assertSame('Akun saya tiba-tiba logout dan muncul password salah.', $laporanDibuat->deskripsi);

        // Verifikasi sesi telah dihapus
        $this->assertDatabaseMissing('tbl_telegram_sessions', [
            'chat_id' => $chatId,
        ]);
    }

    public function test_wizard_lapor_blocks_expired_warranty_order(): void
    {
        $chatId = 10007;
        $customer = User::factory()->withTelegram($chatId, 'customer_tg_6')->create();
        $customerData = CustomerModel::create(['user_id' => $customer->id]);

        $produk = Produk::create(['nama_produk' => 'Streaming App 6']);
        $tipe = TipeLayanan::create(['id_produk' => $produk->id_produk, 'nama_tipe' => 'Shared 6']);

        $varian = VarianLayanan::create([
            'id_tipe' => $tipe->id_tipe,
            'nama_varian' => 'Garansi 3 Hari',
            'harga' => 15000,
            'durasi_hari' => 3,
        ]);

        // Dibuat 10 hari yang lalu (garansi sudah lewat)
        $orderExpired = Pembelian::create([
            'order_id' => 'ORD-EXPIRED-999',
            'id_customer' => $customerData->id,
            'id_varian' => $varian->id_varian,
            'harga_saat_beli' => 10000,
            'status' => PembelianStatus::SUCCESS,
        ]);
        $orderExpired->created_at = now()->subDays(10);
        $orderExpired->save();

        $mockClient = Mockery::mock(TelegramClient::class);
        $mockClient->shouldReceive('sendMessage')
            ->once()
            ->with($chatId, Mockery::on(fn($t) => str_contains($t, 'tidak memiliki pesanan berstatus SUCCESS yang masih dalam masa garansi')))
            ->andReturn(['ok' => true]);
        $this->app->instance(TelegramClient::class, $mockClient);

        $cmdRouter = new CommandRouter($mockClient);
        $cmdRouter->handle([
            'chat' => ['id' => $chatId, 'type' => 'private'],
            'from' => ['id' => $chatId],
            'text' => '/lapor',
        ]);

        $this->assertDatabaseMissing('tbl_telegram_sessions', [
            'chat_id' => $chatId,
        ]);
    }

    public function test_rate_limiting_three_tickets_per_hour(): void
    {
        $customer = User::factory()->create();
        $service = app(LaporanService::class);

        $mockClient = Mockery::mock(TelegramClient::class);
        $mockClient->shouldReceive('sendMessage')->andReturn(['ok' => true]);
        $this->app->instance(TelegramClient::class, $mockClient);

        RateLimiter::clear('laporan_create:' . $customer->id);

        // Buat 3 tiket sukses
        for ($i = 1; $i <= 3; $i++) {
            $service->createTicket($customer, [
                'judul' => "Tiket {$i}",
                'deskripsi' => "Deskripsi {$i}",
            ]);
        }

        // Tiket ke-4 harus gagal karena rate limit
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $service->createTicket($customer, [
            'judul' => 'Tiket 4 Melebihi Batas',
            'deskripsi' => 'Deskripsi 4',
        ]);
    }

    public function test_command_tiket_and_batal(): void
    {
        $chatId = 10008;
        $customer = User::factory()->withTelegram($chatId, 'cust_tiket_7')->create();

        Laporan::factory()->create([
            'user_id' => $customer->id,
            'judul' => 'Kendala Aktif',
            'status' => 'pending',
        ]);

        Laporan::factory()->create([
            'user_id' => $customer->id,
            'judul' => 'Kendala Sudah Selesai',
            'status' => 'selesai',
        ]);

        $mockClient = Mockery::mock(TelegramClient::class);
        $mockClient->shouldReceive('sendMessage')
            ->once()
            ->with($chatId, Mockery::on(function ($text) {
                return str_contains($text, 'Tiket Aktif') &&
                       str_contains($text, 'Kendala Aktif') &&
                       str_contains($text, 'Kendala Sudah Selesai');
            }), Mockery::hasKey('reply_markup'))
            ->andReturn(['ok' => true]);

        $mockClient->shouldReceive('sendMessage')
            ->once()
            ->with($chatId, Mockery::on(fn($t) => str_contains($t, 'dibatalkan')))
            ->andReturn(['ok' => true]);
        $this->app->instance(TelegramClient::class, $mockClient);

        TelegramSession::create([
            'chat_id' => $chatId,
            'flow' => 'lapor',
            'step' => 'select_order',
            'payload' => [],
            'expires_at' => now()->addMinutes(10),
        ]);

        $cmdRouter = new CommandRouter($mockClient);

        // 1. /tiket
        $cmdRouter->handle([
            'chat' => ['id' => $chatId, 'type' => 'private'],
            'from' => ['id' => $chatId],
            'text' => '/tiket',
        ]);

        // 2. /batal
        $cmdRouter->handle([
            'chat' => ['id' => $chatId, 'type' => 'private'],
            'from' => ['id' => $chatId],
            'text' => '/batal',
        ]);

        $this->assertDatabaseMissing('tbl_telegram_sessions', [
            'chat_id' => $chatId,
        ]);
    }

    public function test_website_thread_conversation_view_and_reply(): void
    {
        $customer = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $laporan = Laporan::factory()->create([
            'user_id' => $customer->id,
            'judul' => 'Kendala Web Thread',
            'deskripsi' => 'Deskripsi awal pembuka tiket.',
            'status' => 'pending',
        ]);

        LaporanPesan::create([
            'laporan_id' => $laporan->id,
            'sender_user_id' => $customer->id,
            'sender_role' => 'customer',
            'body' => 'Deskripsi awal pembuka tiket.',
            'sumber' => 'web',
        ]);

        $mockClient = Mockery::mock(TelegramClient::class);
        $mockClient->shouldReceive('sendMessage')->andReturn(['ok' => true]);
        $this->app->instance(TelegramClient::class, $mockClient);

        // Customer melihat thread tiket
        $response = $this->actingAs($customer)->get(route('laporan.show', $laporan->id));
        $response->assertStatus(200);
        $response->assertSee('Deskripsi awal pembuka tiket.');

        // Customer mengirim balasan via form web
        $replyResponse = $this->actingAs($customer)->post(route('laporan.pesan.store', $laporan->id), [
            'pesan' => 'Tambahan detail kendala dari saya via web.',
        ]);
        $replyResponse->assertRedirect(route('laporan.show', $laporan->id));

        $this->assertDatabaseHas('tbl_laporan_pesan', [
            'laporan_id' => $laporan->id,
            'sender_user_id' => $customer->id,
            'body' => 'Tambahan detail kendala dari saya via web.',
            'sumber' => 'web',
        ]);

        // Admin melihat thread tiket
        $adminView = $this->actingAs($admin)->get(route('laporan.show', $laporan->id));
        $adminView->assertStatus(200);
        $adminView->assertSee('Tambahan detail kendala dari saya via web.');

        // Admin membalas thread via web
        $adminReply = $this->actingAs($admin)->post(route('laporan.pesan.store', $laporan->id), [
            'pesan' => 'Halo customer, tiket Anda sudah kami cek dan perbaiki.',
        ]);
        $adminReply->assertRedirect(route('laporan.show', $laporan->id));

        $this->assertDatabaseHas('tbl_laporan_pesan', [
            'laporan_id' => $laporan->id,
            'sender_user_id' => $admin->id,
            'sender_role' => 'admin',
            'body' => 'Halo customer, tiket Anda sudah kami cek dan perbaiki.',
            'sumber' => 'web',
        ]);

        // User lain yang tidak berhak mencoba mengakses -> 403
        $stranger = User::factory()->create();
        $strangerResponse = $this->actingAs($stranger)->get(route('laporan.show', $laporan->id));
        $strangerResponse->assertStatus(403);
    }

    public function test_rate_limiter_twenty_messages_per_minute_per_chat(): void
    {
        $chatId = 10009;
        $customer = User::factory()->withTelegram($chatId, 'cust_rate_limit')->create();

        RateLimiter::clear('telegram_msg:' . $chatId);

        $mockClient = Mockery::mock(TelegramClient::class);
        $mockClient->shouldReceive('sendMessage')->times(20)->andReturn(['ok' => true]);
        $mockClient->shouldReceive('sendMessage')
            ->once()
            ->with($chatId, Mockery::on(fn($t) => str_contains($t, 'Terlalu banyak pesan')))
            ->andReturn(['ok' => true]);
        $this->app->instance(TelegramClient::class, $mockClient);

        $handler = new FlowHandler($mockClient, app(LaporanService::class));

        for ($i = 1; $i <= 20; $i++) {
            $handler->handle([
                'message_id' => 7000 + $i,
                'chat' => ['id' => $chatId, 'type' => 'private'],
                'from' => ['id' => $chatId],
                'text' => "Pesan ke-{$i}",
            ]);
        }

        // Pesan ke-21 harus ditolak oleh rate limiter
        $handler->handle([
            'message_id' => 7021,
            'chat' => ['id' => $chatId, 'type' => 'private'],
            'from' => ['id' => $chatId],
            'text' => 'Pesan ke-21 yang melampaui limit',
        ]);

        $this->assertTrue(RateLimiter::tooManyAttempts('telegram_msg:' . $chatId, 20));
    }

    public function test_reply_ticket_callback_creates_balas_session_and_saves_reply(): void
    {
        $chatId = 10010;
        $customer = User::factory()->withTelegram($chatId, 'cust_balas_session')->create();
        $laporan = Laporan::factory()->create([
            'user_id' => $customer->id,
            'judul' => 'Kendala Tombol Balas',
            'status' => 'pending',
        ]);

        $mockClient = Mockery::mock(TelegramClient::class);
        $mockClient->shouldReceive('answerCallbackQuery')->andReturn(['ok' => true]);
        $mockClient->shouldReceive('sendMessage')
            ->once()
            ->with($chatId, Mockery::on(fn($t) => str_contains($t, 'Balas Tiket')))
            ->andReturn(['ok' => true]);
        $mockClient->shouldReceive('sendMessage')
            ->once()
            ->with($chatId, Mockery::on(fn($t) => str_contains($t, 'Pesan Terkirim')))
            ->andReturn(['ok' => true]);
        $this->app->instance(TelegramClient::class, $mockClient);

        // 1. User menekan tombol '💬 Balas' pada notifikasi
        $callbackRouter = new CallbackRouter($mockClient);
        $callbackRouter->handle([
            'id' => 'cb_balas_1',
            'message' => ['chat' => ['id' => $chatId], 'message_id' => 8001],
            'data' => 'reply_ticket_' . $laporan->id,
        ]);

        $this->assertDatabaseHas('tbl_telegram_sessions', [
            'chat_id' => $chatId,
            'flow' => 'balas',
            'step' => 'input_pesan',
        ]);

        // 2. User mengirim balasan teks
        $flowHandler = new FlowHandler($mockClient, app(LaporanService::class));
        $flowHandler->handle([
            'message_id' => 8002,
            'chat' => ['id' => $chatId, 'type' => 'private'],
            'from' => ['id' => $chatId],
            'text' => 'Ini tanggapan saya via sesi tombol balas bot.',
        ]);

        $this->assertDatabaseHas('tbl_laporan_pesan', [
            'laporan_id' => $laporan->id,
            'sender_user_id' => $customer->id,
            'body' => 'Ini tanggapan saya via sesi tombol balas bot.',
            'sumber' => 'telegram',
        ]);

        // Sesi telah dihapus setelah pesan terkirim
        $this->assertDatabaseMissing('tbl_telegram_sessions', [
            'chat_id' => $chatId,
        ]);
    }

    public function test_incoming_photo_attachment_is_saved_to_disk_and_thread(): void
    {
        $chatId = 10011;
        $customer = User::factory()->withTelegram($chatId, 'cust_photo_attach')->create();
        $laporan = Laporan::factory()->create([
            'user_id' => $customer->id,
            'judul' => 'Kendala Lampiran Gambar',
            'status' => 'pending',
        ]);

        TelegramSession::create([
            'chat_id' => $chatId,
            'flow' => 'balas',
            'step' => 'input_pesan',
            'payload' => ['laporan_id' => $laporan->id],
            'expires_at' => now()->addMinutes(15),
        ]);

        $mockClient = Mockery::mock(TelegramClient::class);
        $mockClient->shouldReceive('getFile')
            ->once()
            ->with('file_photo_123')
            ->andReturn(['ok' => true, 'result' => ['file_path' => 'photos/test_screen.jpg']]);

        $mockClient->shouldReceive('downloadFile')
            ->once()
            ->with('photos/test_screen.jpg', Mockery::type('string'))
            ->andReturn(true);

        $mockClient->shouldReceive('sendMessage')
            ->once()
            ->with($chatId, Mockery::on(fn($t) => str_contains($t, 'Pesan Terkirim')))
            ->andReturn(['ok' => true]);
        $this->app->instance(TelegramClient::class, $mockClient);

        $flowHandler = new FlowHandler($mockClient, app(LaporanService::class));
        $flowHandler->handle([
            'message_id' => 9001,
            'chat' => ['id' => $chatId, 'type' => 'private'],
            'from' => ['id' => $chatId],
            'caption' => 'Ini bukti screenshot error di layar saya',
            'photo' => [
                ['file_id' => 'small_thumb', 'width' => 100, 'height' => 100],
                ['file_id' => 'file_photo_123', 'width' => 800, 'height' => 800],
            ],
        ]);

        $pesan = LaporanPesan::where('laporan_id', $laporan->id)->latest()->first();
        $this->assertNotNull($pesan);
        $this->assertSame('Ini bukti screenshot error di layar saya', $pesan->body);
        $this->assertNotNull($pesan->lampiran);
        $this->assertStringEndsWith('.jpg', $pesan->lampiran);
    }
}
