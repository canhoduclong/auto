@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Chi tiết sản phẩm</h4>
                <a href="{{ route('products.edit', $product->id) }}" class="btn btn-primary">Chỉnh sửa</a>
            </div>

            <hr>

            <div class="row">
                <div class="col-md-8">
                    {{-- Tên sản phẩm --}}
                    <div class="mb-3">
                        <label for="name" class="form-label fw-bold">Tên sản phẩm</label>
                        <p>{{ $product->name }}</p>
                    </div>

                    {{-- Danh mục --}}
                    <div class="mb-3">
                        <label for="category_id" class="form-label fw-bold">Danh mục</label>
                        <p>{{ $product->category->name ?? 'N/A' }}</p>
                    </div>

                    {{-- Thương hiệu --}}
                    <div class="mb-3">
                        <label for="brand_id" class="form-label fw-bold">Thương hiệu</label>
                        <p>{{ $product->brand->name ?? 'N/A' }}</p>
                    </div>
                    
                    {{-- Mô tả --}}
                    <div class="mb-3">
                        <label for="description" class="form-label fw-bold">Mô tả</label>
                        <div>{!! nl2br(e($product->description)) !!}</div>
                    </div>
                </div>
                <div class="col-md-4">
                    {{-- Ảnh đại diện --}}
                    <div class="mb-3">
                        <label class="form-label fw-bold">Ảnh đại diện</label>
                        <div id="mediaPreview">
                            @if(!empty($product->avatar) && $product->avatar->media)
                                <img src="{{ asset('storage/'.$product->avatar->media->file_path) }}"
                                     width="200" class="img-thumbnail">
                            @else
                                <p class="text-muted">Không có ảnh</p>
                            @endif
                        </div>
                    </div>

                    {{-- Gallery --}}
                    <div class="mt-3">
                        <label class="form-label fw-bold">Gallery</label>
                        <div id="gallery-preview" class="d-flex flex-wrap gap-2">
                            @forelse($product->gallery as $link)
                                @if($link->media)
                                    <div class="gallery-item position-relative">
                                        <img src="{{ asset('storage/' . $link->media->file_path) }}" width="80" class="rounded">
                                    </div>
                                @endif
                            @empty
                                <p class="text-muted">Không có ảnh trong gallery</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>


            {{-- Biến thể --}}
            <div class="mb-3 mt-4">
                <label class="form-label fw-bold">Biến thể</label>
                <div class="table-responsive">
                    <table class="table table-bordered" id="variant-table">
                        <thead class="table-light">
                            <tr>
                                <th>SKU</th>
                                <th>Size</th>
                                <th>Chất lượng</th>
                                <th>Ngày SX</th>
                                <th>Hình ảnh</th>
                                <th>Giá bán</th>
                                <th>Số lượng tồn</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($product->variants as $variant)
                            <tr>
                                <td>{{ $variant->sku }}</td>
                                <td>{{ $variant->size ?? '-' }}</td>
                                <td>{{ $variant->quality ?? '-' }}</td>
                                <td>{{ $variant->production_date ? \Carbon\Carbon::parse($variant->production_date)->format('d/m/Y') : '-' }}</td>
                                <td>
                                    @if($variant->avatar && $variant->avatar->media)
                                        <img src="{{ asset('storage/' . $variant->avatar->media->file_path) }}" width="50" class="rounded">
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>{{ number_format($variant->final_price, 0, ',', '.') }} đ</td>
                                <td>{{ $variant->stock }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center">Sản phẩm này chưa có biến thể nào.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Nút quay lại --}}
            <div class="d-flex justify-content-end mt-4">
                <a href="{{ url()->previous(route('products.index')) }}" class="btn btn-secondary">Quay lại</a>
            </div>
        </div>
    </div>
</div>
@endsection
