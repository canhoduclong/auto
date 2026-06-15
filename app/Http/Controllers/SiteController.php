<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SiteController extends Controller
{
    /**
     * Hiển thị trang chính sách quyền riêng tư.
     */
    public function privacyPolicy()
    {
        return view('site.privacy_policy');
    }
}