<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WhatsappWebhook extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'payload',
        'event_type',
        'processed',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'processed' => 'boolean',
        ];
    }

    public function scopeUnprocessed($query)
    {
        return $query->where('processed', false);
    }

    public function markProcessed(): void
    {
        $this->update(['processed' => true]);
    }
}
