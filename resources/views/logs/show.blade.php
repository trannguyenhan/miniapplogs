@extends('layouts.app')

@section('title', 'Log: ' . $logApp->name)

@section('breadcrumb')
    <a href="{{ route('logs.index') }}" style="color: var(--text-secondary); text-decoration:none;">{{ __('app.page_dashboard') }}</a>
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
            <i class="fas fa-sync-alt" id="reload-icon"></i> {{ __('app.btn_reload') }}
        </button>
        <button class="btn btn-secondary" onclick="scrollToBottom()">
            <i class="fas fa-arrow-down"></i> {{ __('app.btn_bottom') }}
        </button>
        <button class="btn btn-secondary" onclick="copyLogs()">
            <i class="fas fa-copy"></i> {{ __('app.btn_copy') }}
        </button>

        <select id="lines-select" class="btn btn-secondary" style="cursor:pointer;" onchange="loadLogs()">
            <option value="200">200 {{ __('app.lines') }}</option>
            <option value="500">500 {{ __('app.lines') }}</option>
            <option value="1000" selected>1000 {{ __('app.lines') }}</option>
            <option value="2000">2000 {{ __('app.lines') }}</option>
            <option value="5000">5000 {{ __('app.lines') }}</option>
        </select>

        @if($logApp->git_branch && $logApp->canGitPull(auth()->user()))
        <button class="btn btn-info" id="btn-git-pull" onclick="showGitPullModal()">
            <i class="fab fa-git-alt" id="git-pull-icon"></i> Git Pull
        </button>
        @endif

        @if($logApp->script_path && $logApp->canRunScript(auth()->user()))
        <button class="btn btn-warning" id="btn-execute" onclick="executeScript()">
            <i class="fas fa-terminal" id="exec-icon"></i> Run Script
        </button>
        @endif

        @if($logApp->canRestart(auth()->user()))
        <button class="btn btn-danger" id="btn-restart" onclick="executeRestart()">
            <i class="fas fa-redo" id="restart-icon"></i> Restart
        </button>
        @endif

        @if(!empty($logApp->custom_buttons))
            @foreach($logApp->custom_buttons as $idx => $btn)
                @if($logApp->canRunCustomButton(auth()->user(), $btn))
                <button class="btn btn-secondary btn-custom-action" data-btn-index="{{ $idx }}" onclick="executeCustomButton(Number(this.dataset.btnIndex), this)">
                    <i class="fas fa-play"></i> {{ $btn['label'] ?? 'Button '.($idx+1) }}
                </button>
                @endif
            @endforeach
        @endif
    </div>
    <div class="log-controls-right">
        <div class="status-indicator">
            <div class="status-dot" id="status-dot"></div>
            <span id="status-text">{{ __('app.ready') }}</span>
        </div>
        <span id="fetched-at" style="font-size:11px; color: var(--text-muted);"></span>
    </div>
</div>

<div class="log-wrapper">
    <div class="search-bar">
        <i class="fas fa-search" style="color: var(--text-muted); font-size:12px;"></i>
        <input type="text" id="search-input" class="search-input" placeholder="{{ __('app.search_placeholder') }} (Enter)" onkeyup="searchLog(event)">
        <span class="search-count" id="search-count"></span>
        <button class="btn btn-sm btn-secondary" onclick="clearSearch()" title="{{ __('app.clear_search') }}">
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
        <span class="log-stats" id="log-stats">{{ __('app.loading') }}</span>
    </div>

    <div id="log-container">
        <div class="error-state" id="empty-state" style="display:none;">
            <i class="fas fa-exclamation-triangle"></i>
            <span id="empty-message">{{ __('app.no_data') }}</span>
            <pre id="error-detail" style="display:none;"></pre>
        </div>
        <div id="log-content"></div>
    </div>
</div>

<!-- Git Pull Modal -->
<div id="git-pull-modal" class="custom-modal" style="display:none;">
    <div class="custom-modal-overlay" onclick="closeGitPullModal()"></div>
    <div class="custom-modal-content" style="max-width:450px;">
        <div class="custom-modal-header">
            <div class="custom-modal-icon" style="color:var(--info);">
                <i class="fab fa-git-alt"></i>
            </div>
            <h3 class="custom-modal-title">Git Pull</h3>
            <button type="button" class="custom-modal-close" onclick="closeGitPullModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="custom-modal-body">
            <p style="margin-bottom:16px;">
                {{ __('app.git_pull_from_branch') }} <strong>{{ $logApp->git_branch }}</strong>
            </p>
            <div style="padding:12px; background:var(--bg-secondary); border-radius:6px; margin-bottom:16px;">
                <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                    <input type="checkbox" id="git-no-rebase" style="cursor:pointer;">
                    <span>{{ __('app.git_no_rebase') }} (--no-rebase)</span>
                </label>
                <div style="font-size:12px; color:var(--text-muted); margin-top:6px; margin-left:24px;">
                    {{ __('app.git_no_rebase_hint') }}
                </div>
            </div>
        </div>
        <div class="custom-modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeGitPullModal()">{{ __('app.btn_cancel') }}</button>
            <button type="button" class="btn btn-primary" id="btn-confirm-git-pull" onclick="executeGitPull()">
                <i class="fab fa-git-alt"></i> {{ __('app.btn_git_pull') }}
            </button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script id="log-i18n" type="application/json">@json([
    'loading' => __('app.loading'),
    'error' => __('app.error'),
    'linesCount' => __('app.lines_count', ['count' => ':count']),
    'fetchedAt' => __('app.fetched_at'),
    'loaded' => __('app.loaded_done'),
    'cannotReadLog' => __('app.cannot_read_log'),
    'connectionError' => __('app.connection_error'),
    'emptyLog' => __('app.log_empty'),
    'readError' => __('app.log_error'),
    'copyDone' => __('app.copied'),
    'copy' => __('app.btn_copy'),
    'autoRefreshOff' => __('app.auto_refresh_off'),
    'searchResults' => __('app.search_results', ['count' => ':count']),
    'notFound' => __('app.not_found'),
    'scriptConfirm' => __('app.confirm_run_script'),
    'scriptConfirmTitle' => __('app.confirm_script_title'),
    'executingScript' => __('app.executing_script'),
    'scriptDone' => __('app.script_done'),
    'scriptSuccess' => __('app.script_success'),
    'noOutput' => __('app.no_output'),
    'scriptError' => __('app.script_error'),
    'restartConfirm' => __('app.restart_confirm'),
    'restartConfirmTitle' => __('app.restart_confirm_title'),
    'executingRestart' => __('app.executing_restart'),
    'restartDone' => __('app.restart_done'),
    'restartSuccess' => __('app.restart_success'),
    'restartError' => __('app.restart_error'),
    'customConfirm' => __('app.custom_confirm'),
    'customConfirmTitle' => __('app.custom_confirm_title'),
    'running' => __('app.running'),
    'runningAction' => __('app.running_action', ['label' => ':label']),
    'actionDone' => __('app.action_done', ['label' => ':label']),
    'actionError' => __('app.action_error', ['label' => ':label']),
    'actionSuccessTitle' => __('app.action_success_title', ['label' => ':label']),
    'pullingCode' => __('app.pulling_code'),
    'pullSuccess' => __('app.pull_success'),
    'pullError' => __('app.pull_error'),
])</script>
<script>
const FETCH_URL = "{{ route('logs.fetch', $logApp) }}";
const EXECUTE_URL = "{{ route('logs.execute', $logApp) }}";
const GIT_PULL_URL = "{{ route('logs.git-pull', $logApp) }}";
const RESTART_URL = "{{ route('logs.restart', $logApp) }}";
const BUTTON_BASE_URL = "{{ rtrim(route('logs.button', [$logApp, 0]), '0') }}";
let autoRefreshInterval = null;
let isLoading = false;
let rawLines = [];
let autoRefreshSeconds = 10;
const i18n = JSON.parse(document.getElementById('log-i18n').textContent);

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

    setStatus('loading', i18n.loading);
    document.getElementById('log-stats').textContent = i18n.loading;

    try {
        const res = await fetch(`${FETCH_URL}?lines=${lines}&_=${Date.now()}`);
        const data = await res.json();

        if (data.success) {
            rawLines = data.lines;
            renderLogs(rawLines);
            document.getElementById('log-stats').textContent = i18n.linesCount.replace(':count', data.count);
            document.getElementById('fetched-at').textContent = i18n.fetchedAt + ': ' + data.fetched_at;
            setStatus('success', i18n.loaded);

            // re-apply search if any
            const q = document.getElementById('search-input').value.trim();
            if (q) { applySearch(q); }

            scrollToBottom();
        } else {
            showError(data.error || i18n.cannotReadLog);
            setStatus('error', i18n.error || 'Error');
        }
    } catch (e) {
        showError(i18n.connectionError + ': ' + e.message);
        setStatus('error', i18n.connectionError);
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
        document.getElementById('empty-message').textContent = i18n.emptyLog;
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
    document.getElementById('empty-message').textContent = i18n.readError;
    const detail = document.getElementById('error-detail');
    detail.style.display = 'block';
    detail.textContent = message;
    document.getElementById('log-stats').textContent = i18n.error || 'Error';
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
        btn.innerHTML = `<i class="fas fa-check"></i> ${i18n.copyDone}`;
        setTimeout(() => btn.innerHTML = `<i class="fas fa-copy"></i> ${i18n.copy}`, 2000);
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
        text.textContent = i18n.autoRefreshOff;
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

    document.getElementById('search-count').textContent = count > 0
        ? i18n.searchResults.replace(':count', count)
        : i18n.notFound;

    if (firstMatch) {
        firstMatch.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}

function clearSearch() {
    document.getElementById('search-input').value = '';
    document.getElementById('search-count').textContent = '';
    if (rawLines.length) renderLogs(rawLines);
}

async function executeScript() {
    const confirmed = await confirm(i18n.scriptConfirm, {
        title: i18n.scriptConfirmTitle
    });
    if (!confirmed) return;

    const btn = document.getElementById('btn-execute');
    const icon = document.getElementById('exec-icon');
    if (!btn) return;

    btn.disabled = true;
    icon.className = 'fas fa-spinner fa-spin';
    setStatus('loading', i18n.executingScript);

    try {
        const res = await fetch(EXECUTE_URL, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        });
        const data = await res.json();

        if (data.success) {
            setStatus('success', i18n.scriptDone);
            showOutput(i18n.scriptSuccess, data.output || i18n.noOutput, 'success');
            loadLogs(); // Reload logs after script
        } else {
            setStatus('error', i18n.scriptError);
            showError(data.error, i18n.scriptError);
        }
    } catch (e) {
        setStatus('error', i18n.connectionError);
        showError(i18n.connectionError + ': ' + e.message, i18n.connectionError);
    } finally {
        btn.disabled = false;
        icon.className = 'fas fa-terminal';
    }
}

async function executeRestart() {
    const confirmed = await confirm(i18n.restartConfirm, {
        title: i18n.restartConfirmTitle
    });
    if (!confirmed) return;

    const btn = document.getElementById('btn-restart');
    const icon = document.getElementById('restart-icon');
    if (!btn) return;

    btn.disabled = true;
    icon.className = 'fas fa-spinner fa-spin';
    setStatus('loading', i18n.executingRestart);

    try {
        const res = await fetch(RESTART_URL, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        });
        const data = await res.json();

        if (data.success) {
            setStatus('success', i18n.restartDone);
            showOutput(i18n.restartSuccess, data.output || i18n.noOutput, 'success');
            loadLogs();
        } else {
            setStatus('error', i18n.restartError);
            showError(data.error, i18n.restartError);
        }
    } catch (e) {
        setStatus('error', i18n.connectionError);
        showError(i18n.connectionError + ': ' + e.message, i18n.connectionError);
    } finally {
        btn.disabled = false;
        icon.className = 'fas fa-redo';
    }
}

async function executeCustomButton(index, btnEl) {
    const label = btnEl.textContent.trim();
    const confirmed = await confirm(i18n.customConfirm.replace(':label', label), {
        title: i18n.customConfirmTitle
    });
    if (!confirmed) return;

    const origHtml = btnEl.innerHTML;
    btnEl.disabled = true;
    btnEl.innerHTML = `<i class="fas fa-spinner fa-spin"></i> ${i18n.running}`;
    setStatus('loading', i18n.runningAction.replace(':label', label));

    try {
        const res = await fetch(BUTTON_BASE_URL + index, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        });
        const data = await res.json();

        if (data.success) {
            setStatus('success', i18n.actionDone.replace(':label', label));
            showOutput(i18n.actionSuccessTitle.replace(':label', label), data.output || i18n.noOutput, 'success');
            loadLogs();
        } else {
            setStatus('error', i18n.actionError.replace(':label', label));
            showError(data.error, i18n.actionError.replace(':label', label));
        }
    } catch (e) {
        setStatus('error', i18n.connectionError);
        showError(i18n.connectionError + ': ' + e.message, i18n.connectionError);
    } finally {
        btnEl.disabled = false;
        btnEl.innerHTML = origHtml;
    }
}

// Git Pull functions
function showGitPullModal() {
    const modal = document.getElementById('git-pull-modal');
    if (!modal) return;
    modal.style.display = 'flex';
    setTimeout(() => modal.classList.add('show'), 10);
}

function closeGitPullModal() {
    const modal = document.getElementById('git-pull-modal');
    if (!modal) return;
    modal.classList.remove('show');
    setTimeout(() => modal.style.display = 'none', 300);
}

async function executeGitPull() {
    const noRebase = document.getElementById('git-no-rebase')?.checked || false;
    const btn = document.getElementById('btn-confirm-git-pull');
    const gitPullBtn = document.getElementById('btn-git-pull');
    const gitPullIcon = document.getElementById('git-pull-icon');
    
    if (!btn || !gitPullBtn) return;
    
    btn.disabled = true;
    gitPullBtn.disabled = true;
    if (gitPullIcon) gitPullIcon.className = 'fab fa-git-alt fa-spin';
    setStatus('loading', i18n.pullingCode);
    closeGitPullModal();
    
    try {
        const res = await fetch(GIT_PULL_URL, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ no_rebase: noRebase })
        });
        const data = await res.json();
        
        if (data.success) {
            setStatus('success', i18n.pullSuccess);
            showOutput(i18n.pullSuccess, data.output || i18n.noOutput, 'success');
            loadLogs(); // Reload logs after git pull
        } else {
            setStatus('error', i18n.pullError);
            showError(data.error, i18n.pullError);
        }
    } catch (e) {
        setStatus('error', i18n.connectionError);
        showError(i18n.connectionError + ': ' + e.message, i18n.connectionError);
    } finally {
        btn.disabled = false;
        gitPullBtn.disabled = false;
        if (gitPullIcon) gitPullIcon.className = 'fab fa-git-alt';
    }
}

// Close git pull modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('git-pull-modal');
        if (modal && modal.style.display !== 'none') {
            closeGitPullModal();
        }
    }
});

// Init
loadLogs();
</script>
@endpush
