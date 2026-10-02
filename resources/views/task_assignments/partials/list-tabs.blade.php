<nav class="mb-4" aria-label="Danh sách công việc">
    <ul class="nav nav-tabs">
        <li class="nav-item">
            <a class="nav-link {{ $activeTaskTab === 'received' ? 'active fw-semibold' : '' }}" href="{{ route('my-tasks') }}" @if($activeTaskTab === 'received') aria-current="page" @endif>
                <i class="bi bi-list-task me-1" aria-hidden="true"></i>Nhiệm vụ
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $activeTaskTab === 'assigned' ? 'active fw-semibold' : '' }}" href="{{ route('tasks.assigned') }}" @if($activeTaskTab === 'assigned') aria-current="page" @endif>
                <i class="bi bi-person-check me-1" aria-hidden="true"></i>Giao việc
            </a>
        </li>
    </ul>
</nav>
