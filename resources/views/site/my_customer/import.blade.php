@extends('layouts.site')

@section('content')
<div class="container">
    <h1>Import khách hàng</h1>

    <div class="card">
        <div class="card-header">Import khách hàng từ file</div>
        <div class="card-body">
            <form action="{{ route('my_customer.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label for="file" class="form-label">Chọn file (.xlsx, .csv)</label>
                    <input type="file" class="form-control" id="file" name="file" accept=".xlsx, .csv" required>
                </div>
                <button type="submit" class="btn btn-primary">Import</button>
                <a href="{{ asset('sample/customer_import_template.xlsx') }}" class="btn btn-link">Tải file mẫu</a>
                <a href="{{ route('pages.my_customer') }}" class="btn btn-secondary">Quay lại</a>
            </form>
        </div>
    </div>

    @if(session('importedCount') !== null)
        <div class="card mt-4">
            <div class="card-header">Kết quả import</div>
            <div class="card-body">
                <p>Số dòng import thành công: {{ session('importedCount') }}</p>
                <p>Số dòng import thất bại: {{ session('failedCount') }}</p>

                @if(session('failedCount') > 0)
                    <h5>Các dòng bị lỗi:</h5>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Dòng</th>
                                <th>Lỗi</th>
                                <th>Dữ liệu</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach(session('failedRows') as $failedRow)
                                <tr>
                                    <td>{{ $failedRow['row'] }}</td>
                                    <td>{{ $failedRow['error'] }}</td>
                                    <td>{{ json_encode($failedRow['data']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    @endif
</div>
@endsection
