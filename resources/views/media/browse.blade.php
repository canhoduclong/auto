<!DOCTYPE html>
<html>
<head>
    <title>Chọn hình ảnh</title>
    <style>
        body { font-family: sans-serif; padding: 10px; }
        .file-box {
            display: inline-block;
            margin: 8px;
            border: 1px solid #ccc;
            padding: 5px;
            cursor: pointer;
            width: 140px;
            text-align: center;
        }
        .file-box img {
            max-width: 120px;
            max-height: 120px;
            object-fit: cover;
        }
        .file-name {
            font-size: 12px;
            margin-top: 5px;
            word-break: break-all;
        }
    </style>
</head>
<body>

<h3>Chọn hình ảnh</h3>

<div class="file-list">
    @foreach($files as $file)
        <div class="file-box"
             onclick="selectImage('{{ asset('storage/'.$file->file_path) }}')">

            <img src="{{ asset('storage/'.$file->file_path) }}">
            <div class="file-name">{{ $file->file_name }}</div>
        </div>
    @endforeach
</div>

<script>
    function selectImage(url) {
        window.opener.CKEDITOR.tools.callFunction({{ $CKEditorFuncNum }}, url);
        window.close();
    }
</script>

</body>
</html>
