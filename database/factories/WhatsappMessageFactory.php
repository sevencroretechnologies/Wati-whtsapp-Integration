<?php

namespace Database\Factories;

use App\Models\WhatsappMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

class WhatsappMessageFactory extends Factory
{
    protected $model = WhatsappMessage::class;

    public function definition(): array
    {
        return [
            'phone' => '+91'.$this->faker->numerify('##########'),
            'direction' => $this->faker->randomElement(['incoming', 'outgoing']),
            'message_type' => 'text',
            'message' => $this->faker->sentence(),
            'status' => $this->faker->randomElement(['pending', 'sent', 'delivered', 'read', 'failed']),
            'external_message_id' => $this->faker->uuid(),
            'payload' => ['test' => true],
        ];
    }

    public function incoming(): static
    {
        return $this->state(fn () => ['direction' => 'incoming', 'status' => 'received']);
    }

    public function outgoing(): static
    {
        return $this->state(fn () => ['direction' => 'outgoing', 'status' => 'sent']);
    }

    public function failed(): static
    {
        return $this->state(fn () => ['status' => 'failed', 'direction' => 'outgoing']);
    }
}
