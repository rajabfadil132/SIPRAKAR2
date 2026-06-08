<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppNotification extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'code',
        'status',
        'href',
        'cabang',
        'source_type',
        'source_id',
        'data',
        'notified_at',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'notified_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
