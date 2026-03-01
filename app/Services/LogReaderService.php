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
    public function readLogs(Server $server, string $logPath, int $lines = 1000): array
    {
        if ($server->isLocal()) {
            return $this->readLocalLogs($logPath, $lines);
        }

        if ($server->usesSsh()) {
            return $this->readSshLogs($server, $logPath, $lines);
        }

        return $this->readAgentLogs($server, $logPath, $lines);
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
}
