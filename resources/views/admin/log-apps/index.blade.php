@extends('layouts.app')
@section('title', __('app.log_app_list'))
@section('breadcrumb')
    <i class="fas fa-file-alt" style="color: var(--accent);"></i>
    <span class="current">{{ __('app.nav_log_apps') }}</span>
@endsection
@section('header-actions')
    <a href="{{ route('admin.log-apps.create') }}" class="btn btn-primary">
        <i class="fas fa-plus"></i> {{ __('app.btn_add_app') }}
    </a>
@endsection
@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('app.nav_log_apps') }}</h1>
        <p class="page-subtitle">{{ __('app.log_app_subtitle') }}</p>
    </div>
</div>

<div class="card">
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ __('app.app_name') }}</th>
                    <th>{{ __('app.nav_servers') }}</th>
                    <th>{{ __('app.log_path_label') }}</th>
                    <th>{{ __('app.status') }}</th>
                    <th>{{ __('app.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($apps as $app)
                <tr>
                    <td style="color: var(--text-muted);">{{ $app->id }}</td>
                    <td>
                        <div style="font-weight:500;">{{ $app->name }}</div>
                        @if($app->description)
                            <div style="font-size:11px; color: var(--text-muted);">{{ Str::limit($app->description, 60) }}</div>
                        @endif
                    </td>
                    <td>
                        <span class="badge badge-info">
                            <i class="fas fa-server"></i> {{ $app->server->name }}
                        </span>
                    </td>
                    <td>
                        <code style="font-family:'JetBrains Mono',monospace; font-size:11px; background: var(--bg-primary); padding:3px 7px; border-radius:4px; color: var(--text-secondary); word-break:break-all; max-width:300px; display:block;">
                            {{ $app->log_path }}
                        </code>
                    </td>
                    <td>
                        @if($app->is_active && $app->server->is_active)
                            <span class="badge badge-success"><i class="fas fa-circle" style="font-size:7px;"></i> {{ __('app.active') }}</span>
                        @elseif(!$app->server->is_active)
                            <span class="badge badge-warning">{{ __('app.server_offline') }}</span>
                        @else
                            <span class="badge badge-danger"><i class="fas fa-circle" style="font-size:7px;"></i> {{ __('app.inactive') }}</span>
                        @endif
                    </td>
                    <td>
                        <div style="display:flex; gap:6px; align-items:center;">
                            @if($app->is_active)
                            <a href="{{ route('logs.show', $app) }}" class="btn btn-sm btn-success" title="{{ __('app.btn_view_log') }}">
                                <i class="fas fa-eye"></i>
                            </a>
                            @endif
                            <a href="{{ route('admin.log-apps.edit', $app) }}" class="btn btn-sm btn-secondary" title="{{ __('app.btn_edit') }}">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" action="{{ route('admin.log-apps.destroy', $app) }}"
                                  onsubmit="return confirm('{{ __('app.confirm_delete_app') }}')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" title="{{ __('app.btn_delete') }}">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align:center; padding:40px; color: var(--text-muted);">
                        <i class="fas fa-file-alt" style="font-size:32px; margin-bottom:10px; display:block;"></i>
                        {{ __('app.no_apps_found') }}
                        <a href="{{ route('admin.log-apps.create') }}" style="color: var(--accent);">{{ __('app.add_now') }}</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($apps->hasPages())
    <div style="padding:16px 20px; border-top: 1px solid var(--border);">
        {{ $apps->links() }}
    </div>
    @endif
</div>
@endsection
