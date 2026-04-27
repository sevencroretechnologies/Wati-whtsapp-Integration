<?php

namespace App\Services;

use App\Models\WhatsappMessage;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WatiService
{
    private string $apiKey;

    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.wati.api_key');
        $this->baseUrl = rtrim(config('services.wati.base_url'), '/');
    }

    public function sendSessionMessage(string $phone, string $message, bool $withLog = true): array
    {
        $phone = $this->sanitizePhone($phone);

        $response = $this->makeRequest('POST', "/api/v1/sendSessionMessage/{$phone}", [
            'messageText' => $message,
        ]);

        if ($withLog) {
            $this->logMessage($phone, 'outgoing', 'text', $message, $response);
        }

        return $response;
    }

    public function sendTemplateMessage(string $phone, string $templateName, array $parameters = [], bool $withLog = true): array
    {
        $phone = $this->sanitizePhone($phone);

        $body = [
            'template_name' => $templateName,
            'broadcast_name' => 'api_broadcast_'.time(),
        ];

        if (! empty($parameters)) {
            $body['parameters'] = array_map(function ($param) {
                return ['name' => $param['name'], 'value' => $param['value']];
            }, $parameters);
        }

        $response = $this->makeRequest('POST', '/api/v1/sendTemplateMessage?whatsappNumber='.urlencode($phone), $body);

        if ($withLog) {
            $this->logMessage($phone, 'outgoing', 'template', $templateName, $response, [
                'template_name' => $templateName,
                'parameters' => $parameters,
            ]);
        }

        return $response;
    }

    public function sendMediaMessage(string $phone, string $fileUrl, bool $withLog = true): array
    {
        $phone = $this->sanitizePhone($phone);

        $response = $this->makeRequest('POST', "/api/v1/sendSessionFile/{$phone}", [
            'url' => $fileUrl,
        ]);

        if ($withLog) {
            $this->logMessage($phone, 'outgoing', 'media', $fileUrl, $response);
        }

        return $response;
    }

    public function sendInteractiveButtons(string $phone, string $message, array $buttons, bool $withLog = true): array
    {
        $phone = $this->sanitizePhone($phone);

        $body = [
            'body' => $message,
            'buttons' => array_map(function ($button) {
                return [
                    'text' => $button['text'],
                ];
            }, $buttons),
        ];

        $response = $this->makeRequest('POST', '/api/v1/sendInteractiveButtonsMessage?whatsappNumber='.urlencode($phone), $body);

        if ($withLog) {
            $this->logMessage($phone, 'outgoing', 'interactive', $message, $response, [
                'buttons' => $buttons,
            ]);
        }

        return $response;
    }

    public function sendBroadcast(array $payload): array
    {
        return $this->makeRequest('POST', '/api/v1/sendTemplateMessages', $payload);
    }

    public function getMessageStatus(string $messageId): array
    {
        return $this->makeRequest('GET', "/api/v1/getMessageStatus/{$messageId}");
    }

    public function logMessage(
        string $phone,
        string $direction,
        string $messageType,
        string $message,
        array $response,
        array $extraPayload = []
    ): WhatsappMessage {
        $status = ($response['success'] ?? false) ? 'sent' : 'failed';
        $externalId = $response['data']['id'] ?? $response['data']['messageId'] ?? null;

        return WhatsappMessage::create([
            'phone' => $phone,
            'direction' => $direction,
            'message_type' => $messageType,
            'message' => $message,
            'status' => $status,
            'external_message_id' => $externalId,
            'payload' => array_merge($extraPayload, ['response' => $response]),
        ]);
    }

    private function makeRequest(string $method, string $endpoint, array $data = []): array
    {
        $url = $this->baseUrl.$endpoint;

        Log::channel('daily')->info('WATI API Request', [
            'method' => $method,
            'url' => $url,
            'data' => $data,
        ]);

        try {
            $request = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->apiKey,
                'Content-Type' => 'application/json',
            ])->retry(3, 1000, function (\Exception $exception) {
                return $exception instanceof ConnectionException;
            });

            /** @var Response $response */
            $response = match (strtoupper($method)) {
                'GET' => $request->get($url, $data),
                'POST' => $request->post($url, $data),
                'PUT' => $request->put($url, $data),
                'DELETE' => $request->delete($url, $data),
                default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}"),
            };

            $responseData = $response->json() ?? [];

            Log::channel('daily')->info('WATI API Response', [
                'status' => $response->status(),
                'body' => $responseData,
            ]);

            if ($response->failed()) {
                Log::channel('daily')->error('WATI API Error', [
                    'status' => $response->status(),
                    'body' => $responseData,
                ]);

                return [
                    'success' => false,
                    'status_code' => $response->status(),
                    'message' => $responseData['message'] ?? 'WATI API request failed',
                    'data' => $responseData,
                ];
            }

            return [
                'success' => true,
                'status_code' => $response->status(),
                'data' => $responseData,
            ];
        } catch (\Exception $e) {
            Log::channel('daily')->error('WATI API Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    private function sanitizePhone(string $phone): string
    {
        return preg_replace('/[^0-9+]/', '', $phone);
    }
}
