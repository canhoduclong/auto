@extends('layouts.admin')

@section('content')
<div class="content-inner">
    <div class="page-header page-header-light">
        <div class="page-header-content d-flex">
            <div class="page-title">
                <h4><i class="ph-pencil me-2 text-primary"></i> Sửa tuyến: {{ $truckRoute->name }}</h4>
            </div>
            <div class="my-auto ms-auto">
                <a href="{{ route('admin.truck-routes.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="ph-arrow-left me-1"></i> Quay lại
                </a>
            </div>
        </div>
    </div>
    <div class="content-wrapper">
        @include('admin.truck-routes._form', [
            'route'    => $truckRoute,
            'action'   => route('admin.truck-routes.update', $truckRoute),
            'method'   => 'PUT',
            'brands'   => $brands,
            'provinces'=> $provinces,
            'stations' => $stations,
            'existingStops' => $truckRoute->stops,
        ])
    </div>
</div>
@endsection
