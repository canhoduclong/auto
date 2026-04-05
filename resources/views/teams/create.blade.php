@extends('layouts.app')

@section('content')
<div class="container">
    <h2>{{ __('teams.create.title') }}</h2>

    <form method="POST" action="{{ route('teams.store') }}">
        @csrf

        <div class="mb-3">
            <label class="form-label">{{ __('teams.form.name') }}</label>
            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
        </div>

        <div class="mb-3">
            <label class="form-label">{{ __('teams.form.code') }}</label>
            <input type="text" name="code" class="form-control" value="{{ old('code') }}">
        </div>

        <div class="mb-3">
            <label class="form-label">{{ __('teams.form.note') }}</label>
            <textarea name="note" class="form-control" rows="3">{{ old('note') }}</textarea>
        </div>

        <button type="submit" class="btn btn-success">{{ __('common.actions.save') }}</button>
        <a href="{{ route('teams.index') }}" class="btn btn-secondary">{{ __('common.actions.cancel') }}</a>
    </form>
</div>
@endsection
