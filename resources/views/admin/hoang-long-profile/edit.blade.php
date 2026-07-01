@extends('layouts.app')

@section('title', 'Profile Hoàng Long TNT')

@section('content')
<div class="container-fluid py-3 py-lg-4">
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body d-flex flex-column flex-lg-row justify-content-between gap-2">
            <div>
                <h3 class="mb-1">Profile Hoàng Long TNT</h3>
                <p class="text-muted mb-0">Cập nhật thông tin giới thiệu và tài liệu đính kèm cho đội sale.</p>
            </div>
            <a href="{{ route('pages.hoang_long_profile') }}" class="btn btn-outline-primary align-self-start" target="_blank">
                <i class="bi bi-box-arrow-up-right me-1"></i>Xem trang sale
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <div class="fw-semibold mb-1">Không thể lưu thông tin</div>
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.hoang-long-profile.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white border-0 pt-3 pb-0">
                <h5 class="mb-1">Thông tin về Hoàng Long TNT</h5>
            </div>
            <div class="card-body">
                <textarea name="profile_info" class="form-control" rows="10" placeholder="Nhập nội dung profile, năng lực, chính sách, thông tin liên hệ...">{{ old('profile_info', $profileInfo) }}</textarea>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white border-0 pt-3 pb-0 d-flex justify-content-between align-items-center gap-2">
                <div>
                    <h5 class="mb-1">Tài liệu đính kèm</h5>
                    <p class="text-muted small mb-0">Mỗi dòng gồm tiêu đề và file upload hoặc URL.</p>
                </div>
                <button type="button" class="btn btn-sm btn-primary" id="add-document-row">
                    <i class="bi bi-plus-lg me-1"></i>Thêm dòng
                </button>
            </div>
            <div class="card-body">
                <div id="document-rows" class="d-grid gap-3">
                    @forelse($documents as $index => $document)
                        <div class="border rounded p-3 document-row">
                            <div class="row g-2 align-items-end">
                                <div class="col-lg-3">
                                    <label class="form-label">Tiêu đề</label>
                                    <input type="text" name="documents[{{ $index }}][title]" class="form-control" value="{{ $document['title'] ?? '' }}">
                                </div>
                                <div class="col-lg-4">
                                    <label class="form-label">Input file</label>
                                    <input type="file" name="documents[{{ $index }}][file]" class="form-control">
                                    <input type="hidden" name="documents[{{ $index }}][existing_file]" value="{{ $document['file_path'] ?? '' }}">
                                    @if(!empty($document['file_url']))
                                        <a href="{{ $document['file_url'] }}" class="small d-inline-block mt-1" target="_blank">File hiện tại</a>
                                    @endif
                                </div>
                                <div class="col-lg-4">
                                    <label class="form-label">Hoặc URL</label>
                                    <input type="url" name="documents[{{ $index }}][url]" class="form-control" value="{{ $document['url'] ?? '' }}" placeholder="https://...">
                                </div>
                                <div class="col-lg-1 d-grid">
                                    <button type="button" class="btn btn-outline-danger remove-document-row" aria-label="Xóa dòng">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="border rounded p-3 document-row">
                            <div class="row g-2 align-items-end">
                                <div class="col-lg-3">
                                    <label class="form-label">Tiêu đề</label>
                                    <input type="text" name="documents[0][title]" class="form-control">
                                </div>
                                <div class="col-lg-4">
                                    <label class="form-label">Input file</label>
                                    <input type="file" name="documents[0][file]" class="form-control">
                                    <input type="hidden" name="documents[0][existing_file]" value="">
                                </div>
                                <div class="col-lg-4">
                                    <label class="form-label">Hoặc URL</label>
                                    <input type="url" name="documents[0][url]" class="form-control" placeholder="https://...">
                                </div>
                                <div class="col-lg-1 d-grid">
                                    <button type="button" class="btn btn-outline-danger remove-document-row" aria-label="Xóa dòng">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save me-1"></i>Lưu Profile
            </button>
        </div>
    </form>
</div>

<template id="document-row-template">
    <div class="border rounded p-3 document-row">
        <div class="row g-2 align-items-end">
            <div class="col-lg-3">
                <label class="form-label">Tiêu đề</label>
                <input type="text" data-name="documents[__INDEX__][title]" class="form-control">
            </div>
            <div class="col-lg-4">
                <label class="form-label">Input file</label>
                <input type="file" data-name="documents[__INDEX__][file]" class="form-control">
                <input type="hidden" data-name="documents[__INDEX__][existing_file]" value="">
            </div>
            <div class="col-lg-4">
                <label class="form-label">Hoặc URL</label>
                <input type="url" data-name="documents[__INDEX__][url]" class="form-control" placeholder="https://...">
            </div>
            <div class="col-lg-1 d-grid">
                <button type="button" class="btn btn-outline-danger remove-document-row" aria-label="Xóa dòng">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>
    </div>
</template>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const rows = document.getElementById('document-rows');
    const template = document.getElementById('document-row-template');
    let index = rows.querySelectorAll('.document-row').length;

    function wireNames(row) {
        row.querySelectorAll('[data-name]').forEach(function (input) {
            input.name = input.dataset.name.replace('__INDEX__', index);
            input.removeAttribute('data-name');
        });
        index += 1;
    }

    document.getElementById('add-document-row')?.addEventListener('click', function () {
        const clone = template.content.firstElementChild.cloneNode(true);
        wireNames(clone);
        rows.appendChild(clone);
    });

    rows.addEventListener('click', function (event) {
        const button = event.target.closest('.remove-document-row');
        if (!button) {
            return;
        }

        button.closest('.document-row')?.remove();
    });
});
</script>
@endpush
