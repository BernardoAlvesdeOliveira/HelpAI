<?php

use App\Http\Controllers\ConversationController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\WebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('/webhook')->group(function () {
    Route::post('/order', [WebhookController::class, 'handle']);
    Route::post('/evolution', [WebhookController::class, 'evolution']);
})->name('webhooks');

Route::post('/saveMessage', [WebhookController::class, 'saveMessage'])->name('saveMessage');

Route::get('/conversation/{remoteJid}/finished', [ConversationController::class, 'getFinished']);

Route::prefix('/menu')->group(function () {
    Route::get('/all', [MenuController::class, 'index']);
    Route::get('/available', [MenuController::class, 'available']);
})->name('menus');
