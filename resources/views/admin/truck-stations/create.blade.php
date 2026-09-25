@extends('layouts.admin')

@section('content')
<div class="content-inner">
    <div class="page-header page-header-light">
        <div class="page-header-content d-flex">
            <div class="page-title">
                <h4><i class="ph-plus-circle me-2 text-primary"></i> Thêm Trạm xe mới</h4>
            </div>
            <div class="my-auto ms-auto">
                <a href="{{ route('admin.truck-stations.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="ph-arrow-left me-1"></i> Quay lại
                </a>
            </div>
        </div>
    </div>
    <div class="content-wrapper">
        @include('admin.truck-stations._form', [
            'station'  => null,
            'action'   => route('admin.truck-stations.store'),
            'method'   => 'POST',
            'brands'   => $brands,
            'provinces'=> $provinces,
            'wards'    => collect(),
        ])
    </div>
</div>
@endsection
