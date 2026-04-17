@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h2>Tạo mới Tỉnh / Thành phố</h2>
        <a href="{{ route('provinces.index') }}" class="btn btn-secondary">Quay lại</a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('provinces.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label class="form-label">Tên Tỉnh / Thành phố</label>
            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Danh sách Phường / Xã</label>
            <textarea name="wards" class="form-control" rows="6" placeholder="Nhập mỗi phường/xã trên một dòng">{{ old('wards') }}</textarea>
            <div class="form-text">Mỗi dòng sẽ tạo một phường/xã riêng.</div>
        </div>
        <button type="submit" class="btn btn-primary">Lưu</button>
    </form>
</div>
@endsection
