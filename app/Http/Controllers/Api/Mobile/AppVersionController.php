<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

class AppVersionController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json([
            'latest_version' => (string) Setting::get('mobile_latest_version', '1.0.0'),
            'force_update' => filter_var(Setting::get('mobile_force_update', false), FILTER_VALIDATE_BOOL),
            'apk_url' => (string) Setting::get('mobile_apk_url', ''),
            'changelog' => (string) Setting::get('mobile_changelog', ''),
        ]);
    }
}
