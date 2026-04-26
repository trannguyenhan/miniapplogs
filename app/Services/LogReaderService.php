<?php

namespace App\Services;

use App\Models\Server;
use Illuminate\Support\Facades\Http;
use phpseclib3\Net\SSH2;
use phpseclib3\Crypt\PublicKeyLoader;

class LogReaderService
{
    /**
     * Đọc log: tự động chọn local, SSH hoặc Agent tùy cấu hình server.
     */
    public function readLogs(Server $server, string $logPath, int $lines = 1000, string $logType = 'file'): array
    {
        if ($logType === 'journalctl') {
            // journalctl doesn't need path resolution
        } elseif ($logType !== 'docker') {
            $logPath = $this->resolvePath($logPath);
        }

        if ($server->isLocal()) {
            return match($logType) {
                'docker' => $this->readLocalDockerLogs($logPath, $lines),
                'journalctl' => $this->readLocalJournalctl($logPath, $lines),
                default => $this->readLocalLogs($logPath, $lines),
            };
        }

        if ($server->usesSsh()) {
            return match($logType) {
                'docker' => $this->readSshDockerLogs($server, $logPath, $lines),
                'journalctl' => $this->readSshJournalctl($server, $logPath, $lines),
                default => $this->readSshLogs($server, $logPath, $lines),
            };
        }

        return match($logType) {
            'docker' => $this->readAgentDockerLogs($server, $logPath, $lines),
            'journalctl' => $this->readAgentJournalctl($server, $logPath, $lines),
            default => $this->readAgentLogs($server, $logPath, $lines),
        };
    }

    /**
     * Thực thi script trên server.
     */
    public function runScript(Server $server, string $scriptPath): array
    {
        if ($server->isLocal()) {
            return $this->runLocalScript($scriptPath);
        }

        if ($server->usesSsh()) {
            return $this->runSshScript($server, $scriptPath);
        }

        return $this->runAgentScript($server, $scriptPath);
    }

    public function runCommand(Server $server, string $command): array
    {
        if ($server->isLocal()) {
            return $this->runLocalCommand($command);
        }

        if ($server->usesSsh()) {
            return $this->runSshCommand($server, $command);
        }

        return $this->runAgentCommand($server, $command);
    }

    protected function resolvePath(string $path): string
    {
        // Support {Y-m-d}, {Ymd}, {dmY}, etc.
        return preg_replace_callback('/\{([a-zA-Z\-_]+)\}/', function ($matches) {
            return date($matches[1]);
        }, $path);
    }

    // ── Local ─────────────────────────────────────────────────────────────────

    public function readLocalLogs(string $logPath, int $lines = 1000): array
    {
        try {
            if (! file_exists($logPath)) {
                return ['success' => false, 'error' => "File không tồn tại: {$logPath}", 'lines' => []];
            }
            if (! is_readable($logPath)) {
                return ['success' => false, 'error' => "Không có quyền đọc file: {$logPath}", 'lines' => []];
            }

            $file  = new \SplFileObject($logPath, 'r');
            $file->seek(PHP_INT_MAX);
            $total  = $file->key();
            $start  = max(0, $total - $lines);
            $result = [];

            $file->seek($start);
            while (! $file->eof()) {
                $line = rtrim($file->current(), "\r\n");
                if ($line !== '') $result[] = $line;
                $file->next();
            }

            return ['success' => true, 'lines' => $result, 'count' => count($result)];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Lỗi đọc file local: ' . $e->getMessage(), 'lines' => []];
        }
    }

    // ── SSH ───────────────────────────────────────────────────────────────────

    public function readSshLogs(Server $server, string $logPath, int $lines = 1000): array
    {
        try {
            $ssh = $this->makeSshConnection($server);

            if (! $ssh) {
                return ['success' => false, 'error' => 'Không thể kết nối SSH tới server.', 'lines' => []];
            }

            $escapedPath = escapeshellarg($logPath);
            $output = $ssh->exec("tail -n {$lines} {$escapedPath} 2>&1");

            if ($ssh->getExitStatus() !== 0 && str_contains($output, 'No such file')) {
                return ['success' => false, 'error' => "File không tồn tại trên server: {$logPath}", 'lines' => []];
            }

            $result = array_filter(
                explode("\n", rtrim($output, "\n")),
                fn($l) => $l !== ''
            );

            return ['success' => true, 'lines' => array_values($result), 'count' => count($result)];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Lỗi SSH: ' . $e->getMessage(), 'lines' => []];
        }
    }

    /**
     * Test SSH connection và trả về thông tin server.
     */
    public function pingSsh(Server $server): array
    {
        try {
            $ssh = $this->makeSshConnection($server);

            if (! $ssh) {
                return ['success' => false, 'error' => 'Xác thực SSH thất bại.'];
            }

            $hostname = trim($ssh->exec('hostname'));
            $uname    = trim($ssh->exec('uname -r'));

            return [
                'success' => true,
                'data'    => [
                    'hostname' => $hostname ?: 'unknown',
                    'kernel'   => $uname    ?: 'unknown',
                ],
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Tạo SSH2 connection từ thông tin Server.
     */
    protected function makeSshConnection(Server $server): ?SSH2
    {
        $ssh = new SSH2(
            $server->ip_address,
            (int) ($server->ssh_port ?? 22),
            10 // timeout
        );

        $authenticated = false;

        // Ưu tiên private key nếu có
        if (! empty($server->ssh_private_key)) {
            try {
                $key = PublicKeyLoader::load($server->ssh_private_key);
                $authenticated = $ssh->login($server->ssh_user, $key);
            } catch (\Exception $e) {
                // Fallback sang password nếu load key thất bại
                if (! empty($server->ssh_password)) {
                    $authenticated = $ssh->login($server->ssh_user, $server->ssh_password);
                }
            }
        } elseif (! empty($server->ssh_password)) {
            $authenticated = $ssh->login($server->ssh_user, $server->ssh_password);
        }

        return $authenticated ? $ssh : null;
    }

    /**
     * Test thông tin SSH từ raw params (dùng khi chưa lưu server).
     */
    public function pingSshRaw(string $host, int $port, string $user, string $password = '', string $privateKey = ''): array
    {
        try {
            $ssh = new SSH2($host, $port, 10);
            $authenticated = false;

            if (! empty($privateKey)) {
                try {
                    $key = PublicKeyLoader::load($privateKey);
                    $authenticated = $ssh->login($user, $key);
                } catch (\Exception $e) {
                    if (! empty($password)) {
                        $authenticated = $ssh->login($user, $password);
                    }
                }
            } elseif (! empty($password)) {
                $authenticated = $ssh->login($user, $password);
            }

            if (! $authenticated) {
                return ['success' => false, 'error' => 'Xác thực SSH thất bại. Kiểm tra lại user/password/key.'];
            }

            $hostname = trim($ssh->exec('hostname'));
            $uname    = trim($ssh->exec('uname -r'));

            return [
                'success' => true,
                'data'    => [
                    'hostname' => $hostname ?: 'unknown',
                    'kernel'   => $uname    ?: 'unknown',
                ],
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ── HTTP Agent ────────────────────────────────────────────────────────────

    public function readAgentLogs(Server $server, string $logPath, int $lines = 1000): array
    {
        try {
            $request = Http::timeout(15)->withHeaders(['Accept' => 'application/json']);

            if ($server->agent_token) {
                $request = $request->withToken($server->agent_token);
            }

            $response = $request->get(rtrim($server->agent_url, '/') . '/logs', [
                'path'  => $logPath,
                'lines' => $lines,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'lines'   => $data['lines'] ?? [],
                    'count'   => $data['count']  ?? 0,
                ];
            }

            $error = $response->json('error') ?? $response->body();
            return ['success' => false, 'error' => "Agent lỗi [{$response->status()}]: {$error}", 'lines' => []];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Không thể kết nối tới agent: ' . $e->getMessage(), 'lines' => []];
        }
    }

    protected function readLocalDockerLogs(string $container, int $lines = 1000): array
    {
        try {
            $output = shell_exec("docker logs --tail " . (int)$lines . " " . escapeshellarg($container) . " 2>&1");
            if ($output === null) return ['success' => false, 'error' => 'Chạy docker logs thất bại hoặc container không có log', 'lines' => []];
            $logLines = array_filter(explode("\n", rtrim($output, "\n")), fn($val) => !is_null($val));
            return ['success' => true, 'path' => $container, 'lines' => array_values($logLines), 'count' => count($logLines)];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage(), 'lines' => []];
        }
    }

    protected function readSshDockerLogs(Server $server, string $container, int $lines = 1000): array
    {
        try {
            $ssh = $this->makeSshConnection($server);
            if (!$ssh) return ['success' => false, 'error' => 'SSH connection failed', 'lines' => []];

            $output = $ssh->exec("docker logs --tail " . (int)$lines . " " . escapeshellarg($container) . " 2>&1");
            if ($output === false) return ['success' => false, 'error' => 'Execute command failed'];
            $logLines = array_filter(explode("\n", rtrim($output, "\n")), fn($val) => !is_null($val));
            return ['success' => true, 'path' => $container, 'lines' => array_values($logLines), 'count' => count($logLines)];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage(), 'lines' => []];
        }
    }

    protected function readAgentDockerLogs(Server $server, string $container, int $lines = 1000): array
    {
        try {
            $request = Http::timeout(15)->withHeaders(['Accept' => 'application/json']);
            if ($server->agent_token) {
                $request = $request->withToken($server->agent_token);
            }

            $response = $request->get(rtrim($server->agent_url, '/') . '/docker-logs', [
                'container' => $container,
                'lines'     => $lines,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'lines'   => $data['lines'] ?? [],
                    'count'   => $data['count']  ?? 0,
                ];
            }

            $error = $response->json('error') ?? $response->body();
            return ['success' => false, 'error' => "Agent lỗi [{$response->status()}]: {$error}", 'lines' => []];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Không thể kết nối tới agent: ' . $e->getMessage(), 'lines' => []];
        }
    }

    // ── Journalctl ──────────────────────────────────────────────────────────────

    protected function readLocalJournalctl(string $unit, int $lines = 1000): array
    {
        try {
            $unit = escapeshellarg($unit);
            $output = shell_exec("journalctl -u {$unit} -n " . (int)$lines . " --no-pager --no-hostname 2>&1");
            
            if ($output === null) {
                return ['success' => false, 'error' => 'Chạy journalctl thất bại hoặc unit không tồn tại', 'lines' => []];
            }

            $logLines = array_filter(
                explode("\n", rtrim($output, "\n")),
                fn($l) => $l !== ''
            );

            return ['success' => true, 'lines' => array_values($logLines), 'count' => count($logLines)];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Lỗi đọc journalctl local: ' . $e->getMessage(), 'lines' => []];
        }
    }

    protected function readSshJournalctl(Server $server, string $unit, int $lines = 1000): array
    {
        try {
            $ssh = $this->makeSshConnection($server);

            if (!$ssh) {
                return ['success' => false, 'error' => 'Không thể kết nối SSH tới server.', 'lines' => []];
            }

            $unit = escapeshellarg($unit);
            $output = $ssh->exec("journalctl -u {$unit} -n " . (int)$lines . " --no-pager --no-hostname 2>&1");

            if ($ssh->getExitStatus() !== 0) {
                $errorMsg = trim($output);
                if (str_contains($errorMsg, 'No journal files were found') || str_contains($errorMsg, 'No entries')) {
                    return ['success' => false, 'error' => "Unit không tồn tại hoặc không có log: {$unit}", 'lines' => []];
                }
                return ['success' => false, 'error' => "journalctl lỗi: {$errorMsg}", 'lines' => []];
            }

            $logLines = array_filter(
                explode("\n", rtrim($output, "\n")),
                fn($l) => $l !== ''
            );

            return ['success' => true, 'lines' => array_values($logLines), 'count' => count($logLines)];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Lỗi SSH journalctl: ' . $e->getMessage(), 'lines' => []];
        }
    }

    protected function readAgentJournalctl(Server $server, string $unit, int $lines = 1000): array
    {
        try {
            $request = Http::timeout(15)->withHeaders(['Accept' => 'application/json']);
            if ($server->agent_token) {
                $request = $request->withToken($server->agent_token);
            }

            $response = $request->get(rtrim($server->agent_url, '/') . '/journalctl', [
                'unit'  => $unit,
                'lines' => $lines,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'lines'   => $data['lines'] ?? [],
                    'count'   => $data['count']  ?? 0,
                ];
            }

            $error = $response->json('error') ?? $response->body();
            return ['success' => false, 'error' => "Agent lỗi [{$response->status()}]: {$error}", 'lines' => []];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Không thể kết nối tới agent: ' . $e->getMessage(), 'lines' => []];
        }
    }

    // ── Health check ──────────────────────────────────────────────────────────

    public function pingAgent(string $agentUrl, string $agentToken = ''): array
    {
        try {
            $request = Http::timeout(5);
            if ($agentToken) {
                $request = $request->withToken($agentToken);
            }
            $response = $request->get(rtrim($agentUrl, '/') . '/health');
            if ($response->successful()) {
                return ['success' => true, 'data' => $response->json()];
            }
            return ['success' => false, 'error' => "HTTP {$response->status()}"];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ── Browse / Info ─────────────────────────────────────────────────────────

    /**
     * Liệt kê file/thư mục tại path trên remote server.
     * Hỗ trợ cả SSH và Agent.
     */
    public function listDirectory(Server $server, string $path): array
    {
        if ($server->usesSsh()) {
            return $this->listSshDirectory($server, $path);
        }

        return $this->listAgentDirectory($server, $path);
    }

    public function listSshDirectory(Server $server, string $path): array
    {
        try {
            $ssh = $this->makeSshConnection($server);
            if (! $ssh) {
                return ['success' => false, 'error' => 'Không kết nối được SSH.'];
            }

            $escaped = escapeshellarg($path);
            $output  = $ssh->exec("ls -1aF {$escaped} 2>&1");

            if ($ssh->getExitStatus() !== 0) {
                return ['success' => false, 'error' => trim($output)];
            }

            $entries = array_filter(explode("\n", trim($output)), fn($l) => $l !== '');
            $items   = [];
            foreach ($entries as $entry) {
                $isDir = str_ends_with($entry, '/');
                $name  = rtrim($entry, '/@*=|');
                if ($name === '.' || $name === '..') continue;
                $items[] = [
                    'name'     => $name,
                    'is_dir'   => $isDir,
                    'path'     => rtrim($path, '/') . '/' . $name,
                ];
            }

            return ['success' => true, 'path' => $path, 'items' => $items];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function listAgentDirectory(Server $server, string $path): array
    {
        try {
            $request = Http::timeout(10)->withHeaders(['Accept' => 'application/json']);
            if ($server->agent_token) {
                $request = $request->withToken($server->agent_token);
            }
            $response = $request->get(rtrim($server->agent_url, '/') . '/list', [
                'path' => $path,
            ]);
            if ($response->successful()) {
                return ['success' => true] + $response->json();
            }
            return ['success' => false, 'error' => $response->json('error') ?? $response->body()];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Lấy thông tin về một path trên remote server qua agent.
     */
    public function infoAgentPath(Server $server, string $path): array
    {
        try {
            $request = Http::timeout(10)->withHeaders(['Accept' => 'application/json']);
            if ($server->agent_token) {
                $request = $request->withToken($server->agent_token);
            }
            $response = $request->get(rtrim($server->agent_url, '/') . '/info', [
                'path' => $path,
            ]);
            if ($response->successful()) {
                return ['success' => true] + $response->json();
            }
            return ['success' => false, 'error' => $response->json('error') ?? $response->body()];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    protected function runLocalScript(string $scriptPath): array
    {
        try {
            if (!file_exists($scriptPath)) {
                return ['success' => false, 'error' => "Script không tồn tại: {$scriptPath}"];
            }
            $output = shell_exec("bash " . escapeshellarg($scriptPath) . " 2>&1");
            return ['success' => true, 'output' => $output];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    protected function runSshScript(Server $server, string $scriptPath): array
    {
        try {
            $ssh = $this->makeSshConnection($server);
            if (!$ssh) return ['success' => false, 'error' => 'SSH connection failed'];
            $output = $ssh->exec("bash " . escapeshellarg($scriptPath) . " 2>&1");
            return ['success' => true, 'output' => $output];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    protected function runAgentScript(Server $server, string $scriptPath): array
    {
        try {
            $request = Http::timeout(60)->withHeaders(['Accept' => 'application/json']);
            if ($server->agent_token) $request = $request->withToken($server->agent_token);
            $response = $request->post(rtrim($server->agent_url, '/') . '/execute', [
                'path' => $scriptPath,
            ]);
            if ($response->successful()) {
                return ['success' => true, 'output' => $response->json('output')];
            }
            return ['success' => false, 'error' => $response->json('error') ?? $response->body()];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    protected function runLocalCommand(string $command): array
    {
        try {
            $output = shell_exec($command . " 2>&1");
            return ['success' => true, 'output' => $output ?? ''];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    protected function runSshCommand(Server $server, string $command): array
    {
        try {
            $ssh = $this->makeSshConnection($server);
            if (!$ssh) return ['success' => false, 'error' => 'SSH connection failed'];
            $output = $ssh->exec($command . " 2>&1");
            return ['success' => true, 'output' => $output];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    protected function runAgentCommand(Server $server, string $command): array
    {
        try {
            $request = Http::timeout(60)->withHeaders(['Accept' => 'application/json']);
            if ($server->agent_token) $request = $request->withToken($server->agent_token);
            $response = $request->post(rtrim($server->agent_url, '/') . '/run-command', [
                'command' => $command,
            ]);
            if ($response->successful()) {
                return ['success' => true, 'output' => $response->json('output')];
            }
            return ['success' => false, 'error' => $response->json('error') ?? $response->body()];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ── Git Pull ────────────────────────────────────────────────────────────────

    /**
     * Thực thi git pull trên server.
     */
    public function gitPull(Server $server, string $branch, bool $noRebase = false, ?string $gitPath = null, ?string $logPath = null): array
    {
        // Nếu không có git_path, tự động detect từ log_path
        if (empty($gitPath) && !empty($logPath)) {
            $gitPath = $this->detectGitPath($server, $logPath);
        }

        if ($server->isLocal()) {
            return $this->gitPullLocal($branch, $noRebase, $gitPath);
        }

        if ($server->usesSsh()) {
            return $this->gitPullSsh($server, $branch, $noRebase, $gitPath);
        }

        return $this->gitPullAgent($server, $branch, $noRebase, $gitPath);
    }

    /**
     * Tự động detect thư mục Git repository từ log_path.
     */
    protected function detectGitPath(Server $server, string $logPath): ?string
    {
        // Ví dụ: /var/www/app/logs/app.log -> /var/www/app
        $logDir = dirname($logPath);
        
        if ($server->isLocal()) {
            // Local: kiểm tra trực tiếp
            $path = $logDir;
            $maxDepth = 5;
            for ($i = 0; $i < $maxDepth; $i++) {
                if (is_dir($path . '/.git')) {
                    return $path;
                }
                $parent = dirname($path);
                if ($parent === $path) break;
                $path = $parent;
            }
        }
        
        // SSH/Agent: trả về logDir để server tự detect
        return $logDir;
    }

    protected function gitPullLocal(string $branch, bool $noRebase = false, ?string $gitPath = null): array
    {
        try {
            // Nếu không có gitPath, thử detect từ thư mục hiện tại
            if (empty($gitPath)) {
                $gitPath = getcwd();
            }
            
            // Kiểm tra xem có phải Git repository không
            if (!is_dir($gitPath . '/.git')) {
                return ['success' => false, 'error' => "Thư mục không phải Git repository: {$gitPath}"];
            }
            
            $branch = escapeshellarg($branch);
            $cdCommand = 'cd ' . escapeshellarg($gitPath) . ' && ';
            $command = $cdCommand . ($noRebase 
                ? "git pull origin {$branch} --no-rebase 2>&1"
                : "git pull origin {$branch} 2>&1");
            
            $output = shell_exec($command);
            
            if ($output === null) {
                return ['success' => false, 'error' => 'Không thể thực thi git pull.'];
            }

            return ['success' => true, 'output' => trim($output)];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Lỗi git pull local: ' . $e->getMessage()];
        }
    }

    protected function gitPullSsh(Server $server, string $branch, bool $noRebase = false, ?string $gitPath = null): array
    {
        try {
            $ssh = $this->makeSshConnection($server);

            if (!$ssh) {
                return ['success' => false, 'error' => 'Không thể kết nối SSH tới server.'];
            }

            // Nếu không có gitPath, detect trên remote server
            if (empty($gitPath)) {
                $gitPath = '.';
            }
            
            $branch = escapeshellarg($branch);
            $gitPathEscaped = escapeshellarg($gitPath);
            
            // Tìm thư mục Git từ gitPath (có thể là thư mục cha của log)
            $findGitCmd = "cd {$gitPathEscaped} && while [ ! -d .git ] && [ \"\$(pwd)\" != \"/\" ]; do cd ..; done && pwd";
            $detectedPath = trim($ssh->exec($findGitCmd));
            
            if (empty($detectedPath) || !str_contains($detectedPath, '/')) {
                return ['success' => false, 'error' => 'Không tìm thấy Git repository. Vui lòng cấu hình git_path.'];
            }
            
            $cdCommand = 'cd ' . escapeshellarg($detectedPath) . ' && ';
            $command = $cdCommand . ($noRebase 
                ? "git pull origin {$branch} --no-rebase 2>&1"
                : "git pull origin {$branch} 2>&1");
            
            $output = $ssh->exec($command);

            if ($ssh->getExitStatus() !== 0) {
                return ['success' => false, 'error' => trim($output) ?: 'Git pull thất bại'];
            }

            return ['success' => true, 'output' => trim($output)];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Lỗi SSH git pull: ' . $e->getMessage()];
        }
    }

    protected function gitPullAgent(Server $server, string $branch, bool $noRebase = false, ?string $gitPath = null): array
    {
        try {
            $request = Http::timeout(60)->withHeaders(['Accept' => 'application/json']);
            if ($server->agent_token) {
                $request = $request->withToken($server->agent_token);
            }
            
            $response = $request->post(rtrim($server->agent_url, '/') . '/git-pull', [
                'branch' => $branch,
                'no_rebase' => $noRebase,
                'path' => $gitPath,
            ]);
            
            if ($response->successful()) {
                return ['success' => true, 'output' => $response->json('output')];
            }
            
            return ['success' => false, 'error' => $response->json('error') ?? $response->body()];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Không thể kết nối tới agent: ' . $e->getMessage()];
        }
    }
}
