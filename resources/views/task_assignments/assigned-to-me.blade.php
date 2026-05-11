@extends($layout ?? 'layouts.app')

@section('title', 'Công việc được giao cho tôi')

@push('styles')
<style>
    .task-list-container {
        background: #f8f9fa;
        min-height: 100vh;
        padding: 20px 0;
    }
    
    .task-filters {
        background: white;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }
    
    .task-item {
        background: white;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 15px;
        border-left: 4px solid #007bff;
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
    
    .task-item-code {
        color: #6c757d;
        font-size: 0.9rem;
        font-family: monospace;
    }
    
    .task-item-meta {
        display: flex;
        gap: 20px;
        margin-top: 10px;
        padding-top: 10px;
        border-top: 1px solid #e9ecef;
        font-size: 0.9rem;
    }
    
    .task-item-meta-item {
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .task-item-actions {
        display: flex;
        gap: 5px;
    }
    
    .task-item-actions .btn {
        padding: 5px 15px;
        font-size: 0.85rem;
    }
    
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #6c757d;
    }
    
    .empty-state-icon {
        font-size: 4rem;
        opacity: 0.3;
        margin-bottom: 20px;
    }
</style>
@endpush

@section('content')
<div class="container-fluid task-list-container">
    <div class="container">
        <div class="mb-4">
            <h2>
                <i class="fas fa-inbox"></i> Công việc được giao cho tôi
            </h2>
            <p class="text-muted">Danh sách các công việc được giao bởi người quản lý</p>
        </div>

        {{-- Filters --}}
        <div class="task-filters">
            <form action="{{ route($filterRoute ?? 'task-assignments.assigned-to-me') }}" method="GET" class="row g-3">
                <div class="col-md-4">
                    <input type="text" class="form-control" name="search" placeholder="Tìm kiếm..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="status">
                        <option value="">-- Tất cả trạng thái --</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Chờ thực hiện</option>
                        <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>Đang thực hiện</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Chờ xác nhận</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="priority">
                        <option value="">-- Tất cả mức độ --</option>
                        <option value="low" {{ request('priority') === 'low' ? 'selected' : '' }}>Thấp</option>
                        <option value="medium" {{ request('priority') === 'medium' ? 'selected' : '' }}>Trung bình</option>
                        <option value="high" {{ request('priority') === 'high' ? 'selected' : '' }}>Cao</option>
                        <option value="urgent" {{ request('priority') === 'urgent' ? 'selected' : '' }}>Khẩn cấp</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i> Tìm
                    </button>
                </div>
            </form>
        </div>

        {{-- Task List --}}
        @forelse ($tasks as $task)
            <div class="task-item">
                <div class="task-item-header">
                    <div>
                        <div class="task-item-title">{{ $task->title }}</div>
                        <div class="task-item-code">{{ $task->code }}</div>
                    </div>
                    <div>
                        <x-task-status-badge :status="$task->status" style="pill" />
                    </div>
                </div>

                @if ($task->description)
                    <p class="mb-2" style="color: #6c757d;">{{ Str::limit($task->description, 150) }}</p>
                @endif

                <div class="task-item-meta">
                    <div class="task-item-meta-item">
                        <i class="fas fa-user"></i>
                        <strong>{{ $task->creator->name }}</strong>
                    </div>
                    <div class="task-item-meta-item">
                        <i class="fas fa-signal"></i>
                        <span class="badge bg-secondary">{{ $task->getPriorityLabel() }}</span>
                    </div>
                    @if ($task->due_date)
                        <div class="task-item-meta-item">
                            <i class="fas fa-calendar"></i>
                            {{ $task->due_date->format('d/m/Y') }}
                            @if ($task->isOverdue())
                                <span class="badge bg-danger ms-1">Quá hạn</span>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="task-item-actions mt-3">
                    <a href="{{ route(auth()->user()?->isSalesFlowRole() ? 'tasks.show' : 'task-assignments.show', $task) }}" class="btn btn-info btn-sm">
                        <i class="fas fa-eye"></i> Chi tiết
                    </a>
                    
                    @if ($task->status !== 'completed' && $task->status !== 'done' && $task->status !== 'rejected')
                        <a href="{{ route(auth()->user()?->isSalesFlowRole() ? 'tasks.show' : 'task-assignments.show', $task) }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-edit"></i> Cập nhật
                        </a>
                    @endif

                    @if ($task->canBeCompleted())
                        <a href="{{ route('task-assignments.complete-form', $task) }}" class="btn btn-success btn-sm">
                            <i class="fas fa-check"></i> Hoàn thành
                        </a>
                    @endif
                </div>
            </div>
        @empty
            <div class="card">
                <div class="card-body empty-state">
                    <div class="empty-state-icon">
                        <i class="fas fa-inbox"></i>
                    </div>
                    <h5>Không có công việc nào</h5>
                    <p class="text-muted">Bạn chưa được giao công việc nào. Hãy chờ quản lý giao việc!</p>
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
