@extends('layouts.app')
@section('content')
<div class="container">
    <h2>Import công ty từ Excel</h2>
    <div class="alert alert-info">
        <b>Hướng dẫn file Excel import:</b><br>
        - Hàng đầu tiên phải có các cột: <b>name</b> (bắt buộc), <b>mst</b>, <b>phone</b>, <b>email</b>, <b>address</b>, <b>note</b>.<br>
        - Cột <b>phone</b> nên để dạng chuỗi, ví dụ: '0123456789'.<br>
        - Nếu thiếu cột hoặc sai tên cột sẽ báo lỗi.<br>
        <a href="/sample/company_import_sample.xlsx" target="_blank">Tải file mẫu</a>
    </div>
    <form action="{{ route('companies.import') }}" method="POST" enctype="multipart/form-data" class="mb-4">
        @csrf
        <input type="file" name="file" accept=".xlsx,.xls" required>
        <button class="btn btn-primary">Import</button>
    </form>
    @if(isset($success))
        <div class="alert alert-success">{{ $success }}</div>
    @endif
    @if(isset($import_failures) && count($import_failures))
        <div class="alert alert-danger">
            <strong>Các dòng lỗi khi import:</strong>
            <ul>
                @foreach($import_failures as $err)
                    <li>
                        <b>Dòng:</b> {{ $err['row'] }} | <b>Cột:</b> {{ $err['attribute'] }}<br>
                        <b>Lỗi:</b> {{ implode('; ', $err['errors']) }}<br>
                        <b>Giá trị:</b> {{ json_encode($err['values']) }}
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
    @if(isset($imported) && count($imported))
        <div class="alert alert-info mt-3">
            <strong>Kết quả import từng dòng:</strong>
            <ul>
                @foreach($imported as $rec)
                    <li>
                        @if($rec['status']==='success')
                            <span class="text-success">✔</span> Thành công: {{ json_encode($rec['row']) }}
                        @else
                            <span class="text-danger">✖</span> Thất bại: {{ json_encode($rec['row']) }}<br>
                            <b>Lỗi:</b> {{ $rec['error'] ?? '' }}
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
    <a href="{{ route('companies.index') }}" class="btn btn-secondary mt-2">Quay lại danh sách công ty</a>
</div>
@endsection
