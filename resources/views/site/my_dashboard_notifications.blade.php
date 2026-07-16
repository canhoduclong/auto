@extends('layouts.site')

@section('title', 'Thông báo của tôi')

@section('content')
<div class="container py-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h2 class="mb-1">Thông báo của tôi</h2>
            <div class="text-muted">Chỉ hiển thị thông báo được gửi tới tài khoản của bạn.</div>
        </div>
        <form method="POST" action="{{ route('pages.my_dashboard.notifications.read_all') }}">
            @csrf
            <button class="btn btn-outline-primary" type="submit">Đánh dấu tất cả đã đọc</button>
        </form>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="list-group list-group-flush">
            @forelse($notifications as $notification)
                @php($data = $notification->data ?? [])
                <a href="{{ route('pages.my_dashboard.notifications.open', $notification->id) }}"
                    class="list-group-item list-group-item-action p-3 {{ is_null($notification->read_at) ? 'bg-light' : '' }}">
                    <div class="d-flex justify-content-between gap-3">
                        <div>
                            <div class="fw-semibold">{{ $data['title'] ?? 'Thông báo' }}</div>
                            <div class="text-muted mt-1">{{ $data['message'] ?? '' }}</div>
                        </div>
                        @if(is_null($notification->read_at))
                            <span class="badge bg-danger align-self-start">Mới</span>
                        @endif
                    </div>
                    <div class="small text-muted mt-2">{{ optional($notification->created_at)->format('d/m/Y H:i') }}</div>
                </a>
            @empty
                <div class="p-5 text-center text-muted">Bạn chưa có thông báo nào.</div>
            @endforelse
        </div>
    </div>

    <div class="mt-3">
        {{ $notifications->links() }}
    </div>
</div>
@endsection
