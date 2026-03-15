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
        'git_branch',
        'git_path',
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
        if (empty($this->script_path) || trim($this->script_path) === '') {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if (empty($this->allowed_roles)) {
            return false;
        }

        // Split và trim để xử lý khoảng trắng: "admin, user" -> ["admin", "user"]
        $allowedRoles = array_map('trim', explode(',', $this->allowed_roles));
        return in_array($user->role, $allowedRoles, true);
    }

    public function canGitPull($user): bool
    {
        if (empty($this->git_branch) || trim($this->git_branch) === '') {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if (empty($this->allowed_roles)) {
            return false;
        }

        // Split và trim để xử lý khoảng trắng: "admin, user" -> ["admin", "user"]
        $allowedRoles = array_map('trim', explode(',', $this->allowed_roles));
        return in_array($user->role, $allowedRoles, true);
    }
}
