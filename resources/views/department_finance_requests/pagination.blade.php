<nav aria-label="Phân trang phiếu" class="d-flex align-items-center gap-2 small fr-pagination">
    @if($requests->onFirstPage())
        <span class="btn btn-outline-secondary btn-sm disabled" aria-disabled="true">‹ Trước</span>
    @else
        <a class="btn btn-outline-secondary btn-sm" href="{{ $requests->previousPageUrl() }}" rel="prev">‹ Trước</a>
    @endif
    <span class="text-nowrap">Trang <strong>{{ $requests->currentPage() }}</strong> / {{ $requests->lastPage() }}</span>
    @if($requests->hasMorePages())
        <a class="btn btn-outline-secondary btn-sm" href="{{ $requests->nextPageUrl() }}" rel="next">Sau ›</a>
    @else
        <span class="btn btn-outline-secondary btn-sm disabled" aria-disabled="true">Sau ›</span>
    @endif
</nav>
