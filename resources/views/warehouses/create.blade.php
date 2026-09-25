@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Create Warehouse</h1>
    <form action="{{ route('warehouses.store') }}" method="POST">
        @csrf
        @include('warehouses._form')
        <button type="submit" class="btn btn-primary">Create</button>
    </form>
</div>
@endsection