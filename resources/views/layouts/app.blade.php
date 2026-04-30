<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'MiniAppLogs') | Log Monitor</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Space+Grotesk:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --bg-primary: #0F172A;
            --bg-secondary: #1E293B;
            --bg-card: #1E293B;
            --bg-hover: #334155;
            --border: #334155;
            --border-light: #475569;
            --text-primary: #F8FAFC;
            --text-secondary: #CBD5E1;
            --text-muted: #94A3B8;
            --accent: #22C55E;
            --accent-hover: #16A34A;
            --accent-dark: rgba(34, 197, 94, 0.15);
            --success: #22C55E;
            --success-bg: rgba(34, 197, 94, 0.1);
            --danger: #EF4444;
            --danger-bg: rgba(239, 68, 68, 0.1);
            --warning: #F59E0B;
            --warning-bg: rgba(245, 158, 11, 0.1);
            --info: #3B82F6;
            --purple: #8B5CF6;
            --sidebar-width: 260px;
            --header-height: 60px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Space Grotesk', 'DM Sans', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            font-size: 14px;
            line-height: 1.5;
        }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: var(--bg-secondary); }
        ::-webkit-scrollbar-thumb { background: var(--border); border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--text-muted); }

        /* ===== LAYOUT ===== */
        .app-wrapper { display: flex; min-height: 100vh; }

        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--bg-secondary);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; left: 0;
            height: 100vh;
            z-index: 100;
            transition: transform 0.3s ease;
        }

        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 20px 20px;
            border-bottom: 1px solid var(--border);
            text-decoration: none;
        }

        .sidebar-logo .logo-icon {
            width: 36px; height: 36px;
            background: var(--accent);
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 16px;
            color: white;
            flex-shrink: 0;
        }

        .sidebar-logo .logo-text {
            font-size: 15px;
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: -0.3px;
        }

        .sidebar-logo .logo-sub {
            font-size: 10px;
            color: var(--text-muted);
            font-weight: 400;
        }

        .sidebar-nav {
            flex: 1;
            overflow-y: auto;
            padding: 16px 12px;
        }

        .nav-section {
            margin-bottom: 24px;
        }

        .nav-section-title {
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-muted);
            padding: 0 8px 8px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 12px;
            border-radius: 6px;
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 13.5px;
            transition: all 0.15s ease;
            margin-bottom: 2px;
        }

        .nav-item:hover {
            background: var(--bg-hover);
            color: var(--text-primary);
        }

        .nav-item.active {
            background: var(--accent-dark);
            color: var(--accent);
        }

        .nav-item i {
            width: 16px;
            text-align: center;
            font-size: 13px;
        }

        .nav-badge {
            margin-left: auto;
            background: var(--bg-hover);
            color: var(--text-muted);
            font-size: 10px;
            padding: 1px 6px;
            border-radius: 10px;
        }

        /* Sidebar footer */
        .sidebar-footer {
            padding: 16px;
            border-top: 1px solid var(--border);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
        }

        .user-avatar {
            width: 32px; height: 32px;
            background: var(--accent);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 13px;
            font-weight: 600;
            color: white;
            flex-shrink: 0;
        }

        .user-name {
            font-size: 13px;
            font-weight: 500;
            color: var(--text-primary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-role {
            font-size: 10px;
            color: var(--text-muted);
        }

        .user-admin-badge {
            color: var(--purple);
        }

        /* Lang switcher */
        .lang-switcher {
            display: flex;
            gap: 4px;
            margin-bottom: 8px;
        }

        .lang-btn {
            flex: 1;
            padding: 4px 0;
            border-radius: 5px;
            border: 1px solid var(--border);
            background: transparent;
            color: var(--text-muted);
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            transition: all 0.15s;
        }

        .lang-btn:hover { background: var(--bg-hover); color: var(--text-primary); }
        .lang-btn.active { background: var(--accent); color: var(--bg-primary); border-color: var(--accent); }

        /* Main content */
        .main-content {
            margin-left: var(--sidebar-width);
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* Top header */
        .top-header {
            height: var(--header-height);
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            padding: 0 24px;
            gap: 16px;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .header-breadcrumb {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            color: var(--text-secondary);
        }

        .header-breadcrumb .current {
            color: var(--text-primary);
            font-weight: 500;
        }

        .header-breadcrumb .sep { color: var(--text-muted); }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Page content */
        .page-content {
            flex: 1;
            padding: 24px;
        }

        /* ===== COMPONENTS ===== */

        /* Page header */
        .page-header {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 24px;
        }

        .page-title {
            font-size: 22px;
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: -0.5px;
        }

        .page-subtitle {
            font-size: 13px;
            color: var(--text-secondary);
            margin-top: 4px;
        }

        .page-header-actions {
            margin-left: auto;
            display: flex;
            gap: 8px;
            align-items: center;
        }

        /* Cards */
        .card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 10px;
            overflow: hidden;
        }

        .card-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .card-title {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-primary);
        }

        .card-body { padding: 20px; }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 14px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            border: 1px solid transparent;
            text-decoration: none;
            transition: all 0.15s ease;
            line-height: 1.4;
        }

        .btn:hover { transform: translateY(-1px); }
        .btn:active { transform: translateY(0); }

        .btn-primary {
            background: var(--accent);
            color: #0d1117;
            border-color: var(--accent);
        }
        .btn-primary:hover { background: var(--accent-hover); }

        .btn-secondary {
            background: transparent;
            color: var(--text-secondary);
            border-color: var(--border);
        }
        .btn-secondary:hover { background: var(--bg-hover); color: var(--text-primary); }

        .btn-danger {
            background: var(--danger-bg);
            color: var(--danger);
            border-color: var(--danger);
        }
        .btn-danger:hover { background: var(--danger); color: white; }

        .btn-success {
            background: var(--success-bg);
            color: var(--success);
            border-color: var(--success);
        }
        .btn-success:hover { background: var(--success); color: white; }

        .btn-sm { padding: 4px 10px; font-size: 12px; }
        .btn-lg { padding: 10px 20px; font-size: 15px; }
        .btn-icon { padding: 7px 10px; }

        /* Table */
        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead tr {
            border-bottom: 1px solid var(--border);
        }

        th {
            padding: 10px 16px;
            text-align: left;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: var(--text-muted);
        }

        td {
            padding: 12px 16px;
            border-bottom: 1px solid var(--border-light);
            vertical-align: middle;
            font-size: 13px;
        }

        tr:last-child td { border-bottom: none; }

        tbody tr:hover { background: var(--bg-hover); }

        /* Forms */
        .form-group { margin-bottom: 18px; }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: var(--text-secondary);
            margin-bottom: 6px;
        }

        .form-label .required { color: var(--danger); }

        .form-control {
            width: 100%;
            background: var(--bg-primary);
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 8px 12px;
            color: var(--text-primary);
            font-size: 13px;
            font-family: 'Space Grotesk', 'DM Sans', sans-serif;
            transition: border-color 0.15s, box-shadow 0.15s;
            outline: none;
        }

        .form-control:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(88, 166, 255, 0.1); }
        .form-control::placeholder { color: var(--text-muted); }

        textarea.form-control { resize: vertical; min-height: 80px; }

        .form-hint {
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 4px;
        }

        .form-error {
            font-size: 12px;
            color: var(--danger);
            margin-top: 4px;
        }

        /* Toggle switch */
        .toggle-wrapper {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .toggle {
            position: relative;
            display: inline-block;
            width: 40px;
            height: 22px;
        }

        .toggle input { opacity: 0; width: 0; height: 0; }

        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0; left: 0; right: 0; bottom: 0;
            background: var(--border);
            border-radius: 22px;
            transition: 0.2s;
        }

        .toggle-slider:before {
            position: absolute;
            content: '';
            height: 16px; width: 16px;
            left: 3px; bottom: 3px;
            background: white;
            border-radius: 50%;
            transition: 0.2s;
        }

        .toggle input:checked + .toggle-slider { background: var(--success); }
        .toggle input:checked + .toggle-slider:before { transform: translateX(18px); }

        /* Badges */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 500;
        }

        .badge-success { background: var(--success-bg); color: var(--success); border: 1px solid var(--success); }
        .badge-danger  { background: var(--danger-bg); color: var(--danger); border: 1px solid var(--danger); }
        .badge-info    { background: rgba(88, 166, 255, 0.1); color: var(--info); border: 1px solid var(--info); }
        .badge-purple  { background: rgba(188, 140, 255, 0.1); color: var(--purple); border: 1px solid var(--purple); }
        .badge-warning { background: var(--warning-bg); color: var(--warning); border: 1px solid var(--warning); }

        /* Alerts */
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            font-size: 13px;
        }

        .alert i { margin-top: 1px; }

        .alert-success { background: var(--success-bg); color: var(--success); border: 1px solid var(--success); }
        .alert-danger  { background: var(--danger-bg); color: var(--danger); border: 1px solid var(--danger); }
        .alert-warning { background: var(--warning-bg); color: var(--warning); border: 1px solid var(--warning); }

        /* Pagination */
        .pagination {
            display: flex;
            gap: 4px;
            align-items: center;
            justify-content: center;
            margin-top: 20px;
        }

        .pagination a, .pagination span {
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 32px;
            height: 32px;
            padding: 0 10px;
            border-radius: 6px;
            font-size: 12px;
            text-decoration: none;
            border: 1px solid var(--border);
            color: var(--text-secondary);
            transition: all 0.15s;
        }

        .pagination a:hover { background: var(--bg-hover); color: var(--text-primary); }
        .pagination .active { background: var(--accent); color: #0d1117; border-color: var(--accent); }
        .pagination .disabled { opacity: 0.4; cursor: not-allowed; }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .main-content { margin-left: 0; }
        }
    </style>
    @stack('styles')
</head>
<body>
<div class="app-wrapper">

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <a href="{{ route('logs.index') }}" class="sidebar-logo">
            <div class="logo-icon"><i class="fas fa-terminal"></i></div>
            <div>
                <div class="logo-text">{{ __('app.app_name') }}</div>
                <div class="logo-sub">{{ __('app.app_subtitle') }}</div>
            </div>
        </a>

        <nav class="sidebar-nav">
            <div class="nav-section">
                <div class="nav-section-title">{{ __('app.nav_log_list') }}</div>
                <a href="{{ route('logs.index') }}" class="nav-item {{ request()->routeIs('logs.index') ? 'active' : '' }}">
                    <i class="fas fa-list-ul"></i>
                    {{ __('app.nav_log_list') }}
                </a>
            </div>

            @if(auth()->user()->isAdmin())
            <div class="nav-section">
                <div class="nav-section-title">{{ __('app.nav_admin') }}</div>
                <a href="{{ route('admin.servers.index') }}" class="nav-item {{ request()->routeIs('admin.servers.*') ? 'active' : '' }}">
                    <i class="fas fa-server"></i>
                    {{ __('app.nav_servers') }}
                </a>
                <a href="{{ route('admin.log-apps.index') }}" class="nav-item {{ request()->routeIs('admin.log-apps.*') ? 'active' : '' }}">
                    <i class="fas fa-file-alt"></i>
                    {{ __('app.nav_log_apps') }}
                </a>
                <a href="{{ route('admin.tags.index') }}" class="nav-item {{ request()->routeIs('admin.tags.*') ? 'active' : '' }}">
                    <i class="fas fa-tags"></i>
                    {{ __('app.nav_tags') }}
                </a>
                <a href="{{ route('admin.users.index') }}" class="nav-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                    <i class="fas fa-users"></i>
                    {{ __('app.nav_users') }}
                </a>
            </div>
            @endif
        </nav>

        <div class="sidebar-footer">
            {{-- Language switcher (hidden – uncomment to enable multi-lang)
            <div class="lang-switcher">
                <a href="{{ route('lang.switch', 'en') }}" class="lang-btn {{ app()->getLocale() === 'en' ? 'active' : '' }}">EN</a>
                <a href="{{ route('lang.switch', 'vi') }}" class="lang-btn {{ app()->getLocale() === 'vi' ? 'active' : '' }}">VI</a>
            </div>
            --}}

            <div class="user-info">
                <div class="user-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
                <div style="flex:1; min-width:0;">
                    <div class="user-name">{{ auth()->user()->name }}</div>
                    <div class="user-role">
                        @if(auth()->user()->isAdmin())
                            <span class="user-admin-badge"><i class="fas fa-shield-alt"></i> {{ __('app.role_admin') }}</span>
                        @else
                            <span>{{ __('app.role_user') }}</span>
                        @endif
                    </div>
                </div>
            </div>
            <a href="{{ route('profile.edit') }}" class="btn btn-secondary" style="width:100%; justify-content:center; margin-bottom:8px;">
                <i class="fas fa-user-edit"></i> {{ __('app.profile') }}
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-secondary" style="width:100%; justify-content:center;">
                    <i class="fas fa-sign-out-alt"></i> {{ __('app.logout') }}
                </button>
            </form>
        </div>
    </aside>

    <!-- Main content -->
    <div class="main-content">
        <!-- Top header -->
        <header class="top-header">
            <div class="header-breadcrumb">
                @yield('breadcrumb')
            </div>
            <div class="header-actions">
                @yield('header-actions')
            </div>
        </header>

        <!-- Page content -->
        <main class="page-content">
            @if(session('success'))
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    {{ session('error') }}
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div>
                        <strong>{{ __('app.errors_occurred') }}</strong>
                        <ul style="margin: 4px 0 0 16px;">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>

<!-- Custom Modal Component -->
<div id="custom-modal" class="custom-modal" style="display:none;">
    <div class="custom-modal-overlay" onclick="closeModal()"></div>
    <div class="custom-modal-content">
        <div class="custom-modal-header">
            <div class="custom-modal-icon" id="modal-icon">
                <i class="fas fa-info-circle"></i>
            </div>
            <h3 class="custom-modal-title" id="modal-title">{{ __('app.notice') }}</h3>
            <button type="button" class="custom-modal-close" onclick="closeModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="custom-modal-body">
            <p id="modal-message"></p>
            <div id="modal-content" style="display:none;"></div>
        </div>
        <div class="custom-modal-footer" id="modal-footer">
            <button type="button" class="btn btn-secondary" id="modal-btn-cancel" onclick="closeModal()" style="display:none;">{{ __('app.btn_cancel') }}</button>
            <button type="button" class="btn btn-primary" id="modal-btn-ok" onclick="confirmModal()">OK</button>
        </div>
    </div>
</div>

@stack('scripts')

<script id="modal-i18n" type="application/json">{!! json_encode([
    'notice' => __('app.notice'),
    'confirm' => __('app.confirm'),
    'cancel' => __('app.btn_cancel'),
    'success' => __('app.success'),
    'error' => __('app.error'),
    'confirmDeleteTitle' => __('app.confirm_delete_title'),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>

<script>
// Custom Modal System
let modalResolve = null;
let modalType = 'alert'; // 'alert' or 'confirm'
const i18n = JSON.parse(document.getElementById('modal-i18n').textContent);

function showModal(options) {
    const modal = document.getElementById('custom-modal');
    const icon = document.getElementById('modal-icon');
    const title = document.getElementById('modal-title');
    const message = document.getElementById('modal-message');
    const content = document.getElementById('modal-content');
    const footer = document.getElementById('modal-footer');
    const btnOk = document.getElementById('modal-btn-ok');
    const btnCancel = document.getElementById('modal-btn-cancel');
    
    // Set type
    modalType = options.type || 'alert';
    
    // Set icon and color
    const iconMap = {
        success: { icon: 'fa-check-circle', color: 'var(--success)' },
        error: { icon: 'fa-exclamation-circle', color: 'var(--danger)' },
        warning: { icon: 'fa-exclamation-triangle', color: 'var(--warning)' },
        info: { icon: 'fa-info-circle', color: 'var(--info)' },
        question: { icon: 'fa-question-circle', color: 'var(--purple)' }
    };
    
    const iconData = iconMap[options.icon || 'info'];
    icon.innerHTML = `<i class="fas ${iconData.icon}"></i>`;
    icon.style.color = iconData.color;
    
    // Set title
    title.textContent = options.title || i18n.notice;
    
    // Set message
    message.textContent = options.message || '';
    
    // Set content (for formatted output)
    if (options.content) {
        content.innerHTML = options.content;
        content.style.display = 'block';
        message.style.display = 'none';
    } else {
        content.style.display = 'none';
        message.style.display = 'block';
    }
    
    // Set buttons
    if (modalType === 'confirm') {
        btnCancel.style.display = '';
        btnOk.textContent = options.confirmText || i18n.confirm;
        btnOk.className = 'btn btn-primary';
    } else {
        btnCancel.style.display = 'none';
        btnOk.textContent = options.okText || 'OK';
        btnOk.className = 'btn btn-primary';
    }
    
    // Show modal
    modal.style.display = 'flex';
    setTimeout(() => modal.classList.add('show'), 10);
    
    // Return promise for confirm
    if (modalType === 'confirm') {
        return new Promise((resolve) => {
            modalResolve = resolve;
        });
    }
}

function closeModal() {
    const modal = document.getElementById('custom-modal');
    modal.classList.remove('show');
    setTimeout(() => modal.style.display = 'none', 300);
    if (modalResolve) {
        modalResolve(false);
        modalResolve = null;
    }
}

function confirmModal() {
    const modal = document.getElementById('custom-modal');
    modal.classList.remove('show');
    setTimeout(() => modal.style.display = 'none', 300);
    if (modalResolve) {
        modalResolve(true);
        modalResolve = null;
    }
}

// Replace window.alert
window.alert = function(message, options = {}) {
    return showModal({
        type: 'alert',
        icon: options.icon || 'info',
        title: options.title || i18n.notice,
        message: message
    });
};

// Replace window.confirm
window.confirm = function(message, options = {}) {
    // Handle both: confirm(message) and confirm(message, {title: '...'})
    if (typeof options === 'string') {
        options = { title: options };
    }
    return showModal({
        type: 'confirm',
        icon: options.icon || 'question',
        title: options.title || i18n.confirm,
        message: message,
        confirmText: options.confirmText || i18n.confirm,
        cancelText: options.cancelText || i18n.cancel
    });
};

// Helper for success messages
    window.showSuccess = function(message, title = i18n.success) {
    return showModal({
        type: 'alert',
        icon: 'success',
        title: title,
        message: message
    });
};

// Helper for error messages
window.showError = function(message, title = i18n.error) {
    return showModal({
        type: 'alert',
        icon: 'error',
        title: title,
        message: message
    });
};

// Helper for formatted content (like script output)
window.showOutput = function(title, output, icon = 'success') {
    const formatted = output.split('\n').map(line => {
        if (!line.trim()) return '<br>';
        return `<div style="padding:4px 0; font-family:'JetBrains Mono',monospace; font-size:12px; color:var(--text-secondary);">${escapeHtml(line)}</div>`;
    }).join('');
    
    return showModal({
        type: 'alert',
        icon: icon,
        title: title,
        content: `<div style="max-height:400px; overflow-y:auto; padding:8px 0;">${formatted}</div>`
    });
};

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Helper for form delete confirmation
async function confirmDelete(event, message) {
    event.preventDefault();
    const form = event.target;
    const confirmed = await confirm(message, {
        title: i18n.confirmDeleteTitle,
        icon: 'warning'
    });
    if (confirmed) {
        form.submit();
    }
    return false;
}

// Close on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('custom-modal');
        if (modal.style.display !== 'none') {
            closeModal();
        }
    }
});
</script>

<style>
.custom-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.3s ease;
}

.custom-modal.show {
    opacity: 1;
    pointer-events: all;
}

.custom-modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(15, 23, 42, 0.85);
    backdrop-filter: blur(4px);
}

.custom-modal-content {
    position: relative;
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 12px;
    width: 90%;
    max-width: 500px;
    max-height: 90vh;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
    transform: scale(0.95) translateY(20px);
    transition: transform 0.3s ease;
    display: flex;
    flex-direction: column;
}

.custom-modal.show .custom-modal-content {
    transform: scale(1) translateY(0);
}

.custom-modal-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 20px 24px;
    border-bottom: 1px solid var(--border);
    background: var(--bg-secondary);
}

.custom-modal-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    background: var(--bg-primary);
    flex-shrink: 0;
}

.custom-modal-title {
    flex: 1;
    font-size: 18px;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0;
}

.custom-modal-close {
    width: 32px;
    height: 32px;
    border: none;
    background: transparent;
    color: var(--text-muted);
    cursor: pointer;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    flex-shrink: 0;
}

.custom-modal-close:hover {
    background: var(--bg-hover);
    color: var(--text-primary);
}

.custom-modal-body {
    padding: 24px;
    color: var(--text-secondary);
    line-height: 1.6;
    overflow-y: auto;
    flex: 1;
}

.custom-modal-body p {
    margin: 0;
    font-size: 14px;
}

.custom-modal-footer {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    padding: 16px 24px;
    border-top: 1px solid var(--border);
    background: var(--bg-secondary);
}

.custom-modal-footer .btn {
    min-width: 100px;
}
</style>

</body>
</html>
