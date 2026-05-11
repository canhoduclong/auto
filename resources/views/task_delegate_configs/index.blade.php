@extends('layouts.admin')

@section('title', 'Phan Quyen Giao Viec')

@push('styles')
<style>
.assigner-block { border: 1.5px solid #e2e8f0; border-radius: 12px; margin-bottom: 18px; overflow: hidden; }
.assigner-header { background: #f8fafc; padding: 12px 16px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; }
.assigner-body { padding: 12px 16px; }
.assignee-pill { display: inline-flex; align-items: center; gap: 6px; background: #dbeafe; color: #1d4ed8; border-radius: 20px; padding: 4px 10px; font-size: 12px; font-weight: 600; margin: 3px; }
.assignee-pill.inactive { background: #f1f5f9; color: #94a3b8; text-decoration: line-through; }
</style>
@endpush

@section('content')
<div class="content-wrapper">
<div class="content-header d-flex align-items-center justify-content-between py-3 px-4">
    <div>
        <h4 class="mb-0">Phan Quyen Giao Viec</h4>
        <small class="text-muted">Admin cau hinh nguoi dung nao duoc phep giao viec cho ai</small>
    </div>
    <a href="{{ route('task-delegate-configs.create') }}" class="btn btn-primary btn-sm">
        <i class="ph-plus me-1"></i>Them phan quyen
    </a>
</div>

<div class="content-body px-4 pb-4">
    @if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    {{-- Filter bar --}}
    <form method="GET" class="d-flex gap-2 mb-4">
        <select name="assigner_id" class="form-select form-select-sm" style="width:200px">
            <option value="">Tat ca nguoi giao viec</option>
            @foreach($users as $u)
                <option value="{{ $u->id }}" {{ request('assigner_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
            @endforeach
        </select>
        <select name="active" class="form-select form-select-sm" style="width:140px">
            <option value="">Tat ca trang thai</option>
            <option value="1" {{ request('active') === '1' ? 'selected' : '' }}>Dang hoat dong</option>
            <option value="0" {{ request('active') === '0' ? 'selected' : '' }}>Da tat</option>
        </select>
        <button type="submit" class="btn btn-sm btn-outline-primary">Loc</button>
        <a href="{{ route('task-delegate-configs.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
    </form>

    @if($grouped->isEmpty())
        <div class="text-center text-muted py-5">
            <i class="ph-users-three" style="font-size:48px;opacity:.3"></i>
            <p class="mt-2">Chua co phan quyen nao. Hay them phan quyen moi.</p>
        </div>
    @else
        @foreach($grouped as $assignerId => $entries)
            @php $assigner = $entries->first()->assigner; @endphp
            <div class="assigner-block">
                <div class="assigner-header">
                    <div>
                        <span class="fw-bold">{{ $assigner?->name ?? '#' . $assignerId }}</span>
                        <span class="text-muted ms-2 small">{{ $entries->count() }} nguoi nhan</span>
                        <span class="text-muted ms-1 small">&bull; Tao boi: {{ $entries->first()->admin?->name }}</span>
                    </div>
                    <form action="{{ route('task-delegate-configs.destroy-assigner') }}" method="POST"
                          onsubmit="return confirm('Xoa tat ca phan quyen cua {{ $assigner?->name }}?')">
                        @csrf
                        <input type="hidden" name="assigner_id" value="{{ $assignerId }}">
                        <button type="submit" class="btn btn-sm btn-outline-danger">
                            <i class="ph-trash me-1"></i>Xoa tat ca
                        </button>
                    </form>
                </div>
                <div class="assigner-body">
                    @foreach($entries as $cfg)
                        <span class="assignee-pill {{ $cfg->is_active ? '' : 'inactive' }}">
                            <i class="ph-user"></i>
                            {{ $cfg->assignee?->name }}
                            @if(!$cfg->is_active)
                                <span class="badge bg-secondary ms-1" style="font-size:9px">Tat</span>
                            @endif
                            {{-- Toggle --}}
                            <form action="{{ route('task-delegate-configs.toggle', $cfg) }}" method="POST" class="d-inline ms-1">
                                @csrf
                                <button type="submit" class="btn btn-link p-0 text-{{ $cfg->is_active ? 'warning' : 'success' }}"
                                        style="font-size:11px" title="{{ $cfg->is_active ? 'Tat' : 'Bat' }} phan quyen">
                                    <i class="ph-{{ $cfg->is_active ? 'pause' : 'play' }}"></i>
                                </button>
                            </form>
                            {{-- Delete --}}
                            <form action="{{ route('task-delegate-configs.destroy', $cfg) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('Xoa phan quyen giao viec cho {{ $cfg->assignee?->name }}?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-link p-0 text-danger" style="font-size:11px">
                                    <i class="ph-x"></i>
                                </button>
                            </form>
                        </span>
                    @endforeach
                    @if($cfg->note ?? null)
                        <div class="small text-muted mt-2 fst-italic">{{ $entries->first()->note }}</div>
                    @endif
                </div>
            </div>
        @endforeach

        <div class="mt-2">{{ $configs->links() }}</div>
    @endif
</div>
</div>
@endsection
