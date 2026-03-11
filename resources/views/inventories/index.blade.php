@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Thong ke kho hang</h1>

    <div class="card mb-3">
        <div class="card-header">Bo loc thong ke</div>
        <div class="card-body">
            <form method="GET" action="{{ route('inventories.index') }}" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Kho</label>
                    <select name="warehouse_id" class="form-select">
                        <option value="">Tat ca kho</option>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" {{ (string) $warehouseId === (string) $warehouse->id ? 'selected' : '' }}>
                                {{ $warehouse->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Ngay thong ke</label>
                    <input type="date" class="form-control" name="selected_date" value="{{ $selectedDate }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Khoang nhanh</label>
                    <select name="range_preset" class="form-select">
                        <option value="week" {{ $rangeStats['range_preset'] === 'week' ? 'selected' : '' }}>1 tuan</option>
                        <option value="month" {{ $rangeStats['range_preset'] === 'month' ? 'selected' : '' }}>1 thang</option>
                        <option value="custom" {{ $rangeStats['range_preset'] === 'custom' ? 'selected' : '' }}>Tu chon</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Tu ngay</label>
                    <input type="date" class="form-control" name="from_date" value="{{ $rangeStats['from_date'] }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Den ngay</label>
                    <input type="date" class="form-control" name="to_date" value="{{ $rangeStats['to_date'] }}">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Xem thong ke</button>
                    <a href="{{ route('inventories.index') }}" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header">Tong ton hien tai</div>
                <div class="card-body">
                    <p class="mb-1"><strong>On Hand:</strong> {{ number_format($stockSummary['on_hand']) }}</p>
                    <p class="mb-1"><strong>Reserved:</strong> {{ number_format($stockSummary['reserved']) }}</p>
                    <p class="mb-0"><strong>Available:</strong> {{ number_format($stockSummary['available']) }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header">Thong ke theo ngay: {{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }}</div>
                <div class="card-body">
                    <p class="mb-1"><strong>So phieu:</strong> {{ number_format($dailyStats['document_count']) }}</p>
                    <p class="mb-1"><strong>Nhap:</strong> {{ number_format($dailyStats['import_qty']) }}</p>
                    <p class="mb-1"><strong>Xuat:</strong> {{ number_format($dailyStats['export_qty']) }}</p>
                    <p class="mb-1"><strong>Dieu chinh:</strong> {{ number_format($dailyStats['adjustment_qty']) }}</p>
                    <p class="mb-0"><strong>Net:</strong> {{ number_format($dailyStats['net_qty']) }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header">Thong ke tu {{ \Carbon\Carbon::parse($rangeStats['from_date'])->format('d/m/Y') }} den {{ \Carbon\Carbon::parse($rangeStats['to_date'])->format('d/m/Y') }}</div>
                <div class="card-body">
                    <p class="mb-1"><strong>So phieu:</strong> {{ number_format($rangeStats['document_count']) }}</p>
                    <p class="mb-1"><strong>Nhap:</strong> {{ number_format($rangeStats['import_qty']) }}</p>
                    <p class="mb-1"><strong>Xuat:</strong> {{ number_format($rangeStats['export_qty']) }}</p>
                    <p class="mb-1"><strong>Dieu chinh:</strong> {{ number_format($rangeStats['adjustment_qty']) }}</p>
                    <p class="mb-0"><strong>Net:</strong> {{ number_format($rangeStats['net_qty']) }}</p>
                </div>
            </div>
        </div>
    </div>

    <h5>Chi tiet ton kho</h5>
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Product Variant</th>
                <th>Warehouse</th>
                <th>On Hand</th>
                <th>Reserved</th>
                <th>Available</th>
                <th>Low Stock Threshold</th>
                <th>Created At</th>
                <th>Updated At</th>
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