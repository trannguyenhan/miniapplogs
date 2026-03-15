@extends('layouts.app')
@section('title', 'Thêm Log Application')
@section('breadcrumb')
    <a href="{{ route('admin.log-apps.index') }}" style="color: var(--text-secondary); text-decoration:none;">Log Apps</a>
    <span style="color: var(--text-muted);">/</span>
    <span class="current">Thêm mới</span>
@endsection
@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Thêm Log Application</h1>
        <p class="page-subtitle">Khai báo tên ứng dụng và đường dẫn file log trên server</p>
    </div>
</div>

<div style="max-width: 620px;">
    <form method="POST" action="{{ route('admin.log-apps.store') }}">
        @csrf
        <div class="card">
            <div class="card-body">

                {{-- Server --}}
                <div class="form-group">
                    <label class="form-label" for="server_id">Server <span class="required">*</span></label>
                    <select id="server_id" name="server_id" class="form-control" required onchange="onServerChange(this)">
                        <option value="">-- Chọn server --</option>
                        @foreach($servers as $server)
                            <option value="{{ $server->id }}"
                                    data-type="{{ $server->connection_type }}"
                                    data-browse="{{ $server->connection_type === 'agent' ? route('admin.servers.browse-agent', $server) : '' }}"
                                    {{ old('server_id') == $server->id ? 'selected' : '' }}>
                                {{ $server->name }}
                                @if($server->connection_type === 'agent')
                                    [Agent]
                                @else
                                    [Local]
                                @endif
                            </option>
                        @endforeach
                    </select>
                    @error('server_id') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                {{-- App name --}}
                <div class="form-group">
                    <label class="form-label" for="name">Tên ứng dụng <span class="required">*</span></label>
                    <input type="text" id="name" name="name" class="form-control"
                           value="{{ old('name') }}" placeholder="VD: API Gateway, Order Service..." required>
                    @error('name') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                {{-- Log Type --}}
                <div class="form-group">
                    <label class="form-label" for="log_type">Loại Log <span class="required">*</span></label>
                    <select id="log_type" name="log_type" class="form-control" required onchange="togglePathHint(); suggestAppName();">
                        <option value="file" {{ old('log_type') == 'file' ? 'selected' : '' }}>File cố định</option>
                        <option value="pattern" {{ old('log_type') == 'pattern' ? 'selected' : '' }}>Theo ngày (Pattern)</option>
                        <option value="docker" {{ old('log_type') == 'docker' ? 'selected' : '' }}>Docker Container</option>
                        <option value="journalctl" {{ old('log_type') == 'journalctl' ? 'selected' : '' }}>Systemd Journal (journalctl)</option>
                    </select>
                    @error('log_type') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                {{-- Log path + Browse button --}}
                <div class="form-group">
                    <label class="form-label" for="log_path">
                        Đường dẫn Log <span class="required">*</span>
                    </label>
                    <div style="display:flex; gap:8px;">
                        <input type="text" id="log_path" name="log_path" class="form-control"
                               value="{{ old('log_path') }}"
                               placeholder="VD: /var/log/nginx/access.log hoặc cms.ggcamp.org.service"
                               style="font-family:'JetBrains Mono',monospace; font-size:13px;"
                               oninput="suggestAppName()"
                               required>
                        <button type="button" id="btn-browse" class="btn btn-secondary"
                                onclick="openBrowser()" style="display:none; white-space:nowrap;">
                            <i class="fas fa-folder-open"></i> Browse
                        </button>
                    </div>
                    <div class="form-hint" id="path-hint">Đường dẫn đến file log trên server</div>
                    <div class="form-hint" id="pattern-hint" style="display:none; color: var(--accent);">
                        Hỗ trợ pattern ngày: <code>{Y-m-d}</code>, <code>{dmY}</code>, <code>{Ymd}</code>... <br>
                        VD: <code>/var/log/app-{Y-m-d}.log</code> -> <code>/var/log/app-{{ date('Y-m-d') }}.log</code>
                    </div>
                    <div class="form-hint" id="docker-hint" style="display:none; color: var(--warning);">
                        Nhập tên hoặc ID của Docker container. VD: <code>nginx-proxy</code>, <code>mysql-db</code>
                    </div>
                    <div class="form-hint" id="journalctl-hint" style="display:none; color: var(--info);">
                        Nhập tên systemd unit (service). VD: <code>cms.ggcamp.org.service</code>, <code>nginx.service</code>, <code>mysql.service</code><br>
                        Hệ thống sẽ chạy: <code>journalctl -u {unit} -n {lines}</code>
                    </div>
                    @error('log_path') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                {{-- Script path --}}
                <div class="form-group">
                    <label class="form-label" for="script_path">Script thực thi (VD: Pull code & Restart)</label>
                    <input type="text" id="script_path" name="script_path" class="form-control"
                           value="{{ old('script_path') }}" placeholder="VD: /var/www/deploy.sh"
                           style="font-family:'JetBrains Mono',monospace; font-size:13px;">
                    <div class="form-hint">Đường dẫn đến file .sh trên server. Để trống nếu không dùng.</div>
                    @error('script_path') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                {{-- Allowed Roles --}}
                <div class="form-group">
                    <label class="form-label">Quyền thực thi script</label>
                    <div style="display:flex; gap:20px; margin-top:8px;">
                        <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                            <input type="checkbox" name="roles[]" value="admin" checked disabled>
                            <span>Admin</span>
                        </label>
                        <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                            <input type="checkbox" name="roles[]" value="user" {{ is_array(old('roles')) && in_array('user', old('roles')) ? 'checked' : (old('roles') === null ? 'checked' : '') }}>
                            <span>User</span>
                        </label>
                    </div>
                    <input type="hidden" name="allowed_roles" id="allowed_roles" value="admin,user">
                    <div class="form-hint">Admin luôn có quyền. Chọn User nếu muốn cho phép người dùng thường chạy script.</div>
                </div>

                {{-- Description --}}
                <div class="form-group">
                    <label class="form-label" for="description">Mô tả</label>
                    <textarea id="description" name="description" class="form-control" rows="2"
                              placeholder="Mô tả về ứng dụng hoặc file log này...">{{ old('description') }}</textarea>
                </div>

                {{-- File Browser Panel (hiện khi server là Agent) --}}
                <div id="browser-panel" style="display:none; margin-bottom:16px;">
                    <div style="border:1px solid var(--border); border-radius:8px; overflow:hidden; background:var(--bg-primary);">
                        {{-- Browser header --}}
                        <div style="background:var(--bg-secondary); padding:10px 14px; display:flex; align-items:center; gap:8px; border-bottom:1px solid var(--border);">
                            <i class="fas fa-folder" style="color:var(--warning);"></i>
                            <code id="browser-current-path" style="font-size:12px; color:var(--text-secondary); flex:1; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">/var/log</code>
                            <button type="button" onclick="browserNav('..')" class="btn btn-sm btn-secondary" style="padding:3px 8px;">
                                <i class="fas fa-arrow-up"></i> Up
                            </button>
                        </div>
                        {{-- path input trong browser --}}
                        <div style="padding:8px 12px; border-bottom:1px solid var(--border); display:flex; gap:6px;">
                            <input type="text" id="browser-path-input" class="form-control"
                                   style="font-family:'JetBrains Mono',monospace; font-size:12px; padding:5px 10px;"
                                   placeholder="/var/log"
                                   onkeydown="if(event.key==='Enter'){event.preventDefault();browserNav(document.getElementById('browser-path-input').value);}">
                            <button type="button" class="btn btn-sm btn-secondary" onclick="browserNav(document.getElementById('browser-path-input').value)" style="white-space:nowrap;">
                                <i class="fas fa-chevron-right"></i> Go
                            </button>
                        </div>
                        {{-- file list --}}
                        <div id="browser-list" style="max-height:280px; overflow-y:auto; padding:6px 0;">
                            <div style="text-align:center; padding:20px; color:var(--text-muted); font-size:13px;">
                                <i class="fas fa-spinner fa-spin"></i> Đang tải...
                            </div>
                        </div>
                    </div>
                </div>

                <div class="toggle-wrapper">
                    <label class="toggle">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', 1) ? 'checked' : '' }}>
                        <span class="toggle-slider"></span>
                    </label>
                    <span class="form-label" style="margin:0;">Kích hoạt</span>
                </div>

                <div style="display:flex; gap:10px; margin-top:24px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Lưu ứng dụng
                    </button>
                    <a href="{{ route('admin.log-apps.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Hủy
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('styles')
<style>
.browser-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 7px 14px;
    cursor: pointer;
    font-size: 13px;
    color: var(--text-secondary);
    transition: background 0.1s;
    border-bottom: 1px solid var(--border-light);
    text-decoration: none;
}
.browser-item:last-child { border-bottom: none; }
.browser-item:hover { background: var(--bg-hover); color: var(--text-primary); }
.browser-item.is-file:hover { background: var(--accent-dark); color: var(--accent); }
.browser-item .item-size {
    margin-left: auto;
    font-size: 11px;
    color: var(--text-muted);
    font-family: 'JetBrains Mono', monospace;
}
.browser-item .no-access { opacity: 0.4; cursor: not-allowed; }
</style>
@endpush

@push('scripts')
<script>
let _browseUrl = '';
let _currentPath = '/var/log';

function onServerChange(sel) {
    const opt     = sel.options[sel.selectedIndex];
    const type    = opt.dataset.type;
    const browseUrl = opt.dataset.browse;

    const btnBrowse   = document.getElementById('btn-browse');
    const browserPanel = document.getElementById('browser-panel');
    const pathHint    = document.getElementById('path-hint');

    if (type === 'agent' && browseUrl) {
        _browseUrl = browseUrl;
        btnBrowse.style.display = '';
        pathHint.innerHTML = 'Đường dẫn trên remote server. Dùng nút <strong>Browse</strong> để duyệt.';
    } else {
        _browseUrl = '';
        btnBrowse.style.display = 'none';
        browserPanel.style.display = 'none';
        pathHint.innerHTML = 'Đường dẫn tuyệt đối đến file log trên server này';
    }
}

function openBrowser() {
    const panel = document.getElementById('browser-panel');
    if (panel.style.display === 'none') {
        panel.style.display = 'block';
        const cur = document.getElementById('log_path').value || '/var/log';
        // nếu path là file, navigate tới thư mục cha
        const browseTo = cur.endsWith('/') || !cur.includes('.') ? cur : cur.substring(0, cur.lastIndexOf('/')) || '/var/log';
        browserNav(browseTo);
    } else {
        panel.style.display = 'none';
    }
}

async function browserNav(path) {
    if (!_browseUrl) return;
    _currentPath = path.trim() || '/var/log';

    document.getElementById('browser-current-path').textContent = _currentPath;
    document.getElementById('browser-path-input').value = _currentPath;
    document.getElementById('browser-list').innerHTML =
        '<div style="text-align:center;padding:20px;color:var(--text-muted);font-size:13px;"><i class="fas fa-spinner fa-spin"></i> Đang tải...</div>';

    try {
        const resp = await fetch(_browseUrl + '?path=' + encodeURIComponent(_currentPath), {
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
        });
        const data = await resp.json();

        if (!data.success) {
            document.getElementById('browser-list').innerHTML =
                `<div style="padding:16px;color:var(--danger);font-size:12px;"><i class="fas fa-exclamation-circle"></i> ${data.error}</div>`;
            return;
        }

        renderBrowserList(data.entries || [], data.path);
    } catch(e) {
        document.getElementById('browser-list').innerHTML =
            `<div style="padding:16px;color:var(--danger);font-size:12px;"><i class="fas fa-exclamation-circle"></i> ${e.message}</div>`;
    }
}

function renderBrowserList(entries, basePath) {
    const list = document.getElementById('browser-list');
    if (!entries.length) {
        list.innerHTML = '<div style="padding:16px;color:var(--text-muted);text-align:center;font-size:12px;">Thư mục trống</div>';
        return;
    }

    list.innerHTML = entries.map(e => {
        const icon    = e.is_dir ? 'fa-folder' : 'fa-file-alt';
        const color   = e.is_dir ? 'var(--warning)' : 'var(--text-muted)';
        const size    = e.size_human ? `<span class="item-size">${e.size_human}</span>` : '';
        const noAccess = !e.readable ? 'no-access' : '';

        if (e.is_dir) {
            return `<div class="browser-item ${noAccess}" onclick="${e.readable ? `browserNav('${e.path.replace(/'/g, "\\'")}')` : ''}">
                <i class="fas ${icon}" style="color:${color}; width:16px;"></i>
                <span>${e.name}/</span>
                ${size}
            </div>`;
        } else {
            return `<div class="browser-item is-file ${noAccess}" onclick="${e.readable ? `selectPath('${e.path.replace(/'/g, "\\'")}')` : ''}">
                <i class="fas ${icon}" style="color:${color}; width:16px;"></i>
                <span>${e.name}</span>
                ${size}
            </div>`;
        }
    }).join('');
}

function selectPath(path) {
    document.getElementById('log_path').value = path;
    document.getElementById('browser-panel').style.display = 'none';
    // highlight input
    const inp = document.getElementById('log_path');
    inp.style.borderColor = 'var(--success)';
    setTimeout(() => inp.style.borderColor = '', 1500);
}

function togglePathHint() {
    const type = document.getElementById('log_type').value;
    const pathHint = document.getElementById('path-hint');
    const patternHint = document.getElementById('pattern-hint');
    const dockerHint = document.getElementById('docker-hint');
    const journalctlHint = document.getElementById('journalctl-hint');

    pathHint.style.display = 'none';
    patternHint.style.display = 'none';
    if (dockerHint) dockerHint.style.display = 'none';
    if (journalctlHint) journalctlHint.style.display = 'none';

    if (type === 'pattern') {
        patternHint.style.display = 'block';
    } else if (type === 'docker') {
        if (dockerHint) dockerHint.style.display = 'block';
    } else if (type === 'journalctl') {
        if (journalctlHint) journalctlHint.style.display = 'block';
    } else {
        pathHint.style.display = 'block';
    }
}

// Auto-suggest app name from log path
function suggestAppName() {
    const logPath = document.getElementById('log_path').value.trim();
    const nameInput = document.getElementById('name');
    const logType = document.getElementById('log_type').value;
    
    // Chỉ suggest nếu name đang trống hoặc chưa được chỉnh sửa thủ công
    if (!logPath || nameInput.value.trim() !== '') {
        return;
    }
    
    let suggestedName = '';
    
    if (logType === 'journalctl') {
        // journalctl: cms.ggcamp.org.service -> cms.ggcamp.org
        // hoặc nginx.service -> nginx
        const unit = logPath.replace(/\.service$/, '');
        const parts = unit.split('.');
        if (parts.length > 1) {
            // Nếu có nhiều phần, lấy phần đầu tiên (domain name)
            suggestedName = parts[0];
        } else {
            suggestedName = unit;
        }
    } else if (logType === 'docker') {
        // docker: nginx-proxy -> nginx-proxy
        suggestedName = logPath;
    } else {
        // file/pattern: /var/log/nginx/access.log -> nginx
        // hoặc /var/log/app/app.log -> app
        const parts = logPath.split('/').filter(p => p);
        const filename = parts[parts.length - 1] || '';
        
        // Lấy tên từ filename (bỏ extension và pattern)
        if (filename) {
            suggestedName = filename.replace(/\.[^.]+$/, '').replace(/\{[^}]+\}/g, '');
        }
        
        // Nếu không có filename hợp lệ, lấy từ thư mục
        if (!suggestedName && parts.length > 1) {
            suggestedName = parts[parts.length - 2];
        }
    }
    
    // Capitalize first letter và format
    if (suggestedName) {
        suggestedName = suggestedName
            .split(/[-_]/)
            .map(word => word.charAt(0).toUpperCase() + word.slice(1))
            .join(' ');
    }
    
    // Chỉ điền nếu có suggested name và name input đang trống
    if (suggestedName && !nameInput.value.trim()) {
        nameInput.value = suggestedName;
        nameInput.style.borderColor = 'var(--accent)';
        setTimeout(() => nameInput.style.borderColor = '', 1500);
    }
}

// Roles handling
function updateAllowedRoles() {
    const roles = ['admin'];
    if (document.querySelector('input[value="user"]').checked) {
        roles.push('user');
    }
    document.getElementById('allowed_roles').value = roles.join(',');
}

document.querySelectorAll('input[name="roles[]"]').forEach(cb => {
    cb.addEventListener('change', updateAllowedRoles);
});

// Init khi load lại trang (old values)
window.addEventListener('DOMContentLoaded', () => {
    const sel = document.getElementById('server_id');
    if (sel.value) onServerChange(sel);
    togglePathHint();
    updateAllowedRoles(); // Init allowed_roles
});
</script>
@endpush
