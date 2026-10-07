@auth
<div class="d-flex flex-wrap align-items-center gap-2 px-3 py-2 border-bottom bg-white" style="font-size:13px">
    <a href="{{ route('operating.index') }}" class="text-decoration-none fw-semibold">Điều hành & Giao việc</a>
    <div class="dropdown"><button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">Công việc của tôi</button>
        <div class="dropdown-menu" style="max-height:70vh;overflow:auto">
            @foreach(\App\Support\WebTaskNavigation::items(auth()->user()) as $item)
                <a class="dropdown-item {{ $item['active']?'active':'' }}" href="{{ $item['url'] }}"><i class="bi bi-{{ $item['icon'] }} me-2"></i>{{ $item['label'] }}</a>
            @endforeach
        </div>
    </div>
    <a href="{{ route('operating.index',['filter'=>'unaccepted']) }}" class="text-decoration-none">Nhận việc</a>
    <a href="{{ route('operating.index',['filter'=>'working']) }}" class="text-decoration-none">Đang thực hiện</a>
</div>
@endauth
