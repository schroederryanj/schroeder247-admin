<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Monitor extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'url',
        'type', 
        'check_interval',
        'expected_status_code',
        'timeout',
        'port',
        'expected_content',
        'ssl_check',
        'enabled',
        'sms_notifications',
        'notification_phone',
        'email_notifications',
        'notification_email',
        'notification_threshold',
        'last_checked_at',
        'current_status',
        'last_notification_sent'
    ];

    protected $casts = [
        'ssl_check' => 'boolean',
        'enabled' => 'boolean',
        'sms_notifications' => 'boolean',
        'email_notifications' => 'boolean',
        'last_checked_at' => 'datetime',
        'last_notification_sent' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(MonitorResult::class);
    }

    public function latestResult(): HasMany
    {
        return $this->hasMany(MonitorResult::class)->latest('checked_at')->limit(1);
    }

    public function getUptimePercentageAttribute(): float
    {
        $total = $this->results()->count();
        if ($total === 0) {
            return 100.0;
        }

        $upCount = $this->results()->where('status', 'up')->count();
        return round(($upCount / $total) * 100, 2);
    }

    public function getAverageResponseTimeAttribute(): int
    {
        return $this->results()
            ->whereNotNull('response_time')
            ->avg('response_time') ?? 0;
    }
}
