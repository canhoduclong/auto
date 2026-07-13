<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AppUpdateController extends Controller
{
    public function manifest(): BinaryFileResponse
    {
        $path = public_path('app-update/version.json');
        abort_unless(is_file($path), 404, 'Chưa có thông tin phiên bản ứng dụng.');

        $response = response()->file($path, [
            'Content-Type' => 'application/json; charset=UTF-8',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');

        return $response;
    }

    public function apk(string $filename): BinaryFileResponse
    {
        abort_unless(
            preg_match('/\Aapp-release(?:-\d+\.\d+\.\d+)?\.apk\z/', $filename) === 1,
            404
        );

        $path = public_path('app-update/'.$filename);
        abort_unless(is_file($path), 404, 'Không tìm thấy bản cài đặt ứng dụng.');

        $cacheControl = $filename === 'app-release.apk'
            ? 'no-cache, no-store, must-revalidate'
            : 'public, max-age=31536000, immutable';

        $response = response()->file($path, [
            'Content-Type' => 'application/vnd.android.package-archive',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
        $response->headers->set('Cache-Control', $cacheControl);

        return $response;
    }
}
