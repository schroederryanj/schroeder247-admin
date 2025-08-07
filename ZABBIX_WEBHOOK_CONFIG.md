# Zabbix Webhook Configuration Guide

## Current Issue

Your Zabbix webhooks are sending malformed data with null values and incorrect macro substitution:

```json
{
  "host": {"hostid": null, "name": null, "host": null},
  "event": {"eventid": null, "clock": "undefinedTundefined", "value": "1"},
  "trigger": {"triggerid": null, "name": "Unknown trigger", "description": null, "priority": "0"},
  "timestamp": 1754545305
}
```

## Root Cause

The Zabbix webhook media type is not properly configured with the correct macros and parameters.

## Correct Zabbix Webhook Configuration

### Step 1: Create/Edit Media Type in Zabbix

1. Go to **Administration → Media types**
2. Create new or edit existing webhook media type
3. Set **Type**: `Webhook`
4. Set **Name**: `Laravel Admin Webhook`

### Step 2: Configure Parameters

Add these parameters to the webhook:

| Parameter | Value |
|-----------|--------|
| `host_id` | `{HOST.ID}` |
| `host_name` | `{HOST.NAME}` |
| `host_host` | `{HOST.HOST}` |
| `event_id` | `{EVENT.ID}` |
| `event_time` | `{EVENT.TIME}` |
| `event_date` | `{EVENT.DATE}` |
| `event_value` | `{EVENT.VALUE}` |
| `trigger_id` | `{TRIGGER.ID}` |
| `trigger_name` | `{TRIGGER.NAME}` |
| `trigger_description` | `{TRIGGER.DESCRIPTION}` |
| `trigger_severity` | `{TRIGGER.SEVERITY}` |
| `item_name` | `{ITEM.NAME}` |
| `item_key` | `{ITEM.KEY}` |
| `item_value` | `{ITEM.VALUE}` |

### Step 3: Configure Script

Use this JavaScript code for the webhook script:

```javascript
var params = JSON.parse(value);

var payload = {
    "host": {
        "hostid": params.host_id,
        "name": params.host_name,
        "host": params.host_host
    },
    "event": {
        "eventid": params.event_id,
        "clock": params.event_time,
        "value": params.event_value
    },
    "trigger": {
        "triggerid": params.trigger_id,
        "name": params.trigger_name,
        "description": params.trigger_description,
        "priority": params.trigger_severity
    },
    "item": {
        "name": params.item_name,
        "key": params.item_key,
        "value": params.item_value
    },
    "status": params.event_value == "1" ? "problem" : "ok",
    "severity": params.trigger_severity,
    "timestamp": Math.floor(Date.now() / 1000)
};

var req = new HttpRequest();
req.addHeader('Content-Type: application/json');

var response = req.post('https://admin.schroeder247.com/zabbix/webhook', JSON.stringify(payload));

if (req.getStatus() != 200) {
    throw 'Webhook failed with status: ' + req.getStatus() + ', response: ' + response;
}

return 'OK';
```

### Step 4: Set URL

Set the **URL** field to: `https://admin.schroeder247.com/zabbix/webhook`

### Step 5: Configure Action

1. Go to **Configuration → Actions → Trigger actions**
2. Create new action or edit existing one
3. In **Operations** tab, add operation:
   - **Operation type**: `Send message`
   - **Send to users**: (select appropriate user)
   - **Send only to**: `Laravel Admin Webhook` (your media type)

## Alternative Simple Configuration

If the above doesn't work, try this simpler flat structure:

### Parameters:
| Parameter | Value |
|-----------|--------|
| `hostid` | `{HOST.ID}` |
| `hostname` | `{HOST.NAME}` |
| `eventid` | `{EVENT.ID}` |
| `event_time` | `{EVENT.TIME}` |
| `trigger_name` | `{TRIGGER.NAME}` |
| `severity` | `{TRIGGER.SEVERITY}` |
| `status` | `{EVENT.VALUE}` |

### Script:
```javascript
var params = JSON.parse(value);

var req = new HttpRequest();
req.addHeader('Content-Type: application/json');

var response = req.post('https://admin.schroeder247.com/zabbix/webhook', JSON.stringify(params));

if (req.getStatus() != 200) {
    throw 'Webhook failed with status: ' + req.getStatus() + ', response: ' + response;
}

return 'OK';
```

## Testing

After configuration:

1. Test the media type by clicking **Test** button
2. Trigger a test alert or wait for a real alert
3. Check Laravel logs for successful webhook processing
4. Verify alerts appear in your admin dashboard

## Common Issues

1. **Macro substitution fails**: Ensure macros are spelled exactly as shown
2. **SSL issues**: Make sure your certificate is valid
3. **Firewall**: Ensure Zabbix can reach your Laravel application
4. **User permissions**: Make sure the user has the media type assigned

## Current Temporary Fix

The Laravel application has been updated to handle malformed webhooks, but you should fix the Zabbix configuration to send proper data.