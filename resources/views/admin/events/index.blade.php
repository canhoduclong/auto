@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Nhat ky su kien admin</h4>
        <a href="{{ route('admin.notifications.index') }}" class="btn btn-outline-secondary btn-sm">Xem thong bao</a>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.events.index') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Loai su kien</label>
                    <select name="event_type" class="form-select">
                        <option value="">Tat ca</option>
                        @foreach($eventTypes as $type)
                            <option value="{{ $type }}" {{ request('event_type') === $type ? 'selected' : '' }}>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Hanh dong</label>
                    <select name="action" class="form-select">
                        <option value="">Tat ca</option>
                        @foreach($actions as $action)
                            <option value="{{ $action }}" {{ request('action') === $action ? 'selected' : '' }}>{{ $action }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Tu khoa</label>
                    <input type="text" class="form-control" name="q" value="{{ request('q') }}" placeholder="Tim theo tieu de/noi dung">
                </div>
                <div class="col-md-2 d-grid">
                    <button class="btn btn-primary" type="submit">Loc</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>Thoi gian</th>
                        <th>Tieu de</th>
                        <th>Loai</th>
                        <th>Hanh dong</th>
                        <th>Nguoi thuc hien</th>
                        <th>Noi dung</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($events as $event)
                        <tr>
                            <td>{{ optional($event->created_at)->format('d/m/Y H:i:s') }}</td>
                            <td>{{ $event->title }}</td>
                            <td><span class="badge bg-light text-dark">{{ $event->event_type }}</span></td>
                            <td><span class="badge bg-info text-dark">{{ $event->action }}</span></td>
                            <td>{{ $event->actor->name ?? 'System' }}</td>
                            <td>{{ $event->message ?? '-' }}</td>
                            <td>
                                @if($event->url)
                                    <a href="{{ $event->url }}" class="btn btn-sm btn-outline-primary">Mo doi tuong</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">Chua co su kien nao duoc ghi nhan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $events->links('pagination::bootstrap-5') }}
    </div>
</div>
@endsection
