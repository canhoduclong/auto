<?php 
// app/Http/Controllers/MediaController.php
namespace App\Http\Controllers;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests; 
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class MediaController extends Controller
{
    use AuthorizesRequests;

    /**
     * Hiển thị gallery chọn ảnh cho biến thể (popup)
     */
    public function variantImageLibrary()
    {
        return view('media.variants');
    }
    public function index()
    {
        $media = Media::latest()->paginate(20);
        return view('media.library', compact('media'));
    }
    public function create()
    {
        return view('media.create');
    }
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required',
            'file.*' => 'file|mimes:jpg,jpeg,png,gif|max:2048',
        ]);

        $files = $request->file('file');
        if (!is_array($files)) {
            $files = [$files];
        }
        foreach ($files as $file) {
            $path = $file->store('media', 'public');
            Media::create([
                'file_name'   => $file->getClientOriginalName(),
                'file_path'   => $path,
                'mime_type'   => $file->getMimeType(),
                'file_size'   => $file->getSize(),
                'type'        => explode('/', $file->getMimeType())[0],
                'uploaded_by' => auth()->id(),
            ]);
        }
        return redirect()->back()->with('success', 'Tải ảnh thành công!');
    }
    public function popupStore(Request $request)
    {
        $request->validate([
            'file' => 'required',
            'file.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $files = $request->file('file');
        if (!is_array($files)) {
            $files = [$files];
        }
        foreach ($files as $file) {
            $path = $file->store('media', 'public');
            \App\Models\Media::create([
                'file_name'   => $file->getClientOriginalName(),
                'file_path'   => $path,
                'mime_type'   => $file->getMimeType(),
                'file_size'   => $file->getSize(),
                'uploaded_by' => auth()->id(),
            ]);
        }
        return redirect()->route('media.library.popup')->with('success', 'Media updated successfully!');
        
        // Trả JSON để JS cập nhật ngay mà không reload
        // return response()->json([
        //    'id'  => $media->id,
        //    'url' => asset('storage/'.$media->file_path),
        //]);
    }

    public function storeGallery(Request $request)
    {
        $request->validate([
            'file' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $path = $request->file('file')->store('media', 'public');

        $media = Media::create([
            'file_name'   => $request->file('file')->getClientOriginalName(),
            'file_path'   => $path,
            'mime_type'   => $request->file('file')->getMimeType(),
            'file_size'   => $request->file('file')->getSize(),
            'uploaded_by' => auth()->id(),
        ]);
         return redirect()->route('media.gallery.popup')->with('success', 'Media updated successfully!');
    }

    public function popup()
    {
        $media = Media::latest()->paginate(20);
        return view('media.popup', compact('media'));
    }
    public function popupGallery()
    {
        $media = Media::latest()->paginate(20);
        return view('media.gallery', compact('media'));
    }
    public function show(Media $media)
    {
        return response()->json($media);
    }
    public function edit(Media $media)
    {
        return view('media.edit', compact('media'));
    }
    public function update(Request $request, Media $media)
    {
        // Kiểm tra quyền
        $this->authorize('update', $media);

        $validated = $request->validate([
            'file_name' => 'nullable|string|max:255',
            'file'      => 'nullable|file|max:5120', // cho phép thay thế file mới
        ]);

        // Nếu thay đổi tên file (metadata)
        if (!empty($validated['file_name'])) {
            $media->file_name = $validated['file_name'];
        }

        // Nếu có upload file mới (replace file)
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $path = $file->store('media', 'public');

            // Xóa file cũ nếu muốn (optional)
            if ($media->file_path && \Storage::disk('public')->exists($media->file_path)) {
                \Storage::disk('public')->delete($media->file_path);
            }

            // Cập nhật thông tin file mới
            $media->file_path  = $path;
            $media->mime_type  = $file->getMimeType();
            $media->file_size  = $file->getSize();
            $media->type       = explode('/', $file->getMimeType())[0]; // image, video...
            $media->uploaded_by = auth()->id(); // người update
        }

        $media->save();

        return redirect()->route('media.index')->with('success', 'Media updated successfully!');
    }

    public function destroy(Media $media)
    {
        Storage::disk('public')->delete($media->file_path);
        $media->links()->delete(); // xoá tất cả liên kết
        $media->delete();
        return redirect()->route('media.index')->with('success', 'Delete successfully!');
        //return response()->json(['success' => true]);
    }
}
