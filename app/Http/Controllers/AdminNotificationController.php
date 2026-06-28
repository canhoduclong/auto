<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\DepartmentBroadcastNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminNotificationController extends Controller
{
    private const DEPARTMENT_ROLE_OPTIONS = [
        'CEO' => 'CEO',
        'warehouse' => 'Warehouse',
        'accountant' => 'Account',
        'Shipper' => 'Shipper',
        'leader' => 'Leader',
        'manager' => 'Manager',
        'Director' => 'Director',
        'sale' => 'Sale',
    ];

    public function index(Request $request): View
    {
        abort_unless($request->user()?->hasRole('admin'), 403);

        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(20);

        $departmentRoleOptions = self::DEPARTMENT_ROLE_OPTIONS;

        return view('admin.notifications.index', compact('notifications', 'departmentRoleOptions'));
    }

    public function storeDepartmentBroadcast(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->hasRole('admin'), 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'message' => ['required', 'string', 'max:2000'],
            'target_roles' => ['required', 'array', 'min:1'],
            'target_roles.*' => ['required', Rule::in(array_keys(self::DEPARTMENT_ROLE_OPTIONS))],
            'url' => ['nullable', 'string', 'max:500'],
        ]);

        $targetRoles = array_values(array_unique($validated['target_roles']));
        $users = User::query()
            ->whereHas('roles', function ($query) use ($targetRoles) {
                $query->whereIn('name', $targetRoles);
            })
            ->get();

        if ($users->isEmpty()) {
            return back()->with('error', 'Không tìm thấy user thuộc phòng ban đã chọn.')->withInput();
        }

        Notification::send($users, new DepartmentBroadcastNotification(
            trim($validated['title']),
            trim($validated['message']),
            $targetRoles,
            !empty($validated['url']) ? trim($validated['url']) : null,
            $request->user()->id,
        ));

        return back()->with('success', 'Đã gửi thông báo tới ' . $users->count() . ' người nhận.');
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
