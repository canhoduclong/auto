@extends('layouts.app')

@section('content')
<div class="container">
    <h1>{{ __('inventory.titles.inventories') }}</h1>

    <div class="card mb-3">
        <div class="card-header">{{ __('inventory.filters.title') }}</div>
        <div class="card-body">
            <form method="GET" action="{{ route('inventories.index') }}" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">{{ __('inventory.labels.warehouse') }}</label>
                    <select name="warehouse_id" class="form-select">
                        <option value="">{{ __('inventory.filters.all_warehouses') }}</option>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" {{ (string) $warehouseId === (string) $warehouse->id ? 'selected' : '' }}>
                                {{ $warehouse->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('inventory.filters.selected_date') }}</label>
                    <input type="date" class="form-control" name="selected_date" value="{{ $selectedDate }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{ __('inventory.filters.range_quick') }}</label>
                    <select name="range_preset" class="form-select">
                        <option value="week" {{ $rangeStats['range_preset'] === 'week' ? 'selected' : '' }}>{{ __('inventory.filters.week') }}</option>
                        <option value="month" {{ $rangeStats['range_preset'] === 'month' ? 'selected' : '' }}>{{ __('inventory.filters.month') }}</option>
                        <option value="custom" {{ $rangeStats['range_preset'] === 'custom' ? 'selected' : '' }}>{{ __('inventory.filters.custom') }}</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{ __('inventory.filters.from_date') }}</label>
                    <input type="date" class="form-control" name="from_date" value="{{ $rangeStats['from_date'] }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{ __('inventory.filters.to_date') }}</label>
                    <input type="date" class="form-control" name="to_date" value="{{ $rangeStats['to_date'] }}">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">{{ __('inventory.buttons.view_stats') }}</button>
                    <a href="{{ route('inventories.index') }}" class="btn btn-secondary">{{ __('inventory.buttons.reset') }}</a>
                </div>
            </form>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header">{{ __('inventory.sections.stock_summary') }}</div>
                <div class="card-body">
                    <p class="mb-1"><strong>{{ __('inventory.labels.on_hand') }}:</strong> {{ number_format($stockSummary['on_hand']) }}</p>
                    <p class="mb-1"><strong>{{ __('inventory.labels.reserved') }}:</strong> {{ number_format($stockSummary['reserved']) }}</p>
                    <p class="mb-0"><strong>{{ __('inventory.labels.available') }}:</strong> {{ number_format($stockSummary['available']) }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header">{{ __('inventory.sections.daily_stats', ['date' => \Carbon\Carbon::parse($selectedDate)->format('d/m/Y')]) }}</div>
                <div class="card-body">
                    <p class="mb-1"><strong>{{ __('inventory.labels.document_count') }}:</strong> {{ number_format($dailyStats['document_count']) }}</p>
                    <p class="mb-1"><strong>{{ __('inventory.labels.import_qty') }}:</strong> {{ number_format($dailyStats['import_qty']) }}</p>
                    <p class="mb-1"><strong>{{ __('inventory.labels.export_qty') }}:</strong> {{ number_format($dailyStats['export_qty']) }}</p>
                    <p class="mb-1"><strong>{{ __('inventory.labels.adjustment_qty') }}:</strong> {{ number_format($dailyStats['adjustment_qty']) }}</p>
                    <p class="mb-0"><strong>{{ __('inventory.labels.net_qty') }}:</strong> {{ number_format($dailyStats['net_qty']) }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header">{{ __('inventory.sections.range_stats', ['from' => \Carbon\Carbon::parse($rangeStats['from_date'])->format('d/m/Y'), 'to' => \Carbon\Carbon::parse($rangeStats['to_date'])->format('d/m/Y')]) }}</div>
                <div class="card-body">
                    <p class="mb-1"><strong>{{ __('inventory.labels.document_count') }}:</strong> {{ number_format($rangeStats['document_count']) }}</p>
                    <p class="mb-1"><strong>{{ __('inventory.labels.import_qty') }}:</strong> {{ number_format($rangeStats['import_qty']) }}</p>
                    <p class="mb-1"><strong>{{ __('inventory.labels.export_qty') }}:</strong> {{ number_format($rangeStats['export_qty']) }}</p>
                    <p class="mb-1"><strong>{{ __('inventory.labels.adjustment_qty') }}:</strong> {{ number_format($rangeStats['adjustment_qty']) }}</p>
                    <p class="mb-0"><strong>{{ __('inventory.labels.net_qty') }}:</strong> {{ number_format($rangeStats['net_qty']) }}</p>
                </div>
            </div>
        </div>
    </div>

    <h5>{{ __('inventory.sections.inventory_detail') }}</h5>
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>{{ __('inventory.labels.product_variant') }}</th>
                <th>{{ __('inventory.labels.warehouse') }}</th>
                <th>{{ __('inventory.labels.on_hand') }}</th>
                <th>{{ __('inventory.labels.reserved') }}</th>
                <th>{{ __('inventory.labels.available') }}</th>
                <th>{{ __('inventory.labels.low_stock_threshold') }}</th>
                <th>{{ __('inventory.labels.created_at') }}</th>
                <th>{{ __('inventory.labels.updated_at') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($inventories as $inventory)
            <tr>
                <td>{{ $inventory->id }}</td>
                <td>
                    {{ $inventory->productVariant->sku ?? ('#' . $inventory->product_variant_id) }}
                    <br>
                    <small class="text-muted">{{ $inventory->productVariant->product->name ?? '' }}</small>
                </td>
                <td>{{ $inventory->warehouse->name ?? ('#' . $inventory->warehouse_id) }}</td>
                <td>{{ $inventory->on_hand }}</td>
                <td>{{ $inventory->reserved }}</td>
                <td>{{ $inventory->available }}</td>
                <td>{{ $inventory->low_stock_threshold }}</td>
                <td>{{ $inventory->created_at }}</td>
                <td>{{ $inventory->updated_at }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    {{ $inventories->links() }}
</div>
@endsection