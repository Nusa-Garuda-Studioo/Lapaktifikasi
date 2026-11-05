<?php

namespace Tests\Feature;

use App\Models\CustomerModel;
use App\Models\Laporan;
use App\Models\NotifikasiLog;
use App\Models\TelegramMessageMap;
use App\Models\Toko;
use App\Models\User;
use App\Services\FonnteService;
use App\Services\Telegram\Exceptions\TelegramChatBlockedException;
use App\Services\Telegram\NotificationRouter;
use App\Services\Telegram\TelegramClient;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mockery;
use Tests\TestCase;

class TelegramNotificationRouterTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.telegram.enabled' => true]);
        config(['services.telegram.admin_group_id' => -1009999888]);
    }

    public function test_customer_with_telegram_receives_telegram_and_logs_sent(): void
    {
        $customer = User::factory()->withTelegram(11223344, 'customertg')->create([
            'email' => 'customer_rahasia@example.com',
        ]);
        CustomerModel::create([
            'user_id' => $customer->id,
            'nomor_telepon' => '081234567890',
        ]);

        $laporan = Laporan::factory()->create([
            'user_id' => $customer->id,
            'judul' => 'Kendala Akun Tidak Bisa Login',
            'deskripsi' => 'Mohon bantuan garansi akun.',
        ]);

        $mockClient = Mockery::mock(TelegramClient::class);
        $mockClient->shouldReceive('sendMessage')
            ->once()
            ->with(11223344, Mockery::on(function ($text) use ($customer) {
                // Must contain ticket details
                $hasTitle = str_contains($text, 'Kendala Akun Tidak Bisa Login');
                // MUST NOT contain sensitive data
                $noEmail = !str_contains($text, $customer->email);
                $noPhone = !str_contains($text, '081234567890');
                return $hasTitle && $noEmail && $noPhone;
            }), Mockery::hasKey('reply_markup'))
            ->andReturn(['ok' => true, 'result' => ['message_id' => 778899]]);

        // Admin group notification mock
        $mockClient->shouldReceive('sendMessage')
            ->with(-1009999888, Mockery::any(), Mockery::any())
            ->andReturn(['ok' => true, 'result' => ['message_id' => 778800]]);

        $this->app->instance(TelegramClient::class, $mockClient);

        $mockFonnte = Mockery::mock(FonnteService::class);
        $this->app->instance(FonnteService::class, $mockFonnte);

        $router = app(NotificationRouter::class);
        $router->notifyTicketCreated($laporan);

        // Assert Telegram log sent
        $this->assertDatabaseHas('tbl_notifikasi_log', [
            'user_id' => $customer->id,
            'event' => 'laporan_created',
            'channel' => 'telegram',
            'status' => 'sent',
            'laporan_id' => $laporan->id,
        ]);

        // Assert Message Map saved
        $this->assertDatabaseHas('tbl_telegram_message_map', [
            'chat_id' => 11223344,
            'telegram_message_id' => 778899,
            'laporan_id' => $laporan->id,
            'user_id' => $customer->id,
        ]);
    }

    public function test_customer_without_telegram_receives_whatsapp_directly(): void
    {
        $customer = User::factory()->create([
            'email' => 'nowa@example.com',
        ]);
        CustomerModel::create([
            'user_id' => $customer->id,
            'nomor_telepon' => '08987654321',
        ]);

        $laporan = Laporan::factory()->create([
            'user_id' => $customer->id,
            'judul' => 'Kendala Garansi Netflix',
        ]);

        $mockClient = Mockery::mock(TelegramClient::class);
        // Only admin group receives telegram
        $mockClient->shouldReceive('sendMessage')
            ->with(-1009999888, Mockery::any(), Mockery::any())
            ->andReturn(['ok' => true, 'result' => ['message_id' => 123]]);
        $this->app->instance(TelegramClient::class, $mockClient);

        $mockFonnte = Mockery::mock(FonnteService::class);
        $mockFonnte->shouldReceive('sendText')
            ->once()
            ->with('08987654321', Mockery::on(function ($msg) {
                return str_contains($msg, 'Kendala Garansi Netflix');
            }))
            ->andReturn(['status' => true]);
        $this->app->instance(FonnteService::class, $mockFonnte);

        $router = app(NotificationRouter::class);
        $router->notifyTicketCreated($laporan);

        // Telegram skipped log
        $this->assertDatabaseHas('tbl_notifikasi_log', [
            'user_id' => $customer->id,
            'channel' => 'telegram',
            'status' => 'skipped',
            'laporan_id' => $laporan->id,
        ]);

        // WhatsApp sent log
        $this->assertDatabaseHas('tbl_notifikasi_log', [
            'user_id' => $customer->id,
            'channel' => 'whatsapp',
            'status' => 'sent',
            'laporan_id' => $laporan->id,
        ]);
    }

    public function test_customer_fallback_to_whatsapp_when_telegram_fails(): void
    {
        $customer = User::factory()->withTelegram(445566, 'tguser')->create();
        CustomerModel::create([
            'user_id' => $customer->id,
            'nomor_telepon' => '08777888999',
        ]);

        $laporan = Laporan::factory()->create([
            'user_id' => $customer->id,
            'judul' => 'Kendala Spotify Terputus',
        ]);

        // Telegram client throws general error
        $mockClient = Mockery::mock(TelegramClient::class);
        $mockClient->shouldReceive('sendMessage')
            ->with(445566, Mockery::any(), Mockery::any())
            ->andThrow(new \RuntimeException('Connection timeout to Telegram'));
        $mockClient->shouldReceive('sendMessage')
            ->with(-1009999888, Mockery::any(), Mockery::any())
            ->andReturn(['ok' => true]);
        $this->app->instance(TelegramClient::class, $mockClient);

        // Fallback WhatsApp receives message
        $mockFonnte = Mockery::mock(FonnteService::class);
        $mockFonnte->shouldReceive('sendText')
            ->once()
            ->with('08777888999', Mockery::on(function ($msg) {
                return str_contains($msg, 'Kendala Spotify Terputus');
            }))
            ->andReturn(['status' => true]);
        $this->app->instance(FonnteService::class, $mockFonnte);

        $router = app(NotificationRouter::class);
        $router->notifyTicketCreated($laporan);

        // Telegram log failed
        $this->assertDatabaseHas('tbl_notifikasi_log', [
            'user_id' => $customer->id,
            'channel' => 'telegram',
            'status' => 'failed',
            'laporan_id' => $laporan->id,
        ]);

        // WhatsApp fallback sent
        $this->assertDatabaseHas('tbl_notifikasi_log', [
            'user_id' => $customer->id,
            'channel' => 'whatsapp',
            'status' => 'sent',
            'laporan_id' => $laporan->id,
        ]);
    }

    public function test_403_chat_blocked_marks_telegram_blocked_at_and_falls_back(): void
    {
        $customer = User::factory()->withTelegram(998811, 'blockeduser')->create();
        CustomerModel::create([
            'user_id' => $customer->id,
            'nomor_telepon' => '08555666777',
        ]);

        $laporan = Laporan::factory()->create([
            'user_id' => $customer->id,
            'judul' => 'Kendala Youtube Premium',
        ]);

        $mockClient = Mockery::mock(TelegramClient::class);
        $mockClient->shouldReceive('sendMessage')
            ->with(998811, Mockery::any(), Mockery::any())
            ->andThrow(new TelegramChatBlockedException('Bot was blocked by user'));
        $mockClient->shouldReceive('sendMessage')
            ->with(-1009999888, Mockery::any(), Mockery::any())
            ->andReturn(['ok' => true]);
        $this->app->instance(TelegramClient::class, $mockClient);

        $mockFonnte = Mockery::mock(FonnteService::class);
        $mockFonnte->shouldReceive('sendText')
            ->once()
            ->with('08555666777', Mockery::any())
            ->andReturn(['status' => true]);
        $this->app->instance(FonnteService::class, $mockFonnte);

        $router = app(NotificationRouter::class);
        $router->notifyTicketCreated($laporan);

        $customer->refresh();
        $this->assertNotNull($customer->telegram_blocked_at);

        $this->assertDatabaseHas('tbl_notifikasi_log', [
            'user_id' => $customer->id,
            'channel' => 'telegram',
            'status' => 'failed',
            'laporan_id' => $laporan->id,
        ]);

        $this->assertDatabaseHas('tbl_notifikasi_log', [
            'user_id' => $customer->id,
            'channel' => 'whatsapp',
            'status' => 'sent',
            'laporan_id' => $laporan->id,
        ]);
    }

    public function test_status_updated_and_completed_notifications(): void
    {
        $customer = User::factory()->withTelegram(123123, 'statususer')->create();
        $laporan = Laporan::factory()->create([
            'user_id' => $customer->id,
            'judul' => 'Kendala Canva Pro',
            'status' => 'pending',
        ]);

        $mockClient = Mockery::mock(TelegramClient::class);
        $mockClient->shouldReceive('sendMessage')
            ->twice()
            ->andReturn(['ok' => true]);
        $this->app->instance(TelegramClient::class, $mockClient);

        $router = app(NotificationRouter::class);

        // Status pending -> proses
        $router->notifyTicketStatusUpdated($laporan, 'pending', 'proses');
        $this->assertDatabaseHas('tbl_notifikasi_log', [
            'user_id' => $customer->id,
            'event' => 'laporan_status_updated',
            'channel' => 'telegram',
            'status' => 'sent',
            'laporan_id' => $laporan->id,
        ]);

        // Status -> selesai
        $router->notifyTicketCompleted($laporan);
        $this->assertDatabaseHas('tbl_notifikasi_log', [
            'user_id' => $customer->id,
            'event' => 'laporan_completed',
            'channel' => 'telegram',
            'status' => 'sent',
            'laporan_id' => $laporan->id,
        ]);
    }

    public function test_laporan_creation_via_controller_triggers_notification(): void
    {
        $customer = User::factory()->withTelegram(556677, 'ctrluser')->create();
        $this->actingAs($customer);

        $mockClient = Mockery::mock(TelegramClient::class);
        $mockClient->shouldReceive('sendMessage')->andReturn(['ok' => true]);
        $this->app->instance(TelegramClient::class, $mockClient);

        $response = $this->post(route('customer.laporan.store'), [
            'judul' => 'Laporan Controller Event Test',
            'deskripsi' => 'Memastikan controller memanggil notification router tanpa error.',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('tbl_notifikasi_log', [
            'user_id' => $customer->id,
            'event' => 'laporan_created',
        ]);
    }

    public function test_customer_chooses_whatsapp_preference(): void
    {
        $customer = User::factory()->withTelegram(776655, 'cust_pref_wa')->create([
            'notif_channel_laporan' => 'whatsapp',
        ]);
        CustomerModel::create([
            'user_id' => $customer->id,
            'nomor_telepon' => '081999888777',
        ]);

        $laporan = Laporan::factory()->create([
            'user_id' => $customer->id,
            'judul' => 'Kendala Chat WA Saja',
        ]);

        $mockClient = Mockery::mock(TelegramClient::class);
        $mockClient->shouldReceive('sendMessage')
            ->with(-1009999888, Mockery::any(), Mockery::any())
            ->andReturn(['ok' => true]);
        $this->app->instance(TelegramClient::class, $mockClient);

        $mockFonnte = Mockery::mock(FonnteService::class);
        $mockFonnte->shouldReceive('sendText')
            ->once()
            ->with('081999888777', Mockery::on(function ($msg) {
                return str_contains($msg, 'Kendala Chat WA Saja');
            }))
            ->andReturn(['status' => true]);
        $this->app->instance(FonnteService::class, $mockFonnte);

        $router = app(NotificationRouter::class);
        $router->notifyTicketCreated($laporan);

        // Telegram skipped because user chose whatsapp
        $this->assertDatabaseHas('tbl_notifikasi_log', [
            'user_id' => $customer->id,
            'channel' => 'telegram',
            'status' => 'skipped',
            'laporan_id' => $laporan->id,
        ]);

        // WhatsApp sent
        $this->assertDatabaseHas('tbl_notifikasi_log', [
            'user_id' => $customer->id,
            'channel' => 'whatsapp',
            'status' => 'sent',
            'laporan_id' => $laporan->id,
        ]);
    }

    public function test_ticket_created_notifies_seller_when_assigned(): void
    {
        $customer = User::factory()->create();
        $seller = User::factory()->seller()->withTelegram(889900, 'seller_tokoku')->create();
        $toko = Toko::create([
            'user_id' => $seller->id,
            'nama_toko' => 'Toko Digital Sejahtera',
            'no_telp' => '081234567899',
            'akun_telegram' => 'seller_tokoku',
            'status' => 'aktif',
        ]);

        $laporan = Laporan::factory()->create([
            'user_id' => $customer->id,
            'assigned_to' => $seller->id,
            'judul' => 'Akun Chat GPT Plus error',
            'deskripsi' => 'Akun tidak bisa dipakai generate prompt.',
        ]);

        $mockClient = Mockery::mock(TelegramClient::class);
        // Expect seller notification
        $mockClient->shouldReceive('sendMessage')
            ->once()
            ->with(889900, Mockery::on(function ($text) {
                return str_contains($text, 'Toko Digital Sejahtera') && str_contains($text, 'Akun Chat GPT Plus error');
            }), Mockery::any())
            ->andReturn(['ok' => true, 'result' => ['message_id' => 1122]]);

        // Admin group
        $mockClient->shouldReceive('sendMessage')
            ->with(-1009999888, Mockery::any(), Mockery::any())
            ->andReturn(['ok' => true]);

        $this->app->instance(TelegramClient::class, $mockClient);

        $mockFonnte = Mockery::mock(FonnteService::class);
        $this->app->instance(FonnteService::class, $mockFonnte);

        $router = app(NotificationRouter::class);
        $router->notifyTicketCreated($laporan);

        $this->assertDatabaseHas('tbl_notifikasi_log', [
            'user_id' => $seller->id,
            'event' => 'laporan_created_seller',
            'channel' => 'telegram',
            'status' => 'sent',
            'laporan_id' => $laporan->id,
        ]);
    }
}
