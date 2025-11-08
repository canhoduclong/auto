@extends('layouts.app')

@section('title', 'Add Inventory Movement')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Add Inventory Movement</h1>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">New Inventory Movement Details</h6>
        </div>
        <div class="card-body">
            <form action="{{ route('inventory-movements.store') }}" method="POST">
                @csrf
                @include('inventory-movements._form')
            </form>
        </div>
    </div>
</div>
@endsection
