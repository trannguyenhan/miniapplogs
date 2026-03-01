@extends('layouts.app')
@section('title', $logApp->name)
@section('breadcrumb')
    <a href="{{ route('admin.log-apps.index') }}" style="color: var(--text-secondary); text-decoration:none;">Log Apps</a>
    <span style="color: var(--text-muted);">/</span>
    <span class="current">{{ $logApp->name }}</span>
@endsection
@section('header-actions')
    <a href="{{ route('logs.show', $logApp) }}" class="btn btn-success">
        <i class="fas fa-eye"></i> Xem log
    </a>
    <a href="{{ route('admin.log-apps.edit', $logApp) }}" class="btn btn-secondary">
        <i class="fas fa-edit"></i> Sửa
    </a>
@endsection
@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">{{ $logApp->name }}</h1>
        <p class="page-subtitle">{{ $logApp->description ?? 'Không có mô tả' }}</p>
    </div>
</div>
<div class="card" style="max-width:600px;">
    <div class="card-body">
        <table style="width:100%;">
            <tr><td style="color:var(--text-muted);padding:8px 0;width:140px;">Server</td><td>{{ $logApp->server->name }}</td></tr>
            <tr><td style="color:var(--text-muted);padding:8px 0;">IP Address</td><td><code style="font-family:'JetBrains Mono',monospace; color:var(--accent);">{{ $logApp->server->ip_address }}</code></td></tr>
            <tr><td style="color:var(--text-muted);padding:8px 0;">Log Path</td><td><code style="font-family:'JetBrains Mono',monospace; font-size:12px; color:var(--text-secondary);">{{ $logApp->log_path }}</code></td></tr>
            <tr><td style="color:var(--text-muted);padding:8px 0;">Trạng thái</td><td>
                @if($logApp->is_active) <span class="badge badge-success">Hoạt động</span>
                @else <span class="badge badge-danger">Tắt</span> @endif
            </td></tr>
        </table>
    </div>
</div>
@endsection
