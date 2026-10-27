<div class="alert alert-warning">
    <div class="fw-semibold">{{ $afterImport ? 'Kết quả: khách có nhiều đơn cùng ngày' : 'Lưu ý: khách B đã có đơn cùng ngày với dữ liệu import' }} — {{ $customerName }}</div>
    <p class="small mb-2">Tính theo ngày giao; nếu chưa có ngày giao thì lấy ngày tạo đơn. Không tính đơn hủy, từ chối, nháp hoặc trong thùng rác. Admin có thể mở từng đơn để đối chiếu.</p>
    <div class="table-responsive"><table class="table table-sm mb-0">
        <thead><tr><th>Ngày</th><th>{{ $afterImport ? 'Số đơn hiện có' : 'Đã có / Sau import' }}</th><th>{{ $afterImport ? 'Các đơn cùng ngày' : 'Các đơn đã có' }}</th></tr></thead>
        <tbody>@foreach($sameDayOrders as $day)
            <tr>
                <td class="text-nowrap">{{ \Carbon\Carbon::parse($day['date'])->format('d/m/Y') }}</td>
                <td><strong>{{ $afterImport ? $day['resulting_count'].' đơn' : $day['existing_count'].' / '.$day['resulting_count'].' đơn' }}</strong></td>
                <td>@foreach($day['orders'] as $existingOrder)
                    <div><a href="{{ route('orders.show', $existingOrder['id']) }}" target="_blank" rel="noopener">{{ $existingOrder['code'] }}</a> · {{ $existingOrder['status'] }} · {{ number_format($existingOrder['total'], 0, ',', '.') }} đ</div>
                @endforeach</td>
            </tr>
        @endforeach</tbody>
    </table></div>
    @unless($afterImport)<div class="small mt-2">Nếu tiếp tục, mỗi ngày nêu trên được bổ sung 1 đơn mới. Vui lòng đối chiếu trước khi xác nhận import.</div>@endunless
</div>
