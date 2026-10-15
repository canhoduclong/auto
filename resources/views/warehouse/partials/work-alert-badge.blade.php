@if((int) $count > 0)
    <span class="badge bg-danger rounded-pill d-inline-flex align-items-center justify-content-center ms-1"
          style="min-width:22px;height:22px;padding:0 5px;font-size:.72rem;vertical-align:middle;"
          title="{{ $count }} việc cần xử lý hoặc thay đổi cần kiểm tra"
          aria-label="{{ $count }} việc cần xử lý hoặc thay đổi cần kiểm tra">{{ (int) $count > 99 ? '99+' : (int) $count }}</span>
@endif
