@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Warehouses</h1>
    <div class="mb-3">
        <a href="{{ route('warehouses.create') }}" class="btn btn-primary">Create Warehouse</a>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Address</th>
                <th>Phone</th>
                <th>Status</th>
                <th>Created At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($warehouses as $warehouse)
            <tr>
                <td>{{ $warehouse->id }}</td>
                <td>{{ $warehouse->name }}</td>
                <td>{{ $warehouse->address }}</td>
                <td>{{ $warehouse->phone }}</td>
                <td>
                    @if($warehouse->status)
                        <span class="badge bg-success">Active</span>
                    @else
                        <span class="badge bg-danger">Inactive</span>
                    @endif
                </td>
                <td>{{ $warehouse->created_at }}</td>
                <td>
                    <a href="{{ route('warehouses.edit', $warehouse) }}" class="btn btn-sm btn-warning">Edit</a>
                    <form action="{{ route('warehouses.destroy', $warehouse) }}" method="POST" style="display:inline-block;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    {{ $warehouses->links() }}
</div>
@endsection