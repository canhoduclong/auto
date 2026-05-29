@extends('layouts.warehouse')

@section('title', 'Tất cả thông báo kho')

@section('content')
<div class="container py-4">
    <h2 class="mb-4">Tất cả thông báo công việc của Kho</h2>
    <div class="card">
        <div class="card-body">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Loại</th>
                        <th>Nội dung</th>
                        <th>Thời gian</th>
                        <th>Liên kết</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($notifications as $notify)
                    <tr>
                        <td>
                            @if($notify['type'] === 'warehouse')
                                <span class="badge bg-warning text-dark">Kho</span>
                            @elseif($notify['type'] === 'sale')
                                <span class="badge bg-info text-dark">Sale</span>
                            @elseif($notify['type'] === 'shipper')
                                <span class="badge bg-success">Shipper</span>
                            @else
                                <span class="badge bg-secondary">Khác</span>
                            @endif
                        </td>
                        <td>
                            <div class="fw-bold">{{ $notify['title'] }}</div>
                            <div class="text-muted small">{{ $notify['meta'] }}</div>
                        </td>
                        <td>{{ $notify['time'] ?? '' }}</td>
                        <td>
                            @if(!empty($notify['link']))
                                <a href="{{ $notify['link'] }}" class="btn btn-sm btn-outline-primary">Xem</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted">Không có thông báo nào.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
