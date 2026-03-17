@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h4 class="mb-0">{{ __('orders.titles.index') }}</h4>
        <div>
            <a href="{{ route('approval-workflows.create') }}" class="btn btn-outline-primary">{{ __('orders.buttons.create_workflow') }}</a>
            <a href="{{ route('orders.create') }}" class="btn btn-success">+ {{ __('orders.buttons.add_order') }}</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">
            <h5 class="card-title mb-0">{{ __('orders.labels.filter') }}</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('orders.index') }}">
                <div class="row">
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="customer_name" class="form-label">{{ __('orders.labels.customer') }}</label>
                            <input type="text" name="customer_name" id="customer_name" class="form-control" value="{{ request('customer_name') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="phone_number" class="form-label">{{ __('orders.labels.phone') }}</label>
                            <input type="text" name="phone_number" id="phone_number" class="form-control" value="{{ request('phone_number') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="user_id" class="form-label">{{ __('orders.labels.updated_by') }}</label>
                            <select name="user_id" id="user_id" class="form-select">
                                <option value="">{{ __('orders.labels.all') }}</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="team_id" class="form-label">{{ __('orders.labels.team') }}</label>
                            <select name="team_id" id="team_id" class="form-select">
                                <option value="">{{ __('orders.labels.all') }}</option>
                                @foreach($teams as $team)
                                    <option value="{{ $team->id }}" {{ (string) request('team_id') === (string) $team->id ? 'selected' : '' }}>{{ $team->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="payment_status" class="form-label">{{ __('orders.labels.payment_status') }}</label>
                            <select name="payment_status" id="payment_status" class="form-select">
                                <option value="">{{ __('orders.labels.all') }}</option>
                                <option value="paid" {{ request('payment_status') == 'paid' ? 'selected' : '' }}>{{ __('orders.payment_statuses.paid') }}</option>
                                <option value="unpaid" {{ request('payment_status') == 'unpaid' ? 'selected' : '' }}>{{ __('orders.payment_statuses.unpaid') }}</option>
                                <option value="partially_paid" {{ request('payment_status') == 'partially_paid' ? 'selected' : '' }}>{{ __('orders.payment_statuses.partially_paid') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="status" class="form-label">{{ __('orders.labels.status') }}</label>
                            <select name="status" id="status" class="form-select">
                                <option value="">{{ __('orders.labels.all') }}</option>
                                @foreach($statusOptions as $value => $label)
                                    <option value="{{ $value }}" {{ request('status') == $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="from_date" class="form-label">{{ __('orders.labels.from_date') }}</label>
                            <input type="date" name="from_date" id="from_date" class="form-control" value="{{ request('from_date') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="to_date" class="form-label">{{ __('orders.labels.to_date') }}</label>
                            <input type="date" name="to_date" id="to_date" class="form-control" value="{{ request('to_date') }}">
                        </div>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="my_pending_approval" id="my_pending_approval" value="1" {{ request('my_pending_approval') ? 'checked' : '' }}>
                            <label class="form-check-label" for="my_pending_approval">{{ __('orders.labels.my_pending_approval') }}</label>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">{{ __('orders.buttons.filter') }}</button>
                <a href="{{ route('orders.index') }}" class="btn btn-secondary">{{ __('orders.buttons.clear_filter') }}</a>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">{{ __('orders.labels.statistics') }}</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <p><strong>{{ __('orders.stats.total_invoice') }}:</strong> {{ number_format($totalInvoiceAmount, 0, ',', '.') }} đ</p>
                </div>
                <div class="col-md-3">
                    <p><strong>{{ __('orders.stats.total_paid') }}:</strong> {{ number_format($totalPaidAmount, 0, ',', '.') }} đ</p>
                </div>
                <div class="col-md-3">
                    <p><strong>{{ __('orders.stats.total_outstanding') }}:</strong> {{ number_format($totalOutstandingAmount, 0, ',', '.') }} đ</p>
                </div>
                <div class="col-md-3">
                    <p><strong>{{ __('orders.stats.paid_orders') }}:</strong> {{ $fullyPaidOrders }}</p>
                    <p><strong>{{ __('orders.stats.unpaid_orders') }}:</strong> {{ $unpaidOrders }}</p>
                    <p><strong>{{ __('orders.stats.partial_paid_orders') }}:</strong> {{ $partiallyPaidOrders }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="table-responsive mt-3">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>{{ __('orders.labels.code') }}</th>
                    <th>{{ __('orders.labels.customer') }}</th>
                    <th>{{ __('orders.labels.employee') }}</th>
                    <th>{{ __('orders.labels.total') }}</th>
                    <th>{{ __('orders.labels.status') }}</th>
                    <th>{{ __('orders.labels.payment_status') }}</th>
                    <th>{{ __('orders.labels.amount_paid') }}</th>
                    <th>{{ __('orders.labels.created_at') }}</th>
                    <th>{{ __('orders.labels.approval_column') }}</th>
                    <th>{{ __('orders.labels.actions') }}</th>
                    <th>{{ __('orders.labels.qrcode') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($orders as $order)
                <tr>
                    <td>{{ $order->id }}</td>
                    <td>{{ $order->code }}</td>
                    <td>{{ $order->customer->name ?? '' }}</td>
                    <td>{{ $order->user->name ?? '' }}</td>
                    <td>{{ number_format($order->total, 0, ',', '.') }} đ</td>
                    <td>
                        <button class="btn btn-sm btn-toggle-status" data-id="{{ $order->id }}" data-status="{{ $order->status }}">
                            {{ $order->status }}
                        </button>
                    </td>
                    <td>
                        @if($order->status === \App\Models\Order::STATUS_COMPLETED)
                            <span class="badge bg-success">{{ __('orders.payment_badges.completed') }}</span>
                        @elseif($order->isPaid())
                            <span class="badge bg-success">{{ __('orders.payment_badges.fully_paid') }}</span>
                        @elseif($order->isPartialPaid())
                            <span class="badge bg-warning text-dark">{{ __('orders.payment_badges.partial_paid') }}</span>
                        @else
                            <span class="badge bg-danger">{{ __('orders.payment_badges.unpaid') }}</span>
                        @endif
                    </td>
                    <td>{{ $order->created_at }}</td>
                    <td>
                        @php
                            $currentApproval = $currentStepByOrder[$order->id] ?? null;
                            $canApprove = $canApproveByOrder[$order->id] ?? false;
                        @endphp

                        @if($order->status === \App\Enums\OrderStatus::Approved->value)
                            <span class="badge bg-success">Đã duyệt</span>
                        @elseif($order->status === \App\Enums\OrderStatus::Rejected->value)
                            <span class="badge bg-danger">Đã từ chối</span>
                        @elseif($currentApproval && $currentApproval->step)
                            <div class="mb-2">
                                <span class="badge bg-info text-dark">
                                    B{{ $currentApproval->step->step_order }} - {{ $currentApproval->step->role_slug }}
                                </span>
                            </div>
                            @if($canApprove)
                                <form method="POST" action="{{ route('orders.approve', $order) }}" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="note" value="{{ __('orders.approval.quick_approve_note') }}">
                                    <button type="submit" class="btn btn-sm btn-success">{{ __('orders.buttons.approve') }}</button>
                                </form>
                                <form method="POST" action="{{ route('orders.reject', $order) }}" class="d-inline ms-1">
                                    @csrf
                                    <input type="hidden" name="note" value="{{ __('orders.approval.quick_reject_note') }}">
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('{{ __('orders.confirms.reject_order') }}')">{{ __('orders.buttons.reject') }}</button>
                                </form>
                            @else
                                <small class="text-muted d-block">{{ __('orders.approval.not_your_role') }}</small>
                            @endif
                        @else
                            <small class="text-muted">{{ __('orders.approval.no_pending_step') }}</small>
                        @endif
                    </td>
                    <td>
                        @php
                            $paid = $order->transactions->where('type', 'payment')->sum('amount') - $order->transactions->where('type', 'refund')->sum('amount');
                        @endphp
                        @if(!$order->isPaid())
                            <span class="text-primary fw-bold">{{ number_format($paid, 0, ',', '.') }} đ</span>
                            <a href="{{ route('transactions.create', ['order_id' => $order->id]) }}" class="btn btn-success btn-sm ms-2">{{ __('orders.buttons.pay') }}</a>
                        @else
                            <span class="text-success">{{ __('orders.payment_badges.fully_paid') }}</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('orders.show', $order) }}" class="btn btn-sm btn-info">{{ __('orders.buttons.view') }}</a>
                        @if(in_array($order->status, ['picked_up', 'shipping', 'completed'], true))
                            <a href="{{ route('order-returns.create', ['order_id' => $order->id]) }}" class="btn btn-warning btn-sm">Tra hang</a>
                        @endif
                        @if(!$order->isPaid() && $order->status !== \App\Models\Order::STATUS_COMPLETED)
                            <a href="{{ route('orders.edit', $order->id) }}" class="btn btn-info btn-sm">{{ __('orders.buttons.edit') }}</a>
                        @endif
                        <form action="{{ route('orders.destroy', $order->id) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('{{ __('orders.confirms.delete_order') }}')">{{ __('orders.buttons.delete') }}</button>
                        </form>
                        @if($order->status !== \App\Models\Order::STATUS_COMPLETED && !$order->isPaid())
                            <a href="{{ route('transactions.create', ['order_id' => $order->id]) }}" class="btn btn-success btn-sm">{{ __('orders.buttons.pay') }}</a>
                        @endif
                    </td>
                    <td>
                        @if($order->qr_code)
                            <img src="data:image/svg+xml;base64,{{ $order->qr_code }}" alt="QR Code">
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        {{ $orders->appends(request()->query())->links() }}
    </div>
</div>
@endsection
