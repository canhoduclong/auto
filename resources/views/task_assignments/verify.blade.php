@extends('layouts.app')

@section('title', 'Xác nhận hoàn thành: ' . $task->code)

@push('styles')
<style>
    .completion-details {
        background: white;
        border-radius: 12px;
        padding: 25px;
        margin-bottom: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }
    
    .completion-images {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 15px;
        margin-top: 20px;
    }
    
    .completion-image {
        position: relative;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        cursor: pointer;
    }
    
    .completion-image img {
        width: 100%;
        height: 150px;
        object-fit: cover;
        transition: transform 0.2s ease;
    }
    
    .completion-image:hover img {
        transform: scale(1.05);
    }
    
    .verification-form {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 25px;
    }
    
    .status-badge {
        display: inline-block;
        padding: 8px 15px;
        border-radius: 20px;
        font-weight: 600;
        margin-bottom: 15px;
    }
    
    .status-completed {
        background: #fff3cd;
        color: #856404;
    }
    
    .timeline {
        position: relative;
        padding-left: 30px;
    }
    
    .timeline::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #dee2e6;
    }
    
    .timeline-item {
        margin-bottom: 20px;
        position: relative;
    }
    
    .timeline-item::before {
        content: '';
        position: absolute;
        left: -37px;
        top: 5px;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: #007bff;
        border: 2px solid white;
    }
    
    .timeline-item.status-done::before {
        background: #28a745;
    }
    
    .timeline-item.status-rejected::before {
        background: #dc3545;
    }
</style>
@endpush

@section('content')
<div class="container mt-4">
    <div class="row">
        <div class="col-lg-10 offset-lg-1">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3>
                    <i class="fas fa-check-double"></i>
                    Xác nhận hoàn thành: <strong>{{ $task->code }}</strong>
                </h3>
                <span class="status-badge status-completed">
                    <i class="fas fa-hourglass-end"></i> Chờ xác nhận
                </span>
            </div>

            {{-- Thông tin công việc --}}
            <div class="completion-details">
                <h5 class="mb-3"><i class="fas fa-info-circle"></i> Thông tin công việc</h5>
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Tiêu đề:</strong> {{ $task->title }}</p>
                        <p><strong>Mã công việc:</strong> {{ $task->code }}</p>
                        <p><strong>Mức độ ưu tiên:</strong> <span class="badge bg-{{ $task->getStatusColor() }}">{{ $task->getPriorityLabel() }}</span></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Người giao:</strong> {{ $task->creator->name }}</p>
                        <p><strong>Người thực hiện:</strong> {{ $task->assignees->first()?->user->name ?? 'N/A' }}</p>
                        <p><strong>Ngày gửi hoàn thành:</strong> {{ $task->completed_at?->format('d/m/Y H:i') ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>

            {{-- Nội dung hoàn thành --}}
            <div class="completion-details">
                <h5 class="mb-3"><i class="fas fa-file-alt"></i> Nội dung hoàn thành</h5>
                <div class="bg-light p-3 rounded">
                    {!! nl2br(e($task->completion_content)) !!}
                </div>
                
                @if ($task->completion_notes)
                    <div class="mt-3">
                        <h6 class="text-muted mb-2">Ghi chú bổ sung:</h6>
                        <div class="bg-light p-3 rounded">
                            {!! nl2br(e($task->completion_notes)) !!}
                        </div>
                    </div>
                @endif
            </div>

            {{-- Hình ảnh minh chứng --}}
            @if ($task->completionImages->isNotEmpty())
                <div class="completion-details">
                    <h5 class="mb-3"><i class="fas fa-images"></i> Hình ảnh minh chứng</h5>
                    <div class="completion-images">
                        @foreach ($task->completionImages as $image)
                            <a href="{{ $image->getImageUrl() }}" target="_blank" class="completion-image" title="{{ $image->original_filename }}">
                                <img src="{{ $image->getImageUrl() }}" alt="{{ $image->original_filename }}">
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Lịch sử trạng thái --}}
            <div class="completion-details">
                <h5 class="mb-3"><i class="fas fa-history"></i> Lịch sử cập nhật</h5>
                <div class="timeline">
                    @forelse ($task->statusLogs->sortByDesc('created_at') as $log)
                        <div class="timeline-item status-{{ $log->to_status }}">
                            <strong>{{ $log->to_status === 'done' ? 'Đã hoàn thành' : ($log->to_status === 'rejected' ? 'Bị từ chối' : 'Cập nhật') }}</strong>
                            <p class="text-muted small mb-1">
                                <i class="fas fa-clock"></i> {{ $log->created_at?->format('d/m/Y H:i') }}
                            </p>
                            @if ($log->reason)
                                <p class="small mb-0">{{ $log->reason }}</p>
                            @endif
                            <p class="small text-muted mb-0">Bởi: {{ $log->changedBy->name }}</p>
                        </div>
                    @empty
                        <p class="text-muted">Chưa có lịch sử</p>
                    @endforelse
                </div>
            </div>

            {{-- Form xác nhận --}}
            <div class="verification-form">
                <h5 class="mb-3"><i class="fas fa-check-circle"></i> Xác nhận hoàn thành</h5>
                
                <form method="POST" action="{{ route('task-assignments.verify-completion', $task) }}" id="verifyForm">
                    @csrf

                    <div class="mb-3">
                        <label for="verification_notes" class="form-label">Ghi chú xác nhận (Tùy chọn)</label>
                        <textarea 
                            class="form-control @error('verification_notes') is-invalid @enderror"
                            id="verification_notes"
                            name="verification_notes"
                            rows="4"
                            placeholder="Ví dụ: Nội dung công việc chi tiết, đạt yêu cầu chất lượng..."
                            maxlength="2000"
                        >{{ old('verification_notes') }}</textarea>
                        <small class="form-text text-muted">Tối đa 2000 ký tự</small>
                        @error('verification_notes')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="fas fa-check"></i> Xác nhận hoàn thành
                        </button>
                        <button type="button" class="btn btn-danger btn-lg" data-bs-toggle="modal" data-bs-target="#rejectModal">
                            <i class="fas fa-times"></i> Yêu cầu làm lại
                        </button>
                        <a href="{{ route('task-assignments.show', $task) }}" class="btn btn-secondary btn-lg ms-auto">
                            Hủy
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Reject Modal --}}
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Yêu cầu làm lại công việc</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('task-assignments.reject-completion', $task) }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="rejected_reason" class="form-label">Lý do yêu cầu làm lại <span class="text-danger">*</span></label>
                        <textarea 
                            class="form-control @error('rejected_reason') is-invalid @enderror"
                            id="rejected_reason"
                            name="rejected_reason"
                            rows="5"
                            placeholder="Vui lòng nêu chi tiết lý do cần điều chỉnh..."
                            minlength="10"
                            maxlength="2000"
                            required
                        >{{ old('rejected_reason') }}</textarea>
                        <small class="form-text text-muted">Tối thiểu 10 ký tự, tối đa 2000 ký tự</small>
                        @error('rejected_reason')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-danger">Yêu cầu làm lại</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
