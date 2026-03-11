<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminNotificationController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->hasRole('admin'), 403);

        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(20);

        return view('admin.notifications.index', compact('notifications'));
    }

    public function markAsRead(Request $request, string $notificationId): RedirectResponse
    {
        abort_unless($request->user()?->hasRole('admin'), 403);

        $notification = $request->user()
            ->notifications()
            ->where('id', $notificationId)
            ->firstOrFail();

        if (is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        $targetUrl = $notification->data['url'] ?? route('admin.events.index');

        return redirect($targetUrl);
    }

    public function markAllAsRead(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->hasRole('admin'), 403);

        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'Da danh dau tat ca thong bao la da doc.');
    }
}
