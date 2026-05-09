@extends('layouts.app')

@section('title', 'Danh sách xác nhận hoàn thành')

@push('styles')
<style>
    .task-list-container {
        background: #f8f9fa;
        min-height: 100vh;
        padding: 20px 0;
    }
    
    .task-item {
        background: white;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 15px;
        border-left: 4px solid #ffc107;
        transition: all 0.2s ease;
    }
    
    .task-item:hover {
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        transform: translateY(-2px);
    }
    
    .task-item-header {
        display: flex;
        justify-content: space-between;
        align-items: start;
        margin-bottom: 10px;
    }
    
    .task-item-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: #333;
    }
    
    .completion-info {
        background: #f8f9fa;
        border-radius: 6px;
        padding: 15px;
        margin: 15px 0;
        border-left: 3px solid #ffc107;
    }
    
    .completion-info h6 {
        color: #ffc107;
        font-weight: 600;
        margin-bottom: 10px;
    }
    
    .image-count {
        display: inline-block;
        padding: 3px 10px;
        background: #e9ecef;
        border-radius: 4px;
        font-size: 0.9rem;
    }
</style>
@endpush

@section('content')
<div class="container-fluid task-list-container">
    <div class="container">
        <div class="mb-4">
            <h2>
                <i class="fas fa-check-double"></i> Xác nhận hoàn thành công việc
            </h2>
            <p class="text-muted">Danh sách các công việc chờ xác nhận</p>
        </div>

        {{-- Task List --}}
        @forelse ($tasks as $task)
            <div class="task-item">
                <div class="task-item-header">
                    <div>
                        <div class="task-item-title">{{ $task->title }}</div>
                        <div class="text-muted small">{{ $task->code }}</div>
                    </div>
                    <div>
                        <x-task-status-badge :status="$task->status" style="pill" />
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-8">
                        <div class="completion-info">
                            <h6><i class="fas fa-clipboard-check"></i> Nội dung hoàn thành:</h6>
                            <div style="color: #495057; font-size: 0.95rem; line-height: 1.5; max-height: 80px; overflow: hidden;">
                                {!! nl2br(e(Str::limit($task->completion_content, 300))) !!}
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 6px;">
                            <p class="mb-2">
                                <strong>Thực hiện bởi:</strong><br>
                                {{ $task->assignees->first()?->user->name ?? 'N/A' }}
                            </p>
                            <p class="mb-0">
                                <strong>Giao bởi:</strong><br>
                                {{ $task->creator->name }}
                            </p>
                        </div>
                    </div>
                </div>

                @if ($task->completionImages->isNotEmpty())
                    <div style="margin-top: 15px;">
                        <small class="text-muted">
                            <span class="image-count">
                                <i class="fas fa-images"></i> {{ $task->completionImages->count() }} hình ảnh
                            </span>
                        </small>
                    </div>
                @endif

                <div style="margin-top: 15px;">
                    <a href="{{ route('task-assignments.show', $task) }}" class="btn btn-info btn-sm">
                        <i class="fas fa-eye"></i> Xem chi tiết & Xác nhận
                    </a>
                    <a href="{{ route('task-assignments.show', $task) }}" class="btn btn-success btn-sm">
                        <i class="fas fa-check"></i> Phê duyệt
                    </a>
                    <a href="{{ route('task-assignments.show', $task) }}" class="btn btn-danger btn-sm">
                        <i class="fas fa-times"></i> Từ chối
                    </a>
                </div>
            </div>
        @empty
            <div class="card">
                <div class="card-body" style="text-align: center; padding: 60px 20px;">
                    <div style="font-size: 4rem; opacity: 0.3; margin-bottom: 20px;">
                        <i class="fas fa-check-double"></i>
                    </div>
                    <h5>Không có công việc nào chờ xác nhận</h5>
                    <p class="text-muted">Tất cả công việc đều đã được xác nhận</p>
                </div>
            </div>
        @endforelse

        {{-- Pagination --}}
        <div class="d-flex justify-content-center mt-4">
            {{ $tasks->links() }}
        </div>
    </div>
</div>
@endsection
