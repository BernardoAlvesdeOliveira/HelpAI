<?php

use App\Http\Controllers\ConversationController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

Route::middleware('auth')->post('/admin/whatsapp/messages', [WebhookController::class, 'sendMessage'])
    ->name('whatsapp.messages.send');

Route::middleware('auth')->get('/admin/whatsapp/conversation-messages', [ConversationController::class, 'messages'])
    ->name('whatsapp.conversation.messages');

require __DIR__.'/settings.php';
