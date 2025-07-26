<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessIncomingSMS;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Twilio\TwiML\MessagingResponse;

class SMSController extends Controller
{
    public function test(): Response
    {
        Log::info('SMS webhook test accessed');
        
        return response()->json([
            'status' => 'success',
            'message' => 'SMS webhook is reachable',
            'timestamp' => now()->toISOString(),
            'url' => request()->fullUrl()
        ]);
    }

    public function handleIncomingMessage(Request $request): Response
    {
        // Log all incoming data for debugging
        Log::info('SMS webhook called', [
            'method' => $request->method(),
            'all_data' => $request->all(),
            'headers' => $request->headers->all()
        ]);

        $from = $request->input('From');
        $body = $request->input('Body');
        $messageSid = $request->input('MessageSid');

        // Validate required Twilio parameters
        if (!$from || !$body) {
            Log::error('Invalid SMS webhook data', [
                'from' => $from,
                'body' => $body,
                'all_data' => $request->all()
            ]);
            
            $twiml = new MessagingResponse();
            return response($twiml, 400)->header('Content-Type', 'text/xml');
        }

        Log::info('Processing SMS', [
            'from' => $from,
            'body' => $body,
            'sid' => $messageSid
        ]);

        // Store the incoming message and queue it for AI processing
        ProcessIncomingSMS::dispatch($from, $body, $messageSid);

        // Always provide immediate response for better UX
        $quickResponse = $this->generateQuickResponse($body);
        
        $twiml = new MessagingResponse();
        $twiml->message($quickResponse);
        
        return response($twiml, 200)->header('Content-Type', 'text/xml');
    }


    private function generateQuickResponse(string $message): string
    {
        $message = strtolower(trim($message));

        // Greetings
        if (in_array($message, ['hello', 'hi', 'hey', 'yo', 'sup'])) {
            return "👋 Hello! I'm your IT monitoring assistant. Try these commands:\n• 'status' - Check monitors\n• 'down' - See down monitors\n• 'help' - Get help\n• Or ask me anything!";
        }

        // Status check - provide immediate response with actual data
        if (str_contains($message, 'status') || $message === 'check' || $message === 'monitors') {
            return $this->getQuickSystemStatus();
        }

        // Down/outage check
        if (str_contains($message, 'down') || str_contains($message, 'offline') || str_contains($message, 'outage')) {
            return $this->getQuickDownStatus();
        }

        // Help
        if (str_contains($message, 'help') || $message === '?') {
            return "📱 SMS Commands:\n• status - System status\n• down - Check outages\n• help - This menu\n• test - Test response\n\nOr just ask me anything about your monitors!";
        }

        // Test
        if ($message === 'test' || str_contains($message, 'ping')) {
            return "✅ SMS system working! Response time: " . round(microtime(true) * 1000) % 1000 . "ms";
        }

        // Default for other messages
        return "🤖 Got your message! I'll check on that for you. For immediate help, try:\n• 'status' - Check monitors\n• 'help' - See commands";
    }
    
    private function getQuickSystemStatus(): string
    {
        try {
            $monitors = \App\Models\Monitor::where('enabled', true)->get();
            
            if ($monitors->isEmpty()) {
                return "📊 No monitors configured yet. Add some at admin.schroeder247.com";
            }

            $upCount = $monitors->where('current_status', 'up')->count();
            $downCount = $monitors->where('current_status', 'down')->count();
            $totalCount = $monitors->count();

            $response = "📊 Monitor Status:\n";
            $response .= "✅ UP: {$upCount}/{$totalCount}\n";
            
            if ($downCount > 0) {
                $response .= "❌ DOWN: {$downCount}\n";
                $downMonitors = $monitors->where('current_status', 'down')->take(2);
                foreach ($downMonitors as $monitor) {
                    $response .= "  • {$monitor->name}\n";
                }
            }
            
            $response .= "\n🕐 " . now()->format('g:i A');

            return $response;
        } catch (\Exception $e) {
            return "📊 Status check in progress... Having trouble accessing monitors. Try again in a moment.";
        }
    }
    
    private function getQuickDownStatus(): string
    {
        try {
            $downMonitors = \App\Models\Monitor::where('enabled', true)
                ->where('current_status', 'down')
                ->get();

            if ($downMonitors->isEmpty()) {
                return "✅ All systems operational! No monitors are down.";
            }

            $response = "❌ Down Monitors ({$downMonitors->count()}):\n";
            
            foreach ($downMonitors->take(5) as $monitor) {
                $response .= "• {$monitor->name}\n";
                if ($monitor->url) {
                    $response .= "  {$monitor->url}\n";
                }
            }

            if ($downMonitors->count() > 5) {
                $response .= "\n...and " . ($downMonitors->count() - 5) . " more";
            }

            return $response;
        } catch (\Exception $e) {
            return "⚠️ Checking for outages... Having trouble accessing data. Try again shortly.";
        }
    }
}
