<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessIncomingSMS;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Twilio\TwiML\MessagingResponse;

class SMSController extends Controller
{
    public function handleIncomingMessage(Request $request): Response
    {
        $from = $request->input('From');
        $body = $request->input('Body');
        $messageSid = $request->input('MessageSid');

        Log::info('Incoming SMS', [
            'from' => $from,
            'body' => $body,
            'sid' => $messageSid
        ]);

        // Store the incoming message and queue it for AI processing
        ProcessIncomingSMS::dispatch($from, $body, $messageSid);

        // Check if this is an urgent query that needs immediate response
        if ($this->isUrgentQuery($body)) {
            $quickResponse = $this->generateQuickResponse($body);
            
            $twiml = new MessagingResponse();
            $twiml->message($quickResponse);
            
            return response($twiml, 200)->header('Content-Type', 'text/xml');
        }

        // Return empty TwiML response for non-urgent messages
        $twiml = new MessagingResponse();
        return response($twiml, 200)->header('Content-Type', 'text/xml');
    }

    private function isUrgentQuery(string $message): bool
    {
        $urgentKeywords = [
            'down', 'offline', 'error', 'critical', 'emergency', 
            'urgent', 'help', 'status check', 'alert'
        ];

        $message = strtolower($message);
        
        foreach ($urgentKeywords as $keyword) {
            if (str_contains($message, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function generateQuickResponse(string $message): string
    {
        $message = strtolower($message);

        if (str_contains($message, 'status check') || str_contains($message, 'status')) {
            return "🔍 Checking all monitors... Full response coming in 30 seconds.";
        }

        if (str_contains($message, 'down') || str_contains($message, 'offline')) {
            return "⚠️ Investigating potential outages... Detailed report incoming.";
        }

        if (str_contains($message, 'help')) {
            return "📞 AI Assistant ready! Ask me about:\n• Monitor status\n• System alerts\n• General questions\n\nProcessing your request...";
        }

        return "🤖 Processing your request... Response coming shortly.";
    }
}
