<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LogApplication;
use App\Models\Server;
use Illuminate\Http\Request;

class LogApplicationController extends Controller
{
    public function index()
    {
        $apps = LogApplication::with('server')->orderBy('name')->paginate(15);
        return view('admin.log-apps.index', compact('apps'));
    }

    public function create()
    {
        $servers = Server::where('is_active', true)->orderBy('name')->get();
        return view('admin.log-apps.create', compact('servers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'server_id'     => 'required|exists:servers,id',
            'name'          => 'required|string|max:255',
            'log_path'      => 'required|string|max:1000',
            'log_type'      => 'required|in:file,pattern,docker,journalctl',
            'script_path'   => 'nullable|string|max:1000',
            'script_role'   => 'nullable|in:admin,user',
            'restart_command' => 'nullable|string|max:500',
            'restart_role'  => 'nullable|in:admin,user',
            'git_branch'    => 'nullable|string|max:255',
            'git_path'      => 'nullable|string|max:1000',
            'git_pull_role' => 'nullable|in:admin,user',
            'btn_labels'    => 'nullable|array',
            'btn_labels.*'  => 'nullable|string|max:255',
            'btn_commands'  => 'nullable|array',
            'btn_commands.*' => 'nullable|string|max:1000',
            'btn_roles'     => 'nullable|array',
            'btn_roles.*'   => 'nullable|in:admin,user',
            'description'   => 'nullable|string',
            'is_active'     => 'boolean',
        ]);

        $validated['is_active']      = $request->boolean('is_active', true);
        $validated['git_pull_role']  = $request->input('git_pull_role', 'admin');
        $validated['script_role']    = $request->input('script_role', 'admin');
        $validated['restart_role']   = $request->input('restart_role', 'admin');
        $validated['custom_buttons'] = $this->parseCustomButtons($request);

        $app = LogApplication::create($validated);

        return redirect()->route('admin.log-apps.index')
            ->with('success', 'Ứng dụng "' . $app->name . '" đã được thêm thành công!');
    }

    public function show(LogApplication $logApp)
    {
        $logApp->load('server');
        return view('admin.log-apps.show', compact('logApp'));
    }

    public function edit(LogApplication $logApp)
    {
        $servers = Server::where('is_active', true)->orderBy('name')->get();
        return view('admin.log-apps.edit', compact('logApp', 'servers'));
    }

    public function update(Request $request, LogApplication $logApp)
    {
        $validated = $request->validate([
            'server_id'     => 'required|exists:servers,id',
            'name'          => 'required|string|max:255',
            'log_path'      => 'required|string|max:1000',
            'log_type'      => 'required|in:file,pattern,docker,journalctl',
            'script_path'   => 'nullable|string|max:1000',
            'script_role'   => 'nullable|in:admin,user',
            'restart_command' => 'nullable|string|max:500',
            'restart_role'  => 'nullable|in:admin,user',
            'git_branch'    => 'nullable|string|max:255',
            'git_path'      => 'nullable|string|max:1000',
            'git_pull_role' => 'nullable|in:admin,user',
            'btn_labels'    => 'nullable|array',
            'btn_labels.*'  => 'nullable|string|max:255',
            'btn_commands'  => 'nullable|array',
            'btn_commands.*' => 'nullable|string|max:1000',
            'btn_roles'     => 'nullable|array',
            'btn_roles.*'   => 'nullable|in:admin,user',
            'description'   => 'nullable|string',
            'is_active'     => 'boolean',
        ]);

        $validated['is_active']      = $request->boolean('is_active', true);
        $validated['git_pull_role']  = $request->input('git_pull_role', 'admin');
        $validated['script_role']    = $request->input('script_role', 'admin');
        $validated['restart_role']   = $request->input('restart_role', 'admin');
        $validated['custom_buttons'] = $this->parseCustomButtons($request);

        $logApp->update($validated);

        return redirect()->route('admin.log-apps.index')
            ->with('success', 'Ứng dụng "' . $logApp->name . '" đã được cập nhật!');
    }

    public function destroy(LogApplication $logApp)
    {
        $name = $logApp->name;
        $logApp->delete();
        return redirect()->route('admin.log-apps.index')
            ->with('success', 'Ứng dụng "' . $name . '" đã được xóa!');
    }

    private function parseCustomButtons(Request $request): ?array
    {
        $labels = $request->input('btn_labels', []);
        $commands = $request->input('btn_commands', []);
        $roles = $request->input('btn_roles', []);
        $buttons = [];

        foreach ($labels as $i => $label) {
            $cmd = $commands[$i] ?? '';
            if (trim($label) === '' && trim($cmd) === '') {
                continue;
            }

            $role = $roles[$i] ?? 'admin';
            if (!in_array($role, ['admin', 'user'], true)) {
                $role = 'admin';
            }

            $buttons[] = [
                'label' => trim($label),
                'command' => trim($cmd),
                'role' => $role,
            ];
        }

        return empty($buttons) ? null : $buttons;
    }
}
