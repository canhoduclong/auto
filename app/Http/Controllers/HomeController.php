<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Setting;
use App\Models\Category;
use App\Models\Product;
use App\Models\Post;
use App\Models\Media;
use Illuminate\Support\Facades\Cache;
use Throwable;

class HomeController extends Controller
{
    private function loadSettings()
    {
        try {
            return Cache::remember('settings', 60, function () {
                return Setting::all()->keyBy('key');
            });
        } catch (Throwable $e) {
            report($e);

            return collect();
        }
    }

    public function index()
    {
        $settings = $this->loadSettings();

        $sliderIds = collect(range(1, 5))
            ->map(fn ($i) => $settings['slider_' . $i]->value ?? null)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values();

        $sliderMedia = collect();
        if ($sliderIds->isNotEmpty()) {
            $mediaById = Media::query()
                ->whereIn('id', $sliderIds->all())
                ->get()
                ->keyBy('id');

            // Keep configured order from settings (slider_1 -> slider_5).
            $sliderMedia = $sliderIds
                ->map(fn ($id) => $mediaById->get($id))
                ->filter()
                ->values();
        }

        $categories = Category::all();
        $featuredProducts = Product::query()
            ->where('status', true)
            ->with([
                'category',
                'avatar.media',
                'variants' => fn ($variantQuery) => $variantQuery
                    ->withAvailableStock()
                    ->where('status', true)
                    ->with(['values.attribute', 'latestPriceRule'])
                    ->orderBy('sort_order')
                    ->orderBy('id'),
            ])
            ->orderBy('sort_order')
            ->orderBy('created_at', 'desc')
            ->take(8)
            ->get();
        $posts = Post::latest()->take(5)->get();
        return view('welcome', compact('settings', 'categories', 'featuredProducts', 'posts', 'sliderMedia'));
    }

    public function variants(Request $request)
    {
        $settings = $this->loadSettings();
        $categories = \App\Models\Category::all();
        $query = \App\Models\ProductVariant::query()
            ->withAvailableStock()
            ->inStock()
            ->where('status', true)
            ->whereHas('product', function ($q) {
                $q->where('status', true);
            });

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        $variants = $query->with(['product.avatar.media', 'latestPriceRule', 'mediaLink.media'])->paginate(10);

        return view('site.variants_list', compact('variants', 'settings', 'categories'));
    }
}
