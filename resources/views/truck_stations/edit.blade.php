@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Chỉnh sửa nhà xe</h2>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('truck-stations.update', $truckStation) }}" method="POST">
        @csrf
        @method('PUT')
        @include('truck_stations._form', ['truckStation' => $truckStation])

        <div class="mt-3">
            <button class="btn btn-primary">Cập nhật</button>
            <a href="{{ route('truck-stations.index') }}" class="btn btn-secondary">Hủy</a>
        </div>
    </form>
</div>
@endsection
