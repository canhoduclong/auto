@extends('layouts.app')
@section('content')
<div class="container">
    <h2>{{ __('customers.import.title') }}</h2>
    <div class="alert alert-info">
        <b>{{ __('customers.import.guide_title') }}</b><br>
        - {{ __('customers.import.guide_line_1') }}<br>
        - {{ __('customers.import.guide_line_2') }}<br>
        - {{ __('customers.import.guide_line_3') }}<br>
        - {{ __('customers.import.guide_line_4') }}<br>
        <a href="/sample/customer_import_sample.xlsx" target="_blank">{{ __('customers.import.download_sample') }}</a>
    </div>
    <form action="{{ route('customers.import') }}" method="POST" enctype="multipart/form-data" class="mb-4">
        @csrf
        <input type="file" name="file" accept=".xlsx,.xls" required>
        <button class="btn btn-primary">{{ __('customers.index.import_excel') }}</button>
    </form>
    @if(isset($success))
        <div class="alert alert-success">{{ $success }}</div>
    @endif
    @if(isset($import_failures) && count($import_failures))
        <div class="alert alert-danger">
            <strong>{{ __('customers.import.errors_title') }}</strong>
            <ul>
                @foreach($import_failures as $err)
                    <li>
                        <b>{{ __('customers.import.row') }}:</b> {{ $err['row'] }} | <b>{{ __('customers.import.column') }}:</b> {{ $err['attribute'] }}<br>
                        <b>{{ __('customers.import.error') }}:</b> {{ implode('; ', $err['errors']) }}<br>
                        <b>{{ __('customers.import.value') }}:</b> {{ json_encode($err['values']) }}
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
    @if(isset($imported) && count($imported))
        <div class="alert alert-info mt-3">
            <strong>{{ __('customers.import.result_each_row') }}</strong>
            <ul>
                @foreach($imported as $rec)
                    <li>
                        @if($rec['status']==='success')
                            <span class="text-success">✔</span> {{ __('customers.import.success') }}: {{ json_encode($rec['row']) }}
                        @else
                            <span class="text-danger">✖</span> {{ __('customers.import.failed') }}: {{ json_encode($rec['row']) }}<br>
                            <b>{{ __('customers.import.error') }}:</b> {{ $rec['error'] ?? '' }}
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
    <a href="{{ route('customers.index') }}" class="btn btn-secondary mt-2">{{ __('customers.import.back_to_list') }}</a>
</div>
@endsection
