<?php

namespace App\Services;

use App\Models\Server;
use Illuminate\Support\Facades\Http;

class LogReaderService
{
    /**
     * Đọc log: tự động chọn local hoặc Agent tùy cấu hình server.
     */
    public function readLogs(Server $server, string $logPath, int $lines = 1000): array
    {
        if ($server->isLocal()) {
            return $this->readLocalLogs($logPath, $lines);
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

    public function pingAgent(string $agentUrl): array
    {
        try {
            $response = Http::timeout(5)->get(rtrim($agentUrl, '/') . '/health');
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
     * Liệt kê file/thư mục tại path trên remote server qua agent.
     */
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
