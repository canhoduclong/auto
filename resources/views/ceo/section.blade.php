@extends('layouts.ceo')

@section('title', $pageTitle)
@section('subtitle', $pageSubtitle)

@push('styles')
<style>
    .ceo-range { margin-bottom: 12px; display: flex; gap: 8px; flex-wrap: wrap; }
    .ceo-cards { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; margin-bottom: 14px; }
    .ceo-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 14px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.05);
    }
    .ceo-card .label { color: #64748b; font-size: .78rem; text-transform: uppercase; letter-spacing: .04em; }
    .ceo-card .value { font-size: 1.45rem; font-weight: 800; color: #0f172a; margin-top: 8px; }
    .ceo-table-wrap {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.05);
    }
    .ceo-table-head { padding: 14px 16px; border-bottom: 1px solid #e2e8f0; }
    .ceo-table { margin-bottom: 0; }
    .ceo-table th { font-size: .75rem; text-transform: uppercase; color: #64748b; }
    @media (max-width: 992px) { .ceo-cards { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
<div class="ceo-range">
    <span class="badge text-bg-light border">{{ $rangeLabel }}</span>
    <span class="badge text-bg-light border">{{ $from->format('d/m/Y') }} - {{ $to->format('d/m/Y') }}</span>
</div>

<div class="ceo-cards">
    @foreach($cards as $card)
        <div class="ceo-card">
            <div class="label">{{ $card['label'] }}</div>
            <div class="value">{{ $card['value'] }}</div>
        </div>
    @endforeach
</div>

<div class="ceo-table-wrap">
    <div class="ceo-table-head">
        <h6 class="mb-0">{{ $tableTitle }}</h6>
    </div>
    <div class="table-responsive">
        <table class="table ceo-table align-middle">
            <thead>
                <tr>
                    @foreach($columns as $column)
                        <th>{{ $column }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>
                        @foreach($row as $cell)
                            <td>{{ $cell }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($columns) }}" class="text-center text-muted py-4">Không có dữ liệu trong kỳ.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
