<?php

namespace App\Http\Controllers;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Tag;
class SiteController extends Controller
{
    protected $settings;
    /**
     * Hiển thị trang chính sách quyền riêng tư.
     */
    public function __construct()
    {
        $this->settings = Cache::remember('settings', 60, function () {
            return Setting::all()->keyBy('key');
        });
    }
    public function privacyPolicy()
    {
        $counts = Cache::remember('counts', 60, function () {
            return [
                'users' => User::count(),
                'posts' => Post::count(),
            ];
        });
        $settings = $this->settings;
        return view('site.privacy_policy', compact('settings'));
    }
}