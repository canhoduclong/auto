@extends('layouts.app')

@section('content')
   <div class="page-header">
        <div class="page-header-content d-lg-flex">
            <div class="d-flex">
                <h4 class="page-title mb-0">
                    Home - <span class="fw-normal">Dashboard</span>
                </h4>

                <a href="#page_header" class="btn btn-light align-self-center collapsed d-lg-none border-transparent rounded-pill p-0 ms-auto" data-bs-toggle="collapse">
                    <i class="ph-caret-down collapsible-indicator ph-sm m-1"></i>
                </a>
                <form method="POST" action="{{ route('logout') }}" class="d-inline">
                    @csrf
                    <button class="btn btn-outline-danger btn-sm">Đăng xuất</button>
                </form>
            </div>
        </div>
    </div>
    <div class="content pt-0"> 
        <div class="row">
            <div class="col-xl-7">        
                <p>Xin chào {{ auth()->user()->name }}, bạn có quyền quản lý toàn hệ thống.</p>
            </div>
            <div class="col-xl-5">
                
            </div>
        </div>
    </div>

@endsection