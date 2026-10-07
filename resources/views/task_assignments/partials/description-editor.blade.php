@once
@push('styles')
<style>
.task-description { font-size:14px; line-height:1.6; overflow-wrap:anywhere; }
.task-description p { margin:0 0 .75em; }
.task-description table { border-collapse:collapse; max-width:100%; }
.task-description th, .task-description td { border:1px solid #cbd5e1; padding:8px; }
.task-description ul, .task-description ol { padding-left:1.5rem; }
.tox-tinymce { border-radius:6px !important; }
</style>
@endpush
@push('scripts')
<script src="{{ asset('vendor/tinymce/tinymce.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (!window.tinymce) return;
    tinymce.init({
        selector: 'textarea.task-description-editor',
        license_key: 'gpl',
        language: 'vi',
        height: 350,
        menubar: false,
        branding: false,
        promotion: false,
        plugins: 'lists advlist link table code wordcount fullscreen',
        toolbar: 'undo redo | blocks | bold italic underline | forecolor backcolor | alignleft aligncenter alignright | bullist numlist | link table | removeformat fullscreen code',
        block_formats: 'Đoạn văn=p; Tiêu đề 2=h2; Tiêu đề 3=h3; Tiêu đề 4=h4',
        content_style: 'body { font-family:Arial,sans-serif;font-size:14px;line-height:1.6; } table { border-collapse:collapse; } td,th { border:1px solid #cbd5e1;padding:8px; }',
        setup: function (editor) {
            const requestedHeight = Number(editor.getElement().dataset.editorHeight);
            if (requestedHeight > 0) editor.options.set('height', requestedHeight);
            editor.on('change input undo redo', function () { editor.save(); });
            editor.on('init', function () {
                editor.getElement().form?.addEventListener('submit', function () { tinymce.triggerSave(); }, true);
            });
        }
    });
});
</script>
@endpush
@endonce
