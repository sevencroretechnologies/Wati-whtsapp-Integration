<?php

namespace Database\Factories;

use App\Models\WhatsappWebhook;
use Illuminate\Database\Eloquent\Factories\Factory;

class WhatsappWebhookFactory extends Factory
{
    protected $model = WhatsappWebhook::class;

    public function definition(): array
    {
        return [
            'payload' => [
                'waId' => '+91'.$this->faker->numerify('##########'),
                'text' => $this->faker->sentence(),
            ],
            'event_type' => $this->faker->randomElement(['incoming_message', 'delivery_status', 'read_receipt']),
            'processed' => false,
        ];
    }

    public function processed(): static
    {
        return $this->state(fn () => ['processed' => true]);
    }
}
