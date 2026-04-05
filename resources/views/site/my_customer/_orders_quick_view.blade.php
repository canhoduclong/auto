@if($orders->isEmpty())
    <div class="alert alert-info">No orders found for this customer.</div>
@else
    <table class="table table-sm table-bordered">
        <thead>
            <tr>
                <th></th>
                <th>Order ID</th>
                <th>Date</th>
                <th>Total</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($orders as $order)
                <tr class="order-row">
                    <td>
                        <button class="btn btn-secondary btn-sm toggle-products" data-bs-toggle="collapse" data-bs-target="#products-{{ $order->id }}">+</button>
                    </td>
                    <td>{{ $order->id }}</td>
                    <td>{{ $order->created_at->format('d/m/Y') }}</td>
                    <td>{{ number_format($order->total_amount) }}</td>
                    <td>{{ $order->status }}</td>
                </tr>
                <tr class="collapse" id="products-{{ $order->id }}">
                    <td colspan="5">
                        <table class="table table-sm table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Quantity</th>
                                    <th>Price</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->items as $item)
                                    <tr>
                                        <td>{{ optional($item->product)->name }} ({{ optional($item->variant)->name }})</td>
                                        <td>{{ $item->quantity }}</td>
                                        <td>{{ number_format($item->price) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
