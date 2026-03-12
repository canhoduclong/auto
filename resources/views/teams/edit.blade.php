@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Cập nhật team</h2>

    <form method="POST" action="{{ route('teams.update', $team) }}">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label class="form-label">Tên team</label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $team->name) }}" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Mã team</label>
            <input type="text" name="code" class="form-control" value="{{ old('code', $team->code) }}">
        </div>

        <div class="mb-3">
            <label class="form-label">Ghi chú</label>
            <textarea name="note" class="form-control" rows="3">{{ old('note', $team->note) }}</textarea>
        </div>

        <button type="submit" class="btn btn-primary">Cập nhật</button>
        <a href="{{ route('teams.index') }}" class="btn btn-secondary">Hủy</a>
    </form>
</div>
@endsection
