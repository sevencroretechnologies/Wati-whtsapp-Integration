# WATI WhatsApp Integration - Laravel 11

Production-ready WhatsApp integration module using [WATI API](https://www.wati.io/) built with Laravel 11. Features clean architecture, service-based approach, queue support, webhook handling, chatbot auto-replies, and Swagger documentation.

## Features

- **Session & Template Messaging** — Send text, template, media, and interactive button messages
- **Webhook Handling** — Receive and process incoming messages, delivery statuses, read receipts
- **Chatbot Auto-Replies** — Sample menu-driven chatbot (hi → welcome, 1 → services, 2 → pricing, 3 → support)
- **Queue Support** — Non-blocking message sending via Laravel queues with retry logic
- **Admin APIs** — Message history, conversation view, resend failed messages, filters
- **Security** — Sanctum authentication, rate limiting, input validation, webhook signature verification
- **Swagger Documentation** — OpenAPI annotations on all endpoints
- **Feature Tests** — Comprehensive test suite with mocked APIs

## Requirements

- PHP 8.2+
- Composer
- SQLite / MySQL / PostgreSQL
- WATI account with API credentials

## Setup

### 1. Clone & Install

```bash
git clone https://github.com/sevencroretechnologies/Wati-whtsapp-Integration.git
cd Wati-whtsapp-Integration
composer install
```

### 2. Environment Configuration

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` and set your WATI credentials:

```env
WATI_API_KEY=your-wati-api-key
WATI_BASE_URL=https://live-mt-server.wati.io
WATI_WEBHOOK_SECRET=your-webhook-secret  # optional
```

### 3. Database Setup

```bash
php artisan migrate
```

### 4. Start the Application

```bash
php artisan serve
```

### 5. Generate Sanctum Token

```bash
php artisan tinker
```

```php
$user = \App\Models\User::factory()->create(['email' => 'admin@example.com']);
$token = $user->createToken('api-token')->plainTextToken;
echo $token;
```

Use this token as Bearer token in API requests.

### 6. Start Queue Worker

```bash
php artisan queue:work
```

### 7. Generate Swagger Docs

```bash
php artisan l5-swagger:generate
```

Visit: `http://localhost:8000/api/documentation`

## API Endpoints

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/api/whatsapp/send-message` | Sanctum | Send session message |
| POST | `/api/whatsapp/send-template` | Sanctum | Send template message |
| POST | `/api/whatsapp/send-media` | Sanctum | Send media/document |
| POST | `/api/whatsapp/send-buttons` | Sanctum | Send interactive buttons |
| POST | `/api/whatsapp/webhook` | None | Webhook receiver |
| GET | `/api/whatsapp/message-status/{id}` | Sanctum | Get message status |
| GET | `/api/whatsapp/messages` | Sanctum | List messages (paginated) |
| GET | `/api/whatsapp/conversation/{phone}` | Sanctum | Conversation history |
| POST | `/api/whatsapp/resend/{id}` | Sanctum | Resend failed message |

### Query Parameters for `/api/whatsapp/messages`

| Parameter | Type | Description |
|-----------|------|-------------|
| `per_page` | integer | Items per page (default: 15, max: 100) |
| `status` | string | Filter by status (pending, sent, delivered, read, failed) |
| `direction` | string | Filter by direction (incoming, outgoing) |
| `phone` | string | Filter by phone number |
| `date_from` | date | Filter from date (YYYY-MM-DD) |
| `date_to` | date | Filter to date (YYYY-MM-DD) |

### Response Format

**Success:**
```json
{
    "status": true,
    "message": "Success",
    "data": {}
}
```

**Error:**
```json
{
    "status": false,
    "message": "Error message"
}
```

## Chatbot Auto-Replies

The webhook handler includes a sample chatbot:

| User Input | Bot Response |
|-----------|-------------|
| `hi` / `hello` / `hey` | Welcome menu with options 1-3 |
| `1` / `services` | List of services |
| `2` / `pricing` | Pricing plans |
| `3` / `support` | Support contact info |

## Webhook Configuration

Set your webhook URL in WATI dashboard:
```
https://your-domain.com/api/whatsapp/webhook
```

If using webhook signature verification, set `WATI_WEBHOOK_SECRET` in `.env`.

## Running Tests

```bash
php artisan test
```

## Project Structure

```
app/
├── Http/
│   ├── Controllers/API/
│   │   └── WhatsAppController.php    # All API endpoints
│   └── Requests/WhatsApp/
│       ├── SendMessageRequest.php     # Session message validation
│       ├── SendTemplateRequest.php    # Template message validation
│       ├── SendMediaRequest.php       # Media message validation
│       └── SendButtonsRequest.php     # Button message validation
├── Jobs/
│   └── SendWhatsAppMessageJob.php     # Queued message sending
├── Models/
│   ├── WhatsappMessage.php            # Message model with scopes
│   └── WhatsappWebhook.php           # Webhook log model
├── Providers/
│   └── AppServiceProvider.php         # Rate limiter & service binding
└── Services/
    └── WatiService.php                # WATI API client

database/
├── factories/
│   ├── WhatsappMessageFactory.php
│   └── WhatsappWebhookFactory.php
└── migrations/
    ├── create_whatsapp_messages_table.php
    └── create_whatsapp_webhooks_table.php

tests/Feature/
├── WhatsAppSendMessageTest.php
├── WhatsAppWebhookTest.php
├── WhatsAppChatbotTest.php
└── WhatsAppAdminTest.php

routes/
└── api.php                            # API route definitions

config/
└── services.php                       # WATI config section

postman_collection.json                # Postman collection
```

## Postman Collection

Import `postman_collection.json` into Postman. Set the `sanctum_token` variable to your API token.

## License

MIT
