@extends('layouts.app')
@section('title', __('app.page_dashboard'))
@section('breadcrumb')
    <i class="fas fa-list-ul" style="color: var(--accent);"></i>
    <span class="current">{{ __('app.page_dashboard') }}</span>
@endsection

@push('styles')
<style>
    .tag-filter-bar { display:flex; flex-wrap:wrap; gap:8px; margin-bottom:24px; align-items:center; }
    .tag-pill {
        display:inline-flex; align-items:center; gap:5px;
        padding:5px 14px; border-radius:20px; font-size:12px; font-weight:500;
        border:1px solid var(--border); background:var(--bg-card);
        color:var(--text-secondary); text-decoration:none; transition:all 0.15s;
        cursor:pointer;
    }
    .tag-pill:hover { border-color:var(--accent); color:var(--accent); background:rgba(88,166,255,0.08); }
    .tag-pill.active { background:var(--accent); color:#fff; border-color:var(--accent); }
    .tag-pill.all { background:var(--bg-secondary); }

    .tag-group { margin-bottom: 32px; }
    .tag-header { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; }
    .tag-icon {
        width: 36px; height: 36px;
        background: rgba(88, 166, 255, 0.1);
        border: 1px solid rgba(88, 166, 255, 0.3);
        border-radius: 9px;
        display: flex; align-items: center; justify-content: center;
        color: var(--accent); font-size: 14px;
    }
    .tag-title { font-size: 15px; font-weight: 600; }
    .tag-count { font-size: 11px; color: var(--text-muted); }
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
    .app-tags { display:flex; flex-wrap:wrap; gap:4px; margin-bottom:8px; }
    .app-tag-badge { font-size:10px; padding:2px 8px; border-radius:10px; background:rgba(88,166,255,0.12); color:var(--accent); border:1px solid rgba(88,166,255,0.25); }
    .empty-state { text-align: center; padding: 60px 20px; color: var(--text-muted); }
    .empty-state i { font-size: 48px; margin-bottom: 16px; color: var(--border); }
    .empty-state h3 { font-size: 18px; color: var(--text-secondary); margin-bottom: 8px; }
    .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px; margin-bottom: 28px; }
    .stat-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: 10px; padding: 16px 20px; display: flex; align-items: center; gap: 14px; }
    .stat-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 16px; }
    .stat-icon.blue { background: rgba(88,166,255,0.1); color: var(--accent); }
    .stat-icon.green { background: rgba(63,185,80,0.1); color: var(--success); }
    .stat-icon.purple { background: rgba(188,140,255,0.1); color: var(--purple); }
    .stat-icon.orange { background: rgba(255,170,60,0.1); color: var(--warning); }
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

<div class="stats-row">
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-file-alt"></i></div>
        <div>
            <div class="stat-value">{{ $apps->count() }}</div>
            <div class="stat-label">{{ __('app.stat_apps') }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="fas fa-tags"></i></div>
        <div>
            <div class="stat-value">{{ $allTags->count() }}</div>
            <div class="stat-label">Tags</div>
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

{{-- Tag filter bar --}}
@if($allTags->isNotEmpty())
<div class="tag-filter-bar">
    <a href="{{ route('logs.index') }}" class="tag-pill all {{ !$tagFilter ? 'active' : '' }}">
        <i class="fas fa-th-large"></i> {{ __('app.all') }}
    </a>
    @foreach($allTags as $tag)
    <a href="{{ route('logs.index', ['tag' => $tag]) }}" class="tag-pill {{ $tagFilter === $tag ? 'active' : '' }}">
        <i class="fas fa-tag"></i> {{ $tag }}
    </a>
    @endforeach
</div>
@endif

{{-- Content --}}
@if($apps->isEmpty())
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
@else
    @foreach($grouped as $tag => $groupApps)
    <div class="tag-group">
        <div class="tag-header">
            @if($tag === '__untagged')
                <div class="tag-icon" style="background:rgba(150,150,150,0.1); border-color:rgba(150,150,150,0.3); color:var(--text-muted);">
                    <i class="fas fa-folder"></i>
                </div>
                <div>
                    <div class="tag-title" style="color:var(--text-secondary);">{{ __('app.untagged') }}</div>
                    <div class="tag-count">{{ __('app.apps_count', ['count' => count($groupApps)]) }}</div>
                </div>
            @else
                <div class="tag-icon"><i class="fas fa-tag"></i></div>
                <div>
                    <div class="tag-title">{{ $tag }}</div>
                    <div class="tag-count">{{ __('app.apps_count', ['count' => count($groupApps)]) }}</div>
                </div>
            @endif
        </div>
        <div class="apps-grid">
            @foreach($groupApps as $app)
            <a href="{{ route('logs.show', $app) }}" class="app-card">
                <div class="app-name">
                    <i class="fas fa-file-code" style="color: var(--accent); font-size:13px;"></i>
                    {{ $app->name }}
                </div>
                @if(auth()->user()->isAdmin())
                <div class="app-path"><i class="fas fa-server" style="margin-right:4px;"></i>{{ $app->server->name }} &nbsp;·&nbsp; {{ $app->log_path }}</div>
                @endif
                @if($app->description)
                    <div class="app-desc">{{ $app->description }}</div>
                @endif
                @if(!empty($app->tags))
                <div class="app-tags">
                    @foreach($app->tags as $t)
                        <span class="app-tag-badge"><i class="fas fa-tag" style="font-size:9px;"></i> {{ $t }}</span>
                    @endforeach
                </div>
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
    @endforeach
@endif
@endsection
