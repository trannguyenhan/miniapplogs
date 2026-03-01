@extends('layouts.app')
@section('title', __('app.page_dashboard'))
@section('breadcrumb')
    <i class="fas fa-list-ul" style="color: var(--accent);"></i>
    <span class="current">{{ __('app.page_dashboard') }}</span>
@endsection

@push('styles')
<style>
    .server-group { margin-bottom: 32px; }
    .server-header { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; }
    .server-icon {
        width: 40px; height: 40px;
        background: rgba(88, 166, 255, 0.1);
        border: 1px solid rgba(88, 166, 255, 0.3);
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        color: var(--accent); font-size: 16px;
    }
    .server-title { font-size: 16px; font-weight: 600; }
    .server-ip { font-size: 12px; color: var(--text-muted); font-family: 'JetBrains Mono', monospace; }
    .apps-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 12px; }
    .app-card {
        background: var(--bg-card); border: 1px solid var(--border); border-radius: 10px;
        padding: 18px 20px; text-decoration: none; display: block;
        transition: all 0.2s ease; position: relative; overflow: hidden;
    }
    .app-card::before {
        content: ''; position: absolute; top: 0; left: 0;
        width: 3px; height: 100%; background: var(--accent); opacity: 0; transition: opacity 0.2s;
    }
    .app-card:hover { border-color: var(--accent); background: var(--bg-hover); transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.3); }
    .app-card:hover::before { opacity: 1; }
    .app-name { font-size: 14px; font-weight: 600; color: var(--text-primary); margin-bottom: 6px; display: flex; align-items: center; gap: 8px; }
    .app-path { font-size: 11px; font-family: 'JetBrains Mono', monospace; color: var(--text-muted); word-break: break-all; margin-bottom: 10px; }
    .app-desc { font-size: 12px; color: var(--text-secondary); margin-bottom: 10px; }
    .app-meta { display: flex; align-items: center; gap: 8px; font-size: 11px; color: var(--text-muted); }
    .app-meta i { color: var(--success); }
    .empty-state { text-align: center; padding: 60px 20px; color: var(--text-muted); }
    .empty-state i { font-size: 48px; margin-bottom: 16px; color: var(--border); }
    .empty-state h3 { font-size: 18px; color: var(--text-secondary); margin-bottom: 8px; }
    .empty-state p { font-size: 13px; }
    .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px; margin-bottom: 28px; }
    .stat-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: 10px; padding: 16px 20px; display: flex; align-items: center; gap: 14px; }
    .stat-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 16px; }
    .stat-icon.blue { background: rgba(88,166,255,0.1); color: var(--accent); }
    .stat-icon.green { background: rgba(63,185,80,0.1); color: var(--success); }
    .stat-icon.purple { background: rgba(188,140,255,0.1); color: var(--purple); }
    .stat-value { font-size: 22px; font-weight: 700; }
    .stat-label { font-size: 11px; color: var(--text-muted); }
</style>
@endpush

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('app.page_dashboard') }}</h1>
        <p class="page-subtitle">{{ __('app.page_subtitle_dash') }}</p>
    </div>
</div>

@php
    $totalServers = $servers->count();
    $totalApps = $servers->sum(fn($s) => $s->activeLogApplications->count());
@endphp

<div class="stats-row">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-server"></i></div>
        <div>
            <div class="stat-value">{{ $totalServers }}</div>
            <div class="stat-label">{{ __('app.stat_servers') }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-file-alt"></i></div>
        <div>
            <div class="stat-value">{{ $totalApps }}</div>
            <div class="stat-label">{{ __('app.stat_apps') }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple"><i class="fas fa-user"></i></div>
        <div>
            <div class="stat-value">{{ auth()->user()->isAdmin() ? __('app.role_admin') : __('app.role_user') }}</div>
            <div class="stat-label">{{ __('app.stat_role') }}</div>
        </div>
    </div>
</div>

@if($servers->isEmpty())
    <div class="card">
        <div class="card-body">
            <div class="empty-state">
                <i class="fas fa-server"></i>
                <h3>{{ __('app.no_servers') }}</h3>
                <p>
                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('admin.servers.create') }}" style="color: var(--accent);">{{ __('app.add_first_server') }}</a>
                    @else
                        {{ __('app.contact_admin') }}
                    @endif
                </p>
            </div>
        </div>
    </div>
@else
    @foreach($servers as $server)
        @if($server->activeLogApplications->isNotEmpty())
        <div class="server-group">
            <div class="server-header">
                <div class="server-icon"><i class="fas fa-server"></i></div>
                <div>
                    <div class="server-title">{{ $server->name }}</div>
                    <div class="server-ip"><i class="fas fa-network-wired" style="margin-right:4px;"></i>{{ $server->ip_address }}{{ $server->ssh_port ? ':'.$server->ssh_port : '' }}</div>
                </div>
                <div style="margin-left: auto;">
                    <span class="badge badge-success"><i class="fas fa-circle" style="font-size:7px;"></i> {{ __('app.online') }}</span>
                </div>
            </div>
            <div class="apps-grid">
                @foreach($server->activeLogApplications as $app)
                <a href="{{ route('logs.show', $app) }}" class="app-card">
                    <div class="app-name">
                        <i class="fas fa-file-code" style="color: var(--accent); font-size:13px;"></i>
                        {{ $app->name }}
                    </div>
                    <div class="app-path"><i class="fas fa-folder" style="margin-right:4px;"></i>{{ $app->log_path }}</div>
                    @if($app->description)
                        <div class="app-desc">{{ $app->description }}</div>
                    @endif
                    <div class="app-meta">
                        <i class="fas fa-eye"></i>
                        <span>{{ __('app.view_last_lines') }}</span>
                        <span style="margin-left:auto; color: var(--accent);"><i class="fas fa-arrow-right"></i></span>
                    </div>
                </a>
                @endforeach
            </div>
        </div>
        @endif
    @endforeach

    @if($totalApps === 0)
        <div class="card">
            <div class="card-body">
                <div class="empty-state">
                    <i class="fas fa-file-alt"></i>
                    <h3>{{ __('app.no_apps') }}</h3>
                    <p>
                        @if(auth()->user()->isAdmin())
                            <a href="{{ route('admin.log-apps.create') }}" style="color: var(--accent);">{{ __('app.add_first_app') }}</a>
                        @else
                            {{ __('app.contact_admin_app') }}
                        @endif
                    </p>
                </div>
            </div>
        </div>
    @endif
@endif
@endsection
