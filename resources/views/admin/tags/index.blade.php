@extends('layouts.app')
@section('title', __('app.tag_list'))
@section('breadcrumb')
    <i class="fas fa-tags" style="color: var(--accent);"></i>
    <span class="current">{{ __('app.nav_tags') }}</span>
@endsection
@section('header-actions')
    <a href="{{ route('admin.tags.create') }}" class="btn btn-primary">
        <i class="fas fa-plus"></i> {{ __('app.add_tag') }}
    </a>
@endsection
@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('app.tag_list') }}</h1>
        <p class="page-subtitle">{{ __('app.tag_list_subtitle') }}</p>
    </div>
</div>

<div class="card">
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ __('app.tag_name') }}</th>
                    <th>{{ __('app.created_at') }}</th>
                    <th>{{ __('app.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tags as $tag)
                <tr>
                    <td style="color: var(--text-muted);">{{ $tag->id }}</td>
                    <td>
                        <span class="badge badge-info"><i class="fas fa-tag"></i> {{ $tag->name }}</span>
                    </td>
                    <td style="color: var(--text-muted); font-size:12px;">{{ $tag->created_at->format('d/m/Y H:i') }}</td>
                    <td>
                        <div style="display:flex; gap:6px;">
                            <a href="{{ route('admin.tags.edit', $tag) }}" class="btn btn-sm btn-secondary">
                                <i class="fas fa-edit"></i>
                            </a>
                                <form method="POST" action="{{ route('admin.tags.destroy', $tag) }}"
                                    data-confirm-message="{{ __('app.confirm_delete_tag', ['name' => $tag->name]) }}"
                                    onsubmit="return confirmDelete(event, this.dataset.confirmMessage)">
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
                    <td colspan="4" style="text-align:center; padding:40px; color: var(--text-muted);">
                        {{ __('app.no_tags_found') }}
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($tags->hasPages())
    <div style="padding:16px 20px; border-top: 1px solid var(--border);">
        {{ $tags->links() }}
    </div>
    @endif
</div>
@endsection
