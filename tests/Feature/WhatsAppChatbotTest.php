<?php

namespace Tests\Feature;

use App\Jobs\SendWhatsAppMessageJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WhatsAppChatbotTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    public function test_chatbot_responds_to_hi(): void
    {
        $this->postJson('/api/whatsapp/webhook', [
            'waId' => '+919876543210',
            'text' => 'hi',
            'id' => 'msg_hi',
        ]);

        Queue::assertPushed(SendWhatsAppMessageJob::class, function ($job) {
            return true;
        });
    }

    public function test_chatbot_responds_to_hello(): void
    {
        $this->postJson('/api/whatsapp/webhook', [
            'waId' => '+919876543210',
            'text' => 'hello',
            'id' => 'msg_hello',
        ]);

        Queue::assertPushed(SendWhatsAppMessageJob::class);
    }

    public function test_chatbot_responds_to_services_option(): void
    {
        $this->postJson('/api/whatsapp/webhook', [
            'waId' => '+919876543210',
            'text' => '1',
            'id' => 'msg_1',
        ]);

        Queue::assertPushed(SendWhatsAppMessageJob::class);
    }

    public function test_chatbot_responds_to_pricing_option(): void
    {
        $this->postJson('/api/whatsapp/webhook', [
            'waId' => '+919876543210',
            'text' => '2',
            'id' => 'msg_2',
        ]);

        Queue::assertPushed(SendWhatsAppMessageJob::class);
    }

    public function test_chatbot_responds_to_support_option(): void
    {
        $this->postJson('/api/whatsapp/webhook', [
            'waId' => '+919876543210',
            'text' => '3',
            'id' => 'msg_3',
        ]);

        Queue::assertPushed(SendWhatsAppMessageJob::class);
    }

    public function test_chatbot_does_not_respond_to_unknown_text(): void
    {
        $this->postJson('/api/whatsapp/webhook', [
            'waId' => '+919876543210',
            'text' => 'random unrecognized text',
            'id' => 'msg_random',
        ]);

        Queue::assertNotPushed(SendWhatsAppMessageJob::class);
    }
}
