<?php

require_once 'vendor/autoload.php';

// Load Laravel app
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\SMSConversation;
use App\Models\Monitor;

echo "=== SMS Chat System Diagnostic ===\n\n";

// Check environment variables
echo "1. Environment Variables:\n";
echo "TWILIO_SID: " . (config('services.twilio.sid') ? 'SET' : 'NOT SET') . "\n";
echo "TWILIO_AUTH_TOKEN: " . (config('services.twilio.auth_token') ? 'SET' : 'NOT SET') . "\n";
echo "TWILIO_FROM: " . (config('services.twilio.phone_number') ?: 'NOT SET') . "\n";
echo "OPENAI_API_KEY: " . (config('app.openai_api_key') ? 'SET' : 'NOT SET') . "\n";

// Check database connectivity
echo "\n2. Database Connectivity:\n";
try {
    $conversationCount = SMSConversation::count();
    echo "✓ SMS Conversations table: {$conversationCount} records\n";
    
    $monitorCount = Monitor::count();
    echo "✓ Monitors table: {$monitorCount} records\n";
} catch (Exception $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
}

// Check recent SMS conversations
echo "\n3. Recent SMS Conversations:\n";
try {
    $recentSMS = SMSConversation::orderBy('created_at', 'desc')->limit(5)->get();
    
    if ($recentSMS->isEmpty()) {
        echo "No SMS conversations found.\n";
    } else {
        foreach ($recentSMS as $sms) {
            echo "- {$sms->created_at}: {$sms->message_type} from {$sms->phone_number}\n";
            echo "  Content: " . substr($sms->content, 0, 50) . "...\n";
            echo "  Processed: " . ($sms->processed ? 'Yes' : 'No') . "\n";
        }
    }
} catch (Exception $e) {
    echo "✗ Error checking SMS conversations: " . $e->getMessage() . "\n";
}

// Check webhook URL
echo "\n4. Webhook Information:\n";
$webhookUrl = config('app.url') . '/api/sms/webhook';
echo "Webhook URL: {$webhookUrl}\n";
echo "This URL needs to be configured in your Twilio console.\n";

// Check queue configuration
echo "\n5. Queue Configuration:\n";
echo "Queue Driver: " . config('queue.default') . "\n";
echo "Note: Queue workers must be running for SMS processing to work.\n";

echo "\n=== End Diagnostic ===\n";