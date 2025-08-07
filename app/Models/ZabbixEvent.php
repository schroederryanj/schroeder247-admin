<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ZabbixEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'zabbix_host_id',
        'zabbix_event_id',
        'trigger_id',
        'name',
        'description',
        'severity',
        'status',
        'value',
        'event_time',
        'recovery_time',
        'acknowledged',
        'raw_data',
        'notification_sent',
    ];

    protected $casts = [
        'event_time' => 'datetime',
        'recovery_time' => 'datetime',
        'acknowledged' => 'boolean',
        'raw_data' => 'array',
        'notification_sent' => 'boolean',
    ];

    public function zabbixHost(): BelongsTo
    {
        return $this->belongsTo(ZabbixHost::class);
    }

    public function getSeverityColorAttribute(): string
    {
        return match ($this->severity) {
            'disaster' => 'red-600',
            'high' => 'red-500',
            'average' => 'orange-500',
            'warning' => 'yellow-500',
            'information' => 'blue-500',
            'not_classified' => 'gray-500',
            default => 'gray-500',
        };
    }

    public function getSeverityIconAttribute(): string
    {
        return match ($this->severity) {
            'disaster' => '💥',
            'high' => '🔴',
            'average' => '🟠',
            'warning' => '🟡',
            'information' => '🔵',
            'not_classified' => '⚪',
            default => '❓',
        };
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'problem';
    }
}
