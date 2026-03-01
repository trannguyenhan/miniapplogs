@extends('layouts.app')
@section('title', 'Thêm người dùng')
@section('breadcrumb')
    <a href="{{ route('admin.users.index') }}" style="color: var(--text-secondary); text-decoration:none;">Users</a>
    <span style="color: var(--text-muted);">/</span>
    <span class="current">Thêm mới</span>
@endsection
@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Thêm người dùng</h1>
        <p class="page-subtitle">Tạo tài khoản và phân quyền truy cập</p>
    </div>
</div>

<div style="max-width: 520px;">
    <form method="POST" action="{{ route('admin.users.store') }}">
        @csrf
        <div class="card">
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label" for="name">Họ tên <span class="required">*</span></label>
                    <input type="text" id="name" name="name" class="form-control" value="{{ old('name') }}" required>
                    @error('name') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="email">Email <span class="required">*</span></label>
                    <input type="email" id="email" name="email" class="form-control" value="{{ old('email') }}" required>
                    @error('email') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Mật khẩu <span class="required">*</span></label>
                    <input type="password" id="password" name="password" class="form-control" required minlength="8">
                    <div class="form-hint">Tối thiểu 8 ký tự</div>
                    @error('password') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="role">Quyền <span class="required">*</span></label>
                    <select id="role" name="role" class="form-control" required>
                        <option value="user" {{ old('role') == 'user' ? 'selected' : '' }}>
                            👤 User - Chỉ xem log
                        </option>
                        <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>
                            🛡️ Admin - Toàn quyền
                        </option>
                    </select>
                    @error('role') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div style="display:flex; gap:10px; margin-top:8px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Tạo tài khoản
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
