@extends('layouts.app')

@section('title', 'Công việc đang thực hiện')

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
        border-left: 4px solid #0dcaf0;
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
    
    .progress-bar {
        height: 8px;
        margin: 10px 0;
    }
</style>
@endpush

@section('content')
<div class="container-fluid task-list-container">
    <div class="container">
        <div class="mb-4">
            <h2>
                <i class="fas fa-play-circle"></i> Công việc đang thực hiện
            </h2>
            <p class="text-muted">Các công việc bạn đang làm</p>
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

                @if ($task->description)
                    <p class="mb-2" style="color: #6c757d;">{{ Str::limit($task->description, 150) }}</p>
                @endif

                <div>
                    <small class="text-muted">
                        <i class="fas fa-user"></i> Người giao: <strong>{{ $task->creator->name }}</strong>
                    </small>
                </div>

                @if ($task->due_date)
                    <div class="mt-2">
                        <small class="text-muted">
                            <i class="fas fa-calendar"></i>
                            Hạn chót: {{ $task->due_date->format('d/m/Y H:i') }}
                            @if ($task->isOverdue())
                                <span class="badge bg-danger ms-1">Quá hạn</span>
                            @endif
                        </small>
                    </div>
                @endif

                <div style="margin-top: 15px;">
                    <a href="{{ route('task-assignments.show', $task) }}" class="btn btn-info btn-sm">
                        <i class="fas fa-eye"></i> Chi tiết
                    </a>
                    <a href="{{ route('task-assignments.show', $task) }}" class="btn btn-success btn-sm">
                        <i class="fas fa-check"></i> Hoàn thành
                    </a>
                </div>
            </div>
        @empty
            <div class="card">
                <div class="card-body" style="text-align: center; padding: 60px 20px;">
                    <div style="font-size: 4rem; opacity: 0.3; margin-bottom: 20px;">
                        <i class="fas fa-play-circle"></i>
                    </div>
                    <h5>Không có công việc nào đang thực hiện</h5>
                    <p class="text-muted">Kiểm tra công việc được giao để bắt đầu làm</p>
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
