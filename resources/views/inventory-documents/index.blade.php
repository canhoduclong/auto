@extends('layouts.app')

@section('title', 'Inventory Documents')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Inventory Documents</h1>
        <a href="{{ route('inventory-documents.create') }}" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm"><i class="fas fa-plus fa-sm text-white-50"></i> Add Inventory Document</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Inventory Document List</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Type</th>
                            <th>Warehouse</th>
                            <th>Date</th>
                            <th>User</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($inventoryDocuments as $document)
                            <tr>
                                <td>{{ $document->id }}</td>
                                <td>{{ $document->type }}</td>
                                <td>{{ $document->warehouse->name }}</td>
                                <td>{{ $document->document_date }}</td>
                                <td>{{ $document->user->name ?? 'N/A' }}</td>
                                <td>
                                    <form action="{{ route('inventory-documents.destroy', $document->id) }}" method="POST">
                                        <a href="{{ route('inventory-documents.show', $document->id) }}" class="btn btn-sm btn-info">View</a>
                                        <a href="{{ route('inventory-documents.edit', $document->id) }}" class="btn btn-sm btn-warning">Edit</a>
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">No inventory documents found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-center">
                {{ $inventoryDocuments->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
