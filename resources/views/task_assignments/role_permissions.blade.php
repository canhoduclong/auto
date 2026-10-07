@extends('layouts.admin')
@section('title','Quyền công việc theo vai trò')
@section('content')
<div class="container-fluid p-3"><h4>Quyền công việc theo vai trò</h4>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<p class="text-muted">Cấp quyền chức năng theo vai trò. Phạm vi người được giao cấu hình tại <a href="{{ route('task-delegate-configs.index') }}">Quyền giao việc cho từng người</a>. Quyền Administrator và các quyền mặc định của cấp quản lý hiện có vẫn được giữ.</p>
@foreach($roles as $role)<form method="POST" action="{{ route('task-role-permissions.update',$role) }}" class="card card-body mb-3">@csrf @method('PUT')
<strong class="mb-2">{{ $role->name }}</strong><div class="d-flex flex-wrap gap-3">@foreach(\App\Models\TaskPermission::PERMISSION_LABELS as $slug=>$label)<label><input type="checkbox" name="permissions[]" value="{{ $slug }}" @checked($role->taskPermissions->contains('permission_slug',$slug))> {{ $label }}</label>@endforeach</div><div class="mt-3"><button type="submit" class="btn btn-primary btn-sm">Lưu quyền</button></div></form>@endforeach
</div>
@endsection
