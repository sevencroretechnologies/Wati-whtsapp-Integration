<?php

namespace Tests\Feature;

use App\Models\WhatsappMessage;
use App\Models\WhatsappWebhook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WhatsAppWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    public function test_webhook_receives_incoming_message(): void
    {
        $payload = [
            'waId' => '+919876543210',
            'text' => 'Hello',
            'id' => 'msg_123',
        ];

        $response = $this->postJson('/api/whatsapp/webhook', $payload);

        $response->assertOk()
            ->assertJson([
                'status' => true,
                'message' => 'Webhook processed',
            ]);

        $this->assertDatabaseHas('whatsapp_messages', [
            'phone' => '+919876543210',
            'direction' => 'incoming',
            'message' => 'Hello',
            'status' => 'received',
        ]);

        $this->assertDatabaseHas('whatsapp_webhooks', [
            'event_type' => 'incoming_message',
            'processed' => true,
        ]);
    }

    public function test_webhook_handles_delivery_status(): void
    {
        WhatsappMessage::factory()->create([
            'external_message_id' => 'msg_456',
            'status' => 'sent',
        ]);

        $payload = [
            'statusString' => 'DELIVERED',
            'id' => 'msg_456',
        ];

        $response = $this->postJson('/api/whatsapp/webhook', $payload);

        $response->assertOk();

        $this->assertDatabaseHas('whatsapp_messages', [
            'external_message_id' => 'msg_456',
            'status' => 'delivered',
        ]);
    }

    public function test_webhook_handles_read_receipt(): void
    {
        WhatsappMessage::factory()->create([
            'external_message_id' => 'msg_789',
            'status' => 'delivered',
        ]);

        $payload = [
            'statusString' => 'READ',
            'id' => 'msg_789',
        ];

        $response = $this->postJson('/api/whatsapp/webhook', $payload);

        $response->assertOk();

        $this->assertDatabaseHas('whatsapp_messages', [
            'external_message_id' => 'msg_789',
            'status' => 'read',
        ]);
    }

    public function test_webhook_handles_failed_message(): void
    {
        WhatsappMessage::factory()->create([
            'external_message_id' => 'msg_fail',
            'status' => 'sent',
        ]);

        $payload = [
            'statusString' => 'FAILED',
            'id' => 'msg_fail',
        ];

        $response = $this->postJson('/api/whatsapp/webhook', $payload);

        $response->assertOk();

        $this->assertDatabaseHas('whatsapp_messages', [
            'external_message_id' => 'msg_fail',
            'status' => 'failed',
        ]);
    }

    public function test_webhook_rejects_invalid_signature(): void
    {
        config(['services.wati.webhook_secret' => 'test-secret']);

        $response = $this->postJson('/api/whatsapp/webhook', [
            'text' => 'Hello',
        ], [
            'X-Wati-Signature' => 'invalid-signature',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'status' => false,
                'message' => 'Invalid webhook signature',
            ]);
    }

    public function test_webhook_stores_payload_in_database(): void
    {
        $payload = [
            'waId' => '+919876543210',
            'text' => 'Test message',
            'id' => 'msg_store_test',
        ];

        $this->postJson('/api/whatsapp/webhook', $payload);

        $this->assertEquals(1, WhatsappWebhook::count());

        $webhook = WhatsappWebhook::first();
        $this->assertEquals('incoming_message', $webhook->event_type);
        $this->assertTrue($webhook->processed);
    }

    public function test_webhook_handles_button_reply(): void
    {
        $payload = [
            'waId' => '+919876543210',
            'text' => 'Option 1',
            'type' => 'button_reply',
            'id' => 'msg_btn_1',
        ];

        $response = $this->postJson('/api/whatsapp/webhook', $payload);

        $response->assertOk();

        $this->assertDatabaseHas('whatsapp_messages', [
            'phone' => '+919876543210',
            'direction' => 'incoming',
            'message_type' => 'button_reply',
        ]);
    }
}
