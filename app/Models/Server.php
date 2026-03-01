<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Server extends Model
{
    const CONNECTION_LOCAL = 'local';
    const CONNECTION_SSH   = 'ssh';
    const CONNECTION_AGENT = 'agent';

    protected $fillable = [
        'name',
        'connection_type',
        // SSH fields
        'ip_address',
        'ssh_port',
        'ssh_user',
        'ssh_password',
        'ssh_private_key',
        // Agent fields
        'agent_url',
        'agent_token',
        // Common
        'description',
        'is_active',
    ];

    protected $hidden = [
        'agent_token',
        'ssh_password',
        'ssh_private_key',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'ssh_port'  => 'integer',
    ];

    public function isLocal(): bool
    {
        return $this->connection_type === self::CONNECTION_LOCAL;
    }

    public function usesSsh(): bool
    {
        return $this->connection_type === self::CONNECTION_SSH;
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
