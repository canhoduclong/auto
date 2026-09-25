@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h2 class="mb-1">Quản lý Teams</h2>
            <p class="text-muted small">Quản lý thành viên và thông tin team</p>
        </div>
        <a href="{{ route('teams.create') }}" class="btn btn-success">Tạo Team mới</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @forelse($teams as $team)
        <div class="card mb-3 shadow-sm">
            <div class="card-header bg-light">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h5 class="mb-0">
                            <strong>{{ $team->name }}</strong>
                            @if($team->code)
                                <span class="badge bg-info ms-2">{{ $team->code }}</span>
                            @endif
                        </h5>
                        @if($team->note)
                            <small class="text-muted d-block mt-1">{{ $team->note }}</small>
                        @endif
                    </div>
                    <div class="col-md-6 text-end">
                        <span class="badge bg-primary fs-6">{{ $team->users_count }} thành viên</span>
                        <a href="{{ route('teams.edit', $team) }}" class="btn btn-sm btn-outline-warning ms-2">Sửa</a>
                        <form action="{{ route('teams.destroy', $team) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Bạn chắc chắn muốn xóa team này? (Phải không có thành viên)')">Xóa</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- Members List -->
                    <div class="col-md-8">
                        <h6 class="mb-3">Danh sách thành viên</h6>
                        @if($team->users->count() > 0)
                            <div class="list-group">
                                @foreach($team->users as $user)
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1">{{ $user->name }}</h6>
                                            <small class="text-muted">{{ $user->email }}</small>
                                        </div>
                                        <form action="{{ route('teams.remove-user', $team) }}" method="POST" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="user_id" value="{{ $user->id }}">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Xóa {{ $user->name }} khỏi team?')">Xóa</button>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-muted">Không có thành viên nào</p>
                        @endif
                    </div>

                    <!-- Add User Form -->
                    <div class="col-md-4">
                        <h6 class="mb-3">Thêm thành viên</h6>
                        <form action="{{ route('teams.assign-user', $team) }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <select name="user_id" class="form-select form-select-sm" required>
                                    <option value="">-- Chọn người dùng --</option>
                                    @foreach($allUsers as $user)
                                        @if(!$team->users->contains('id', $user->id))
                                            <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="btn btn-sm btn-primary w-100">Thêm vào team</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="alert alert-info text-center py-5">
            <h5>Không có team nào</h5>
            <p class="mb-0"><a href="{{ route('teams.create') }}" class="btn btn-primary btn-sm mt-2">Tạo team đầu tiên</a></p>
        </div>
    @endforelse

    <div class="mt-4">
        {{ $teams->links() }}
    </div>
</div>
@endsection
