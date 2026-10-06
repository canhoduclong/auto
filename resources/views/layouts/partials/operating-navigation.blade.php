@auth
<div class="d-flex flex-wrap align-items-center gap-2 px-3 py-2 border-bottom bg-white" style="font-size:13px">
    <a href="{{ route('operating.index') }}" class="text-decoration-none fw-semibold">Điều hành & Giao việc</a>
    <a href="{{ route('operating.index',['filter'=>'received']) }}" class="text-decoration-none">Việc tôi nhận</a>
    <a href="{{ route('operating.index',['filter'=>'assigned']) }}" class="text-decoration-none">Việc tôi giao</a>
    <a href="{{ route('operating.index') }}#bieu-quyet" class="text-decoration-none">Biểu quyết</a>
</div>
@endauth
