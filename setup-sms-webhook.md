# SMS Chat Setup Instructions

## Current Status
✅ Twilio credentials configured  
✅ SMS processing code implemented  
❌ Webhook URL needs to be configured in Twilio console  
❌ Queue workers need to be running  

## Step 1: Configure Twilio Webhook

1. **Login to Twilio Console**: https://console.twilio.com/
2. **Go to Phone Numbers**: Phone Numbers > Manage > Active numbers
3. **Click your Twilio number**: +14805880431
4. **Set webhook URL**: 
   ```
   https://admin.schroeder247.com/api/sms/webhook
   ```
5. **Set HTTP method**: POST
6. **Save configuration**

## Step 2: Verify Queue Workers

SSH into your production server and check:

```bash
# Check if queue workers are running
php artisan queue:work --daemon

# Or check supervisor status if configured
supervisorctl status
```

## Step 3: Test the Setup

1. Send an SMS to your Twilio number: +14805880431
2. Try these test messages:
   - "hello" (general chat)
   - "status" (system status)
   - "help" (get help menu)

## Step 4: Check Logs

If messages aren't working, check:

```bash
# Check Laravel logs
tail -f storage/logs/laravel.log

# Check failed jobs
php artisan queue:failed
```

## Features Available

The SMS chat system supports:

- **System Queries**: Ask about monitor status, uptime, alerts
- **AI Chat**: General questions (requires OpenAI API key)
- **Quick Responses**: Immediate replies for urgent keywords
- **Conversation History**: All conversations are stored

## Example Conversations

**System Status:**
```
You: "status"
Bot: "📊 System Status:
✅ UP: 3
⚠️ WARNING: 1
🕐 Last checked: 14:30"
```

**General Chat:**
```
You: "What's the weather like?"
Bot: "🤖 I'm an IT assistant focused on monitoring. For weather, I'd suggest checking a weather app! 
Need help with your monitors instead?"
```