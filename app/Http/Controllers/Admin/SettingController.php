<?php

namespace App\Http\Controllers\Admin; 
use App\Http\Controllers\Controller; 
use Illuminate\Http\Request;
use App\Models\Setting;
 

class SettingController extends Controller
{
    /**
     * Middleware để bảo vệ các route trong controller này
     * Chỉ người dùng đã đăng nhập và có quyền 'manage-settings' mới có thể truy cập
     */ 
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:manage-settings');
    }

    public function index()
    {
        $settings = Setting::all()->keyBy('key');
        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->except('_token');

        foreach ($data as $key => $value) {
            Setting::set($key, $value);
        }

        return redirect()->back()->with('success', 'Settings updated successfully.');
    }
}
