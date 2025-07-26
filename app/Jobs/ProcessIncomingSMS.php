<?php

namespace App\Jobs;

use App\Models\Monitor;
use App\Models\SMSConversation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use OpenAI;
use Twilio\Rest\Client as TwilioClient;

class ProcessIncomingSMS implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private string $phoneNumber,
        private string $messageBody,
        private ?string $twilioSid = null
    ) {}

    public function handle(): void
    {
        $startTime = microtime(true);

        try {
            // Store the incoming message
            $conversation = SMSConversation::create([
                'phone_number' => $this->phoneNumber,
                'message_type' => 'incoming',
                'content' => $this->messageBody,
                'twilio_sid' => $this->twilioSid,
                'processed' => false,
            ]);

            // Generate AI response
            $aiResponse = $this->generateAIResponse($this->messageBody);

            // Send response via Twilio
            $this->sendSMSResponse($this->phoneNumber, $aiResponse);

            // Update conversation record
            $endTime = microtime(true);
            $responseTimeMs = round(($endTime - $startTime) * 1000);

            $conversation->update([
                'ai_response' => $aiResponse,
                'processed' => true,
                'response_time_ms' => $responseTimeMs,
                'processed_at' => now(),
            ]);

            // Log successful processing
            Log::info('SMS processed successfully', [
                'phone' => $this->phoneNumber,
                'response_time_ms' => $responseTimeMs,
            ]);

        } catch (\Exception $e) {
            Log::error('SMS processing failed', [
                'phone' => $this->phoneNumber,
                'error' => $e->getMessage(),
            ]);

            // Send error response
            $this->sendSMSResponse(
                $this->phoneNumber, 
                "⚠️ Sorry, I'm having trouble processing your request right now. Please try again in a few minutes."
            );
        }
    }

    private function generateAIResponse(string $message): string
    {
        // Check if this is a system query
        if ($this->isSystemQuery($message)) {
            return $this->handleSystemQuery($message);
        }

        // Use OpenAI for general queries
        return $this->getOpenAIResponse($message);
    }

    private function isSystemQuery(string $message): bool
    {
        $systemKeywords = [
            'status', 'monitor', 'uptime', 'down', 'up', 'check',
            'alert', 'incident', 'outage', 'response time'
        ];

        $message = strtolower($message);
        
        foreach ($systemKeywords as $keyword) {
            if (str_contains($message, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function handleSystemQuery(string $message): string
    {
        $message = strtolower($message);

        if (str_contains($message, 'status') || str_contains($message, 'check')) {
            return $this->getSystemStatus();
        }

        if (str_contains($message, 'down') || str_contains($message, 'outage')) {
            return $this->getDownMonitors();
        }

        return $this->getSystemStatus();
    }

    private function getSystemStatus(): string
    {
        $monitors = Monitor::where('enabled', true)->get();
        
        if ($monitors->isEmpty()) {
            return "📊 No monitors configured yet.";
        }

        $upCount = $monitors->where('current_status', 'up')->count();
        $downCount = $monitors->where('current_status', 'down')->count();
        $warningCount = $monitors->where('current_status', 'warning')->count();

        $response = "📊 System Status:\n";
        $response .= "✅ UP: {$upCount}\n";
        
        if ($warningCount > 0) {
            $response .= "⚠️ WARNING: {$warningCount}\n";
        }
        
        if ($downCount > 0) {
            $response .= "❌ DOWN: {$downCount}\n";
        }

        $response .= "\n🕐 Last checked: " . now()->format('H:i');

        return $response;
    }

    private function getDownMonitors(): string
    {
        $downMonitors = Monitor::where('enabled', true)
            ->where('current_status', 'down')
            ->get();

        if ($downMonitors->isEmpty()) {
            return "✅ All monitors are UP!";
        }

        $response = "❌ DOWN Monitors:\n";
        
        foreach ($downMonitors->take(3) as $monitor) {
            $response .= "• {$monitor->name}\n";
        }

        if ($downMonitors->count() > 3) {
            $remaining = $downMonitors->count() - 3;
            $response .= "...and {$remaining} more";
        }

        return $response;
    }

    private function getOpenAIResponse(string $message): string
    {
        if (!config('app.openai_api_key')) {
            return "🤖 AI assistant is not configured. I can help with monitor status queries instead!";
        }

        try {
            $client = OpenAI::client(config('app.openai_api_key'));

            $result = $client->chat()->create([
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a helpful IT consultant assistant. Keep responses under 160 characters when possible. Be friendly and concise.'
                    ],
                    [
                        'role' => 'user', 
                        'content' => $message
                    ]
                ],
                'max_tokens' => 150,
            ]);

            return $result->choices[0]->message->content ?? "Sorry, I couldn't generate a response.";

        } catch (\Exception $e) {
            Log::warning('OpenAI API call failed', ['error' => $e->getMessage()]);
            return "🤖 I'm having trouble with my AI brain right now. Try asking about monitor status instead!";
        }
    }

    private function sendSMSResponse(string $to, string $message): void
    {
        $twilio = new TwilioClient(
            config('services.twilio.sid'),
            config('services.twilio.auth_token')
        );

        $twilio->messages->create($to, [
            'from' => config('services.twilio.phone_number'),
            'body' => $message
        ]);

        // Store outgoing message
        SMSConversation::create([
            'phone_number' => $to,
            'message_type' => 'outgoing',
            'content' => $message,
            'processed' => true,
            'processed_at' => now(),
        ]);
    }
}
