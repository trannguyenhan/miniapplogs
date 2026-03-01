@extends('layouts.app')
@section('title', 'Sửa người dùng: ' . $user->name)
@section('breadcrumb')
    <a href="{{ route('admin.users.index') }}" style="color: var(--text-secondary); text-decoration:none;">Users</a>
    <span style="color: var(--text-muted);">/</span>
    <span class="current">Sửa: {{ $user->name }}</span>
@endsection
@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Sửa: {{ $user->name }}</h1>
        <p class="page-subtitle">Cập nhật thông tin và phân quyền</p>
    </div>
</div>

<div style="max-width: 520px;">
    <form method="POST" action="{{ route('admin.users.update', $user) }}">
        @csrf @method('PUT')
        <div class="card">
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label" for="name">Họ tên <span class="required">*</span></label>
                    <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                    @error('name') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="email">Email <span class="required">*</span></label>
                    <input type="email" id="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
                    @error('email') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Mật khẩu mới</label>
                    <input type="password" id="password" name="password" class="form-control" minlength="8" placeholder="Để trống nếu không đổi">
                    <div class="form-hint">Tối thiểu 8 ký tự. Để trống để giữ nguyên mật khẩu hiện tại.</div>
                    @error('password') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="role">Quyền <span class="required">*</span></label>
                    <select id="role" name="role" class="form-control" required {{ $user->id === auth()->id() ? 'disabled' : '' }}>
                        <option value="user" {{ old('role', $user->role) == 'user' ? 'selected' : '' }}>
                            👤 User - Chỉ xem log
                        </option>
                        <option value="admin" {{ old('role', $user->role) == 'admin' ? 'selected' : '' }}>
                            🛡️ Admin - Toàn quyền
                        </option>
                    </select>
                    @if($user->id === auth()->id())
                        <input type="hidden" name="role" value="{{ $user->role }}">
                        <div class="form-hint" style="color: var(--warning);">Bạn không thể tự thay đổi quyền của mình.</div>
                    @endif
                    @error('role') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div style="display:flex; gap:10px; margin-top:8px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Cập nhật
                    </button>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Hủy
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
