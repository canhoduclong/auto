@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Thêm nhà xe</h2>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('truck-stations.store') }}" method="POST">
        @csrf
        @include('truck_stations._form', ['truckStation' => null])

        <div class="mt-3">
            <button class="btn btn-primary">Lưu</button>
            <a href="{{ route('truck-stations.index') }}" class="btn btn-secondary">Hủy</a>
        </div>
    </form>
</div>
@endsection
