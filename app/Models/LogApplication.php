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
        'log_type',
        'script_path',
        'allowed_roles',
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

    public function canRunScript($user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $allowedRoles = explode(',', $this->allowed_roles);
        return in_array($user->role, $allowedRoles);
    }
}
