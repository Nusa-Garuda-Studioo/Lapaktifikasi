<?php

namespace Tests\Feature;

use App\Models\Laporan;
use App\Models\ProdukModel;
use App\Models\SettingWebsite;
use App\Models\TelegramLinkToken;
use App\Models\Toko;
use App\Models\User;
use App\Services\Telegram\CallbackRouter;
use App\Services\Telegram\CommandRouter;
use App\Services\Telegram\Exceptions\TelegramLinkException;
use App\Services\Telegram\MembershipHandler;
use App\Services\Telegram\TelegramClient;
use App\Services\Telegram\TelegramLinkService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mockery;
use Tests\TestCase;

class TelegramAccountLinkTest extends TestCase
{
    use DatabaseTransactions;

    protected TelegramLinkService $linkService;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.telegram.enabled' => true]);
        config(['services.telegram.bot_username' => 'test_bot']);
        $this->linkService = app(TelegramLinkService::class);
    }

    public function test_create_link_token_and_valid_link(): void
    {
        $user = User::factory()->create();

        $tokenData = $this->linkService->createLinkToken($user);
        $this->assertNotEmpty($tokenData['token']);
        $this->assertEquals("https://t.me/test_bot?start={$tokenData['token']}", $tokenData['url']);

        // Verify token hash is stored, not plaintext
        $this->assertDatabaseHas('tbl_telegram_link_tokens', [
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $tokenData['token']),
            'used_at' => null,
        ]);
        $this->assertDatabaseMissing('tbl_telegram_link_tokens', [
            'token_hash' => $tokenData['token'],
        ]);

        // Link account
        $linkedUser = $this->linkService->link($tokenData['token'], 98765432, 'telegram_user', 'private');
        $this->assertEquals(98765432, $linkedUser->telegram_chat_id);
        $this->assertEquals('telegram_user', $linkedUser->telegram_username);
        $this->assertNotNull($linkedUser->telegram_linked_at);
        $this->assertNull($linkedUser->telegram_blocked_at);

        // Token marked as used
        $tokenRecord = TelegramLinkToken::where('user_id', $user->id)->first();
        $this->assertNotNull($tokenRecord->used_at);
    }

    public function test_link_rejects_expired_token(): void
    {
        $user = User::factory()->create();
        $tokenData = $this->linkService->createLinkToken($user);

        // Simulate expired token
        TelegramLinkToken::where('user_id', $user->id)->update([
            'expires_at' => now()->subMinutes(1),
        ]);

        $this->expectException(TelegramLinkException::class);
        $this->expectExceptionMessage('sudah kedaluwarsa');

        $this->linkService->link($tokenData['token'], 12345, 'user', 'private');
    }

    public function test_link_rejects_already_used_token(): void
    {
        $user = User::factory()->create();
        $tokenData = $this->linkService->createLinkToken($user);

        // First link succeeds
        $this->linkService->link($tokenData['token'], 12345, 'user', 'private');

        // Second attempt with same token fails
        $this->expectException(TelegramLinkException::class);
        $this->expectExceptionMessage('sudah pernah digunakan');

        $this->linkService->link($tokenData['token'], 12345, 'user', 'private');
    }

    public function test_create_link_token_invalidates_previous_tokens(): void
    {
        $user = User::factory()->create();

        $token1 = $this->linkService->createLinkToken($user);
        $token2 = $this->linkService->createLinkToken($user);

        // Old token was deleted
        $this->assertDatabaseMissing('tbl_telegram_link_tokens', [
            'token_hash' => hash('sha256', $token1['token']),
        ]);
        $this->assertDatabaseHas('tbl_telegram_link_tokens', [
            'token_hash' => hash('sha256', $token2['token']),
        ]);

        $this->expectException(TelegramLinkException::class);
        $this->linkService->link($token1['token'], 12345, 'user', 'private');
    }

    public function test_link_rejects_chat_id_already_used_by_another_user(): void
    {
        $userA = User::factory()->withTelegram(77788899, 'usera')->create();
        $userB = User::factory()->create();

        $tokenDataB = $this->linkService->createLinkToken($userB);

        $this->expectException(TelegramLinkException::class);
        $this->expectExceptionMessage('sudah tertaut dengan akun Lapaktifikasi yang lain');

        $this->linkService->link($tokenDataB['token'], 77788899, 'usera', 'private');
    }

    public function test_link_rejects_non_private_chat(): void
    {
        $user = User::factory()->create();
        $tokenData = $this->linkService->createLinkToken($user);

        $this->expectException(TelegramLinkException::class);
        $this->expectExceptionMessage('private chat');

        $this->linkService->link($tokenData['token'], -1001234567, 'bot', 'group');
    }

    public function test_unlink_clears_user_and_toko_telegram_data(): void
    {
        $seller = User::factory()->seller()->withTelegram(555666, 'seller_tele')->create();
        $toko = Toko::create([
            'user_id' => $seller->id,
            'nama_toko' => 'Toko Uji Unlink',
            'no_telp' => '081234567890',
            'akun_telegram' => 'seller_tele',
            'telegram_chat_id' => '555666',
            'status' => 'aktif',
        ]);

        $this->linkService->unlink($seller);

        $seller->refresh();
        $toko->refresh();

        $this->assertNull($seller->telegram_chat_id);
        $this->assertNull($seller->telegram_username);
        $this->assertNull($seller->telegram_linked_at);
        $this->assertNull($toko->telegram_chat_id);
    }

    public function test_bot_start_command_with_token_links_successfully(): void
    {
        $user = User::factory()->create(['name' => 'Budi Santoso']);
        $tokenData = $this->linkService->createLinkToken($user);

        $mockClient = Mockery::mock(TelegramClient::class);
        $mockClient->shouldReceive('sendMessage')
            ->once()
            ->with(12345678, Mockery::on(function ($text) {
                return str_contains($text, 'Akun Berhasil Ditautkan') && str_contains($text, 'Budi Santoso');
            }))
            ->andReturn(['ok' => true]);

        $router = new CommandRouter($mockClient, $this->linkService);
        $router->handle([
            'chat' => ['id' => 12345678, 'type' => 'private'],
            'from' => ['id' => 12345678, 'username' => 'buditele'],
            'text' => "/start {$tokenData['token']}",
        ]);

        $user->refresh();
        $this->assertEquals(12345678, $user->telegram_chat_id);
    }

    public function test_bot_putus_command_and_callback(): void
    {
        $user = User::factory()->withTelegram(333444, 'user_putus')->create(['email' => 'putus@example.com']);

        $mockClient = Mockery::mock(TelegramClient::class);
        $mockClient->shouldReceive('sendMessage')
            ->once()
            ->with(333444, Mockery::on(function ($text) {
                return str_contains($text, 'Apakah Anda yakin ingin memutuskan tautan');
            }), Mockery::hasKey('reply_markup'))
            ->andReturn(['ok' => true]);

        $commandRouter = new CommandRouter($mockClient, $this->linkService);
        $commandRouter->handle([
            'chat' => ['id' => 333444, 'type' => 'private'],
            'text' => '/putus',
        ]);

        // Test Callback unlink_confirm
        $mockClient->shouldReceive('answerCallbackQuery')
            ->once()
            ->with('cb_123')
            ->andReturn(['ok' => true]);

        $mockClient->shouldReceive('editMessageText')
            ->once()
            ->with(333444, 999, Mockery::on(function ($text) {
                return str_contains($text, 'Tautan Berhasil Diputuskan');
            }))
            ->andReturn(['ok' => true]);

        $callbackRouter = new CallbackRouter($mockClient, $this->linkService);
        $callbackRouter->handle([
            'id' => 'cb_123',
            'data' => 'unlink_confirm',
            'message' => [
                'message_id' => 999,
                'chat' => ['id' => 333444],
            ],
        ]);

        $user->refresh();
        $this->assertNull($user->telegram_chat_id);
    }

    public function test_membership_handler_handles_kicked_and_member(): void
    {
        $user = User::factory()->withTelegram(654321, 'member_user')->create();

        $client = app(TelegramClient::class);
        $handler = new MembershipHandler($client);

        // Bot is blocked by user
        $handler->handle([
            'chat' => ['id' => 654321],
            'new_chat_member' => ['status' => 'kicked'],
        ]);

        $user->refresh();
        $this->assertNotNull($user->telegram_blocked_at);

        // Bot is unblocked by user
        $handler->handle([
            'chat' => ['id' => 654321],
            'new_chat_member' => ['status' => 'member'],
        ]);

        $user->refresh();
        $this->assertNull($user->telegram_blocked_at);
    }

    public function test_seller_onboarding_and_auto_activation(): void
    {
        $seller = User::factory()->seller()->create();

        // New toko created for unlinked seller starts as nonaktif
        $toko = Toko::create([
            'user_id' => $seller->id,
            'nama_toko' => 'Toko Baru Belum Taut',
            'no_telp' => '0811111111',
            'akun_telegram' => 'tele_baru',
            'status' => 'aktif', // Attempt to create as aktif
        ]);

        $toko->refresh();
        $this->assertEquals('nonaktif', $toko->status);
        $this->assertNotNull($toko->telegram_grace_due_at);

        $produk = ProdukModel::create([
            'id_toko' => $toko->id_toko,
            'nama_produk' => 'Produk Seller Baru',
            'status' => 'nonaktif',
        ]);

        // Now seller links telegram
        $tokenData = $this->linkService->createLinkToken($seller);
        $this->linkService->link($tokenData['token'], 888777, 'seller_active', 'private');

        $toko->refresh();
        $produk->refresh();

        $this->assertEquals('aktif', $toko->status);
        $this->assertEquals('aktif', $produk->status);
        $this->assertEquals('888777', $toko->telegram_chat_id);
    }

    public function test_seller_enforce_telegram_command(): void
    {
        // 1. Seller within grace period -> safe
        $sellerSafe = User::factory()->seller()->create();
        $tokoSafe = Toko::create([
            'user_id' => $sellerSafe->id,
            'nama_toko' => 'Toko Masa Tenggang Aman',
            'no_telp' => '081234567891',
            'akun_telegram' => 'seller_safe',
            'telegram_grace_due_at' => now()->addDays(5),
            'status' => 'aktif',
        ]);
        // Force status aktif to simulate existing toko
        Toko::where('id_toko', $tokoSafe->id_toko)->update(['status' => 'aktif', 'telegram_grace_due_at' => now()->addDays(5)]);
        $produkSafe = ProdukModel::create([
            'id_toko' => $tokoSafe->id_toko,
            'nama_produk' => 'Produk Aman',
            'status' => 'aktif',
        ]);

        // 2. Seller with expired grace period and not linked -> must be deactivated
        $sellerExpired = User::factory()->seller()->create();
        $tokoExpired = Toko::create([
            'user_id' => $sellerExpired->id,
            'nama_toko' => 'Toko Melewati Batas',
            'no_telp' => '081234567892',
            'akun_telegram' => 'seller_expired',
        ]);
        Toko::where('id_toko', $tokoExpired->id_toko)->update(['status' => 'aktif', 'telegram_grace_due_at' => now()->subDay()]);
        $produkExpired = ProdukModel::create([
            'id_toko' => $tokoExpired->id_toko,
            'nama_produk' => 'Produk Melewati Batas',
            'status' => 'aktif',
        ]);

        // Run enforcement command
        $this->artisan('seller:enforce-telegram')->assertExitCode(0);

        $tokoSafe->refresh();
        $produkSafe->refresh();
        $this->assertEquals('aktif', $tokoSafe->status);
        $this->assertEquals('aktif', $produkSafe->status);

        $tokoExpired->refresh();
        $produkExpired->refresh();
        $this->assertEquals('nonaktif', $tokoExpired->status);
        $this->assertEquals('nonaktif', $produkExpired->status);
    }

    public function test_customer_can_create_report_without_telegram(): void
    {
        $customer = User::factory()->create();
        $this->actingAs($customer);

        $response = $this->post(route('customer.laporan.store'), [
            'judul' => 'Kendala Login Tanpa Telegram',
            'deskripsi' => 'User customer tidak perlu menautkan Telegram untuk membuat tiket kendala.',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('tbl_laporan', [
            'user_id' => $customer->id,
            'judul' => 'Kendala Login Tanpa Telegram',
            'status' => 'pending',
        ]);
    }

    public function test_telegram_web_controller_endpoints(): void
    {
        $customer = User::factory()->create();
        $this->actingAs($customer);

        // 1. Create link token via JSON
        $response = $this->postJson(route('telegram.link_token'));
        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'token', 'url', 'expires_at']);

        // 2. Update channel preference
        $response = $this->postJson(route('telegram.channel_preference'), [
            'notif_channel_laporan' => 'telegram',
        ]);
        $response->assertStatus(200)
            ->assertJson(['status' => 'success', 'channel' => 'telegram']);

        $customer->refresh();
        $this->assertEquals('telegram', $customer->notif_channel_laporan);

        // 3. Unlink endpoint
        $customer->update(['telegram_chat_id' => 123456]);
        $response = $this->postJson(route('telegram.unlink'));
        $response->assertStatus(200)
            ->assertJson(['status' => 'success']);

        $customer->refresh();
        $this->assertNull($customer->telegram_chat_id);
    }

    public function test_seller_can_see_telegram_card_on_seller_profil_page(): void
    {
        $seller = User::factory()->seller()->create();
        $toko = Toko::create([
            'user_id' => $seller->id,
            'nama_toko' => 'Toko Seller Telegram UI',
            'no_telp' => '081234567890',
            'akun_telegram' => 'seller_ui_test',
            'status' => 'aktif',
        ]);

        $response = $this->actingAs($seller)->get('/seller/profil');
        $response->assertStatus(200);
        $response->assertSee('id="telegram"', false);
        $response->assertSee('Hubungkan Akun Telegram Seller');
        // Channel preference must be hidden from seller
        $response->assertDontSee('Preferensi Saluran Notifikasi Tiket Laporan');
    }

    public function test_seller_opening_customer_profile_is_redirected_to_seller_profil(): void
    {
        $seller = User::factory()->seller()->create();

        $response = $this->actingAs($seller)->get('/profile_customer/' . $seller->id);
        $response->assertRedirect('/seller/profil');
    }

    public function test_admin_opening_customer_profile_is_redirected_to_setting_website(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/profile_customer/' . $admin->id);
        $response->assertRedirect(route('admin.setting_website'));
    }

    public function test_customer_can_see_telegram_card_and_channel_preferences_on_customer_profile(): void
    {
        $customer = User::factory()->create();

        $response = $this->actingAs($customer)->get('/profile_customer/' . $customer->id);
        $response->assertStatus(200);
        $response->assertSee('id="telegram-section"', false);
        $response->assertSee('Hubungkan Telegram');
        $response->assertSee('Preferensi Saluran Notifikasi Tiket Laporan');
        $response->assertSee('name="notif_channel_laporan"', false);
    }

    public function test_seller_can_create_link_token_and_unlink_via_seller_endpoints(): void
    {
        $seller = User::factory()->seller()->create();
        $this->actingAs($seller);

        // 1. Create token via seller route
        $response = $this->postJson(route('seller.telegram.link_token'));
        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'token', 'url', 'expires_at']);

        // 2. Unlink via seller route
        $seller->update(['telegram_chat_id' => 999888]);
        $responseUnlink = $this->postJson(route('seller.telegram.unlink'));
        $responseUnlink->assertStatus(200)
            ->assertJson(['status' => 'success']);

        $seller->refresh();
        $this->assertNull($seller->telegram_chat_id);
    }

    public function test_admin_can_see_telegram_card_and_group_info_on_setting_website(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.setting_website'));
        $response->assertStatus(200);
        $response->assertSee('id="telegram"', false);
        $response->assertSee('Integrasi Akun Telegram Admin');
        $response->assertSee('TELEGRAM_ADMIN_GROUP_ID');
    }

    public function test_bot_start_without_token_directs_seller_to_seller_profil(): void
    {
        $seller = User::factory()->seller()->withTelegram(777666, 'seller_chat_bot')->create();

        $called = false;
        $mockClient = Mockery::mock(TelegramClient::class);
        $mockClient->shouldReceive('sendMessage')
            ->once()
            ->with(777666, Mockery::on(function ($text) use (&$called) {
                $called = str_contains($text, '/seller/profil') && str_contains($text, 'Profil Toko');
                return $called;
            }))
            ->andReturn(['ok' => true]);

        $router = new \App\Services\Telegram\CommandRouter($mockClient);
        $router->handle([
            'chat' => ['id' => 777666, 'type' => 'private'],
            'text' => '/start',
        ]);

        $this->assertTrue($called);
    }
}

