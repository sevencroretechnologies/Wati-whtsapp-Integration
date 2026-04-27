<?php

namespace Tests\Feature;

use App\Jobs\SendWhatsAppMessageJob;
use App\Models\User;
use App\Models\WhatsappMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WhatsAppAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Queue::fake();
    }

    public function test_list_messages_with_pagination(): void
    {
        WhatsappMessage::factory()->count(20)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/whatsapp/messages?per_page=10');

        $response->assertOk()
            ->assertJson(['status' => true])
            ->assertJsonStructure([
                'data' => [
                    'data',
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ]);

        $this->assertCount(10, $response->json('data.data'));
    }

    public function test_filter_messages_by_status(): void
    {
        WhatsappMessage::factory()->count(5)->create(['status' => 'sent']);
        WhatsappMessage::factory()->count(3)->create(['status' => 'failed']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/whatsapp/messages?status=failed');

        $response->assertOk();
        $this->assertCount(3, $response->json('data.data'));
    }

    public function test_filter_messages_by_direction(): void
    {
        WhatsappMessage::factory()->count(4)->incoming()->create();
        WhatsappMessage::factory()->count(6)->outgoing()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/whatsapp/messages?direction=incoming');

        $response->assertOk();
        $this->assertCount(4, $response->json('data.data'));
    }

    public function test_filter_messages_by_phone(): void
    {
        WhatsappMessage::factory()->count(3)->create(['phone' => '+919876543210']);
        WhatsappMessage::factory()->count(5)->create(['phone' => '+919876543211']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/whatsapp/messages?phone='.urlencode('+919876543210'));

        $response->assertOk();
        $this->assertCount(3, $response->json('data.data'));
    }

    public function test_get_conversation_by_phone(): void
    {
        WhatsappMessage::factory()->count(5)->create(['phone' => '+919876543210']);
        WhatsappMessage::factory()->count(3)->create(['phone' => '+919876543211']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/whatsapp/conversation/'.urlencode('+919876543210'));

        $response->assertOk()
            ->assertJson(['status' => true]);

        $this->assertCount(5, $response->json('data.data'));
    }

    public function test_resend_failed_message(): void
    {
        $message = WhatsappMessage::factory()->failed()->create([
            'message_type' => 'text',
            'message' => 'Failed message text',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/whatsapp/resend/{$message->id}");

        $response->assertOk()
            ->assertJson([
                'status' => true,
                'message' => 'Message re-queued for delivery',
            ]);

        Queue::assertPushed(SendWhatsAppMessageJob::class);

        $this->assertDatabaseHas('whatsapp_messages', [
            'id' => $message->id,
            'status' => 'retrying',
        ]);
    }

    public function test_resend_non_failed_message_returns_error(): void
    {
        $message = WhatsappMessage::factory()->create(['status' => 'sent']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/whatsapp/resend/{$message->id}");

        $response->assertStatus(400)
            ->assertJson([
                'status' => false,
                'message' => 'Only failed messages can be resent',
            ]);
    }

    public function test_resend_nonexistent_message_returns_404(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/whatsapp/resend/9999');

        $response->assertStatus(404);
    }

    public function test_messages_require_authentication(): void
    {
        $response = $this->getJson('/api/whatsapp/messages');

        $response->assertStatus(401);
    }

    public function test_filter_messages_by_date_range(): void
    {
        WhatsappMessage::factory()->count(3)->create([
            'created_at' => now()->subDays(5),
        ]);
        WhatsappMessage::factory()->count(2)->create([
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/whatsapp/messages?date_from='.now()->subDay()->toDateString());

        $response->assertOk();
        $this->assertCount(2, $response->json('data.data'));
    }
}
