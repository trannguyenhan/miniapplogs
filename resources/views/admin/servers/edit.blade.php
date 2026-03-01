@extends('layouts.app')
@section('title', __('app.edit_server') . ': ' . $server->name)
@section('breadcrumb')
    <a href="{{ route('admin.servers.index') }}" style="color: var(--text-secondary); text-decoration:none;">{{ __('app.nav_servers') }}</a>
    <span style="color: var(--text-muted);">/</span>
    <span class="current">{{ $server->name }}</span>
@endsection
@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('app.edit_server') }}: {{ $server->name }}</h1>
        <p class="page-subtitle">{{ __('app.edit_server_subtitle') }}</p>
    </div>
</div>

<div style="max-width: 640px;">
    <form method="POST" action="{{ route('admin.servers.update', $server) }}">
        @csrf @method('PUT')
        <div class="card">
            <div class="card-body">

                {{-- Name --}}
                <div class="form-group">
                    <label class="form-label" for="name">{{ __('app.server_name') }} <span class="required">*</span></label>
                    <input type="text" id="name" name="name" class="form-control"
                           value="{{ old('name', $server->name) }}" required>
                    @error('name') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                {{-- Connection Type --}}
                <div class="form-group">
                    <label class="form-label">Connection Type <span class="required">*</span></label>
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">

                        <label id="tab-local" class="conn-tab {{ old('connection_type', $server->connection_type) === 'local' ? 'active' : '' }}" onclick="setConnType('local')">
                            <input type="radio" name="connection_type" value="local" style="display:none;"
                                   {{ old('connection_type', $server->connection_type) === 'local' ? 'checked' : '' }}>
                            <i class="fas fa-hdd" style="font-size:20px; margin-bottom:6px; color:var(--success);"></i>
                            <div style="font-weight:600;font-size:13px;">Local</div>
                            <div style="font-size:11px;color:var(--text-muted);margin-top:2px;">Đọc file trực tiếp trên server này</div>
                        </label>

                        <label id="tab-agent" class="conn-tab {{ old('connection_type', $server->connection_type) === 'agent' ? 'active' : '' }}" onclick="setConnType('agent')">
                            <input type="radio" name="connection_type" value="agent" style="display:none;"
                                   {{ old('connection_type', $server->connection_type) === 'agent' ? 'checked' : '' }}>
                            <i class="fas fa-plug" style="font-size:20px; margin-bottom:6px; color:var(--purple);"></i>
                            <div style="font-weight:600;font-size:13px;">HTTP Agent</div>
                            <div style="font-size:11px;color:var(--text-muted);margin-top:2px;">Agent nhỏ chạy trên remote server</div>
                        </label>

                    </div>
                </div>

                {{-- Agent fields --}}
                <div id="agent-fields" style="{{ old('connection_type', $server->connection_type) === 'agent' ? '' : 'display:none;' }}">

                    <div class="form-group">
                        <label class="form-label" for="agent_url">Agent URL <span class="required">*</span></label>
                        <div style="display:flex; gap:8px;">
                            <input type="url" id="agent_url" name="agent_url" class="form-control"
                                   value="{{ old('agent_url', $server->agent_url) }}"
                                   placeholder="http://192.168.1.100:9876">
                            <button type="button" id="btn-test-agent" class="btn btn-secondary" onclick="testAgent()" style="white-space:nowrap;">
                                <i class="fas fa-wifi"></i> Test
                            </button>
                        </div>
                        <div class="form-hint">URL của agent trên remote server</div>
                        @error('agent_url') <div class="form-error">{{ $message }}</div> @enderror
                        <div id="agent-test-result" style="display:none; margin-top:8px; font-size:12px; padding:8px 12px; border-radius:6px;"></div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="agent_token">Agent Token</label>
                        <input type="text" id="agent_token" name="agent_token" class="form-control"
                               value="{{ old('agent_token') }}"
                               placeholder="{{ $server->agent_token ? '••••••• (đã lưu — để trống để giữ nguyên)' : 'Bearer token xác thực' }}"
                               style="font-family:'JetBrains Mono',monospace; font-size:12px;">
                        @if($server->agent_token)
                            <div class="form-hint" style="color:var(--success);">
                                <i class="fas fa-check-circle"></i> Token đã lưu. Để trống nếu không muốn thay đổi.
                            </div>
                        @endif
                        @error('agent_token') <div class="form-error">{{ $message }}</div> @enderror
                    </div>

                </div>

                {{-- Description --}}
                <div class="form-group" style="margin-top:16px;">
                    <label class="form-label" for="description">
                        {{ __('app.description') }}
                        <span style="color:var(--text-muted); font-weight:400;">({{ __('app.optional') }})</span>
                    </label>
                    <textarea id="description" name="description" class="form-control" rows="2">{{ old('description', $server->description) }}</textarea>
                </div>

                {{-- Active --}}
                <div class="toggle-wrapper">
                    <label class="toggle">
                        <input type="checkbox" name="is_active" value="1"
                               {{ old('is_active', $server->is_active) ? 'checked' : '' }}>
                        <span class="toggle-slider"></span>
                    </label>
                    <span style="font-size:13px; color: var(--text-secondary);">{{ __('app.activate_server') }}</span>
                </div>

                <div style="display:flex; gap:10px; margin-top:24px; padding-top:20px; border-top:1px solid var(--border);">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> {{ __('app.btn_update') }}
                    </button>
                    <a href="{{ route('admin.servers.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times"></i> {{ __('app.btn_cancel') }}
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('styles')
<style>
.conn-tab {
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    padding: 16px 12px; border: 1px solid var(--border); border-radius: 8px;
    cursor: pointer; text-align: center; transition: all 0.15s;
    background: var(--bg-primary); user-select: none;
}
.conn-tab:hover { border-color: var(--text-muted); background: var(--bg-hover); }
.conn-tab.active { border-color: var(--accent); background: var(--accent-dark); }
</style>
@endpush

@push('scripts')
<script>
function setConnType(type) {
    ['local','agent'].forEach(t => {
        document.getElementById('tab-' + t).classList.toggle('active', t === type);
        document.querySelector(`input[name="connection_type"][value="${t}"]`).checked = (t === type);
    });
    document.getElementById('agent-fields').style.display = type === 'agent' ? 'block' : 'none';
}

async function testAgent() {
    const url   = document.getElementById('agent_url').value.trim();
    const token = document.getElementById('agent_token').value.trim();
    const btn   = document.getElementById('btn-test-agent');
    const res   = document.getElementById('agent-test-result');

    if (!url) { alert('Nhập Agent URL trước.'); return; }
    btn.disabled  = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang test...';
    res.style.display = 'none';

    try {
        const resp = await fetch('{{ route('admin.servers.test-agent') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ agent_url: url, agent_token: token }),
        });
        const data = await resp.json();
        res.style.display = 'block';
        if (data.success) {
            res.style.cssText = 'display:block;background:var(--success-bg);color:var(--success);border:1px solid var(--success);padding:8px 12px;border-radius:6px;font-size:12px;margin-top:8px;';
            res.innerHTML = `<i class="fas fa-check-circle"></i> Agent online! Version: ${data.data?.version ?? 'unknown'}`;
        } else {
            res.style.cssText = 'display:block;background:var(--danger-bg);color:var(--danger);border:1px solid var(--danger);padding:8px 12px;border-radius:6px;font-size:12px;margin-top:8px;';
            res.innerHTML = `<i class="fas fa-times-circle"></i> Lỗi: ${data.error}`;
        }
    } catch(e) {
        res.style.cssText = 'display:block;background:var(--danger-bg);color:var(--danger);border:1px solid var(--danger);padding:8px 12px;border-radius:6px;font-size:12px;margin-top:8px;';
        res.innerHTML = `<i class="fas fa-times-circle"></i> ${e.message}`;
    } finally {
        btn.disabled  = false;
        btn.innerHTML = '<i class="fas fa-wifi"></i> Test';
    }
}
</script>
@endpush
