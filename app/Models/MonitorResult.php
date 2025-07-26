<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonitorResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'monitor_id',
        'status',
        'response_time',
        'status_code',
        'error_message',
        'checked_at'
    ];

    protected $casts = [
        'checked_at' => 'datetime',
    ];

    public function monitor(): BelongsTo
    {
        return $this->belongsTo(Monitor::class);
    }
}
