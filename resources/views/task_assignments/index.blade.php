@extends('layouts.admin')

@section('title', 'Giao Viec')

@push('styles')
<style>
.ta-card { border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px 16px; background: #fff; transition: box-shadow .14s; }
.ta-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,.08); }
.ta-meta { font-size: 12px; color: #94a3b8; }
.ta-priority-urgent { border-left: 4px solid #ef4444; }
.ta-priority-high    { border-left: 4px solid #f59e0b; }
.ta-priority-medium  { border-left: 4px solid #3b82f6; }
.ta-priority-low     { border-left: 4px solid #94a3b8; }
.step-chain { display: flex; align-items: center; gap: 4px; flex-wrap: wrap; }
.step-pill { padding: 2px 8px; border-radius: 20px; font-size: 11px; font-weight: 600; }
.step-pill.approved { background: #dcfce7; color: #15803d; }
.step-pill.pending  { background: #fef9c3; color: #854d0e; }
.step-pill.rejected { background: #fee2e2; color: #b91c1c; }
</style>
@endpush

@section('content')
<div class="content-wrapper">
<div class="content-header d-flex align-items-center justify-content-between py-3 px-4">
    <div>
        <h4 class="mb-0">Giao Viec</h4>
        <small class="text-muted">Quan ly va theo doi quy trinh giao viec</small>
    </div>
    <a href="{{ route('task-assignments.create') }}" class="btn btn-primary btn-sm">
        <i class="ph-plus me-1"></i>Tao cong viec moi
    </a>
</div>

<div class="content-body px-4 pb-4">
    @if(session('success'))<div class="alert alert-success alert-dismissible fade show" role="alert">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if(session('error'))<div class="alert alert-danger alert-dismissible fade show" role="alert">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    {{-- Status tabs --}}
    <ul class="nav nav-tabs mb-3">
        @foreach([
            ''           => ['Tat ca',       $counts['all']],
            'pending'    => ['Cho xu ly',    $counts['pending']],
            'in_progress'=> ['Dang thuc hien', $counts['in_progress']],
            'completed'  => ['Hoan thanh',   $counts['completed']],
        ] as $val => [$label, $cnt])
            <li class="nav-item">
                <a class="nav-link {{ request('status') == $val && ($val !== '' || !request()->has('status')) ? 'active' : '' }}"
                   href="{{ route('task-assignments.index', array_merge(request()->except('status','page'), $val ? ['status'=>$val] : [])) }}">
                    {{ $label }} <span class="badge bg-secondary ms-1">{{ $cnt }}</span>
                </a>
            </li>
        @endforeach
    </ul>

    {{-- Filters --}}
    <form method="GET" action="{{ route('task-assignments.index') }}" class="d-flex gap-2 flex-wrap mb-4">
        <input type="hidden" name="status" value="{{ request('status') }}">
        <input type="text" name="search" class="form-control form-control-sm" style="width:220px"
               value="{{ request('search') }}" placeholder="Tim kiem ma, tieu de...">
        <select name="priority" class="form-select form-select-sm" style="width:140px">
            <option value="">Tat ca uu tien</option>
            @foreach(\App\Models\TaskAssignment::PRIORITY_LABELS as $v => $l)
                <option value="{{ $v }}" {{ request('priority') == $v ? 'selected' : '' }}>{{ $l }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-sm btn-outline-primary"><i class="ph-magnifying-glass me-1"></i>Loc</button>
        <a href="{{ route('task-assignments.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
    </form>

    {{-- Cards grid --}}
    @if($tasks->isEmpty())
        <div class="text-center text-muted py-5">
            <i class="ph-clipboard-text" style="font-size:48px;opacity:.3"></i>
            <p class="mt-2">Chua co cong viec nao.</p>
        </div>
    @else
        <div class="row g-3">
            @foreach($tasks as $task)
                @php $pl = 'ta-priority-' . $task->priority; @endphp
                <div class="col-md-6 col-xl-4">
                    <div class="ta-card {{ $pl }}">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <a href="{{ route('task-assignments.show', $task) }}" class="fw-bold text-decoration-none">
                                    {{ $task->title }}
                                </a>
                                <div class="ta-meta">{{ $task->code }} &bull; {{ $task->creator?->name }}</div>
                            </div>
                            <span class="badge bg-{{ $task->statusColor() }} ms-2">
                                {{ \App\Models\TaskAssignment::STATUS_LABELS[$task->status] ?? $task->status }}
                            </span>
                        </div>

                        @if($task->description)
                            <p class="small text-muted mb-2" style="line-height:1.4">{{ \Str::limit($task->description, 80) }}</p>
                        @endif

                                {{-- Custom assignees --}}
                                @if($task->assignees->isNotEmpty())
                                    <div class="d-flex gap-1 flex-wrap mb-2">
                                        @foreach($task->assignees as $ta)
                                            <span class="badge bg-{{ $ta->statusColor() }} bg-opacity-75"
                                                  style="font-size:10px" title="{{ $ta->status }}">
                                                <i class="ph-user"></i> {{ $ta->user?->name }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif

                        <div class="d-flex justify-content-between align-items-center ta-meta">
                            <span>
                                <span class="badge bg-{{ $task->priorityColor() }} pending-badge">
                                    {{ \App\Models\TaskAssignment::PRIORITY_LABELS[$task->priority] }}
                                </span>
                            </span>
                            <span>
                                @if($task->due_date)
                                    <i class="ph-calendar-blank"></i>
                                    <span class="{{ $task->isOverdue() ? 'text-danger fw-semibold' : '' }}">
                                        {{ $task->due_date->format('d/m/Y') }}
                                    </span>
                                @endif
                            </span>
                        </div>

                        <div class="d-flex gap-2 mt-3">
                            <a href="{{ route('task-assignments.show', $task) }}" class="btn btn-sm btn-outline-info">
                                <i class="ph-eye"></i> Chi tiết
                            </a>
                            @if($task->canBeEditedBy(auth()->user()))
                                <a href="{{ route('task-assignments.edit', $task) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="ph-pencil"></i> Chỉnh sửa
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4">
            {{ $tasks->links() }}
        </div>
    @endif
</div>
</div>
@endsection
