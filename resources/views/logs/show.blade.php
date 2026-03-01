@extends('layouts.app')

@section('title', 'Log: ' . $logApp->name)

@section('breadcrumb')
    <a href="{{ route('logs.index') }}" style="color: var(--text-secondary); text-decoration:none;">Danh sách</a>
    @if(auth()->user()->role === 'admin')
    <span class="sep">/</span>
    <span style="color: var(--text-muted);">{{ $logApp->server->name }}</span>
    @endif
    <span class="sep">/</span>
    <span class="current">{{ $logApp->name }}</span>
@endsection

@section('header-actions')
    <span id="auto-refresh-status" class="badge badge-info" style="cursor:pointer;" onclick="toggleAutoRefresh()">
        <i class="fas fa-sync" id="ar-icon"></i>
        <span id="ar-text">Auto-refresh: OFF</span>
    </span>
@endsection

@push('styles')
<style>
    .log-meta-bar {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        align-items: center;
        padding: 14px 20px;
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 10px;
        margin-bottom: 16px;
    }

    .log-meta-item {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        color: var(--text-secondary);
    }

    .log-meta-item i { color: var(--text-muted); }
    .log-meta-item code {
        font-family: 'JetBrains Mono', monospace;
        background: var(--bg-primary);
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 11px;
        color: var(--accent);
    }

    .log-controls {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 14px;
        flex-wrap: wrap;
    }

    .log-controls-left { display: flex; gap: 8px; flex-wrap: wrap; }
    .log-controls-right { margin-left: auto; display: flex; gap: 8px; align-items: center; }

    .status-indicator {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        color: var(--text-muted);
    }

    .status-dot {
        width: 8px; height: 8px;
        border-radius: 50%;
        background: var(--text-muted);
    }
    .status-dot.loading {
        background: var(--warning);
        animation: pulse 1s infinite;
    }
    .status-dot.success { background: var(--success); }
    .status-dot.error { background: var(--danger); }

    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.3; }
    }

    .log-wrapper {
        background: #0a0e14;
        border: 1px solid var(--border);
        border-radius: 10px;
        overflow: hidden;
    }

    .log-header {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 16px;
        background: var(--bg-secondary);
        border-bottom: 1px solid var(--border);
    }

    .log-header-title {
        font-size: 12px;
        color: var(--text-secondary);
        font-family: 'JetBrains Mono', monospace;
    }

    .log-stats {
        margin-left: auto;
        font-size: 11px;
        color: var(--text-muted);
    }

    #log-container {
        height: calc(100vh - 320px);
        min-height: 400px;
        overflow-y: auto;
        overflow-x: auto;
        padding: 12px 0;
        font-family: 'JetBrains Mono', monospace;
        font-size: 12px;
        line-height: 1.65;
    }

    .log-line {
        display: flex;
        padding: 0 16px;
        min-height: 22px;
        align-items: flex-start;
        word-break: break-all;
        white-space: pre-wrap;
    }

    .log-line:hover { background: rgba(255,255,255,0.03); }

    .log-line-number {
        color: #404959;
        min-width: 50px;
        margin-right: 16px;
        text-align: right;
        user-select: none;
        flex-shrink: 0;
        font-size: 11px;
        padding-top: 1px;
    }

    .log-line-text { flex: 1; color: #c9d1d9; }

    /* Log level coloring */
    .log-line.level-error .log-line-text { color: #f85149; }
    .log-line.level-warn .log-line-text { color: #d29922; }
    .log-line.level-info .log-line-text { color: #58a6ff; }
    .log-line.level-debug .log-line-text { color: #8b949e; }

    /* Search highlight */
    .highlight { background: rgba(255, 200, 0, 0.3); border-radius: 2px; }

    .search-bar {
        display: flex;
        gap: 8px;
        align-items: center;
        padding: 10px 16px;
        background: var(--bg-secondary);
        border-bottom: 1px solid var(--border);
    }

    .search-input {
        flex: 1;
        background: var(--bg-primary);
        border: 1px solid var(--border);
        border-radius: 6px;
        padding: 6px 10px;
        color: var(--text-primary);
        font-size: 12px;
        font-family: 'JetBrains Mono', monospace;
        outline: none;
    }

    .search-input:focus { border-color: var(--accent); }

    .search-count {
        font-size: 11px;
        color: var(--text-muted);
        white-space: nowrap;
    }

    .error-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 300px;
        color: var(--text-muted);
        gap: 10px;
    }

    .error-state i { font-size: 40px; color: var(--danger); }
    .error-state pre {
        font-size: 12px;
        color: var(--danger);
        background: var(--danger-bg);
        padding: 10px 14px;
        border-radius: 6px;
        border: 1px solid var(--danger);
        max-width: 500px;
        word-break: break-all;
        white-space: pre-wrap;
    }
</style>
@endpush

@section('content')
@if(auth()->user()->role === 'admin')
<div class="log-meta-bar">
    <div class="log-meta-item">
        <i class="fas fa-server"></i>
        <span>{{ $logApp->server->name }}</span>
    </div>
    <div class="log-meta-item">
        <i class="fas fa-network-wired"></i>
        <code>{{ $logApp->server->ip_address }}:{{ $logApp->server->ssh_port }}</code>
    </div>
    <div class="log-meta-item">
        <i class="fas fa-folder"></i>
        <code>{{ $logApp->log_path }}</code>
    </div>
    @if($logApp->description)
    <div class="log-meta-item">
        <i class="fas fa-info-circle"></i>
        <span>{{ $logApp->description }}</span>
    </div>
    @endif
</div>
@endif

<div class="log-controls">
    <div class="log-controls-left">
        <button class="btn btn-primary" id="btn-reload" onclick="loadLogs()">
            <i class="fas fa-sync-alt" id="reload-icon"></i> Reload
        </button>
        <button class="btn btn-secondary" onclick="scrollToBottom()">
            <i class="fas fa-arrow-down"></i> Cuối
        </button>
        <button class="btn btn-secondary" onclick="copyLogs()">
            <i class="fas fa-copy"></i> Copy
        </button>

        <select id="lines-select" class="btn btn-secondary" style="cursor:pointer;" onchange="loadLogs()">
            <option value="200">200 dòng</option>
            <option value="500">500 dòng</option>
            <option value="1000" selected>1000 dòng</option>
            <option value="2000">2000 dòng</option>
            <option value="5000">5000 dòng</option>
        </select>
    </div>
    <div class="log-controls-right">
        <div class="status-indicator">
            <div class="status-dot" id="status-dot"></div>
            <span id="status-text">Sẵn sàng</span>
        </div>
        <span id="fetched-at" style="font-size:11px; color: var(--text-muted);"></span>
    </div>
</div>

<div class="log-wrapper">
    <div class="search-bar">
        <i class="fas fa-search" style="color: var(--text-muted); font-size:12px;"></i>
        <input type="text" id="search-input" class="search-input" placeholder="Tìm kiếm trong log... (Enter)" onkeyup="searchLog(event)">
        <span class="search-count" id="search-count"></span>
        <button class="btn btn-sm btn-secondary" onclick="clearSearch()" title="Xóa tìm kiếm">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <div class="log-header">
        <i class="fas fa-file-code" style="color: var(--accent); font-size:12px;"></i>
        @if(auth()->user()->role === 'admin')
        <span class="log-header-title">{{ $logApp->log_path }}</span>
        @else
        <span class="log-header-title">{{ $logApp->name }}</span>
        @endif
        <span class="log-stats" id="log-stats">Đang tải...</span>
    </div>

    <div id="log-container">
        <div class="error-state" id="empty-state" style="display:none;">
            <i class="fas fa-exclamation-triangle"></i>
            <span id="empty-message">Không có nội dung</span>
            <pre id="error-detail" style="display:none;"></pre>
        </div>
        <div id="log-content"></div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const FETCH_URL = "{{ route('logs.fetch', $logApp) }}";
let autoRefreshInterval = null;
let isLoading = false;
let rawLines = [];
let autoRefreshSeconds = 10;

// Status
function setStatus(state, text) {
    const dot = document.getElementById('status-dot');
    dot.className = 'status-dot ' + state;
    document.getElementById('status-text').textContent = text;
}

// Load logs via AJAX
async function loadLogs() {
    if (isLoading) return;
    isLoading = true;

    const lines = document.getElementById('lines-select').value;
    const icon = document.getElementById('reload-icon');
    icon.className = 'fas fa-spinner fa-spin';
    document.getElementById('btn-reload').disabled = true;

    setStatus('loading', 'Đang tải...');
    document.getElementById('log-stats').textContent = 'Đang tải...';

    try {
        const res = await fetch(`${FETCH_URL}?lines=${lines}&_=${Date.now()}`);
        const data = await res.json();

        if (data.success) {
            rawLines = data.lines;
            renderLogs(rawLines);
            document.getElementById('log-stats').textContent = `${data.count} dòng`;
            document.getElementById('fetched-at').textContent = 'Cập nhật: ' + data.fetched_at;
            setStatus('success', 'Đã tải xong');

            // re-apply search if any
            const q = document.getElementById('search-input').value.trim();
            if (q) { applySearch(q); }

            scrollToBottom();
        } else {
            showError(data.error || 'Không thể đọc log');
            setStatus('error', 'Lỗi');
        }
    } catch (e) {
        showError('Lỗi kết nối: ' + e.message);
        setStatus('error', 'Lỗi kết nối');
    } finally {
        isLoading = false;
        icon.className = 'fas fa-sync-alt';
        document.getElementById('btn-reload').disabled = false;
    }
}

function getLogLevel(line) {
    const l = line.toLowerCase();
    if (l.includes('error') || l.includes('exception') || l.includes('fatal') || l.includes('critical')) return 'level-error';
    if (l.includes('warn') || l.includes('warning')) return 'level-warn';
    if (l.includes('info') || l.includes('[info]')) return 'level-info';
    if (l.includes('debug')) return 'level-debug';
    return '';
}

function renderLogs(lines) {
    const container = document.getElementById('log-content');
    const emptyState = document.getElementById('empty-state');

    if (!lines || lines.length === 0) {
        container.innerHTML = '';
        emptyState.style.display = 'flex';
        document.getElementById('empty-message').textContent = 'File log trống hoặc không có nội dung.';
        return;
    }

    emptyState.style.display = 'none';

    const fragment = document.createDocumentFragment();
    lines.forEach((line, i) => {
        const el = document.createElement('div');
        el.className = 'log-line ' + getLogLevel(line);
        el.innerHTML = `<span class="log-line-number">${i + 1}</span><span class="log-line-text">${escapeHtml(line)}</span>`;
        fragment.appendChild(el);
    });

    container.innerHTML = '';
    container.appendChild(fragment);
}

function showError(message) {
    rawLines = [];
    document.getElementById('log-content').innerHTML = '';
    const emptyState = document.getElementById('empty-state');
    emptyState.style.display = 'flex';
    document.getElementById('empty-message').textContent = 'Không thể đọc log';
    const detail = document.getElementById('error-detail');
    detail.style.display = 'block';
    detail.textContent = message;
    document.getElementById('log-stats').textContent = 'Lỗi';
}

function escapeHtml(str) {
    return str
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function scrollToBottom() {
    const c = document.getElementById('log-container');
    c.scrollTop = c.scrollHeight;
}

function copyLogs() {
    if (!rawLines.length) return;
    navigator.clipboard.writeText(rawLines.join('\n')).then(() => {
        const btn = event.currentTarget;
        btn.innerHTML = '<i class="fas fa-check"></i> Đã copy';
        setTimeout(() => btn.innerHTML = '<i class="fas fa-copy"></i> Copy', 2000);
    });
}

// Auto refresh
let arEnabled = false;
function toggleAutoRefresh() {
    arEnabled = !arEnabled;
    const icon = document.getElementById('ar-icon');
    const text = document.getElementById('ar-text');
    const badge = document.getElementById('auto-refresh-status');

    if (arEnabled) {
        badge.className = 'badge badge-success';
        icon.className = 'fas fa-sync fa-spin';
        text.textContent = `Auto: ${autoRefreshSeconds}s`;
        autoRefreshInterval = setInterval(loadLogs, autoRefreshSeconds * 1000);
    } else {
        badge.className = 'badge badge-info';
        icon.className = 'fas fa-sync';
        text.textContent = 'Auto-refresh: OFF';
        clearInterval(autoRefreshInterval);
    }
}

// Search
function searchLog(e) {
    if (e.key !== 'Enter') return;
    const q = document.getElementById('search-input').value.trim();
    if (!q) { clearSearch(); return; }
    applySearch(q);
}

function applySearch(q) {
    const lines = document.querySelectorAll('.log-line-text');
    let count = 0;
    let firstMatch = null;

    lines.forEach(el => {
        const text = el.textContent;
        if (text.toLowerCase().includes(q.toLowerCase())) {
            const escaped = escapeHtml(q);
            el.innerHTML = escapeHtml(text).replace(new RegExp(escaped.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'gi'), m => `<span class="highlight">${m}</span>`);
            count++;
            if (!firstMatch) firstMatch = el;
        }
    });

    document.getElementById('search-count').textContent = count > 0 ? `${count} kết quả` : 'Không tìm thấy';

    if (firstMatch) {
        firstMatch.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}

function clearSearch() {
    document.getElementById('search-input').value = '';
    document.getElementById('search-count').textContent = '';
    if (rawLines.length) renderLogs(rawLines);
}

// Init
loadLogs();
</script>
@endpush
