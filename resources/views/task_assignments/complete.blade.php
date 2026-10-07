@extends($layout ?? 'layouts.app')

@section('title', 'Hoàn thành công việc: ' . $task->code)

@include('task_assignments.partials.detail-styles')



@section('content')
<div class="container mt-4 task-workspace">
    <div class="row">
        <div class="col-12">
            <h3 class="mb-4">
                <i class="bi bi-list-task"></i>
                Hoàn thành công việc: <strong>{{ $task->code }}</strong>
            </h3>
            
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p><strong>Tiêu đề:</strong> {{ $task->title }}</p>
                            <p><strong>Mức độ ưu tiên:</strong> <span class="badge bg-{{ $task->priorityColor() }}">{{ $task->getPriorityLabel() }}</span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Ngày hết hạn:</strong> {{ $task->due_date?->format('d/m/Y H:i') ?? 'Không có' }}</p>
                            <p><strong>Người giao:</strong> {{ $task->creator?->name }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <section class="card task-content-card mb-3">
                <div class="card-header">Nội dung công việc</div>
                <div class="card-body task-description">{!! \App\Support\TaskDescription::render($task->description) !!}</div>
            </section>
            @include('task_assignments.partials.progress')
            @include('task_assignments.partials.completion-report')
        </div>
    </div>
</div>
@endsection
