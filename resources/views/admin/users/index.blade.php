@extends('layouts.app')
@section('title', __('app.user_list'))
@section('breadcrumb')
    <i class="fas fa-users" style="color: var(--accent);"></i>
    <span class="current">{{ __('app.nav_users') }}</span>
@endsection
@section('header-actions')
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
        <i class="fas fa-user-plus"></i> {{ __('app.btn_add_user') }}
    </a>
@endsection
@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('app.nav_users') }}</h1>
        <p class="page-subtitle">{{ __('app.user_subtitle') }}</p>
    </div>
</div>

<div class="card">
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ __('app.user_name') }}</th>
                    <th>{{ __('app.user_email') }}</th>
                    <th>{{ __('app.user_role') }}</th>
                    <th>{{ __('app.created_at') }}</th>
                    <th>{{ __('app.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                <tr>
                    <td style="color: var(--text-muted);">{{ $user->id }}</td>
                    <td>
                        <div style="display:flex; align-items:center; gap:10px;">
                            <div style="width:32px; height:32px; background: #7c3aed; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:13px; font-weight:600; color:white; flex-shrink:0;">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                            <div>
                                <div style="font-weight:500;">{{ $user->name }}</div>
                                @if($user->id === auth()->id())
                                    <div style="font-size:10px; color: var(--accent);">{{ __('app.user_you') }}</div>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td style="color: var(--text-secondary);">{{ $user->email }}</td>
                    <td>
                        @if($user->role === 'admin')
                            <span class="badge badge-purple"><i class="fas fa-shield-alt"></i> {{ __('app.role_admin') }}</span>
                        @else
                            <span class="badge badge-info"><i class="fas fa-user"></i> {{ __('app.role_user') }}</span>
                        @endif
                    </td>
                    <td style="color: var(--text-muted); font-size:12px;">{{ $user->created_at->format('d/m/Y H:i') }}</td>
                    <td>
                        <div style="display:flex; gap:6px;">
                            <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-secondary">
                                <i class="fas fa-edit"></i>
                            </a>
                            @if($user->id !== auth()->id())
                            <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                                  onsubmit="return confirmDelete(event, '{{ __('app.confirm_delete_user', ['name' => $user->name]) }}')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align:center; padding:40px; color: var(--text-muted);">
                        {{ __('app.no_users_found') }}
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($users->hasPages())
    <div style="padding:16px 20px; border-top: 1px solid var(--border);">
        {{ $users->links() }}
    </div>
    @endif
</div>
@endsection
