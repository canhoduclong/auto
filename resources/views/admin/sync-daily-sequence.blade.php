@extends('layouts.admin')
@section('content')
<div class="container mt-4">
    <h2>Đồng bộ số thứ tự ưu tiên đơn hàng trong ngày</h2>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    <form method="POST" action="{{ route('admin.orders.sync_daily_sequence') }}">
        @csrf
        <div class="mb-3">
            <label for="sync-date" class="form-label">Chọn ngày cần đồng bộ</label>
            <input type="date" id="sync-date" name="date" class="form-control" value="{{ now()->toDateString() }}" required>
        </div>
        <button type="submit" class="btn btn-primary">Đồng bộ số thứ tự ưu tiên</button>
    </form>
</div>
@endsection
