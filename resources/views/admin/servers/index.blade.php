@extends('layouts.app')
@section('title', __('app.server_list'))
@section('breadcrumb')
    <i class="fas fa-server" style="color: var(--accent);"></i>
    <span class="current">{{ __('app.nav_servers') }}</span>
@endsection
@section('header-actions')
    <a href="{{ route('admin.servers.create') }}" class="btn btn-primary">
        <i class="fas fa-plus"></i> {{ __('app.btn_add_server') }}
    </a>
@endsection
@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('app.nav_servers') }}</h1>
        <p class="page-subtitle">{{ __('app.server_subtitle') }}</p>
    </div>
</div>

<div class="card">
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ __('app.server_name') }}</th>
                    <th>IP Address</th>
                    <th>{{ __('app.connection_type') }}</th>
                    <th>SSH User</th>
                    <th>{{ __('app.log_apps') }}</th>
                    <th>{{ __('app.status') }}</th>
                    <th>{{ __('app.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($servers as $server)
                <tr>
                    <td style="color: var(--text-muted);">{{ $server->id }}</td>
                    <td>
                        <div style="font-weight:500;">{{ $server->name }}</div>
                        @if($server->description)
                            <div style="font-size:11px; color: var(--text-muted);">{{ Str::limit($server->description, 50) }}</div>
                        @endif
                    </td>
                    <td>
                        @if($server->ip_address)
                        <code style="font-family:'JetBrains Mono',monospace; font-size:12px; background: var(--bg-primary); padding:2px 6px; border-radius:4px; color: var(--accent);">
                            {{ $server->ip_address }}{{ $server->ssh_port ? ':'.$server->ssh_port : '' }}
                        </code>
                        @else
                            <span style="color: var(--text-muted); font-size:12px;">—</span>
                        @endif
                    </td>
                    <td>
                        @if($server->use_ssh)
                            <span class="badge badge-info"><i class="fas fa-network-wired"></i> {{ __('app.connection_ssh') }}</span>
                        @else
                            <span class="badge badge-purple"><i class="fas fa-hdd"></i> {{ __('app.connection_local') }}</span>
                        @endif
                    </td>
                    <td style="font-family:'JetBrains Mono',monospace; font-size:12px;">{{ $server->ssh_user ?? '—' }}</td>
                    <td>
                        <span class="badge badge-info">{{ __('app.log_app_count', ['count' => $server->log_applications_count]) }}</span>
                    </td>
                    <td>
                        @if($server->is_active)
                            <span class="badge badge-success"><i class="fas fa-circle" style="font-size:7px;"></i> {{ __('app.active') }}</span>
                        @else
                            <span class="badge badge-danger"><i class="fas fa-circle" style="font-size:7px;"></i> {{ __('app.inactive') }}</span>
                        @endif
                    </td>
                    <td>
                        <div style="display:flex; gap:6px;">
                            <a href="{{ route('admin.servers.edit', $server) }}" class="btn btn-sm btn-secondary">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" action="{{ route('admin.servers.destroy', $server) }}"
                                  onsubmit="return confirmDelete(event, '{{ __('app.confirm_delete_server') }}')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="text-align:center; padding:40px; color: var(--text-muted);">
                        <i class="fas fa-server" style="font-size:32px; margin-bottom:10px; display:block;"></i>
                        {{ __('app.no_servers_found') }}
                        <a href="{{ route('admin.servers.create') }}" style="color: var(--accent);">{{ __('app.add_now') }}</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($servers->hasPages())
    <div style="padding:16px 20px; border-top: 1px solid var(--border);">
        {{ $servers->links() }}
    </div>
    @endif
</div>
@endsection
