<?php

namespace Tests\Feature;

use App\Jobs\SendWhatsAppMessageJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WhatsAppSendMessageTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Queue::fake();
    }

    public function test_send_session_message_successfully(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/whatsapp/send-message', [
                'phone' => '+919876543210',
                'message' => 'Hello from test',
            ]);

        $response->assertOk()
            ->assertJson([
                'status' => true,
                'message' => 'Message queued for delivery',
            ]);

        Queue::assertPushed(SendWhatsAppMessageJob::class);
    }

    public function test_send_message_validation_fails_without_phone(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/whatsapp/send-message', [
                'message' => 'Hello',
            ]);

        $response->assertStatus(422)
            ->assertJson(['status' => false]);
    }

    public function test_send_message_validation_fails_with_invalid_phone(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/whatsapp/send-message', [
                'phone' => 'invalid',
                'message' => 'Hello',
            ]);

        $response->assertStatus(422)
            ->assertJson(['status' => false]);
    }

    public function test_send_message_requires_authentication(): void
    {
        $response = $this->postJson('/api/whatsapp/send-message', [
            'phone' => '+919876543210',
            'message' => 'Hello',
        ]);

        $response->assertStatus(401);
    }

    public function test_send_template_message_successfully(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/whatsapp/send-template', [
                'phone' => '+919876543210',
                'template_name' => 'welcome_message',
                'parameters' => [
                    ['name' => 'name', 'value' => 'John'],
                ],
            ]);

        $response->assertOk()
            ->assertJson([
                'status' => true,
                'message' => 'Template message queued for delivery',
            ]);

        Queue::assertPushed(SendWhatsAppMessageJob::class);
    }

    public function test_send_media_message_successfully(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/whatsapp/send-media', [
                'phone' => '+919876543210',
                'file_url' => 'https://example.com/document.pdf',
            ]);

        $response->assertOk()
            ->assertJson([
                'status' => true,
                'message' => 'Media message queued for delivery',
            ]);

        Queue::assertPushed(SendWhatsAppMessageJob::class);
    }

    public function test_send_buttons_message_successfully(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/whatsapp/send-buttons', [
                'phone' => '+919876543210',
                'message' => 'Choose an option',
                'buttons' => [
                    ['text' => 'Option 1'],
                    ['text' => 'Option 2'],
                ],
            ]);

        $response->assertOk()
            ->assertJson([
                'status' => true,
                'message' => 'Interactive button message queued for delivery',
            ]);

        Queue::assertPushed(SendWhatsAppMessageJob::class);
    }

    public function test_send_buttons_max_three_buttons(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/whatsapp/send-buttons', [
                'phone' => '+919876543210',
                'message' => 'Choose',
                'buttons' => [
                    ['text' => 'A'],
                    ['text' => 'B'],
                    ['text' => 'C'],
                    ['text' => 'D'],
                ],
            ]);

        $response->assertStatus(422);
    }
}
