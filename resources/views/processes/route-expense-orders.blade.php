        <div class="table-responsive"><table class="process-table"><thead><tr><th>Chọn</th><th>Khách hàng / Đơn hàng</th><th aria-sort="{{ request('sort', 'date') === 'date' ? (request('direction', 'desc') === 'asc' ? 'ascending' : 'descending') : 'none' }}"><a class="shipping-sort-link" href="{{ route('shipping-expenses.index', array_merge(request()->except('page'), ['sort' => 'date', 'direction' => request('sort', 'date') === 'date' && request('direction', 'desc') === 'asc' ? 'desc' : 'asc'])) }}">Ngày giao <span aria-hidden="true">{{ request('sort', 'date') === 'date' ? (request('direction', 'desc') === 'asc' ? '↑' : '↓') : '↕' }}</span></a></th><th aria-sort="{{ request('sort', 'date') === 'status' ? (request('direction', 'desc') === 'asc' ? 'ascending' : 'descending') : 'none' }}"><a class="shipping-sort-link" href="{{ route('shipping-expenses.index', array_merge(request()->except('page'), ['sort' => 'status', 'direction' => request('sort', 'date') === 'status' && request('direction', 'desc') === 'asc' ? 'desc' : 'asc'])) }}">Trạng thái <span aria-hidden="true">{{ request('sort', 'date') === 'status' ? (request('direction', 'desc') === 'asc' ? '↑' : '↓') : '↕' }}</span></a></th><th aria-sort="{{ request('sort', 'date') === 'confirmation' ? (request('direction', 'desc') === 'asc' ? 'ascending' : 'descending') : 'none' }}"><a class="shipping-sort-link" href="{{ route('shipping-expenses.index', array_merge(request()->except('page'), ['sort' => 'confirmation', 'direction' => request('sort', 'date') === 'confirmation' && request('direction', 'desc') === 'asc' ? 'desc' : 'asc'])) }}">Xác nhận <span aria-hidden="true">{{ request('sort', 'date') === 'confirmation' ? (request('direction', 'desc') === 'asc' ? '↑' : '↓') : '↕' }}</span></a></th><th aria-sort="{{ request('sort', 'date') === 'payment' ? (request('direction', 'desc') === 'asc' ? 'ascending' : 'descending') : 'none' }}"><a class="shipping-sort-link" href="{{ route('shipping-expenses.index', array_merge(request()->except('page'), ['sort' => 'payment', 'direction' => request('sort', 'date') === 'payment' && request('direction', 'desc') === 'asc' ? 'desc' : 'asc'])) }}">Thanh toán <span aria-hidden="true">{{ request('sort', 'date') === 'payment' ? (request('direction', 'desc') === 'asc' ? '↑' : '↓') : '↕' }}</span></a></th><th>Phí đề nghị</th><th>Diễn giải</th></tr></thead><tbody>
            @forelse($route['orders'] as $order)
                <tr>
                    <td><input type="checkbox" class="form-check-input expense-select" aria-label="Chọn đơn {{ $order->code }}" @disabled(!$definition || !$order->expense_selectable)></td>
                    <td>
                        @include('processes.shipping-order-identity', ['order' => $order])
                        <input type="hidden" data-expense-input name="items[{{ $order->id }}][order_id]" value="{{ $order->id }}" disabled></td>
                    <td class="text-nowrap expense-delivery">{{ $order->delivered_at?->format('d/m/Y H:i') ?? '—' }}</td>
                    <td class="text-nowrap">{{ $order->status === 'completed' ? 'Hoàn thành' : 'Đã giao' }}</td>
                    <td class="text-nowrap">
                        @if($order->expense_claim_id)
                            <a href="{{ route('shipping-expenses.show', $order->expense_claim_id) }}">{{ ['running'=>'Chờ xác nhận','revision'=>'Cần điều chỉnh','confirmed'=>'Đã xác nhận','rejected'=>'Đã từ chối'][$order->expense_status] ?? 'Đã gửi yêu cầu' }} #{{ $order->expense_claim_id }}</a>
                        @else
                            Chưa gửi yêu cầu
                        @endif
                    </td>
                    <td class="text-nowrap">{{ $order->shipping_fee_transaction_id ? (['approved'=>'Đã thanh toán','pending_approval'=>'Chờ duyệt thanh toán','approved_pending_completion'=>'Chờ thanh toán','rejected'=>'Đã từ chối'][$order->expense_payment_status] ?? 'Đã gửi thanh toán') : 'Chưa gửi thanh toán' }}</td>
                    <td><input type="text" inputmode="numeric" data-money data-expense-input class="form-control text-end" autocomplete="off" name="items[{{ $order->id }}][amount]" value="{{ number_format($order->shipping_fee, 0, ',', '.') }}" disabled required style="min-width:130px"></td>
                    <td><input data-expense-input class="form-control" name="items[{{ $order->id }}][note]" maxlength="1000" disabled style="min-width:160px"></td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted">Không có đơn đã giao phù hợp bộ lọc.</td></tr>
            @endforelse
        </tbody></table></div>
