@extends('layouts.app')

@section('title', 'Edit Inventory Record')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Edit Inventory Record</h1>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Edit Inventory Details</h6>
        </div>
        <div class="card-body">
            <form action="{{ route('inventories.update', $inventory->id) }}" method="POST">
                @csrf
                @method('PUT')
                @include('inventories._form')
            </form>
        </div>
    </div>
</div>
@endsection
