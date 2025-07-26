<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SMSConversation extends Model
{
    use HasFactory;

    protected $table = 'sms_conversations';

    protected $fillable = [
        'phone_number',
        'message_type',
        'content',
        'ai_response',
        'processed',
        'twilio_sid',
        'response_time_ms',
        'processed_at'
    ];

    protected $casts = [
        'processed' => 'boolean',
        'processed_at' => 'datetime',
    ];

    public function scopeIncoming($query)
    {
        return $query->where('message_type', 'incoming');
    }

    public function scopeOutgoing($query)
    {
        return $query->where('message_type', 'outgoing');
    }

    public function scopeUnprocessed($query)
    {
        return $query->where('processed', false);
    }

    public function scopeForPhone($query, string $phoneNumber)
    {
        return $query->where('phone_number', $phoneNumber);
    }
}
