@extends('layouts.app')
@section('title', __('app.add_user'))
@section('breadcrumb')
    <a href="{{ route('admin.users.index') }}" style="color: var(--text-secondary); text-decoration:none;">{{ __('app.nav_users') }}</a>
    <span style="color: var(--text-muted);">/</span>
    <span class="current">{{ __('app.add_user') }}</span>
@endsection
@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('app.add_user') }}</h1>
        <p class="page-subtitle">{{ __('app.user_add_subtitle') }}</p>
    </div>
</div>

<div style="max-width: 520px;">
    <form method="POST" action="{{ route('admin.users.store') }}">
        @csrf
        <div class="card">
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label" for="name">{{ __('app.full_name') }} <span class="required">*</span></label>
                    <input type="text" id="name" name="name" class="form-control" value="{{ old('name') }}" required>
                    @error('name') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="email">Email <span class="required">*</span></label>
                    <input type="email" id="email" name="email" class="form-control" value="{{ old('email') }}" required>
                    @error('email') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">{{ __('app.user_password') }} <span class="required">*</span></label>
                    <input type="password" id="password" name="password" class="form-control" required minlength="8">
                    <div class="form-hint">{{ __('app.min_8_chars') }}</div>
                    @error('password') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="role">{{ __('app.user_role') }} <span class="required">*</span></label>
                    <select id="role" name="role" class="form-control" required>
                        <option value="user" {{ old('role') == 'user' ? 'selected' : '' }}>
                            👤 {{ __('app.user_role_user_desc') }}
                        </option>
                        <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>
                            🛡️ {{ __('app.user_role_admin_desc') }}
                        </option>
                    </select>
                    @error('role') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div style="display:flex; gap:10px; margin-top:8px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> {{ __('app.add_user') }}
                    </button>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times"></i> {{ __('app.btn_cancel') }}
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
