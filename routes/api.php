<?php

use App\Http\Controllers\SMSController;
use Illuminate\Support\Facades\Route;

// Twilio webhook endpoints - no auth required
Route::get('/sms/test', [SMSController::class, 'test'])
    ->name('sms.test');

Route::post('/sms/webhook', [SMSController::class, 'handleIncomingMessage'])
    ->name('sms.webhook');