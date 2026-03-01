<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Server;
use App\Services\LogReaderService;
use Illuminate\Http\Request;

class ServerController extends Controller
{
    public function index()
    {
        $servers = Server::withCount('logApplications')->orderBy('name')->paginate(15);
        return view('admin.servers.index', compact('servers'));
    }

    public function create()
    {
        return view('admin.servers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'connection_type' => 'required|in:local,agent',
            'agent_url'       => 'required_if:connection_type,agent|nullable|url',
            'agent_token'     => 'nullable|string|max:512',
            'description'     => 'nullable|string',
            'is_active'       => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        Server::create($validated);

        return redirect()->route('admin.servers.index')
            ->with('success', 'Server "' . $validated['name'] . '" đã được thêm thành công!');
    }

    public function show(Server $server)
    {
        $server->load('logApplications');
        return view('admin.servers.show', compact('server'));
    }

    public function edit(Server $server)
    {
        return view('admin.servers.edit', compact('server'));
    }

    public function update(Request $request, Server $server)
    {
        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'connection_type' => 'required|in:local,agent',
            'agent_url'       => 'required_if:connection_type,agent|nullable|url',
            'agent_token'     => 'nullable|string|max:512',
            'description'     => 'nullable|string',
            'is_active'       => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        // Không xóa token cũ nếu để trống
        if (empty($validated['agent_token'])) {
            unset($validated['agent_token']);
        }

        $server->update($validated);

        return redirect()->route('admin.servers.index')
            ->with('success', 'Server "' . $server->name . '" đã được cập nhật!');
    }

    public function destroy(Server $server)
    {
        $name = $server->name;
        $server->delete();
        return redirect()->route('admin.servers.index')
            ->with('success', 'Server "' . $name . '" đã được xóa!');
    }

    /**
     * Kiểm tra kết nối agent (AJAX).
     */
    public function testAgent(Request $request)
    {
        $url = $request->input('agent_url');
        if (! $url) {
            return response()->json(['success' => false, 'error' => 'agent_url is required'], 422);
        }

        $result = app(LogReaderService::class)->pingAgent($url);
        return response()->json($result);
    }

    /**
     * Browse thư mục trên remote server qua agent (AJAX).
     * Dùng để chọn đường dẫn log khi tạo LogApplication.
     */
    public function browseAgent(Request $request, Server $server)
    {
        if (! $server->usesAgent()) {
            return response()->json(['success' => false, 'error' => 'Server không dùng HTTP Agent'], 422);
        }

        $path   = $request->input('path', '/var/log');
        $result = app(LogReaderService::class)->listAgentDirectory($server, $path);
        return response()->json($result);
    }
}
