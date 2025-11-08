@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Website Settings</h2>

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <form action="{{ route('admin.settings.update') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label for="brand_name" class="form-label">Brand Name</label>
            <input type="text" class="form-control" id="brand_name" name="brand_name" value="{{ $settings['brand_name']->value ?? '' }}">
        </div>

        <div class="mb-3">
            <label for="slogan" class="form-label">Slogan</label>
            <input type="text" class="form-control" id="slogan" name="slogan" value="{{ $settings['slogan']->value ?? '' }}">
        </div>

        <div class="mb-3">
            <label class="form-label">Logo</label>
            <div class="mb-2" id="logo-preview">
                @if(isset($settings['logo']) && $settings['logo']->value)
                    @php
                        $media = App\Models\Media::find($settings['logo']->value);
                    @endphp
                    @if($media)
                        <img src="{{ asset('storage/' . $media->file_path) }}" width="120" class="img-thumbnail">
                    @endif
                @endif
            </div>
            <input type="hidden" name="logo" id="logo-media-id" value="{{ $settings['logo']->value ?? '' }}">
            <button type="button" class="btn btn-info" id="btnSelectLogo">Chọn ảnh từ thư viện</button>
        </div>

        <div class="mb-3">
            <label for="address" class="form-label">Address</label>
            <textarea class="form-control" id="address" name="address" rows="3">{{ $settings['address']->value ?? '' }}</textarea>
        </div>

        <div class="mb-3">
            <label for="hotline" class="form-label">Hotline</label>
            <input type="text" class="form-control" id="hotline" name="hotline" value="{{ $settings['hotline']->value ?? '' }}">
        </div>

        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" id="email" name="email" value="{{ $settings['email']->value ?? '' }}">
        </div>

        <div class="mb-3">
            <label for="tax_number" class="form-label">Tax Number</label>
            <input type="text" class="form-control" id="tax_number" name="tax_number" value="{{ $settings['tax_number']->value ?? '' }}">
        </div>

        <div class="mb-3">
            <label for="policy_page" class="form-label">Policy Page URL</label>
            <input type="text" class="form-control" id="policy_page" name="policy_page" value="{{ $settings['policy_page']->value ?? '' }}">
        </div>

        <button type="submit" class="btn btn-primary">Save Settings</button>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var btn = document.getElementById('btnSelectLogo');
    if (btn) {
        btn.addEventListener('click', function() {
            let modalHtml = `
            <div class='modal fade' id='logoModal' tabindex='-1'>
              <div class='modal-dialog modal-lg'>
                <div class='modal-content'>
                  <div class='modal-header'>
                    <h5 class='modal-title'>Chọn hình ảnh</h5>
                    <button type='button' class='btn-close' data-bs-dismiss='modal'></button>
                  </div>
                  <div class='modal-body p-0'>
                    <iframe id='logoIframe' src='{{ route('variants.image-library') }}' frameborder='0' style='width:100%; height:400px;'></iframe>
                  </div>
                </div>
              </div>
            </div>`;
            let modalDiv = document.createElement('div');
            modalDiv.innerHTML = modalHtml;
            document.body.appendChild(modalDiv);
            let modal = new bootstrap.Modal(document.getElementById('logoModal'));
            modal.show();
            window.addEventListener('message', function handler(event) {
                if (event.data && event.data.type === 'mediaSelected') {
                    document.getElementById('logo-media-id').value = event.data.mediaId;
                    document.getElementById('logo-preview').innerHTML = `<img src="${event.data.url}" width="120" class="img-thumbnail">`;
                    modal.hide();
                    window.removeEventListener('message', handler);
                }
            });
            document.getElementById('logoModal').addEventListener('hidden.bs.modal', function () {
                modalDiv.remove();
            });
        });
    }
});
</script>
@endpush