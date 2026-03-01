<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Server extends Model
{
    const CONNECTION_LOCAL = 'local';
    const CONNECTION_AGENT = 'agent';

    protected $fillable = [
        'name',
        'connection_type',
        'agent_url',
        'agent_token',
        'description',
        'is_active',
    ];

    protected $hidden = [
        'agent_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function isLocal(): bool
    {
        return $this->connection_type === self::CONNECTION_LOCAL;
    }

    public function usesAgent(): bool
    {
        return $this->connection_type === self::CONNECTION_AGENT;
    }

    public function logApplications(): HasMany
    {
        return $this->hasMany(LogApplication::class);
    }

    public function activeLogApplications(): HasMany
    {
        return $this->hasMany(LogApplication::class)->where('is_active', true);
    }
}
