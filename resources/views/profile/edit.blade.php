@extends('layouts.app')
@section('title', __('app.profile_title'))
@section('breadcrumb')
    <span class="current">{{ __('app.profile') }}</span>
@endsection
@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('app.profile_title') }}</h1>
        <p class="page-subtitle">{{ __('app.profile_subtitle') }}</p>
    </div>
</div>

<div style="max-width: 520px;">
    @if(session('success'))
        <div class="alert alert-success" style="margin-bottom:16px;">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
        </div>
    @endif

    <form method="POST" action="{{ route('profile.update') }}">
        @csrf @method('PUT')
        <div class="card">
            <div class="card-body">

                {{-- Name --}}
                <div class="form-group">
                    <label class="form-label" for="name">{{ __('app.full_name') }} <span class="required">*</span></label>
                    <input type="text" id="name" name="name" class="form-control"
                           value="{{ old('name', $user->name) }}" required>
                    @error('name') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                {{-- Email --}}
                <div class="form-group">
                    <label class="form-label" for="email">Email <span class="required">*</span></label>
                    <input type="email" id="email" name="email" class="form-control"
                           value="{{ old('email', $user->email) }}" required>
                    @error('email') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                {{-- Role (readonly) --}}
                <div class="form-group">
                    <label class="form-label">{{ __('app.role') }}</label>
                    <div style="display:flex; align-items:center; gap:8px; margin-top:4px;">
                        @if($user->isAdmin())
                            <span class="badge badge-purple"><i class="fas fa-shield-alt"></i> {{ __('app.role_admin') }}</span>
                        @else
                            <span class="badge badge-info"><i class="fas fa-user"></i> {{ __('app.role_user') }}</span>
                        @endif
                    </div>
                    <div class="form-hint">{{ __('app.role_cannot_change') }}</div>
                </div>

                <hr style="border-color: var(--border); margin: 20px 0;">
                <div style="font-size:13px; font-weight:600; color:var(--text-secondary); margin-bottom:14px;">
                    <i class="fas fa-lock"></i> {{ __('app.change_password') }}
                </div>

                {{-- Current password --}}
                <div class="form-group">
                      <label class="form-label" for="current_password">{{ __('app.current_password') }}</label>
                    <input type="password" id="current_password" name="current_password" class="form-control"
                          placeholder="{{ __('app.current_password_hint') }}">
                    @error('current_password') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                {{-- New password --}}
                <div class="form-group">
                      <label class="form-label" for="password">{{ __('app.new_password') }}</label>
                    <input type="password" id="password" name="password" class="form-control"
                          placeholder="{{ __('app.new_password_hint') }}" minlength="8">
                      <div class="form-hint">{{ __('app.min_8_chars') }}</div>
                    @error('password') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                {{-- Confirm password --}}
                <div class="form-group">
                      <label class="form-label" for="password_confirmation">{{ __('app.confirm_new_password') }}</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" class="form-control"
                          placeholder="{{ __('app.confirm_new_password_hint') }}">
                </div>

                <div style="display:flex; gap:10px; margin-top:8px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> {{ __('app.save_changes') }}
                    </button>
                    <a href="{{ route('logs.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times"></i> {{ __('app.btn_cancel') }}
                    </a>
                </div>

            </div>
        </div>
    </form>
</div>
@endsection
