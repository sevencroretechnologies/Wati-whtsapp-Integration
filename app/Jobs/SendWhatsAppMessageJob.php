<?php

namespace App\Jobs;

use App\Services\WatiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendWhatsAppMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public int $maxExceptions = 3;

    public function __construct(
        private readonly string $phone,
        private readonly string $type,
        private readonly array $data
    ) {}

    public function handle(WatiService $watiService): void
    {
        try {
            $result = match ($this->type) {
                'session' => $watiService->sendSessionMessage($this->phone, $this->data['message']),
                'template' => $watiService->sendTemplateMessage(
                    $this->phone,
                    $this->data['template_name'],
                    $this->data['parameters'] ?? []
                ),
                'media' => $watiService->sendMediaMessage($this->phone, $this->data['file_url']),
                'buttons' => $watiService->sendInteractiveButtons(
                    $this->phone,
                    $this->data['message'],
                    $this->data['buttons']
                ),
                default => throw new \InvalidArgumentException("Unsupported message type: {$this->type}"),
            };

            if (! ($result['success'] ?? false)) {
                Log::channel('daily')->warning('WhatsApp message job failed to send', [
                    'phone' => $this->phone,
                    'type' => $this->type,
                    'result' => $result,
                ]);

                if ($this->attempts() < $this->tries) {
                    $this->release($this->backoff);
                }
            }
        } catch (\Exception $e) {
            Log::channel('daily')->error('WhatsApp message job exception', [
                'phone' => $this->phone,
                'type' => $this->type,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::channel('daily')->error('WhatsApp message job permanently failed', [
            'phone' => $this->phone,
            'type' => $this->type,
            'error' => $exception->getMessage(),
        ]);
    }
}
