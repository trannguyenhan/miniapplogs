<?php

namespace App\Http\Controllers;

use App\Models\LogApplication;
use App\Models\Server;
use App\Services\LogReaderService;
use Illuminate\Http\Request;

class LogViewController extends Controller
{
    public function __construct(private LogReaderService $logReader) {}

    /**
     * Trang chủ - danh sách tất cả ứng dụng
     */
    public function index()
    {
        $servers = Server::with('activeLogApplications')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('logs.index', compact('servers'));
    }

    /**
     * Xem log của 1 ứng dụng cụ thể
     */
    public function show(LogApplication $logApp)
    {
        if (!$logApp->is_active || !$logApp->server->is_active) {
            abort(404, 'Ứng dụng này hiện không hoạt động.');
        }

        return view('logs.show', compact('logApp'));
    }

    /**
     * API: Lấy nội dung log (JSON) - dùng cho reload/ajax
     */
    public function fetch(LogApplication $logApp)
    {
        if (!$logApp->is_active || !$logApp->server->is_active) {
            return response()->json([
                'success' => false,
                'error'   => 'Ứng dụng không hoạt động.',
            ], 404);
        }

        $lines  = min(request()->integer('lines', 1000), 5000);
        $result = $this->logReader->readLogs($logApp->server, $logApp->log_path, $lines, $logApp->log_type ?: 'file');

        return response()->json(array_merge($result, [
            'app_name'   => $logApp->name,
            'server'     => $logApp->server->name,
            'log_path'   => $logApp->log_path,
            'fetched_at' => now()->format('d/m/Y H:i:s'),
        ]));
    }

    /**
     * Thực thi script pull code/restart
     */
    public function executeScript(LogApplication $logApp)
    {
        if (!$logApp->script_path) {
            return response()->json(['success' => false, 'error' => 'Ứng dụng này không có script được cấu hình.']);
        }

        if (!$logApp->canRunScript(auth()->user())) {
            return response()->json(['success' => false, 'error' => 'Bạn không có quyền thực thi script này.'], 403);
        }

        $result = $this->logReader->runScript($logApp->server, $logApp->script_path);

        return response()->json($result);
    }
}
