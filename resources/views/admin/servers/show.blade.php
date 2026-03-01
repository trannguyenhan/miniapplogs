@extends('layouts.app')
@section('title', 'Server: ' . $server->name)
@section('breadcrumb')
    <a href="{{ route('admin.servers.index') }}" style="color: var(--text-secondary); text-decoration:none;">Servers</a>
    <span style="color: var(--text-muted);">/</span>
    <span class="current">{{ $server->name }}</span>
@endsection
@section('header-actions')
    <a href="{{ route('admin.servers.edit', $server) }}" class="btn btn-secondary">
        <i class="fas fa-edit"></i> Sửa
    </a>
@endsection
@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">{{ $server->name }}</h1>
        <p class="page-subtitle">
            <code style="font-family:'JetBrains Mono',monospace; color: var(--accent);">{{ $server->ip_address }}:{{ $server->ssh_port }}</code>
            · User: <code style="font-family:'JetBrains Mono',monospace; color: var(--text-secondary);">{{ $server->ssh_user }}</code>
        </p>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <i class="fas fa-file-alt" style="color: var(--accent);"></i>
        <span class="card-title">Danh sách ứng dụng log ({{ $server->logApplications->count() }})</span>
        <div style="margin-left:auto;">
            <a href="{{ route('admin.log-apps.create') }}?server={{ $server->id }}" class="btn btn-sm btn-primary">
                <i class="fas fa-plus"></i> Thêm ứng dụng
            </a>
        </div>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Tên</th>
                    <th>Đường dẫn log</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($server->logApplications as $app)
                <tr>
                    <td style="font-weight:500;">{{ $app->name }}</td>
                    <td><code style="font-family:'JetBrains Mono',monospace; font-size:11px; color: var(--text-secondary);">{{ $app->log_path }}</code></td>
                    <td>
                        @if($app->is_active)
                            <span class="badge badge-success">Hoạt động</span>
                        @else
                            <span class="badge badge-danger">Tắt</span>
                        @endif
                    </td>
                    <td>
                        <div style="display:flex; gap:6px;">
                            @if($app->is_active)
                            <a href="{{ route('logs.show', $app) }}" class="btn btn-sm btn-success">
                                <i class="fas fa-eye"></i> Xem log
                            </a>
                            @endif
                            <a href="{{ route('admin.log-apps.edit', $app) }}" class="btn btn-sm btn-secondary">
                                <i class="fas fa-edit"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" style="text-align:center; padding:30px; color: var(--text-muted);">
                        Chưa có ứng dụng nào trên server này.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
