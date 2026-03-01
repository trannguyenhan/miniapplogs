@extends('layouts.app')
@section('title', 'Sửa: ' . $logApp->name)
@section('breadcrumb')
    <a href="{{ route('admin.log-apps.index') }}" style="color: var(--text-secondary); text-decoration:none;">Log Apps</a>
    <span style="color: var(--text-muted);">/</span>
    <span class="current">Sửa: {{ $logApp->name }}</span>
@endsection
@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Sửa: {{ $logApp->name }}</h1>
        <p class="page-subtitle">Cập nhật thông tin ứng dụng log</p>
    </div>
</div>

<div style="max-width: 620px;">
    <form method="POST" action="{{ route('admin.log-apps.update', $logApp) }}">
        @csrf @method('PUT')
        <div class="card">
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label" for="server_id">Server <span class="required">*</span></label>
                    <select id="server_id" name="server_id" class="form-control" required>
                        <option value="">-- Chọn server --</option>
                        @foreach($servers as $server)
                            <option value="{{ $server->id }}" {{ old('server_id', $logApp->server_id) == $server->id ? 'selected' : '' }}>
                                {{ $server->name }} ({{ $server->ip_address }})
                            </option>
                        @endforeach
                    </select>
                    @error('server_id') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="name">Tên ứng dụng <span class="required">*</span></label>
                    <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $logApp->name) }}" required>
                    @error('name') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="log_path">Đường dẫn Log <span class="required">*</span></label>
                    <input type="text" id="log_path" name="log_path" class="form-control"
                           value="{{ old('log_path', $logApp->log_path) }}"
                           style="font-family:'JetBrains Mono',monospace; font-size:13px;"
                           required>
                    @error('log_path') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="description">Mô tả</label>
                    <textarea id="description" name="description" class="form-control" rows="2">{{ old('description', $logApp->description) }}</textarea>
                </div>

                <div class="toggle-wrapper">
                    <label class="toggle">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $logApp->is_active) ? 'checked' : '' }}>
                        <span class="toggle-slider"></span>
                    </label>
                    <span class="form-label" style="margin:0;">Kích hoạt</span>
                </div>

                <div style="display:flex; gap:10px; margin-top:24px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Cập nhật
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
