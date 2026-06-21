<?php

namespace App\Http\Controllers;

use App\Models\AdminEvent;
use App\Models\User;
use App\Models\UserPresenceLog;
use App\Models\Role;
use App\Models\Setting;
use App\Models\Team;
use App\Models\Warehouse;
use App\Models\Block;
use App\Models\Department;
use App\Services\UserWorkspaceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('roles', 'warehouse', 'team');

        if ($request->filled('q')) {
            $search = $request->input('q');
            $query->where(function ($sub) use ($search) {
                $sub->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('zalo_name', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('team_id')) {
            $query->where('team_id', $request->input('team_id'));
        }

        if ($request->filled('role_id')) {
            $roleId = (int) $request->input('role_id');
            $query->whereHas('roles', function ($sub) use ($roleId) {
                $sub->where('roles.id', $roleId);
            });
        }

        $users = $query->orderBy('name')->paginate(15)->appends($request->query());
        $teams = Team::orderBy('name')->get(['id', 'name']);
        $roles = Role::orderBy('name')->get(['id', 'name']);
        $canCreateUsers = Setting::enabled('user_registration_enabled', true);

        // Load selected user detail if user_id param exists
        $selectedUser = null;
        $activities = collect();
        if ($request->filled('user_id')) {
            $selectedUser = User::with('roles', 'team', 'warehouse', 'department')
                ->find($request->input('user_id'));
            
            if ($selectedUser && Schema::hasTable('admin_events')) {
                $activities = AdminEvent::where('actor_id', $selectedUser->id)
                    ->orderByDesc('created_at')
                    ->limit(50)
                    ->get();
            }
        }

        return view('users.index', compact('users', 'teams', 'roles', 'canCreateUsers', 'selectedUser', 'activities'));
    }

    public function bulkAssignTeamForm(Request $request)
    {
        $query = User::with('roles', 'team')->orderBy('name');

        if ($request->filled('q')) {
            $search = $request->input('q');
            $query->where(function ($sub) use ($search) {
                $sub->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('role_id')) {
            $roleId = (int) $request->input('role_id');
            $query->whereHas('roles', function ($sub) use ($roleId) {
                $sub->where('roles.id', $roleId);
            });
        }

        if ($request->filled('team_id')) {
            $query->where('team_id', $request->input('team_id'));
        }

        $users = $query->paginate(20)->appends($request->query());
        $teams = Team::orderBy('name')->get(['id', 'name']);
        $roles = Role::orderBy('name')->get(['id', 'name']);

        return view('users.bulk_assign_team', compact('users', 'teams', 'roles'));
    }

    public function bulkAssignTeam(Request $request)
    {
        $validated = $request->validate([
            'team_id' => 'nullable|exists:teams,id',
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'required|exists:users,id',
        ]);

        User::whereIn('id', $validated['user_ids'])->update([
            'team_id' => $validated['team_id'] ?? null,
        ]);

        $message = empty($validated['team_id'])
            ? __('users.messages.bulk_unassigned_team')
            : __('users.messages.bulk_assigned_team');

        return redirect()->route('users.bulk-assign-team.form')->with('success', $message);
    }

    public function bulkDelete(Request $request)
    {
        $validated = $request->validate([
            'redirect_to' => 'nullable|string',
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'required|integer|exists:users,id',
        ]);

        $userIds = collect($validated['user_ids'])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        DB::transaction(function () use ($userIds) {
            $this->releaseUserReferences($userIds->all());

            $users = User::whereIn('id', $userIds)->get();

            foreach ($users as $user) {
                $user->roles()->detach();
                $user->delete();
            }
        });

        return $this->redirectAfterDeletion($request, __('users.messages.bulk_deleted'));
    }

    public function create()
    {
        if (!Setting::enabled('user_registration_enabled', true)) {
            abort(404);
        }

        $roles = Role::all();
        $teams = Team::orderBy('name')->get();
        $warehouses = Warehouse::orderBy('name')->get();
        $blocks = Block::active()->orderBy('name')->get();
        $departments = Department::active()->with('block')->orderBy('name')->get();
        return view('users.create', compact('roles', 'teams', 'warehouses', 'blocks', 'departments'));
    }

    public function store(Request $request)
    {
        if (!Setting::enabled('user_registration_enabled', true)) {
            abort(404);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'zalo_name' => 'nullable|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6|confirmed',
            'roles' => 'array',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'team_id' => 'nullable|exists:teams,id',
            'block_id' => 'nullable|exists:blocks,id',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        $user = User::create([
            'name'          => $request->name,
            'zalo_name'     => $request->zalo_name,
            'email'         => $request->email,
            'password'      => Hash::make($request->password),
            'warehouse_id'  => $request->warehouse_id,
            'team_id'       => $request->team_id,
            'block_id'      => $request->block_id,
            'department_id' => $request->department_id,
        ]);

        if ($request->roles) {
            $user->roles()->attach($request->roles);
        }
        /*
        if (!empty($validated['roles'])) {
            $user->roles()->sync($validated['roles']);
        }
        */

        return redirect()->route('users.index')->with('success', __('users.messages.created'));
    }

    public function show(User $user)
    {
        $user->load('roles', 'team', 'warehouse', 'department');

        $activities = Schema::hasTable('admin_events')
            ? AdminEvent::where('actor_id', $user->id)
                ->orderByDesc('created_at')
                ->limit(50)
                ->get()
            : collect();

        $webSessions = Schema::hasTable('sessions')
            ? DB::table('sessions')
                ->where('user_id', $user->id)
                ->orderByDesc('last_activity')
                ->limit(10)
                ->get()
                ->map(function ($session) {
                    $session->last_activity_at = Carbon::createFromTimestamp((int) $session->last_activity);
                    $session->is_active = $session->last_activity_at->gte(now()->subMinutes(5));

                    return $session;
                })
            : collect();

        $mobileSessions = Schema::hasTable('mobile_api_tokens')
            ? $user->mobileApiTokens()
                ->orderByDesc('last_used_at')
                ->limit(10)
                ->get()
                ->map(function ($token) {
                    $token->is_active = $token->last_used_at?->gte(now()->subMinutes(5)) ?? false;

                    return $token;
                })
            : collect();

        $lastWebSession = $webSessions->first();
        $lastMobileSession = $mobileSessions->first();
        $lastConnection = collect([
            $lastWebSession ? ['at' => $lastWebSession->last_activity_at, 'ip' => $lastWebSession->ip_address] : null,
            $lastMobileSession ? ['at' => $lastMobileSession->last_used_at, 'ip' => $lastMobileSession->ip_address] : null,
        ])->filter()->sortByDesc('at')->first();
        $lastActivityAt = collect([
            $user->last_seen_at,
            $lastWebSession?->last_activity_at,
            $lastMobileSession?->last_used_at,
        ])->filter()->sortDesc()->first();

        $presence = [
            'is_online' => $webSessions->contains('is_active', true)
                || $mobileSessions->contains('is_active', true)
                || ($user->last_seen_at?->gte(now()->subMinutes(5)) ?? false),
            'last_activity_at' => $lastActivityAt,
            'last_ip_address' => $lastConnection['ip'] ?? null,
            'web_sessions' => $webSessions,
            'mobile_sessions' => $mobileSessions,
        ];

        $usageFrom = now()->subDays(6)->startOfDay();
        $usagePoints = Schema::hasTable('user_presence_logs')
            ? UserPresenceLog::query()
                ->where('user_id', $user->id)
                ->where('observed_at', '>=', $usageFrom)
                ->get()
                ->map(fn (UserPresenceLog $log) => [
                    'at' => $log->observed_at,
                    'source' => $log->source === 'mobile' ? 'Mobile' : 'Web',
                    'reason' => $log->reason ?: 'Hoạt động hệ thống',
                    'ip' => $log->ip_address,
                ])
            : collect();

        // Backfill the seven-day chart with existing audited actions.
        if (Schema::hasTable('admin_events')) {
            $historicalPoints = AdminEvent::query()
                ->where('actor_id', $user->id)
                ->where('created_at', '>=', $usageFrom)
                ->get(['created_at', 'title', 'action', 'url'])
                ->map(fn (AdminEvent $event) => [
                    'at' => $event->created_at,
                    'source' => 'Web',
                    'reason' => $event->title ?: $event->action,
                    'ip' => null,
                ]);
            $usagePoints = $usagePoints->concat($historicalPoints);
        }

        $usageGrid = [];
        foreach (range(0, 6) as $dayOffset) {
            $date = now()->subDays($dayOffset)->toDateString();
            $dayPoints = $usagePoints->filter(fn (array $point) => $point['at']->toDateString() === $date);
            $hours = [];
            foreach (range(0, 23) as $hour) {
                $hourPoints = $dayPoints->filter(fn (array $point) => (int) $point['at']->format('G') === $hour)
                    ->sortBy('at')
                    ->values();
                $details = $hourPoints->take(8)->map(function (array $point): string {
                    return $point['at']->format('H:i') . ' · ' . $point['source'] . ' · ' . $point['reason']
                        . ($point['ip'] ? ' · IP ' . $point['ip'] : '');
                })->all();
                if ($hourPoints->count() > 8) {
                    $details[] = '+ ' . ($hourPoints->count() - 8) . ' hoạt động khác';
                }
                $hours[$hour] = [
                    'count' => $hourPoints->count(),
                    'tooltip' => $details ? implode("\n", $details) : 'Không ghi nhận hoạt động',
                ];
            }
            $usageGrid[] = [
                'date' => Carbon::parse($date),
                'hours' => $hours,
                'points' => $dayPoints->count(),
            ];
        }

        $usageSummary = [
            'points' => $usagePoints->count(),
            'active_days' => $usagePoints->groupBy(fn (array $point) => $point['at']->toDateString())->count(),
            'active_hours' => $usagePoints->groupBy(fn (array $point) => $point['at']->format('Y-m-d-H'))->count(),
        ];

        return view('users.show', compact('user', 'activities', 'presence', 'usageGrid', 'usageSummary'));
    }

    public function edit(User $user, UserWorkspaceService $workspaceService)
    {
        $roles = Role::all();
        $teams = Team::orderBy('name')->get();
        $warehouses = Warehouse::orderBy('name')->get();
        $blocks = Block::active()->orderBy('name')->get();
        $departments = Department::active()->with('block')->orderBy('name')->get();
        $availableWorkspaces = $workspaceService->availableForUser($user);

        return view('users.edit', compact(
            'user',
            'roles',
            'teams',
            'warehouses',
            'blocks',
            'departments',
            'availableWorkspaces'
        ));
    }

    public function update(Request $request, User $user, UserWorkspaceService $workspaceService)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'zalo_name' => 'nullable|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'password' => 'nullable|min:6|confirmed',
            'roles' => 'array',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'team_id' => 'nullable|exists:teams,id',
            'block_id' => 'nullable|exists:blocks,id',
            'department_id' => 'nullable|exists:departments,id',
            'default_workspace' => 'nullable|string|max:120',
            'default_mobile_role_id' => 'nullable|integer|exists:roles,id',
        ]);

        $selectedRoleIds = collect($request->input('roles', []))
            ->map(fn ($roleId) => (int) $roleId)
            ->filter()
            ->values();

        $selectedRoleNames = Role::query()
            ->whereIn('id', $selectedRoleIds)
            ->pluck('name')
            ->all();

        $requestedDefaultWorkspace = $request->filled('default_workspace')
            ? (string) $request->input('default_workspace')
            : null;

        if ($requestedDefaultWorkspace !== null
            && $workspaceService->findForRoleNames($selectedRoleNames, $requestedDefaultWorkspace) === null) {
            return back()
                ->withInput()
                ->withErrors(['default_workspace' => 'Layout mac dinh khong hop le voi cac vai tro duoc chon.']);
        }

        $defaultMobileRoleId = $request->filled('default_mobile_role_id')
            ? (int) $request->input('default_mobile_role_id')
            : null;
        $defaultMobileRole = null;

        if ($defaultMobileRoleId !== null) {
            $defaultMobileRole = Role::query()->find($defaultMobileRoleId);
            $mobileLayout = config('workspaces.catalog.' . $defaultMobileRole?->layout_mobile_slug);

            if (!$selectedRoleIds->contains($defaultMobileRoleId)
                || !is_array($mobileLayout)
                || ($mobileLayout['platform'] ?? null) !== 'my_app') {
                return back()
                    ->withInput()
                    ->withErrors(['default_mobile_role_id' => 'Layout Mobile mặc định không hợp lệ với các vai trò được chọn.']);
            }
        }

        $user->update([
            'name'          => $request->name,
            'zalo_name'     => $request->zalo_name,
            'email'         => $request->email,
            'password'      => $request->password ? Hash::make($request->password) : $user->password,
            'warehouse_id'  => $request->warehouse_id,
            'team_id'       => $request->team_id,
            'block_id'      => $request->block_id,
            'department_id' => $request->department_id,
            'default_workspace' => $requestedDefaultWorkspace,
            'default_role_id' => $defaultMobileRoleId,
            'mobile_selected_role' => $defaultMobileRole?->name,
        ]);

        $user->roles()->sync($request->roles ?? []);

        return redirect()->route('users.index')->with('success', __('users.messages.updated'));
    }

    public function destroy(Request $request, User $user)
    {
        DB::transaction(function () use ($user) {
            $this->releaseUserReferences([$user->id]);
            $user->roles()->detach();
            $user->delete();
        });

        return $this->redirectAfterDeletion($request, __('users.messages.deleted'));
    }

    private function redirectAfterDeletion(Request $request, string $message)
    {
        $redirectTo = $request->input('redirect_to');

        if (is_string($redirectTo) && $redirectTo !== '') {
            $redirectHost = parse_url($redirectTo, PHP_URL_HOST);
            $appHost = parse_url(url('/'), PHP_URL_HOST);

            if ($redirectHost === null || $redirectHost === $appHost) {
                return redirect()->to($redirectTo)->with('success', $message);
            }
        }

        return redirect()->route('users.index')->with('success', $message);
    }

    private function releaseUserReferences(array $userIds): void
    {
        if ($userIds === []) {
            return;
        }

        if (Schema::hasTable('customers') && Schema::hasColumn('customers', 'assigned_to')) {
            DB::table('customers')
                ->whereIn('assigned_to', $userIds)
                ->update(['assigned_to' => null]);
        }

        if (Schema::hasTable('companies') && Schema::hasColumn('companies', 'assigned_to')) {
            DB::table('companies')
                ->whereIn('assigned_to', $userIds)
                ->update(['assigned_to' => null]);
        }

        if (Schema::hasTable('approval_orders') && Schema::hasColumn('approval_orders', 'approved_by')) {
            DB::table('approval_orders')
                ->whereIn('approved_by', $userIds)
                ->update(['approved_by' => null]);
        }
    }
}
