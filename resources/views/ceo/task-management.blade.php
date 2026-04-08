@extends('layouts.ceo')

@section('title', 'Quản lý giao việc')

@push('styles')
<style>
    .task-management-container {
        background: #f8f9fa;
        min-height: 100vh;
        padding: 20px 0;
    }
    .task-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 30px;
        border-radius: 15px;
        margin-bottom: 30px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }
    .task-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    .stat-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        text-align: center;
        transition: transform 0.2s ease;
    }
    .stat-card:hover {
        transform: translateY(-2px);
    }
    .stat-value {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 5px;
    }
    .stat-label {
        font-size: 0.9rem;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .task-form-section {
        background: white;
        border-radius: 12px;
        padding: 25px;
        margin-bottom: 30px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    .task-list-section {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    .task-item {
        border-bottom: 1px solid #dee2e6;
        padding: 20px;
        transition: background 0.2s ease;
    }
    .task-item:hover {
        background: #f8f9fa;
    }
    .task-item:last-child {
        border-bottom: none;
    }
    .task-title {
        font-weight: 600;
        color: #343a40;
        margin-bottom: 8px;
    }
    .task-meta {
        display: flex;
        gap: 15px;
        font-size: 0.85rem;
        color: #6c757d;
        margin-bottom: 10px;
    }
    .task-description {
        color: #495057;
        margin-bottom: 15px;
    }
    .task-actions {
        display: flex;
        gap: 10px;
        align-items: center;
    }
    .status-badge {
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
    }
    .status-pending { background: #e9ecef; color: #6c757d; }
    .status-in_progress { background: #cce5ff; color: #0066cc; }
    .status-completed { background: #d4edda; color: #155724; }
    .status-overdue { background: #f8d7da; color: #721c24; }
    .status-cancelled { background: #fff3cd; color: #856404; }
    .filter-section {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 12px;
        margin-bottom: 20px;
    }
    .customer-select {
        position: relative;
    }
    .customer-dropdown {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        max-height: 200px;
        overflow-y: auto;
        z-index: 1000;
        display: none;
    }
    .customer-option {
        padding: 8px 12px;
        cursor: pointer;
        border-bottom: 1px solid #f8f9fa;
    }
    .customer-option:hover {
        background: #f8f9fa;
    }
    .customer-option:last-child {
        border-bottom: none;
    }
    @media (max-width: 768px) {
        .task-meta {
            flex-direction: column;
            gap: 5px;
        }
        .task-actions {
            flex-direction: column;
            align-items: stretch;
        }
        .task-stats {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<div class="task-management-container">
    <div class="container-fluid">
        <div class="task-header">
            <h1><i class="bi bi-list-task"></i> Quản lý giao việc</h1>
            <p>Hệ thống giao việc nhiều cấp CEO → Manager → Sale</p>
        </div>

        <!-- Statistics -->
        <div class="task-stats">
            <div class="stat-card">
                <div class="stat-value">{{ $stats['total'] }}</div>
                <div class="stat-label">Tổng task</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">{{ $stats['pending'] }}</div>
                <div class="stat-label">Chờ xử lý</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">{{ $stats['in_progress'] }}</div>
                <div class="stat-label">Đang thực hiện</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">{{ $stats['completed'] }}</div>
                <div class="stat-label">Hoàn thành</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">{{ $stats['overdue'] }}</div>
                <div class="stat-label">Quá hạn</div>
            </div>
        </div>

        <!-- Create Task Form -->
        <div class="task-form-section">
            <h4><i class="bi bi-plus-circle"></i> Tạo task mới</h4>
            <form method="POST" action="{{ route('ceo.task-management.store') }}">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Tiêu đề task *</label>
                            <input type="text" class="form-control" name="title" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">Loại task *</label>
                            <select class="form-select" name="type" required>
                                <option value="">Chọn loại</option>
                                <option value="kpi">KPI</option>
                                <option value="customer_task">Task khách hàng</option>
                                <option value="general">Chung</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">Người thực hiện *</label>
                            <select class="form-select" name="assigned_to" required>
                                <option value="">Chọn người</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Deadline</label>
                            <input type="datetime-local" class="form-control" name="deadline">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3 customer-select">
                            <label class="form-label">Khách hàng (tùy chọn)</label>
                            <input type="text" class="form-control" id="customer_search" placeholder="Tìm khách hàng...">
                            <input type="hidden" name="customer_id" id="customer_id">
                            <div class="customer-dropdown" id="customer_dropdown"></div>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Mô tả chi tiết</label>
                    <textarea class="form-control" name="description" rows="3"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Tạo task
                </button>
            </form>
        </div>

        <!-- Filters -->
        <div class="filter-section">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <input type="text" class="form-control" name="search" placeholder="Tìm theo tiêu đề..." value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="status">
                        <option value="">Tất cả trạng thái</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Chờ xử lý</option>
                        <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>Đang thực hiện</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Hoàn thành</option>
                        <option value="overdue" {{ request('status') == 'overdue' ? 'selected' : '' }}>Quá hạn</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Đã hủy</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="type">
                        <option value="">Tất cả loại</option>
                        <option value="kpi" {{ request('type') == 'kpi' ? 'selected' : '' }}>KPI</option>
                        <option value="customer_task" {{ request('type') == 'customer_task' ? 'selected' : '' }}>Task khách hàng</option>
                        <option value="general" {{ request('general') == 'general' ? 'selected' : '' }}>Chung</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="assignee_id">
                        <option value="">Tất cả người thực hiện</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ request('assignee_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-primary w-100">
                        <i class="bi bi-search"></i> Lọc
                    </button>
                </div>
            </form>
        </div>

        <!-- Task List -->
        <div class="task-list-section">
            @forelse($tasks as $task)
                <div class="task-item">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <div class="task-title">
                                {{ $task->title }}
                                <span class="status-badge status-{{ $task->status }}">
                                    {{ $task->status }}
                                </span>
                            </div>
                            <div class="task-meta">
                                <span><i class="bi bi-tag"></i> {{ $task->getTypeLabel() }}</span>
                                <span><i class="bi bi-person"></i> {{ $task->assignee->name ?? 'N/A' }}</span>
                                <span><i class="bi bi-calendar"></i> {{ $task->deadline ? $task->deadline->format('d/m/Y H:i') : 'Không có' }}</span>
                                @if($task->customer)
                                    <span><i class="bi bi-building"></i> {{ $task->customer->name }}</span>
                                @endif
                            </div>
                            @if($task->description)
                                <div class="task-description">{{ Str::limit($task->description, 150) }}</div>
                            @endif
                            @if($task->note)
                                <div class="alert alert-info mt-2">
                                    <strong>Ghi chú:</strong> {{ $task->note }}
                                </div>
                            @endif
                            @if($task->next_appointment)
                                <div class="alert alert-warning mt-2">
                                    <strong>Lịch hẹn tiếp theo:</strong> {{ $task->next_appointment->format('d/m/Y H:i') }}
                                </div>
                            @endif
                        </div>
                        <div class="task-actions">
                            @if($task->assigned_to == auth()->id())
                                <!-- Sale can update note and appointment -->
                                <button class="btn btn-sm btn-outline-primary" onclick="editTask({{ $task->id }})">
                                    <i class="bi bi-pencil"></i> Cập nhật
                                </button>
                            @else
                                <!-- CEO/Manager can change status -->
                                <select class="form-select form-select-sm" onchange="updateStatus({{ $task->id }}, this.value)">
                                    <option value="pending" {{ $task->status == 'pending' ? 'selected' : '' }}>Chờ xử lý</option>
                                    <option value="in_progress" {{ $task->status == 'in_progress' ? 'selected' : '' }}>Đang thực hiện</option>
                                    <option value="completed" {{ $task->status == 'completed' ? 'selected' : '' }}>Hoàn thành</option>
                                    <option value="cancelled" {{ $task->status == 'cancelled' ? 'selected' : '' }}>Hủy</option>
                                </select>
                            @endif
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteTask({{ $task->id }})">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-5">
                    <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                    <h5 class="text-muted mt-3">Chưa có task nào</h5>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        @if($tasks->hasPages())
            <div class="d-flex justify-content-center mt-4">
                {{ $tasks->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Edit Task Modal -->
<div class="modal fade" id="editTaskModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cập nhật task</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editTaskForm">
                @csrf
                @method('PATCH')
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Ghi chú</label>
                        <textarea class="form-control" name="note" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Lịch hẹn tiếp theo</label>
                        <input type="datetime-local" class="form-control" name="next_appointment">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Cập nhật</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
let currentTaskId = null;

function editTask(taskId) {
    currentTaskId = taskId;
    // Load task data and show modal
    fetch(`/ceo/task-management/${taskId}`)
        .then(response => response.json())
        .then(data => {
            document.querySelector('#editTaskForm [name="note"]').value = data.note || '';
            document.querySelector('#editTaskForm [name="next_appointment"]').value = data.next_appointment || '';
            document.querySelector('#editTaskForm').action = `/ceo/task-management/${taskId}`;
            new bootstrap.Modal(document.getElementById('editTaskModal')).show();
        });
}

function updateStatus(taskId, status) {
    fetch(`/ceo/task-management/${taskId}/status`, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ status: status })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    });
}

function deleteTask(taskId) {
    if (confirm('Bạn có chắc muốn xóa task này?')) {
        fetch(`/ceo/task-management/${taskId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(() => location.reload());
    }
}

// Customer search functionality
document.getElementById('customer_search').addEventListener('input', function() {
    const query = this.value;
    const dropdown = document.getElementById('customer_dropdown');

    if (query.length < 2) {
        dropdown.style.display = 'none';
        return;
    }

    fetch(`/ceo/task-management/customers?q=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(customers => {
            dropdown.innerHTML = '';
            customers.forEach(customer => {
                const option = document.createElement('div');
                option.className = 'customer-option';
                option.textContent = `${customer.name} (${customer.phone})`;
                option.onclick = () => {
                    document.getElementById('customer_search').value = customer.name;
                    document.getElementById('customer_id').value = customer.id;
                    dropdown.style.display = 'none';
                };
                dropdown.appendChild(option);
            });
            dropdown.style.display = customers.length > 0 ? 'block' : 'none';
        });
});
</script>
@endpush
@endsection