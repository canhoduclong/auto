<div class="card h-100 p-3">
    <h2 class="h6">Khách Google Sheet chờ ghép · {{ $pending->total() }}</h2>
    <form method="GET" action="{{ route('customer-debt.sheet-opening.index') }}" class="d-flex flex-wrap gap-2 mb-3" data-sheet-search>
        <input type="hidden" name="tab" value="customers">
        @foreach(request()->only(['system_search', 'page']) as $field => $value)
            <input type="hidden" name="{{ $field }}" value="{{ $value }}">
        @endforeach
        <input type="search" name="source_search" class="form-control w-auto flex-grow-1" maxlength="150"
            aria-label="Tìm khách Google Sheet" placeholder="Tên, mã khách hoặc số điện thoại" value="{{ request('source_search') }}">
        <button class="btn btn-outline-primary">Tìm kiếm</button>
    </form>
    <p class="small text-danger" data-sheet-error role="alert" hidden></p>
    <div class="table-responsive">
        <table class="table table-sm align-middle">
            <thead><tr><th>Khách Google Sheet</th><th>Ghép hoặc tạo khách</th></tr></thead>
            <tbody>
                @forelse($pending as $source)
                    <tr>
                        <td>{{ $source['name'] }} @if($source['code'])<small class="text-muted d-block">{{ $source['code'] }}</small>@endif</td>
                        <td>
                            <div class="d-flex flex-wrap gap-1">
                                <button type="button" class="btn btn-sm btn-outline-primary" data-open-customer-match
                                    data-source-key="{{ $source['key'] }}" data-source-name="{{ $source['name'] }}">
                                    Ghép khách
                                </button>
                                @can('create', \App\Models\Customer::class)
                                    <form method="POST" action="{{ route('customer-debt.sheet-opening.link') }}">
                                        @csrf
                                        <input type="hidden" name="source_key" value="{{ $source['key'] }}">
                                        <button class="btn btn-sm btn-outline-secondary" name="action" value="create">Tạo khách mới</button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="2">Không có khách nguồn chờ ghép trong bộ lọc.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <nav data-sheet-pages aria-label="Trang khách Google Sheet">{{ $pending->links() }}</nav>
</div>
