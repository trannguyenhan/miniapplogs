@extends('layouts.app')

@section('title', __('app.system_settings'))

@section('breadcrumb')
    <span>{{ __('app.nav_admin') }}</span>
    <span class="sep">/</span>
    <span class="current">{{ __('app.system_settings') }}</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('app.system_settings') }}</h1>
        <p class="page-subtitle">{{ __('app.system_settings_subtitle') }}</p>
    </div>
</div>

{{-- Authentication Method --}}
<div class="card" style="margin-bottom: 24px;">
    <div class="card-header">
        <i class="fas fa-shield-alt" style="color: var(--accent);"></i>
        <span class="card-title">{{ __('app.auth_method') }}</span>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.system-settings.update') }}" id="sso-form">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label class="form-label">{{ __('app.auth_method') }} <span class="required">*</span></label>
                <select name="auth_method" class="form-control" id="auth-method-select" style="max-width: 300px;">
                    <option value="local" {{ $authMethod === 'local' ? 'selected' : '' }}>{{ __('app.auth_local') }}</option>
                    <option value="sso" {{ $authMethod === 'sso' ? 'selected' : '' }}>{{ __('app.auth_sso_only') }}</option>
                    <option value="both" {{ $authMethod === 'both' ? 'selected' : '' }}>{{ __('app.auth_both') }}</option>
                </select>
                <div class="form-hint">{{ __('app.auth_method_hint') }}</div>
            </div>

            {{-- SSO Provider Config --}}
            <div id="sso-config" style="{{ $authMethod === 'local' ? 'display:none;' : '' }}">
                <hr style="border-color: var(--border); margin: 20px 0;">
                <h3 style="font-size: 15px; font-weight: 600; margin-bottom: 16px; color: var(--text-primary);">
                    <i class="fas fa-key" style="color: var(--info); margin-right: 6px;"></i>
                    {{ __('app.sso_provider_config') }}
                </h3>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label class="form-label">{{ __('app.sso_provider_name') }}</label>
                        <input type="text" name="sso_provider_name" class="form-control"
                               value="{{ old('sso_provider_name', $ssoConfig['provider_name']) }}"
                               placeholder="TuenSSO, Keycloak, Okta...">
                        <div class="form-hint">{{ __('app.sso_provider_name_hint') }}</div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">{{ __('app.sso_issuer_url') }} <span class="required">*</span></label>
                        <input type="url" name="sso_issuer_url" class="form-control"
                               value="{{ old('sso_issuer_url', $ssoConfig['issuer_url']) }}"
                               placeholder="https://sso.example.com">
                        <div class="form-hint">{{ __('app.sso_issuer_url_hint') }}</div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">{{ __('app.sso_client_id') }} <span class="required">*</span></label>
                        <input type="text" name="sso_client_id" class="form-control"
                               value="{{ old('sso_client_id', $ssoConfig['client_id']) }}"
                               placeholder="miniapplogs">
                    </div>

                    <div class="form-group">
                        <label class="form-label">{{ __('app.sso_client_secret') }}</label>
                        <input type="password" name="sso_client_secret" class="form-control"
                               value=""
                               placeholder="{{ $ssoConfig['client_secret'] ? '••••••••' : '' }}">
                        @if($ssoConfig['client_secret'])
                        <div class="form-hint">{{ __('app.password_saved') }}</div>
                        @endif
                    </div>

                    <div class="form-group">
                        <label class="form-label">{{ __('app.sso_scopes') }}</label>
                        <input type="text" name="sso_scopes" class="form-control"
                               value="{{ old('sso_scopes', $ssoConfig['scopes']) }}"
                               placeholder="openid profile email">
                        <div class="form-hint">{{ __('app.sso_scopes_hint') }}</div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">{{ __('app.sso_default_role') }}</label>
                        <select name="sso_default_role" class="form-control">
                            <option value="user" {{ ($ssoConfig['default_role'] ?? 'user') === 'user' ? 'selected' : '' }}>User</option>
                            <option value="admin" {{ ($ssoConfig['default_role'] ?? '') === 'admin' ? 'selected' : '' }}>Admin</option>
                        </select>
                        <div class="form-hint">{{ __('app.sso_default_role_hint') }}</div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">{{ __('app.sso_role_claim') }}</label>
                    <input type="text" name="sso_role_claim" class="form-control" style="max-width: 300px;"
                           value="{{ old('sso_role_claim', $ssoConfig['role_claim']) }}"
                           placeholder="roles">
                    <div class="form-hint">{{ __('app.sso_role_claim_hint') }}</div>
                </div>

                <hr style="border-color: var(--border); margin: 20px 0;">
                <h3 style="font-size: 15px; font-weight: 600; margin-bottom: 16px; color: var(--text-primary);">
                    <i class="fas fa-link" style="color: var(--warning); margin-right: 6px;"></i>
                    {{ __('app.sso_account_pages') }}
                </h3>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label class="form-label">{{ __('app.sso_profile_url') }}</label>
                        <input type="url" name="sso_profile_url" class="form-control"
                               value="{{ old('sso_profile_url', $ssoConfig['profile_url']) }}"
                               placeholder="https://sso.example.com/account">
                        <div class="form-hint">{{ __('app.sso_profile_url_hint') }}</div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">{{ __('app.sso_logout_url') }}</label>
                        <input type="url" name="sso_logout_url" class="form-control"
                               value="{{ old('sso_logout_url', $ssoConfig['logout_url']) }}"
                               placeholder="https://sso.example.com/connect/logout?...">
                        <div class="form-hint">{{ __('app.sso_logout_url_hint') }}</div>
                    </div>
                </div>
            </div>

            <div style="margin-top: 20px;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> {{ __('app.save_changes') }}
                </button>
            </div>
        </form>
    </div>
</div>

{{-- SSO Role Mappings --}}
<div class="card" id="role-mappings" style="{{ $authMethod === 'local' ? 'display:none;' : '' }}">
    <div class="card-header">
        <i class="fas fa-exchange-alt" style="color: var(--purple);"></i>
        <span class="card-title">{{ __('app.sso_role_mappings') }}</span>
    </div>
    <div class="card-body">
        <p style="font-size: 13px; color: var(--text-secondary); margin-bottom: 16px;">
            {{ __('app.sso_role_mappings_desc') }}
        </p>

        {{-- Add mapping form --}}
        <form method="POST" action="{{ route('admin.system-settings.mappings.store') }}" style="margin-bottom: 20px;">
            @csrf
            <div style="display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap;">
                <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 150px;">
                    <label class="form-label">{{ __('app.sso_claim_field') }}</label>
                    <input type="text" name="sso_claim_field" class="form-control" placeholder="roles, groups" required>
                </div>
                <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 150px;">
                    <label class="form-label">{{ __('app.sso_claim_value') }}</label>
                    <input type="text" name="sso_claim_value" class="form-control" placeholder="admin, devops" required>
                </div>
                <div class="form-group" style="margin-bottom: 0; min-width: 120px;">
                    <label class="form-label">{{ __('app.local_role') }}</label>
                    <select name="local_role" class="form-control" required>
                        <option value="admin">Admin</option>
                        <option value="user">User</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" style="height: 36px;">
                    <i class="fas fa-plus"></i> {{ __('app.btn_add') }}
                </button>
            </div>
        </form>

        {{-- Existing mappings --}}
        @if($mappings->count() > 0)
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>{{ __('app.sso_claim_field') }}</th>
                        <th>{{ __('app.sso_claim_value') }}</th>
                        <th>→ {{ __('app.local_role') }}</th>
                        <th style="width: 80px;">{{ __('app.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($mappings as $mapping)
                    <tr>
                        <td><code style="background: var(--bg-primary); padding: 2px 8px; border-radius: 4px; font-size: 12px;">{{ $mapping->sso_claim_field }}</code></td>
                        <td><code style="background: var(--bg-primary); padding: 2px 8px; border-radius: 4px; font-size: 12px;">{{ $mapping->sso_claim_value }}</code></td>
                        <td>
                            @if($mapping->local_role === 'admin')
                                <span class="badge badge-purple"><i class="fas fa-shield-alt"></i> Admin</span>
                            @else
                                <span class="badge badge-info"><i class="fas fa-user"></i> User</span>
                            @endif
                        </td>
                        <td>
                            <form method="POST" action="{{ route('admin.system-settings.mappings.destroy', $mapping) }}"
                                  onsubmit="return confirmDelete(event, '{{ __('app.confirm_delete_mapping') }}')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm btn-icon" title="{{ __('app.btn_delete') }}">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div style="text-align: center; padding: 20px; color: var(--text-muted); font-size: 13px;">
            <i class="fas fa-info-circle" style="margin-right: 4px;"></i>
            {{ __('app.no_mappings') }}
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
document.getElementById('auth-method-select').addEventListener('change', function() {
    const showSso = this.value !== 'local';
    document.getElementById('sso-config').style.display = showSso ? '' : 'none';
    document.getElementById('role-mappings').style.display = showSso ? '' : 'none';
});
</script>
@endpush
@endsection
