<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogApplication extends Model
{
    protected $fillable = [
        'server_id',
        'name',
        'log_path',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }
}
