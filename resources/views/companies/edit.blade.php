@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Sửa thông tin: {{ $company->name }}</h2>

    <form action="{{ route('companies.update', $company) }}" method="POST">
        @csrf
        @method('PUT')
        @include('companies._form', ['company' => $company])
        <div class="mt-3">
            <button class="btn btn-primary">Cập nhật</button>
            <a href="{{ route('companies.index') }}" class="btn btn-secondary">Hủy</a>
        </div>
    </form>
</div>
@endsection