<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\WhatsApp\SendButtonsRequest;
use App\Http\Requests\WhatsApp\SendMediaRequest;
use App\Http\Requests\WhatsApp\SendMessageRequest;
use App\Http\Requests\WhatsApp\SendTemplateRequest;
use App\Jobs\SendWhatsAppMessageJob;
use App\Models\WhatsappMessage;
use App\Models\WhatsappWebhook;
use App\Services\WatiService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Info(
 *     title="WATI WhatsApp Integration API",
 *     version="1.0.0",
 *     description="Production-ready WhatsApp integration using WATI API"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Enter your Sanctum token"
 * )
 */
class WhatsAppController extends Controller
{
    public function __construct(
        private readonly WatiService $watiService
    ) {}

    /**
     * @OA\Post(
     *     path="/api/whatsapp/send-message",
     *     summary="Send a session message via WhatsApp",
     *     tags={"WhatsApp"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"phone","message"},
     *
     *             @OA\Property(property="phone", type="string", example="+919876543210"),
     *             @OA\Property(property="message", type="string", example="Hello from API")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Message queued successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Message queued for delivery"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function sendMessage(SendMessageRequest $request): JsonResponse
    {
        try {
            SendWhatsAppMessageJob::dispatch(
                $request->validated('phone'),
                'session',
                ['message' => $request->validated('message')]
            );

            return response()->json([
                'status' => true,
                'message' => 'Message queued for delivery',
                'data' => [
                    'phone' => $request->validated('phone'),
                ],
            ]);
        } catch (\Exception $e) {
            Log::channel('daily')->error('Send message failed', ['error' => $e->getMessage()]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to queue message: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/whatsapp/send-template",
     *     summary="Send a template message via WhatsApp",
     *     tags={"WhatsApp"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"phone","template_name"},
     *
     *             @OA\Property(property="phone", type="string", example="+919876543210"),
     *             @OA\Property(property="template_name", type="string", example="welcome_message"),
     *             @OA\Property(
     *                 property="parameters",
     *                 type="array",
     *
     *                 @OA\Items(
     *
     *                     @OA\Property(property="name", type="string"),
     *                     @OA\Property(property="value", type="string")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Template message queued",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function sendTemplate(SendTemplateRequest $request): JsonResponse
    {
        try {
            SendWhatsAppMessageJob::dispatch(
                $request->validated('phone'),
                'template',
                [
                    'template_name' => $request->validated('template_name'),
                    'parameters' => $request->validated('parameters', []),
                ]
            );

            return response()->json([
                'status' => true,
                'message' => 'Template message queued for delivery',
                'data' => [
                    'phone' => $request->validated('phone'),
                    'template_name' => $request->validated('template_name'),
                ],
            ]);
        } catch (\Exception $e) {
            Log::channel('daily')->error('Send template failed', ['error' => $e->getMessage()]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to queue template message: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/whatsapp/send-media",
     *     summary="Send a media/document message via WhatsApp",
     *     tags={"WhatsApp"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"phone","file_url"},
     *
     *             @OA\Property(property="phone", type="string", example="+919876543210"),
     *             @OA\Property(property="file_url", type="string", example="https://example.com/doc.pdf")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Media message queued",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function sendMedia(SendMediaRequest $request): JsonResponse
    {
        try {
            SendWhatsAppMessageJob::dispatch(
                $request->validated('phone'),
                'media',
                ['file_url' => $request->validated('file_url')]
            );

            return response()->json([
                'status' => true,
                'message' => 'Media message queued for delivery',
                'data' => [
                    'phone' => $request->validated('phone'),
                ],
            ]);
        } catch (\Exception $e) {
            Log::channel('daily')->error('Send media failed', ['error' => $e->getMessage()]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to queue media message: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/whatsapp/send-buttons",
     *     summary="Send interactive button message via WhatsApp",
     *     tags={"WhatsApp"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"phone","message","buttons"},
     *
     *             @OA\Property(property="phone", type="string", example="+919876543210"),
     *             @OA\Property(property="message", type="string", example="Choose an option"),
     *             @OA\Property(
     *                 property="buttons",
     *                 type="array",
     *
     *                 @OA\Items(
     *
     *                     @OA\Property(property="text", type="string", example="Option 1")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Button message queued",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function sendButtons(SendButtonsRequest $request): JsonResponse
    {
        try {
            SendWhatsAppMessageJob::dispatch(
                $request->validated('phone'),
                'buttons',
                [
                    'message' => $request->validated('message'),
                    'buttons' => $request->validated('buttons'),
                ]
            );

            return response()->json([
                'status' => true,
                'message' => 'Interactive button message queued for delivery',
                'data' => [
                    'phone' => $request->validated('phone'),
                ],
            ]);
        } catch (\Exception $e) {
            Log::channel('daily')->error('Send buttons failed', ['error' => $e->getMessage()]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to queue button message: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/whatsapp/webhook",
     *     summary="Receive incoming WhatsApp webhook events",
     *     tags={"Webhook"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(type="object")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Webhook received",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Webhook processed")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Invalid webhook signature"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function webhook(Request $request): JsonResponse
    {
        try {
            if (! $this->verifyWebhookSignature($request)) {
                Log::channel('daily')->warning('Invalid webhook signature', [
                    'ip' => $request->ip(),
                ]);

                return response()->json([
                    'status' => false,
                    'message' => 'Invalid webhook signature',
                ], 401);
            }

            $payload = $request->all();
            $eventType = $this->determineEventType($payload);

            $webhook = WhatsappWebhook::create([
                'payload' => $payload,
                'event_type' => $eventType,
                'processed' => false,
            ]);

            $this->processWebhookEvent($eventType, $payload, $webhook);

            return response()->json([
                'status' => true,
                'message' => 'Webhook processed',
            ]);
        } catch (\Exception $e) {
            Log::channel('daily')->error('Webhook processing failed', [
                'error' => $e->getMessage(),
                'payload' => $request->all(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Webhook processing failed',
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/whatsapp/message-status/{id}",
     *     summary="Get message delivery status from WATI",
     *     tags={"WhatsApp"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="External message ID from WATI",
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Message status retrieved",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function messageStatus(string $id): JsonResponse
    {
        try {
            $result = $this->watiService->getMessageStatus($id);

            return response()->json([
                'status' => $result['success'] ?? false,
                'message' => ($result['success'] ?? false) ? 'Message status retrieved' : 'Failed to retrieve status',
                'data' => $result['data'] ?? [],
            ]);
        } catch (\Exception $e) {
            Log::channel('daily')->error('Get message status failed', ['error' => $e->getMessage()]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve message status: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/whatsapp/messages",
     *     summary="List all messages with pagination and filters",
     *     tags={"Admin"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15)),
     *     @OA\Parameter(name="status", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="direction", in="query", @OA\Schema(type="string", enum={"incoming","outgoing"})),
     *     @OA\Parameter(name="phone", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="date_from", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="date_to", in="query", @OA\Schema(type="string", format="date")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Messages list",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function messages(Request $request): JsonResponse
    {
        try {
            $query = WhatsappMessage::query()->latest();

            if ($request->filled('status')) {
                $query->byStatus($request->input('status'));
            }

            if ($request->filled('direction')) {
                $query->where('direction', $request->input('direction'));
            }

            if ($request->filled('phone')) {
                $query->byPhone($request->input('phone'));
            }

            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->input('date_from'));
            }

            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->input('date_to'));
            }

            $perPage = min((int) $request->input('per_page', 15), 100);
            $messages = $query->paginate($perPage);

            return response()->json([
                'status' => true,
                'message' => 'Messages retrieved successfully',
                'data' => $messages,
            ]);
        } catch (\Exception $e) {
            Log::channel('daily')->error('List messages failed', ['error' => $e->getMessage()]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve messages',
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/whatsapp/conversation/{phone}",
     *     summary="Get conversation history for a phone number",
     *     tags={"Admin"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="phone",
     *         in="path",
     *         required=true,
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=50)),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Conversation history",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function conversation(string $phone): JsonResponse
    {
        try {
            $perPage = min((int) request('per_page', 50), 100);
            $messages = WhatsappMessage::byPhone($phone)
                ->latest()
                ->paginate($perPage);

            return response()->json([
                'status' => true,
                'message' => 'Conversation retrieved successfully',
                'data' => $messages,
            ]);
        } catch (\Exception $e) {
            Log::channel('daily')->error('Get conversation failed', ['error' => $e->getMessage()]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve conversation',
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/whatsapp/resend/{id}",
     *     summary="Resend a failed message",
     *     tags={"Admin"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Message re-queued",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *
     *     @OA\Response(response=404, description="Message not found"),
     *     @OA\Response(response=400, description="Message is not in failed state")
     * )
     */
    public function resend(int $id): JsonResponse
    {
        try {
            $message = WhatsappMessage::findOrFail($id);

            if ($message->status !== 'failed') {
                return response()->json([
                    'status' => false,
                    'message' => 'Only failed messages can be resent',
                ], 400);
            }

            $payload = $message->payload ?? [];
            $type = $message->message_type;

            $jobData = match ($type) {
                'text' => ['message' => $message->message],
                'template' => [
                    'template_name' => $payload['template_name'] ?? $message->message,
                    'parameters' => $payload['parameters'] ?? [],
                ],
                'media' => ['file_url' => $message->message],
                'interactive' => [
                    'message' => $message->message,
                    'buttons' => $payload['buttons'] ?? [],
                ],
                default => ['message' => $message->message],
            };

            $jobType = match ($type) {
                'text' => 'session',
                'template' => 'template',
                'media' => 'media',
                'interactive' => 'buttons',
                default => 'session',
            };

            SendWhatsAppMessageJob::dispatch($message->phone, $jobType, $jobData);

            $message->update(['status' => 'retrying']);

            return response()->json([
                'status' => true,
                'message' => 'Message re-queued for delivery',
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Message not found',
            ], 404);
        } catch (\Exception $e) {
            Log::channel('daily')->error('Resend message failed', ['error' => $e->getMessage()]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to resend message: '.$e->getMessage(),
            ], 500);
        }
    }

    private function verifyWebhookSignature(Request $request): bool
    {
        $secret = config('services.wati.webhook_secret');

        if (empty($secret)) {
            return true;
        }

        $signature = $request->header('X-Wati-Signature');

        if (empty($signature)) {
            return false;
        }

        $computedSignature = hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($computedSignature, $signature);
    }

    private function determineEventType(array $payload): string
    {
        if (isset($payload['statusString'])) {
            return match ($payload['statusString']) {
                'DELIVERED' => 'delivery_status',
                'READ' => 'read_receipt',
                'FAILED' => 'failed_message',
                default => 'status_update',
            };
        }

        if (isset($payload['text']) || isset($payload['message'])) {
            if (isset($payload['type']) && $payload['type'] === 'button_reply') {
                return 'button_reply';
            }

            return 'incoming_message';
        }

        return 'unknown';
    }

    private function processWebhookEvent(string $eventType, array $payload, WhatsappWebhook $webhook): void
    {
        match ($eventType) {
            'incoming_message' => $this->handleIncomingMessage($payload, $webhook),
            'button_reply' => $this->handleButtonReply($payload, $webhook),
            'delivery_status' => $this->handleDeliveryStatus($payload, $webhook),
            'read_receipt' => $this->handleReadReceipt($payload, $webhook),
            'failed_message' => $this->handleFailedMessage($payload, $webhook),
            default => $webhook->markProcessed(),
        };
    }

    private function handleIncomingMessage(array $payload, WhatsappWebhook $webhook): void
    {
        $phone = $payload['waId'] ?? $payload['from'] ?? '';
        $text = $payload['text'] ?? $payload['message'] ?? '';

        WhatsappMessage::create([
            'phone' => $phone,
            'direction' => 'incoming',
            'message_type' => 'text',
            'message' => $text,
            'status' => 'received',
            'external_message_id' => $payload['id'] ?? null,
            'payload' => $payload,
        ]);

        $this->processChatbotReply($phone, $text);

        $webhook->markProcessed();
    }

    private function handleButtonReply(array $payload, WhatsappWebhook $webhook): void
    {
        $phone = $payload['waId'] ?? $payload['from'] ?? '';
        $text = $payload['text'] ?? $payload['button_text'] ?? '';

        WhatsappMessage::create([
            'phone' => $phone,
            'direction' => 'incoming',
            'message_type' => 'button_reply',
            'message' => $text,
            'status' => 'received',
            'external_message_id' => $payload['id'] ?? null,
            'payload' => $payload,
        ]);

        $this->processChatbotReply($phone, $text);

        $webhook->markProcessed();
    }

    private function handleDeliveryStatus(array $payload, WhatsappWebhook $webhook): void
    {
        $messageId = $payload['id'] ?? $payload['messageId'] ?? null;

        if ($messageId) {
            WhatsappMessage::where('external_message_id', $messageId)
                ->update(['status' => 'delivered']);
        }

        $webhook->markProcessed();
    }

    private function handleReadReceipt(array $payload, WhatsappWebhook $webhook): void
    {
        $messageId = $payload['id'] ?? $payload['messageId'] ?? null;

        if ($messageId) {
            WhatsappMessage::where('external_message_id', $messageId)
                ->update(['status' => 'read']);
        }

        $webhook->markProcessed();
    }

    private function handleFailedMessage(array $payload, WhatsappWebhook $webhook): void
    {
        $messageId = $payload['id'] ?? $payload['messageId'] ?? null;

        if ($messageId) {
            WhatsappMessage::where('external_message_id', $messageId)
                ->update(['status' => 'failed']);
        }

        $webhook->markProcessed();
    }

    private function processChatbotReply(string $phone, string $text): void
    {
        $normalizedText = strtolower(trim($text));

        $reply = match ($normalizedText) {
            'hi', 'hello', 'hey' => "Welcome! Please choose an option:\n1. Our Services\n2. Pricing\n3. Support\n\nReply with the number of your choice.",
            '1', 'services' => "Our Services:\n- Web Development\n- Mobile App Development\n- Cloud Solutions\n- AI & Machine Learning\n\nReply 'hi' for main menu.",
            '2', 'pricing' => "Pricing Plans:\n- Starter: \$99/month\n- Professional: \$299/month\n- Enterprise: Custom pricing\n\nContact us for detailed pricing.\nReply 'hi' for main menu.",
            '3', 'support' => "Support:\nOur support team is available 24/7.\nEmail: support@example.com\nPhone: +1-800-123-4567\n\nReply 'hi' for main menu.",
            default => null,
        };

        if ($reply !== null) {
            SendWhatsAppMessageJob::dispatch($phone, 'session', ['message' => $reply]);
        }
    }
}
