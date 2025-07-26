<?php

use App\Http\Controllers\SMSController;
use Illuminate\Support\Facades\Route;

// Twilio webhook endpoints - no auth required
Route::post('/sms/webhook', [SMSController::class, 'handleIncomingMessage'])
    ->name('sms.webhook');