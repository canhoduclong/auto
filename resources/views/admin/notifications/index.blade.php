@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Thong bao admin</h4>
        <form action="{{ route('admin.notifications.read_all') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-sm btn-outline-primary">Danh dau tat ca da doc</button>
        </form>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Tieu de</th>
                            <th>Noi dung</th>
                            <th>Thoi gian</th>
                            <th>Trang thai</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($notifications as $notification)
                            <tr class="{{ is_null($notification->read_at) ? 'table-warning' : '' }}">
                                <td>{{ $notification->data['title'] ?? 'Thong bao' }}</td>
                                <td>{{ $notification->data['message'] ?? '-' }}</td>
                                <td>{{ optional($notification->created_at)->format('d/m/Y H:i') }}</td>
                                <td>
                                    @if(is_null($notification->read_at))
                                        <span class="badge bg-warning text-dark">Chua doc</span>
                                    @else
                                        <span class="badge bg-success">Da doc</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <form action="{{ route('admin.notifications.read', $notification->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-primary">Xem su kien</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">Chua co thong bao nao.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-3">
        {{ $notifications->links('pagination::bootstrap-5') }}
    </div>
</div>
@endsection
