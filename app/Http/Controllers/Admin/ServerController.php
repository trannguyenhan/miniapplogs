<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Server;
use App\Services\LogReaderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

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
            'connection_type' => 'required|in:local,ssh,agent',
            // SSH
            'ip_address'      => 'required_if:connection_type,ssh|nullable|string|max:255',
            'ssh_port'        => 'nullable|integer|min:1|max:65535',
            'ssh_user'        => 'required_if:connection_type,ssh|nullable|string|max:255',
            'ssh_password'    => 'nullable|string|max:1024',
            'ssh_private_key' => 'nullable|string',
            // Agent
            'agent_url'       => 'required_if:connection_type,agent|nullable|url',
            'agent_token'     => 'nullable|string|max:512',
            // Common
            'description'     => 'nullable|string',
            'is_active'       => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        // Clear irrelevant fields based on type
        if ($validated['connection_type'] !== 'ssh') {
            $validated['ip_address']      = null;
            $validated['ssh_port']        = null;
            $validated['ssh_user']        = null;
            $validated['ssh_password']    = null;
            $validated['ssh_private_key'] = null;
        }
        if ($validated['connection_type'] !== 'agent') {
            $validated['agent_url']   = null;
            $validated['agent_token'] = null;
        }

        Server::create($validated);

        return redirect()->route('admin.servers.index')
            ->with('success', __('app.server_added', ['name' => $validated['name']]));
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
            'connection_type' => 'required|in:local,ssh,agent',
            // SSH
            'ip_address'      => 'required_if:connection_type,ssh|nullable|string|max:255',
            'ssh_port'        => 'nullable|integer|min:1|max:65535',
            'ssh_user'        => 'required_if:connection_type,ssh|nullable|string|max:255',
            'ssh_password'    => 'nullable|string|max:1024',
            'ssh_private_key' => 'nullable|string',
            // Agent
            'agent_url'       => 'required_if:connection_type,agent|nullable|url',
            'agent_token'     => 'nullable|string|max:512',
            // Common
            'description'     => 'nullable|string',
            'is_active'       => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        // Clear irrelevant fields
        if ($validated['connection_type'] !== 'ssh') {
            $validated['ip_address']      = null;
            $validated['ssh_port']        = null;
            $validated['ssh_user']        = null;
            $validated['ssh_password']    = null;
            $validated['ssh_private_key'] = null;
        } else {
            // Giữ nguyên password cũ nếu để trống
            if (empty($validated['ssh_password'])) {
                unset($validated['ssh_password']);
            }
            // Giữ nguyên private key cũ nếu để trống
            if (empty($validated['ssh_private_key'])) {
                unset($validated['ssh_private_key']);
            }
        }

        if ($validated['connection_type'] !== 'agent') {
            $validated['agent_url']   = null;
            $validated['agent_token'] = null;
        } else {
            // Giữ nguyên token cũ nếu để trống
            if (empty($validated['agent_token'])) {
                unset($validated['agent_token']);
            }
        }

        // Update via query builder to avoid reading/decrypting old corrupted encrypted values.
        if (array_key_exists('ssh_password', $validated) && !is_null($validated['ssh_password'])) {
            $validated['ssh_password'] = Crypt::encryptString($validated['ssh_password']);
        }
        if (array_key_exists('ssh_private_key', $validated) && !is_null($validated['ssh_private_key'])) {
            $validated['ssh_private_key'] = Crypt::encryptString($validated['ssh_private_key']);
        }
        if (array_key_exists('agent_token', $validated) && !is_null($validated['agent_token'])) {
            $validated['agent_token'] = Crypt::encryptString($validated['agent_token']);
        }

        $validated['updated_at'] = now();

        DB::table('servers')
            ->where('id', $server->id)
            ->update($validated);

        return redirect()->route('admin.servers.index')
            ->with('success', __('app.server_updated', ['name' => $server->name]));
    }

    public function destroy(Server $server)
    {
        $name = $server->name;
        $server->delete();
        return redirect()->route('admin.servers.index')
            ->with('success', __('app.server_deleted', ['name' => $name]));
    }

    /**
     * Test SSH connection (AJAX).
     */
    public function testSsh(Request $request)
    {
        $request->validate([
            'ip_address'      => 'required|string',
            'ssh_port'        => 'nullable|integer',
            'ssh_user'        => 'required|string',
            'ssh_password'    => 'nullable|string',
            'ssh_private_key' => 'nullable|string',
        ]);

        $result = app(LogReaderService::class)->pingSshRaw(
            host:       $request->input('ip_address'),
            port:       (int) $request->input('ssh_port', 22),
            user:       $request->input('ssh_user'),
            password:   $request->input('ssh_password', ''),
            privateKey: $request->input('ssh_private_key', ''),
        );

        return response()->json($result);
    }

    /**
     * Kiểm tra kết nối agent (AJAX).
     */
    public function testAgent(Request $request)
    {
        $request->validate([
            'agent_url'   => 'required|url|max:512',
            'agent_token' => 'nullable|string|max:512',
        ]);

        $url   = $request->input('agent_url');
        $token = $request->input('agent_token', '');

        // Block SSRF: only allow http/https, disallow private/loopback addresses
        $parsed = parse_url($url);
        $scheme = strtolower($parsed['scheme'] ?? '');
        if (! in_array($scheme, ['http', 'https'], true)) {
            return response()->json(['success' => false, 'error' => 'Only http/https URLs are allowed.'], 422);
        }
        $host = $parsed['host'] ?? '';
        if ($this->isPrivateHost($host)) {
            return response()->json(['success' => false, 'error' => 'URL trỏ đến địa chỉ nội bộ không được phép.'], 422);
        }

        $result = app(LogReaderService::class)->pingAgent($url, $token);
        return response()->json($result);
    }

    private function isPrivateHost(string $host): bool
    {
        // Resolve hostname to IP for check
        $ip = filter_var($host, FILTER_VALIDATE_IP) ? $host : gethostbyname($host);
        if (! filter_var($ip, FILTER_VALIDATE_IP)) {
            return false; // Can't resolve, let it fail naturally
        }
        // Block loopback, private ranges, link-local
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }

    /**
     * Browse thư mục trên remote server (AJAX).
     * Hỗ trợ cả SSH và Agent.
     */
    public function browseServer(Request $request, Server $server)
    {
        if ($server->isLocal()) {
            return response()->json(['success' => false, 'error' => 'Server local không hỗ trợ browse từ xa.'], 422);
        }

        $path   = $request->input('path', '/var/log');
        $result = app(LogReaderService::class)->listDirectory($server, $path);
        return response()->json($result);
    }

    /**
     * @deprecated Dùng browseServer() thay thế.
     */
    public function browseAgent(Request $request, Server $server)
    {
        return $this->browseServer($request, $server);
    }
}
