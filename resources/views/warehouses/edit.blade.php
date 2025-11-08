@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Edit Warehouse</h1>
    <form action="{{ route('warehouses.update', $warehouse) }}" method="POST">
        @csrf
        @method('PUT')
        @include('warehouses._form')
        <button type="submit" class="btn btn-primary">Update</button>
    </form>
</div>
@endsection