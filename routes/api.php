<?php

use App\Http\Controllers\API\WhatsAppController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Webhook endpoint (no auth - receives from WATI)
Route::post('/whatsapp/webhook', [WhatsAppController::class, 'webhook'])
    ->name('whatsapp.webhook');

// Authenticated WhatsApp API endpoints
Route::middleware(['auth:sanctum', 'throttle:whatsapp'])->prefix('whatsapp')->name('whatsapp.')->group(function () {
    // Messaging
    Route::post('/send-message', [WhatsAppController::class, 'sendMessage'])->name('send-message');
    Route::post('/send-template', [WhatsAppController::class, 'sendTemplate'])->name('send-template');
    Route::post('/send-media', [WhatsAppController::class, 'sendMedia'])->name('send-media');
    Route::post('/send-buttons', [WhatsAppController::class, 'sendButtons'])->name('send-buttons');

    // Status
    Route::get('/message-status/{id}', [WhatsAppController::class, 'messageStatus'])->name('message-status');

    // Admin
    Route::get('/messages', [WhatsAppController::class, 'messages'])->name('messages');
    Route::get('/conversation/{phone}', [WhatsAppController::class, 'conversation'])->name('conversation');
    Route::post('/resend/{id}', [WhatsAppController::class, 'resend'])->name('resend');
});
