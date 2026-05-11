@extends($layout ?? 'layouts.app')

@section('title', 'Lịch sử công việc')

@push('styles')
<style>
    .history-item {
        background: white;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 15px;
        border-left: 4px solid #6c757d;
        display: flex;
        gap: 20px;
    }
    
    .history-item.status-done {
        border-left-color: #28a745;
        background: #f8fff9;
    }
    
    .history-item.status-rejected {
        border-left-color: #dc3545;
        background: #fff8f8;
    }
    
    .history-item-status {
        flex-shrink: 0;
        width: 80px;
        text-align: center;
    }
    
    .history-item-status-badge {
        display: inline-block;
        padding: 10px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.85rem;
    }
    
    .history-item-content {
        flex: 1;
    }
    
    .history-item-title {
        font-size: 1rem;
        font-weight: 600;
        color: #333;
        margin-bottom: 5px;
    }
    
    .history-item-info {
        display: flex;
        gap: 15px;
        font-size: 0.9rem;
        color: #6c757d;
    }
    
    .history-item-info-item {
        display: flex;
        align-items: center;
        gap: 5px;
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
<div class="container mt-4">
    <div class="mb-4">
        <h2>
            <i class="fas fa-history"></i> Lịch sử công việc
        </h2>
        <p class="text-muted">Các công việc bạn đã hoàn thành</p>
    </div>

    {{-- History List --}}
    @forelse ($tasks as $task)
        <div class="history-item status-{{ $task->status }}">
            <div class="history-item-status">
                <div class="history-item-status-badge bg-{{ $task->getStatusColor() }} text-white">
                    <i class="fas fa-{{ $task->status === 'done' ? 'check-circle' : 'times-circle' }}"></i>
                    <br>
                    <small>{{ $task->status === 'done' ? 'Hoàn thành' : 'Từ chối' }}</small>
                </div>
            </div>
            <div class="history-item-content">
                <div class="history-item-title">{{ $task->title }}</div>
                <div class="history-item-info">
                    <div class="history-item-info-item">
                        <i class="fas fa-code"></i>
                        {{ $task->code }}
                    </div>
                    <div class="history-item-info-item">
                        <i class="fas fa-user"></i>
                        {{ $task->creator->name }}
                    </div>
                    <div class="history-item-info-item">
                        <i class="fas fa-clock"></i>
                        {{ $task->completed_at?->format('d/m/Y H:i') ?? 'N/A' }}
                    </div>
                    <div class="history-item-info-item">
                        <i class="fas fa-signal"></i>
                        <span class="badge bg-secondary">{{ $task->getPriorityLabel() }}</span>
                    </div>
                </div>
                
                @if ($task->status === 'rejected' && $task->rejected_reason)
                    <div class="alert alert-danger mt-2 mb-0">
                        <strong>Lý do từ chối:</strong> {{ $task->rejected_reason }}
                    </div>
                @endif

                <div class="mt-2">
                    <a href="{{ route($detailRoute ?? 'task-assignments.show', $task) }}" class="btn btn-sm btn-info">
                        <i class="fas fa-eye"></i> Chi tiết
                    </a>
                </div>
            </div>
        </div>
    @empty
        <div class="card">
            <div class="card-body empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-history"></i>
                </div>
                <h5>Chưa có công việc nào</h5>
                <p class="text-muted">Bạn chưa hoàn thành công việc nào</p>
            </div>
        </div>
    @endforelse

    {{-- Pagination --}}
    <div class="d-flex justify-content-center mt-4">
        {{ $tasks->links() }}
    </div>
</div>
@endsection
