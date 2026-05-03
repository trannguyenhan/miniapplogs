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
     * Build a command that always executes inside the configured project path.
     */
    private function buildProjectCommand(string $projectPath, string $command): string
    {
        return 'cd ' . escapeshellarg($projectPath) . ' && (' . $command . ')';
    }

    /**
     * Trang chủ - danh sách tất cả ứng dụng
     */
    public function index(Request $request)
    {
        $apps = LogApplication::with('server')
            ->where('is_active', true)
            ->whereHas('server', fn($q) => $q->where('is_active', true))
            ->orderBy('name')
            ->get();

        // Group by tag for user view
        $tagFilter = $request->query('tag');
        $allTags = $apps->flatMap(fn($a) => $a->tags ?? [])->unique()->sort()->values();

        if ($tagFilter) {
            $apps = $apps->filter(fn($a) => in_array($tagFilter, $a->tags ?? [], true));
        }

        // Group: apps with tags grouped, untagged separate
        $grouped = [];
        foreach ($apps as $app) {
            $tags = $app->tags ?? [];
            if (empty($tags)) {
                $grouped['__untagged'][] = $app;
            } else {
                foreach ($tags as $tag) {
                    $grouped[$tag][] = $app;
                }
            }
        }
        ksort($grouped);

        // Admins also still get servers for admin view
        $servers = null;
        if (auth()->user()->isAdmin()) {
            $servers = \App\Models\Server::with('activeLogApplications')
                ->where('is_active', true)->orderBy('name')->get();
        }

        return view('logs.index', compact('grouped', 'allTags', 'tagFilter', 'apps', 'servers'));
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
        if (empty($logApp->script_path) || trim($logApp->script_path) === '') {
            return response()->json(['success' => false, 'error' => 'Ứng dụng này không có script được cấu hình.']);
        }

        if (empty($logApp->git_path) || trim($logApp->git_path) === '') {
            return response()->json(['success' => false, 'error' => 'Project Path chưa được cấu hình. Vui lòng thiết lập Project Path để thực thi script.']);
        }

        if (!$logApp->canRunScript(auth()->user())) {
            return response()->json(['success' => false, 'error' => 'Bạn không có quyền thực thi script này.'], 403);
        }

        ignore_user_abort(true);
        set_time_limit(0);
        $result = $this->logReader->runScript($logApp->server, $logApp->script_path, $logApp->git_path);

        return response()->json($result);
    }

    /**
     * Thực thi Git Pull
     */
    public function gitPull(LogApplication $logApp, Request $request)
    {
        if (empty($logApp->git_branch) || trim($logApp->git_branch) === '') {
            return response()->json(['success' => false, 'error' => 'Ứng dụng này chưa được cấu hình Git branch.']);
        }

        if (empty($logApp->git_path) || trim($logApp->git_path) === '') {
            return response()->json(['success' => false, 'error' => 'Project Path chưa được cấu hình. Vui lòng thiết lập Project Path để thực thi Git Pull.']);
        }

        if (!$logApp->canGitPull(auth()->user())) {
            return response()->json(['success' => false, 'error' => 'Bạn không có quyền thực thi Git Pull.'], 403);
        }

        $noRebase = $request->boolean('no_rebase', false);

        $result = $this->logReader->gitPull($logApp->server, $logApp->git_branch, $noRebase, $logApp->git_path);

        return response()->json($result);
    }

    /**
     * Thực thi lệnh Restart
     */
    public function executeRestart(LogApplication $logApp)
    {
        if (!$logApp->canRestart(auth()->user())) {
            return response()->json(['success' => false, 'error' => 'Bạn không có quyền thực thi lệnh này.'], 403);
        }

        if (empty($logApp->git_path) || trim($logApp->git_path) === '') {
            return response()->json(['success' => false, 'error' => 'Project Path chưa được cấu hình. Vui lòng thiết lập Project Path để thực thi lệnh này.']);
        }

        $command = $this->buildProjectCommand($logApp->git_path, $logApp->restart_command);
        ignore_user_abort(true);
        set_time_limit(0);
        $result = $this->logReader->runCommand($logApp->server, $command);

        return response()->json($result);
    }

    /**
     * Thực thi Custom Button theo index
     */
    public function executeButton(LogApplication $logApp, int $index)
    {
        $buttons = $logApp->custom_buttons ?? [];
        if (!isset($buttons[$index])) {
            return response()->json(['success' => false, 'error' => 'Button không tồn tại.'], 404);
        }

        $button = $buttons[$index];
        if (!$logApp->canRunCustomButton(auth()->user(), $button)) {
            return response()->json(['success' => false, 'error' => 'Bạn không có quyền thực thi button này.'], 403);
        }

        $command = $button['command'] ?? '';
        if (empty(trim($command))) {
            return response()->json(['success' => false, 'error' => 'Lệnh trống.']);
        }

        if (empty($logApp->git_path) || trim($logApp->git_path) === '') {
            return response()->json(['success' => false, 'error' => 'Project Path chưa được cấu hình. Vui lòng thiết lập Project Path để thực thi lệnh này.']);
        }

        $command = $this->buildProjectCommand($logApp->git_path, $command);

        ignore_user_abort(true);
        set_time_limit(0);
        $result = $this->logReader->runCommand($logApp->server, $command);

        return response()->json($result);
    }
}
