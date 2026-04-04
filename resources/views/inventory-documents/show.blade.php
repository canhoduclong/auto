@extends('layouts.app')

@section('title', __('inventory.titles.document_detail', ['id' => $inventoryDocument->id]))

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('inventory.titles.document_detail', ['id' => $inventoryDocument->id]) }}</h1>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">{{ __('inventory.sections.document_detail') }}</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>{{ __('inventory.labels.date') }}:</strong> {{ $inventoryDocument->document_date }}</p>
                    <p><strong>{{ __('inventory.labels.type') }}:</strong> {{ __('inventory.types.' . $inventoryDocument->type) }}</p>
                    <p><strong>{{ __('inventory.labels.warehouse') }}:</strong> {{ $inventoryDocument->warehouse->name }}</p>
                </div>
                <div class="col-md-6">
                    <p><strong>{{ __('inventory.labels.user') }}:</strong> {{ $inventoryDocument->user->name ?? __('inventory.default.na') }}</p>
                    <p><strong>{{ __('inventory.labels.shipping_fee') }}:</strong> {{ number_format($inventoryDocument->shipping_fee, 2) }}</p>
                    <p><strong>{{ __('inventory.labels.notes') }}:</strong> {{ $inventoryDocument->notes }}</p>
                </div>
            </div>

            <hr>

            <h4>{{ __('inventory.labels.items') }}</h4>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>{{ __('inventory.labels.product_variant') }}</th>
                        <th>{{ __('inventory.labels.quantity') }}</th>
                        <th>ĐVT</th>
                        <th>Khối lượng</th>
                        <th>{{ __('inventory.labels.unit_cost') }}</th>
                        <th>{{ __('inventory.labels.total_cost') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($inventoryDocument->items as $item)
                        @php
                            $unitLabel = $item->productVariant->product->unit_label ?? 'Cái';
                            $weightUnitLabel = in_array((string) ($item->productVariant->product->unit ?? 'cai'), ['con', 'cai'], true)
                                ? 'Kg'
                                : $unitLabel;
                            $lineWeight = (float) (($item->productVariant->size ?? 0) * ($item->quantity ?? 0));
                        @endphp
                        <tr>
                            <td>{{ $item->productVariant->product->name }} ({{ $item->productVariant->sku }})</td>
                            <td>{{ number_format((float) $item->quantity, 0, ',', '.') }}</td>
                            <td>{{ $unitLabel }}</td>
                            <td>{{ number_format($lineWeight, 3, ',', '.') }} {{ $weightUnitLabel }}</td>
                            <td>{{ number_format($item->unit_cost, 2) }}</td>
                            <td>{{ number_format($item->quantity * $item->unit_cost, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <a href="{{ route('inventory-documents.index') }}" class="btn btn-secondary">{{ __('inventory.buttons.back_to_list') }}</a>
        </div>
    </div>
</div>
@endsection
