<?php

namespace App\Jobs;

use App\Models\WhatsappMessage;
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

    private ?int $messageRecordId = null;

    public function __construct(
        private readonly string $phone,
        private readonly string $type,
        private readonly array $data
    ) {}

    public function handle(WatiService $watiService): void
    {
        try {
            $isFirstAttempt = $this->attempts() <= 1;

            if ($isFirstAttempt) {
                $messageRecord = $this->createMessageRecord();
                $this->messageRecordId = $messageRecord->id;
            }

            $result = match ($this->type) {
                'session' => $watiService->sendSessionMessage($this->phone, $this->data['message'], withLog: false),
                'template' => $watiService->sendTemplateMessage(
                    $this->phone,
                    $this->data['template_name'],
                    $this->data['parameters'] ?? [],
                    withLog: false
                ),
                'media' => $watiService->sendMediaMessage($this->phone, $this->data['file_url'], withLog: false),
                'buttons' => $watiService->sendInteractiveButtons(
                    $this->phone,
                    $this->data['message'],
                    $this->data['buttons'],
                    withLog: false
                ),
                default => throw new \InvalidArgumentException("Unsupported message type: {$this->type}"),
            };

            if ($result['success'] ?? false) {
                $this->updateMessageRecord('sent', $result);
            } else {
                Log::channel('daily')->warning('WhatsApp message job failed to send', [
                    'phone' => $this->phone,
                    'type' => $this->type,
                    'attempt' => $this->attempts(),
                    'result' => $result,
                ]);

                if ($this->attempts() < $this->tries) {
                    $this->updateMessageRecord('retrying', $result);
                    $this->release($this->backoff);
                } else {
                    $this->updateMessageRecord('failed', $result);

                    throw new \RuntimeException(
                        'WhatsApp message permanently failed after '.$this->tries.' attempts: '.($result['message'] ?? 'Unknown error')
                    );
                }
            }
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            throw $e;
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

        $this->updateMessageRecord('failed');
    }

    private function createMessageRecord(): WhatsappMessage
    {
        $messageContent = match ($this->type) {
            'template' => $this->data['template_name'] ?? '',
            'media' => $this->data['file_url'] ?? '',
            default => $this->data['message'] ?? '',
        };

        $messageType = match ($this->type) {
            'session' => 'text',
            'buttons' => 'interactive',
            default => $this->type,
        };

        return WhatsappMessage::create([
            'phone' => $this->phone,
            'direction' => 'outgoing',
            'message_type' => $messageType,
            'message' => $messageContent,
            'status' => 'pending',
            'payload' => $this->data,
        ]);
    }

    private function updateMessageRecord(string $status, array $result = []): void
    {
        if ($this->messageRecordId) {
            $update = ['status' => $status];

            if (! empty($result)) {
                $externalId = $result['data']['id'] ?? $result['data']['messageId'] ?? null;
                if ($externalId) {
                    $update['external_message_id'] = $externalId;
                }
                $update['payload'] = array_merge($this->data, ['response' => $result]);
            }

            WhatsappMessage::where('id', $this->messageRecordId)->update($update);
        }
    }
}
