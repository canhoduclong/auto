<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;  
use Illuminate\Http\Request; 
use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage; 

class CategoryController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $this->authorize('viewAny', Category::class);

        $categories = Category::whereNull('parent_id')
            ->with('children')
            ->orderByDesc('id')
            ->paginate(15)
            ->appends($request->query());

        return view('categories.index', compact('categories')); 
    }
    
    public function create()
    {
        $categories = Category::all(); // lấy để chọn parent
        return view('categories.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:categories,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $data = $request->only('name', 'parent_id');

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('categories', 'public');
            $data['image'] = $path;
        }

        Category::create($data);

        return redirect()->route('categories.index')->with('success', 'Tạo danh mục thành công!');
    }

    public function edit(Category $category)
    {
        $categories = Category::where('id', '!=', $category->id)->get(); // tránh chọn chính nó
        return view('categories.edit', compact('category', 'categories'));
    }

    public function update(Request $request, Category $category)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:categories,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $data = $request->only('name', 'parent_id');

        if ($request->hasFile('image')) {
            if ($category->image) {
                Storage::disk('public')->delete($category->image);
            }
            $path = $request->file('image')->store('categories', 'public');
            $data['image'] = $path;
        }

        $category->update($data);

        return redirect()->route('categories.index')->with('success', 'Cập nhật danh mục thành công!');
    }

    public function bulkDelete(Request $request)
    {
        $validated = $request->validate([
            'redirect_to' => 'nullable|string',
            'category_ids' => 'required|array|min:1',
            'category_ids.*' => 'required|integer|exists:categories,id',
        ]);

        $categoryIds = collect($validated['category_ids'])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $categories = Category::whereIn('id', $categoryIds)->get();

        foreach ($categories as $category) {
            $this->authorize('delete', $category);
        }

        DB::transaction(function () use ($categories) {
            foreach ($categories as $category) {
                $this->deleteCategorySafely($category);
            }
        });

        return $this->redirectAfterDeletion($request, 'Đã xóa các danh mục đã chọn.');
    }

    public function destroy(Request $request, Category $category)
    {
        $this->authorize('delete', $category);

        DB::transaction(function () use ($category) {
            $this->deleteCategorySafely($category);
        });

        return $this->redirectAfterDeletion($request, 'Category deleted successfully.');
    }

    private function deleteCategorySafely(Category $category): void
    {

        // Set category_id = null cho các sản phẩm đang dùng category này
        $category->products()->update(['category_id' => null]);

        // Tránh lỗi khóa ngoại cho danh mục con khi xóa danh mục cha
        $category->children()->update(['parent_id' => null]);

        $category->delete();
    }

    private function redirectAfterDeletion(Request $request, string $message)
    {
        $redirectTo = $request->input('redirect_to');

        if (is_string($redirectTo) && $redirectTo !== '') {
            $redirectHost = parse_url($redirectTo, PHP_URL_HOST);
            $appHost = parse_url(url('/'), PHP_URL_HOST);

            if ($redirectHost === null || $redirectHost === $appHost) {
                return redirect()->to($redirectTo)->with('success', $message);
            }
        }

        return redirect()->route('categories.index')->with('success', $message);
    }
}
