@php
    use App\Services\TaskMenuService;
    $user = auth()->user();
    $canAssign = TaskMenuService::canAssignTasks($user);
    $canComplete = TaskMenuService::canCompleteTasks($user);
    $isFrontRole = $user && $user->isSalesFlowRole();
    $myTasksRoute = $isFrontRole ? 'my-tasks' : 'tasks.my-tasks';
@endphp

@if ($canAssign || $canComplete)
    <div class="task-menu-container">
        @if ($canAssign)
            <div class="task-menu-section">
                <h6 class="text-muted small font-weight-bold">
                    <i class="fas fa-user-check"></i> Người giao việc
                </h6>
                <ul class="list-unstyled">
                    <li>
                        <a href="{{ route('tasks.create') }}" class="task-menu-link">
                            <i class="fas fa-plus-circle"></i> Giao việc
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('tasks.assigned') }}" class="task-menu-link">
                            <i class="fas fa-list"></i> Công việc đã giao
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('task-assignments.tracking') }}" class="task-menu-link">
                            <i class="fas fa-chart-line"></i> Theo dõi tiến độ
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('tasks.verify') }}" class="task-menu-link">
                            <i class="fas fa-check-double"></i> Xác nhận hoàn thành
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('tasks.rejected') }}" class="task-menu-link">
                            <i class="fas fa-undo"></i> Task bị từ chối/làm lại
                        </a>
                    </li>
                </ul>
            </div>
        @endif

        @if ($canComplete)
            <div class="task-menu-section">
                <h6 class="text-muted small font-weight-bold">
                    <i class="fas fa-user-tag"></i> Người nhận việc
                </h6>
                <ul class="list-unstyled">
                    <li>
                        <a href="{{ route($myTasksRoute) }}" class="task-menu-link">
                            <i class="fas fa-inbox"></i> Công việc được giao
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('tasks.in-progress') }}" class="task-menu-link">
                            <i class="fas fa-spinner"></i> Đang thực hiện
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('tasks.awaiting-verification') }}" class="task-menu-link">
                            <i class="fas fa-hourglass-end"></i> Chờ xác nhận
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('task-assignments.history') }}" class="task-menu-link">
                            <i class="fas fa-history"></i> Lịch sử công việc
                        </a>
                    </li>
                </ul>
            </div>
        @endif
    </div>

    <style>
        .task-menu-section {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e9ecef;
        }

        .task-menu-section:last-child {
            border-bottom: none;
        }

        .task-menu-section h6 {
            margin-bottom: 10px !important;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
        }

        .task-menu-link {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            color: #495057;
            text-decoration: none;
            border-radius: 4px;
            transition: all 0.2s ease;
        }

        .task-menu-link:hover {
            background: #f8f9fa;
            color: #007bff;
            padding-left: 16px;
        }

        .task-menu-link i {
            width: 18px;
            text-align: center;
        }
    </style>
@endif
