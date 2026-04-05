@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Quy trình xét duyệt</h4>
        <a href="{{ route('approval-workflows.create') }}" class="btn btn-primary">Tạo quy trình</a>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Mã quy trình</th>
                            <th>Tên quy trình</th>
                            <th>Trạng thái</th>
                            <th>Các bước</th>
                            <th>Ngày tạo</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($workflows as $workflow)
                            <tr>
                                <td>{{ $workflow->id }}</td>
                                <td>{{ $workflow->code }}</td>
                                <td>{{ $workflow->name }}</td>
                                <td>
                                    @if($workflow->is_active)
                                        <span class="badge bg-success">Đang áp dụng</span>
                                    @else
                                        <span class="badge bg-secondary">Không hoạt động</span>
                                    @endif
                                </td>
                                <td>
                                    @foreach($workflow->steps->sortBy('step_order') as $step)
                                        <span class="badge bg-info text-dark me-1 mb-1">
                                            B{{ $step->step_order }}: {{ $step->role_slug }}
                                        </span>
                                    @endforeach
                                </td>
                                <td>{{ $workflow->created_at }}</td>
                                <td>
                                    <a href="{{ route('approval-workflows.edit', $workflow) }}" class="btn btn-sm btn-warning">Sửa</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">Chưa có quy trình nào.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-3">
        {{ $workflows->links() }}
    </div>
</div>
@endsection
