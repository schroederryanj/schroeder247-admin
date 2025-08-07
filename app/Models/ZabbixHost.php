<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ZabbixHost extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'zabbix_host_id',
        'name',
        'host',
        'interfaces',
        'status',
        'groups',
        'monitored',
        'sms_notifications',
        'notification_phone',
        'email_notifications',
        'notification_email',
        'last_synced_at',
    ];

    protected $casts = [
        'interfaces' => 'array',
        'groups' => 'array',
        'monitored' => 'boolean',
        'sms_notifications' => 'boolean',
        'email_notifications' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(ZabbixEvent::class);
    }

    public function activeEvents(): HasMany
    {
        return $this->hasMany(ZabbixEvent::class)->where('status', 'problem');
    }

    public function getIsDownAttribute(): bool
    {
        return $this->activeEvents()->exists();
    }

    public function getSeverityLevelAttribute(): string
    {
        if (!$this->isDown) {
            return 'ok';
        }

        $highestSeverity = $this->activeEvents()
            ->orderByRaw("CASE severity
                WHEN 'disaster' THEN 6
                WHEN 'high' THEN 5
                WHEN 'average' THEN 4
                WHEN 'warning' THEN 3
                WHEN 'information' THEN 2
                ELSE 1
            END DESC")
            ->first();

        return $highestSeverity ? $highestSeverity->severity : 'unknown';
    }
}
