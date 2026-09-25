@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Thêm công ty mới</h2>

    <form action="{{ route('companies.store') }}" method="POST">
        @csrf
        @include('companies._form', ['company' => null])
        <div class="mt-3">
            <button class="btn btn-success">Lưu</button>
            <a href="{{ route('companies.index') }}" class="btn btn-secondary">Hủy</a>
        </div>
    </form>
</div>
@endsection