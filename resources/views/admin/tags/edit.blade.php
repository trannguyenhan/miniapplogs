@extends('layouts.app')
@section('title', __('app.edit_tag'))
@section('breadcrumb')
    <a href="{{ route('admin.tags.index') }}" style="color: var(--text-secondary); text-decoration:none;">{{ __('app.nav_tags') }}</a>
    <span style="color: var(--text-muted);">/</span>
    <span class="current">{{ __('app.edit_tag') }}: {{ $tag->name }}</span>
@endsection
@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('app.edit_tag') }}: {{ $tag->name }}</h1>
        <p class="page-subtitle">{{ __('app.edit_tag_subtitle') }}</p>
    </div>
</div>

<div style="max-width: 520px;">
    <form method="POST" action="{{ route('admin.tags.update', $tag) }}">
        @csrf @method('PUT')
        <div class="card">
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label" for="name">{{ __('app.tag_name') }} <span class="required">*</span></label>
                    <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $tag->name) }}" required maxlength="100">
                    @error('name') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div style="display:flex; gap:10px; margin-top:8px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> {{ __('app.btn_update') }}
                    </button>
                    <a href="{{ route('admin.tags.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times"></i> {{ __('app.btn_cancel') }}
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
