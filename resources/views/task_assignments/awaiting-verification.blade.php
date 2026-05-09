@extends('layouts.app')

@section('title', 'Công việc chờ xác nhận')

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
    
    .completion-preview {
        background: #f8f9fa;
        border-radius: 6px;
        padding: 15px;
        margin-top: 10px;
        border-left: 3px solid #007bff;
    }
    
    .completion-preview h6 {
        color: #007bff;
        font-weight: 600;
        margin-bottom: 10px;
    }
    
    .completion-preview-text {
        color: #495057;
        font-size: 0.95rem;
        line-height: 1.5;
        max-height: 100px;
        overflow: hidden;
    }
</style>
@endpush

@section('content')
<div class="container-fluid task-list-container">
    <div class="container">
        <div class="mb-4">
            <h2>
                <i class="fas fa-hourglass-end"></i> Công việc chờ xác nhận
            </h2>
            <p class="text-muted">Các công việc đã được gửi và chờ xác nhận</p>
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

                <div class="completion-preview">
                    <h6><i class="fas fa-check"></i> Nội dung hoàn thành:</h6>
                    <div class="completion-preview-text">
                        {!! Str::limit($task->completion_content, 200) !!}
                    </div>
                </div>

                @if ($task->completionImages->isNotEmpty())
                    <div style="margin-top: 10px;">
                        <small class="text-muted">
                            <i class="fas fa-images"></i>
                            {{ $task->completionImages->count() }} hình ảnh đi kèm
                        </small>
                    </div>
                @endif

                <div style="margin-top: 15px;">
                    <a href="{{ route('task-assignments.show', $task) }}" class="btn btn-info btn-sm">
                        <i class="fas fa-eye"></i> Xem chi tiết
                    </a>
                </div>
            </div>
        @empty
            <div class="card">
                <div class="card-body" style="text-align: center; padding: 60px 20px;">
                    <div style="font-size: 4rem; opacity: 0.3; margin-bottom: 20px;">
                        <i class="fas fa-hourglass-end"></i>
                    </div>
                    <h5>Không có công việc nào chờ xác nhận</h5>
                    <p class="text-muted">Tất cả công việc của bạn đã được xác nhận hoặc từ chối</p>
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
