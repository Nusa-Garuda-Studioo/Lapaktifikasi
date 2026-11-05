<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Laporan;
use App\Models\LaporanPesan;
use App\Models\NotifikasiLog;
use App\Models\TelegramLinkToken;
use App\Models\TelegramMessageMap;
use App\Models\TelegramSession;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TelegramLaporanSchemaTest extends TestCase
{
    use DatabaseTransactions;

    public function test_user_telegram_helpers_and_roles()
    {
        $admin = User::factory()->admin()->create();
        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->isSeller());
        $this->assertFalse($admin->hasTelegram());

        $seller = User::factory()->seller()->withTelegram(12345678, 'seller_user')->create();
        $this->assertTrue($seller->isSeller());
        $this->assertFalse($seller->isAdmin());
        $this->assertTrue($seller->hasTelegram());
        $this->assertEquals(12345678, $seller->telegram_chat_id);
        $this->assertEquals('seller_user', $seller->telegram_username);

        $blockedUser = User::factory()->blockedTelegram()->create();
        $this->assertFalse($blockedUser->hasTelegram());
    }

    public function test_telegram_sensitive_data_hidden_from_serialization()
    {
        $user = User::factory()->withTelegram(987654321, 'tele_user')->create();
        $userArray = $user->toArray();
        $this->assertArrayNotHasKey('telegram_chat_id', $userArray);
        $this->assertArrayNotHasKey('password', $userArray);
        $this->assertStringNotContainsString('987654321', $user->toJson());

        $token = TelegramLinkToken::factory()->create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', 'super-secret-token'),
        ]);
        $tokenArray = $token->toArray();
        $this->assertArrayNotHasKey('token_hash', $tokenArray);
        $this->assertStringNotContainsString('super-secret-token', $token->toJson());
    }

    public function test_laporan_and_pesan_relations()
    {
        $customer = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $laporan = Laporan::factory()->create([
            'user_id' => $customer->id,
            'assigned_to' => $admin->id,
            'sla_due_at' => now()->addDay(),
            'telegram_topic_id' => 99999,
        ]);

        $this->assertInstanceOf(User::class, $laporan->user);
        $this->assertEquals($customer->id, $laporan->user->id);
        $this->assertInstanceOf(User::class, $laporan->assignedTo);
        $this->assertEquals($admin->id, $laporan->assignedTo->id);
        $this->assertEquals(99999, $laporan->telegram_topic_id);

        $pesan = LaporanPesan::factory()->create([
            'laporan_id' => $laporan->id,
            'sender_user_id' => $customer->id,
            'sender_role' => 'customer',
            'body' => 'Mohon bantuan garansi akun tidak bisa login',
            'sumber' => 'telegram',
            'telegram_message_id' => 55512,
        ]);

        $this->assertCount(1, $laporan->pesan);
        $this->assertEquals('Mohon bantuan garansi akun tidak bisa login', $laporan->pesan->first()->body);
        $this->assertInstanceOf(Laporan::class, $pesan->laporan);
        $this->assertInstanceOf(User::class, $pesan->sender);
        $this->assertEquals(55512, $pesan->telegram_message_id);
    }

    public function test_telegram_link_token_validity_and_relations()
    {
        $user = User::factory()->create();
        $validToken = TelegramLinkToken::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($validToken->isValid());
        $this->assertFalse($validToken->isExpired());
        $this->assertFalse($validToken->isUsed());
        $this->assertInstanceOf(User::class, $validToken->user);

        $expiredToken = TelegramLinkToken::factory()->expired()->create(['user_id' => $user->id]);
        $this->assertTrue($expiredToken->isExpired());
        $this->assertFalse($expiredToken->isValid());

        $usedToken = TelegramLinkToken::factory()->used()->create(['user_id' => $user->id]);
        $this->assertTrue($usedToken->isUsed());
        $this->assertFalse($usedToken->isValid());
    }

    public function test_telegram_session_model()
    {
        $session = TelegramSession::factory()->create([
            'chat_id' => 11223344,
            'flow' => 'komplain_pesanan',
            'step' => 'input_alasan',
            'payload' => ['inv' => 'INV-12345', 'attempt' => 1],
            'expires_at' => now()->addMinutes(10),
        ]);

        $this->assertEquals(11223344, $session->chat_id);
        $this->assertEquals('komplain_pesanan', $session->flow);
        $this->assertEquals('input_alasan', $session->step);
        $this->assertIsArray($session->payload);
        $this->assertEquals('INV-12345', $session->payload['inv']);
        $this->assertFalse($session->isExpired());
    }

    public function test_telegram_message_map_relations()
    {
        $customer = User::factory()->create();
        $laporan = Laporan::factory()->create(['user_id' => $customer->id]);

        $map = TelegramMessageMap::factory()->create([
            'chat_id' => 998877,
            'telegram_message_id' => 12345,
            'laporan_id' => $laporan->id,
            'user_id' => $customer->id,
        ]);

        $this->assertInstanceOf(Laporan::class, $map->laporan);
        $this->assertInstanceOf(User::class, $map->user);
        $this->assertEquals($laporan->id, $map->laporan->id);
        $this->assertEquals($customer->id, $map->user->id);
    }

    public function test_notifikasi_log_relations()
    {
        $user = User::factory()->create();
        $laporan = Laporan::factory()->create(['user_id' => $user->id]);

        $log = NotifikasiLog::factory()->create([
            'user_id' => $user->id,
            'event' => 'laporan_status_updated',
            'channel' => 'telegram',
            'status' => 'sent',
            'error' => null,
            'laporan_id' => $laporan->id,
            'sent_at' => now(),
        ]);

        $this->assertInstanceOf(User::class, $log->user);
        $this->assertInstanceOf(Laporan::class, $log->laporan);
        $this->assertEquals('sent', $log->status);
        $this->assertCount(1, $laporan->notifikasiLogs);
    }

    public function test_existing_laporan_features_unaffected()
    {
        // Pastikan laporan tanpa field telegram tetap valid dan kompatibel penuh
        $laporan = Laporan::create([
            'user_id' => User::factory()->create()->id,
            'judul' => 'Kendala Akun Lama',
            'deskripsi' => 'Deskripsi kendala lama',
            'status' => 'pending',
        ]);

        $this->assertNull($laporan->assigned_to);
        $this->assertNull($laporan->first_response_at);
        $this->assertNull($laporan->sla_due_at);
        $this->assertNull($laporan->telegram_topic_id);
        $this->assertEquals('pending', $laporan->status);
    }
}

