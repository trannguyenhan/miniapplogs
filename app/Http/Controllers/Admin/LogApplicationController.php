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
            'allowed_roles' => 'nullable|string',
            'description'   => 'nullable|string',
            'is_active'     => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['allowed_roles'] = $request->input('allowed_roles', 'admin,user');

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
            'allowed_roles' => 'nullable|string',
            'description'   => 'nullable|string',
            'is_active'     => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['allowed_roles'] = $request->input('allowed_roles', 'admin,user');

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
}
