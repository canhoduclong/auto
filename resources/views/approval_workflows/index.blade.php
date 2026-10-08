@extends('layouts.app')
@section('title', 'Quy trình xét duyệt')

@push('styles')
<style>
.workflow-page{width:100%;max-width:none;padding:20px 24px 32px;color:#24364b}.workflow-header{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:24px}.workflow-header h1{font-size:26px;font-weight:700;margin:0 0 6px}.workflow-subtitle{color:#728197;font-size:14px;margin:0}.workflow-panel{background:#fff;border:1px solid #dfe6ef;margin-bottom:24px;box-shadow:0 3px 12px rgba(24,43,68,.035)}.workflow-panel-heading{display:flex;justify-content:space-between;align-items:center;gap:16px;padding:18px 20px;border-bottom:1px solid #dfe6ef}.workflow-panel-heading h2{font-size:17px;font-weight:650;margin:0 0 4px}.workflow-count{background:#edf3fa;color:#486581;padding:4px 10px;font-size:12px;white-space:nowrap;border-radius:4px}.workflow-table{width:100%;table-layout:fixed;margin:0;font-size:14px;border-collapse:collapse}.workflow-table th{background:#f3f6fa;color:#607087;font-size:12px;font-weight:650;padding:13px 20px;text-align:left}.workflow-table td{padding:18px 20px;vertical-align:top;border-bottom:1px solid #e8edf3;overflow-wrap:anywhere}.workflow-table tbody tr:last-child td{border-bottom:0}.workflow-table tbody tr:hover{background:#fafcff}.workflow-name{font-size:15px;font-weight:650;color:#24364b;margin-bottom:6px}.workflow-meta{font-size:12px;color:#77869b;margin-top:5px}.workflow-chip{display:inline-block;background:#eef3f9;border:1px solid #e1e8f0;color:#45617e;padding:5px 9px;font-size:12px;border-radius:4px;margin:0 4px 6px 0;line-height:1.4}.workflow-status{display:inline-flex;align-items:center;gap:6px;padding:6px 9px;font-size:12px;font-weight:600;white-space:nowrap;background:#edf7f2;color:#23724f;border-radius:4px}.workflow-status::before{content:'';width:6px;height:6px;border-radius:50%;background:currentColor}.workflow-status.inactive{background:#f1f3f6;color:#6d7786}.workflow-steps{display:flex;flex-direction:column;gap:8px}.workflow-step{display:flex;align-items:flex-start;gap:9px;line-height:1.5}.workflow-step-number{flex:0 0 24px;height:24px;background:#eaf2fb;color:#32639a;display:inline-flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;border-radius:4px}.workflow-page .btn{font-size:13px;font-weight:600;white-space:nowrap}.workflow-page .btn-outline-primary{color:#1765ab;border-color:#b9cfe2}.workflow-page .btn-outline-primary:hover{background:#1765ab;color:#fff}.workflow-footer{padding:14px 20px;border-top:1px solid #e8edf3}.workflow-footer:empty{display:none}.workflow-empty{padding:36px!important;text-align:center;color:#77869b}@media(max-width:1199px){.workflow-table{min-width:980px}}@media(max-width:767px){.workflow-page{padding:16px 10px}.workflow-header{align-items:flex-start;flex-wrap:wrap}.workflow-header h1{font-size:22px}.workflow-panel-heading{padding:14px;flex-wrap:wrap}.workflow-table th,.workflow-table td{padding:14px}.workflow-subtitle{font-size:13px}}
</style>
@endpush

@section('content')
@php
    $roleLabels = ['manager_shipper'=>'Điều phối', 'shipper'=>'Shipper', 'accountant'=>'Kế toán', 'account'=>'Kế toán', 'accounting'=>'Kế toán', 'leader'=>'Trưởng nhóm', 'manager'=>'Quản lý', 'warehouse'=>'Kho', 'director'=>'Giám đốc', 'sale'=>'Sale', 'admin'=>'Quản trị'];
    $activityLabels = \App\Models\ApprovalWorkflow::availableActivities();
@endphp
<div class="container-fluid workflow-page">
    <header class="workflow-header">
        <div><h1>Quy trình xét duyệt</h1><p class="workflow-subtitle">Quản lý hoạt động áp dụng, vai trò và người thực hiện từng bước.</p></div>
        <a href="{{ route('approval-workflows.create') }}" class="btn btn-primary">+ Tạo quy trình</a>
    </header>
    @if(session('success'))
        <div class="alert alert-success" role="status">{{ session('success') }}</div>
    @endif
    <section class="workflow-panel">
        <div class="workflow-panel-heading"><div><h2>Quy trình xét duyệt theo vai trò</h2><p class="workflow-subtitle">Các bước xét duyệt theo thứ tự được cấu hình.</p></div><span class="workflow-count">{{ $workflows->total() }} quy trình</span></div>
        <div class="table-responsive">
            <table class="workflow-table">
                <colgroup><col style="width:26%"><col style="width:23%"><col style="width:29%"><col style="width:14%"><col style="width:8%"></colgroup>
                <thead><tr><th>Quy trình</th><th>Hoạt động áp dụng</th><th>Áp dụng · Các bước xét duyệt</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
                <tbody>
                @forelse($workflows as $workflow)
                    <tr>
                        <td><div class="workflow-name">{{ $workflow->name }}</div><div class="workflow-meta">{{ $workflow->code }} · ID {{ $workflow->id }}</div><div class="workflow-meta">Ngày tạo: {{ $workflow->created_at?->format('d/m/Y H:i') }}</div></td>
                        <td>
                            @foreach((array) ($workflow->applies_to ?: [\App\Models\ApprovalWorkflow::ACTIVITY_ORDER_CREATE]) as $activity)
                                <span class="workflow-chip">{{ $activityLabels[$activity] ?? $activity }}</span>
                            @endforeach
                        </td>
                        <td><div class="workflow-steps">
                            @forelse($workflow->steps as $step)
                                <div class="workflow-step"><span class="workflow-step-number">{{ $step->step_order }}</span><div>{{ $roleLabels[strtolower($step->role_slug)] ?? $step->role_slug }} @if($step->can_skip)<span class="workflow-meta">· Có thể bỏ qua</span>@endif</div></div>
                            @empty
                                <span class="workflow-meta">Chưa cấu hình bước xét duyệt.</span>
                            @endforelse
                            </div><div class="workflow-meta mt-2">Áp dụng theo vai trò · Chưa chỉ định user riêng.</div>
                        </td>
                        <td><span class="workflow-status {{ $workflow->is_active?'':'inactive' }}">{{ $workflow->is_active?'Đang áp dụng':'Không hoạt động' }}</span></td>
                        <td><a href="{{ route('approval-workflows.edit', $workflow) }}" class="btn btn-sm btn-outline-primary">Sửa</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="workflow-empty">Chưa có quy trình xét duyệt.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($workflows->hasPages())<div class="workflow-footer">{{ $workflows->links() }}</div>@endif
    </section>

    @if($processes->isNotEmpty())
    <section class="workflow-panel">
        <div class="workflow-panel-heading"><div><h2>Quy trình xử lý hoạt động</h2><p class="workflow-subtitle">Chỉ định vai trò và người thực hiện tại từng bước.</p></div><a href="{{ route('process-management.index') }}" class="btn btn-sm btn-outline-primary">Cấu hình quy trình</a></div>
        <div class="table-responsive">
            <table class="workflow-table">
                <colgroup><col style="width:26%"><col style="width:23%"><col style="width:29%"><col style="width:14%"><col style="width:8%"></colgroup>
                <thead><tr><th>Quy trình</th><th>Hoạt động áp dụng</th><th>Áp dụng · Người thực hiện</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
                <tbody>
                @foreach($processes as $process)
                    <tr>
                        <td><div class="workflow-name">{{ $process->name }}</div><div class="workflow-meta">{{ $process->code }}</div><div class="workflow-meta">Phiên bản {{ $process->version }}</div></td>
                        <td><span class="workflow-chip">{{ \App\Support\ProcessActivities::all()[$process->activity]['label'] ?? $process->activity }}</span></td>
                        <td>
                            <div class="workflow-meta mb-3">Khởi tạo: <strong>{{ $roleLabels[strtolower($process->configuration['initiator_role'] ?? '')] ?? ($process->configuration['initiator_role'] ?? 'Chưa cấu hình') }}</strong></div>
                            <div class="workflow-steps">
                            @foreach($process->configuration['steps'] ?? [] as $step)
                                <div class="workflow-step"><span class="workflow-step-number">{{ $loop->iteration }}</span><div><strong>{{ $step['name'] }}</strong><div class="workflow-meta">{{ ($step['assignment_mode']??'user_role')==='user'?'User cụ thể':($roleLabels[strtolower($step['role']??'')] ?? ($step['role']??'')) }}{{ ($step['assignment_mode']??'user_role')==='role'?'':(' · '.($processUsers->get($step['user_id'])?->name ?? 'Chưa chỉ định')) }}</div></div></div>
                            @endforeach
                            </div>
                        </td>
                        <td><span class="workflow-status {{ $process->is_active?'':'inactive' }}">{{ $process->is_active?'Đang áp dụng':'Không hoạt động' }}</span></td>
                        <td><a href="{{ route('process-management.index') }}#process-{{ $process->id }}" class="btn btn-sm btn-outline-primary">Sửa</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </section>
    @endif
</div>
@endsection
