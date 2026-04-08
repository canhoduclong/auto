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
    .task-header h1 {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 10px;
    }
    .task-header p {
        font-size: 1.1rem;
        opacity: 0.9;
        margin-bottom: 0;
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
        padding: 25px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        text-align: center;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        border-top: 4px solid #667eea;
        position: relative;
        overflow: hidden;
    }
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.15);
    }
    .stat-card.total { border-top-color: #667eea; }
    .stat-card.pending { border-top-color: #ffc107; }
    .stat-card.in-progress { border-top-color: #0dcaf0; }
    .stat-card.completed { border-top-color: #198754; }
    .stat-card.overdue { border-top-color: #dc3545; }
    
    .stat-icon {
        font-size: 2rem;
        margin-bottom: 10px;
        opacity: 0.8;
    }
    .stat-icon.total { color: #667eea; }
    .stat-icon.pending { color: #ffc107; }
    .stat-icon.in-progress { color: #0dcaf0; }
    .stat-icon.completed { color: #198754; }
    .stat-icon.overdue { color: #dc3545; }

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
        border-top: 4px solid #667eea;
    }
    .task-form-section h4 {
        color: #667eea;
        margin-bottom: 20px;
        font-weight: 700;
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
        transition: background 0.2s ease, border-left 0.2s ease;
        border-left: 4px solid transparent;
    }
    .task-item:hover {
        background: #f8f9fa;
    }
    .task-item.status-pending {
        /*border-left-color: #ffc107;*/
    }
    .task-item.status-in_progress {
        /*border-left-color: #0dcaf0;*/
    }
    .task-item.status-completed {
        /*border-left-color: #198754;*/
    }
    .task-item.status-overdue {
        /*border-left-color: #dc3545;*/
    }
    .task-item.status-cancelled {
        /*border-left-color: #6c757d;*/
    }
    .task-item:last-child {
        border-bottom: none;
    }
    .task-title {
        font-weight: 600;
        color: #343a40;
        margin-bottom: 8px;
        font-size: 1.1rem;
    }
    .task-meta {
        display: flex;
        gap: 20px;
        font-size: 0.85rem;
        color: #6c757d;
        margin-bottom: 10px;
        flex-wrap: wrap;
    }
    .task-meta-item {
        display: flex;
        align-items: center;
        gap: 5px;
    }
    .task-meta-item i {
        color: #667eea;
    }
    .task-description {
        color: #495057;
        margin-bottom: 15px;
        padding: 10px;
        background: #f8f9fa;
        border-radius: 8px;
        border-left: 3px solid #667eea;
        background-color: #eabe3938;
    }
    .task-actions {
        display: flex;
        gap: 10px;
        align-items: center;
    }
    .status-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    .status-pending { /*background: #fff3cd;*/ color: #856404; /*border: 1px solid #ffc107;*/ }
    .status-in_progress {/* background: #cfe2ff;*/ color: #084298; border: 1px solid #0dcaf0; }
    .status-completed { background: #d1e7dd; color: #0f5132; border: 1px solid #198754; }
    .status-overdue { background: #f8d7da; color: #842029; border: 1px solid #dc3545; }
    .status-cancelled { background: #e2e3e5; color: #383d41; border: 1px solid #6c757d; }

    .type-badge {
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .type-kpi { background: #e7d4f5; color: #6f42c1; }
    .type-customer_task { background: #d1ecf1; color: #0c5460; }
    .type-general { background: #e7f3ff; color: #0066cc; }

    .filter-section {
        background: white;
        padding: 20px;
        border-radius: 12px;
        margin-bottom: 20px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        border-top: 4px solid #667eea;
    }
    .filter-section h6 {
        color: #667eea;
        margin-bottom: 15px;
        font-weight: 700;
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
        border: 1px solid #0dcaf0;
        border-radius: 8px;
        max-height: 200px;
        overflow-y: auto;
        z-index: 1000;
        display: none;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .customer-option {
        padding: 10px 12px;
        cursor: pointer;
        border-bottom: 1px solid #f8f9fa;
        transition: background 0.2s ease;
    }
    .customer-option:hover {
        background: #f0f8ff;
    }
    .customer-option:last-child {
        border-bottom: none;
    }
    .alert-box {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px;
        margin-top: 10px;
        border-radius: 8px;
        border-left: 4px solid;
    }
    .alert-info {
        background: #d1ecf1;
        border-left-color: #0c5460;
        color: #0c5460;
    }
    .alert-warning {
        background: #fff3cd;
        border-left-color: #856404;
        color: #856404;
    }
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
    }
    .btn-primary:hover {
        background: linear-gradient(135deg, #5568d3 0%, #6a3f8f 100%);
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
            <div class="stat-card total">
                <div class="stat-icon total"><i class="bi bi-list-check"></i></div>
                <div class="stat-value">{{ $stats['total'] }}</div>
                <div class="stat-label">Tổng task</div>
            </div>
            <div class="stat-card pending">
                <div class="stat-icon pending"><i class="bi bi-hourglass-split"></i></div>
                <div class="stat-value">{{ $stats['pending'] }}</div>
                <div class="stat-label">Chờ xử lý</div>
            </div>
            <div class="stat-card in-progress">
                <div class="stat-icon in-progress"><i class="bi bi-play-circle"></i></div>
                <div class="stat-value">{{ $stats['in_progress'] }}</div>
                <div class="stat-label">Đang thực hiện</div>
            </div>
            <div class="stat-card completed">
                <div class="stat-icon completed"><i class="bi bi-check-circle"></i></div>
                <div class="stat-value">{{ $stats['completed'] }}</div>
                <div class="stat-label">Hoàn thành</div>
            </div>
            <div class="stat-card overdue">
                <div class="stat-icon overdue"><i class="bi bi-exclamation-circle"></i></div>
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
                            <label class="form-label"><i class="bi bi-pencil-square"></i> Tiêu đề task *</label>
                            <input type="text" class="form-control" name="title" required placeholder="Nhập tiêu đề task">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label"><i class="bi bi-tag"></i> Loại task *</label>
                            <select class="form-select" name="type" required>
                                <option value="">Chọn loại</option>
                                <option value="kpi">🎯 KPI</option>
                                <option value="customer_task">👤 Task khách hàng</option>
                                <option value="general">📋 Chung</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label"><i class="bi bi-person"></i> Người thực hiện *</label>
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
                            <label class="form-label"><i class="bi bi-calendar-event"></i> Deadline</label>
                            <input type="datetime-local" class="form-control" name="deadline">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3 customer-select">
                            <label class="form-label"><i class="bi bi-building"></i> Khách hàng (tùy chọn)</label>
                            <input type="text" class="form-control" id="customer_search" placeholder="Tìm khách hàng...">
                            <input type="hidden" name="customer_id" id="customer_id">
                            <div class="customer-dropdown" id="customer_dropdown"></div>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label"><i class="bi bi-file-text"></i> Mô tả chi tiết</label>
                    <textarea class="form-control" name="description" rows="3" placeholder="Nhập mô tả chi tiết task..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Tạo task
                </button>
            </form>
        </div>

        <!-- Filters -->
        <div class="filter-section">
            <h6><i class="bi bi-funnel"></i> Bộ lọc</h6>
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <input type="text" class="form-control" name="search" placeholder="🔍 Tìm theo tiêu đề..." value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="status">
                        <option value="">📊 Tất cả trạng thái</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>⏳ Chờ xử lý</option>
                        <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>▶️ Đang thực hiện</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>✅ Hoàn thành</option>
                        <option value="overdue" {{ request('status') == 'overdue' ? 'selected' : '' }}>⚠️ Quá hạn</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>❌ Đã hủy</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="type">
                        <option value="">🏆 Tất cả loại</option>
                        <option value="kpi" {{ request('type') == 'kpi' ? 'selected' : '' }}>🎯 KPI</option>
                        <option value="customer_task" {{ request('type') == 'customer_task' ? 'selected' : '' }}>👤 Task khách hàng</option>
                        <option value="general" {{ request('general') == 'general' ? 'selected' : '' }}>📋 Chung</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="assignee_id">
                        <option value="">👥 Tất cả người thực hiện</option>
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
                <div class="task-item status-{{ $task->status }}">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <div class="task-title">
                                {{ $task->title }}
                                <span class="status-badge status-{{ $task->status }}">
                                    @switch($task->status)
                                        @case('pending')
                                            <i class="bi bi-hourglass-split"></i> {{ $task->status }}
                                            @break
                                        @case('in_progress')
                                            <i class="bi bi-play-circle"></i> {{ $task->status }}
                                            @break
                                        @case('completed')
                                            <i class="bi bi-check-circle"></i> {{ $task->status }}
                                            @break
                                        @case('overdue')
                                            <i class="bi bi-exclamation-circle"></i> {{ $task->status }}
                                            @break
                                        @case('cancelled')
                                            <i class="bi bi-x-circle"></i> {{ $task->status }}
                                            @break
                                    @endswitch
                                </span>
                                <span class="type-badge type-{{ $task->type }}">
                                    @switch($task->type)
                                        @case('kpi')
                                            <i class="bi bi-target"></i> KPI
                                            @break
                                        @case('customer_task')
                                            <i class="bi bi-person-check"></i> Task KH
                                            @break
                                        @case('general')
                                            <i class="bi bi-list"></i> Chung
                                            @break
                                    @endswitch
                                </span>
                            </div>
                            <div class="task-meta">
                                <div class="task-meta-item">
                                    <i class="bi bi-tag-fill"></i>
                                    <span>{{ $task->getTypeLabel() }}</span>
                                </div>
                                <div class="task-meta-item">
                                    <i class="bi bi-person-fill"></i>
                                    <span>{{ $task->assignee->name ?? 'N/A' }}</span>
                                </div>
                                <div class="task-meta-item">
                                    <i class="bi bi-calendar-fill"></i>
                                    <span>{{ $task->deadline ? $task->deadline->format('d/m/Y H:i') : 'Không có' }}</span>
                                </div>
                                @if($task->customer)
                                    <div class="task-meta-item">
                                        <i class="bi bi-building"></i>
                                        <span>{{ $task->customer->name }}</span>
                                    </div>
                                @endif
                            </div>
                            @if($task->description)
                                <div class="task-description">
                                    <i class="bi bi-info-circle"></i>
                                    {{ Str::limit($task->description, 150) }}
                                </div>
                            @endif
                            @if($task->note)
                                <div class="alert-box alert-info">
                                    <i class="bi bi-chat-left-text"></i>
                                    <div>
                                        <strong>Ghi chú:</strong> {{ $task->note }}
                                    </div>
                                </div>
                            @endif
                            @if($task->next_appointment)
                                <div class="alert-box alert-warning">
                                    <i class="bi bi-clock-history"></i>
                                    <div>
                                        <strong>Lịch hẹn tiếp theo:</strong> {{ $task->next_appointment->format('d/m/Y H:i') }}
                                    </div>
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
                                    <option value="pending" {{ $task->status == 'pending' ? 'selected' : '' }}>⏳ Chờ xử lý</option>
                                    <option value="in_progress" {{ $task->status == 'in_progress' ? 'selected' : '' }}>▶️ Đang thực hiện</option>
                                    <option value="completed" {{ $task->status == 'completed' ? 'selected' : '' }}>✅ Hoàn thành</option>
                                    <option value="cancelled" {{ $task->status == 'cancelled' ? 'selected' : '' }}>❌ Hủy</option>
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
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-pencil-square"></i> Cập nhật task</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="editTaskForm">
                @csrf
                @method('PATCH')
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-chat-left-text"></i> Ghi chú</label>
                        <textarea class="form-control" name="note" rows="3" placeholder="Nhập ghi chú..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-calendar-event"></i> Lịch hẹn tiếp theo</label>
                        <input type="datetime-local" class="form-control" name="next_appointment">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Cập nhật
                    </button>
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