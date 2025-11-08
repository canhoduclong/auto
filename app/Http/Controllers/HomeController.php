<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Setting;
use App\Models\Category;
use App\Models\Product;
use App\Models\Post;

class HomeController extends Controller
{
    public function index()
    {
        $settings = Setting::all()->keyBy('key');
        $categories = Category::all();
        $products = Product::latest()->take(10)->get();
        $posts = Post::latest()->take(5)->get();
        return view('welcome', compact('settings', 'categories', 'products', 'posts'));
    }

    public function variants(Request $request)
    {
        $settings = Setting::all()->keyBy('key');
        $categories = \App\Models\Category::all();
        $query = \App\Models\ProductVariant::query()->where('stock', '>', 0);

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        $variants = $query->with(['product.avatar.media', 'latestPriceRule', 'media'])->paginate(10);

        return view('site.variants_list', compact('variants', 'settings', 'categories'));
    }
}
