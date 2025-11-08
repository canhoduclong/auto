<div class="row">
    @foreach($media as $m)
    <div class="col-md-2 mb-3">
        <div class="card media-card" data-media-id="{{ $m->id }}" data-media-url="{{ asset('storage/' . $m->file_path) }}" style="cursor: pointer;">
            <img src="{{ asset('storage/' . $m->file_path) }}" class="card-img-top" alt="{{ $m->file_name }}">
            <div class="card-body">
                <p class="card-text">{{ $m->file_name }}</p>
            </div>
        </div>
    </div>
    @endforeach
</div>
