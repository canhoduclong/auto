<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\LoginNoticeService;
use Illuminate\Http\Request;

class LoginNoticeController extends Controller
{
    public function edit(LoginNoticeService $service)
    {
        return view('admin.settings.login-notice', ['notice' => $service->configuration()]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'enabled' => 'required|boolean',
            'title' => 'required|string|max:200',
            'content' => 'required|string|max:10000',
            'footer' => 'nullable|string|max:3000',
        ]);
        $data['enabled'] = $request->boolean('enabled');
        $data['footer'] = $data['footer'] ?? '';
        Setting::set('system_login_notice', json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        return back()->with('success', 'Đã cập nhật thông báo đăng nhập cho web và my_app.');
    }
}
