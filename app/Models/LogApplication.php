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
        'script_role',
        'restart_command',
        'restart_role',
        'custom_buttons',
        'git_branch',
        'git_path',
        'git_pull_role',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active'      => 'boolean',
        'custom_buttons' => 'array',
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

        return ($this->script_role ?? 'admin') === 'user'
            && in_array($user->role, ['admin', 'user'], true);
    }

    public function canGitPull($user): bool
    {
        if (empty($this->git_branch) || trim($this->git_branch) === '') {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return ($this->git_pull_role ?? 'admin') === 'user'
            && in_array($user->role, ['admin', 'user'], true);
    }

    public function canRestart($user): bool
    {
        if (empty($this->restart_command) || trim($this->restart_command) === '') {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return ($this->restart_role ?? 'admin') === 'user'
            && in_array($user->role, ['admin', 'user'], true);
    }

    public function canRunCustomButtons($user): bool
    {
        $buttons = $this->custom_buttons ?? [];
        foreach ($buttons as $button) {
            if ($this->canRunCustomButton($user, $button)) {
                return true;
            }
        }

        return false;
    }

    public function canRunCustomButton($user, array $button): bool
    {
        $requiredRole = $button['role'] ?? 'admin';

        if ($requiredRole === 'user') {
            return in_array($user->role, ['admin', 'user'], true);
        }

        return $user->isAdmin();
    }
}
