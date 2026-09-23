<?php

namespace App\Http\Controllers;

use App\Models\ApprovalWorkflow;
use App\Models\TaskAssignee;
use App\Models\TaskAssignment;
use App\Models\TaskCompletionImage;
use App\Models\TaskStatusLog;
use App\Models\TaskDelegateConfig;
use App\Models\User;
use App\Services\ApprovalService;
use App\Services\TaskMenuService;
use Carbon\Carbon;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class TaskAssignmentController extends Controller
{
    protected $settings;

    public function __construct(private ApprovalService $approvalService)
    {
        try {
            $this->settings = Cache::remember('settings', 60, function () {
                return Setting::all()->keyBy('key');
            });
        } catch (Throwable $e) {
            report($e);
            $this->settings = collect();
        }

        view()->share('settings', $this->settings);
    }

    // ── Index ─────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $user = auth()->user();

        $q = TaskAssignment::with(['creator:id,name', 'workflow:id,name', 'approvalSteps.step', 'assignees.user:id,name'])
            ->latest();

        // Role-based visibility
        if (!$user->hasRole('admin') && !$user->hasRole('CEO') && !$user->hasRole('manager')) {
            $userRoles = $user->roles->pluck('name')->toArray();
            $q->where(function ($inner) use ($user, $userRoles) {
                $inner->where('created_by', $user->id)
                      // custom assignee
                      ->orWhereHas('assignees', fn ($s) => $s->where('user_id', $user->id))
                      // workflow step actor
                      ->orWhereHas('approvalSteps', fn ($s) => $s->where('approved_by', $user->id))
                      ->orWhereHas('approvalSteps.step', fn ($s) => $s->whereIn('role_slug', $userRoles));
            });
        }

        // Filters
        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }
        if ($request->filled('priority')) {
            $q->where('priority', $request->priority);
        }
        if ($request->filled('search')) {
            $kw = $request->search;
            $q->where(function ($s) use ($kw) {
                $s->where('title', 'like', "%{$kw}%")
                  ->orWhere('code', 'like', "%{$kw}%");
            });
        }
        if ($request->filled('created_by')) {
            $q->where('created_by', $request->created_by);
        }

        $tasks = $q->paginate(20)->withQueryString();

        // Counts for tabs
        $counts = [
            'all'         => TaskAssignment::count(),
            'pending'     => TaskAssignment::where('status', 'pending')->count(),
            'in_progress' => TaskAssignment::whereIn('status', ['in_progress', TaskAssignment::STATUS_PROCESSING])->count(),
            'completed'   => TaskAssignment::where('status', 'completed')->count(),
        ];

        return view('task_assignments.index', compact('tasks', 'counts'));
    }

    // ── Create ────────────────────────────────────────────────────────

    public function create(Request $request)
    {
        $user = auth()->user();
        abort_unless(TaskMenuService::canAssignTasks($user) || TaskDelegateConfig::canAssignTasks($user), 403);
        $isFrontRoles = $user && $user->isSalesFlowRole();
        $isFrontendRoute = $request->routeIs('tasks.create');

        $workflows = ApprovalWorkflow::where('is_active', true)
            ->where(function ($q) {
                $q->whereJsonContains('applies_to', ApprovalWorkflow::ACTIVITY_TASK_ASSIGNMENT)
                  ->orWhereNull('applies_to');
            })
            ->with('steps')
            ->get();

        $allowedAssignees = $this->allowedAssigneesFor($user);

        // Pre-select parent from query string
        $parentId = $request->query('parent_id');

        $users = User::orderBy('name')->get(['id', 'name']);

        $useFrontend = $isFrontRoles && $isFrontendRoute;
        $layout = $useFrontend ? 'layouts.site' : 'layouts.admin';
        $indexRoute = $useFrontend ? 'tasks.index' : 'task-assignments.index';
        $storeRoute = $useFrontend ? 'tasks.store' : 'task-assignments.store';

        return view('task_assignments.create', compact('workflows', 'users', 'allowedAssignees', 'parentId', 'layout', 'indexRoute', 'storeRoute'));
    }

    // ── Store ─────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $user = auth()->user();
        abort_unless(TaskMenuService::canAssignTasks($user) || TaskDelegateConfig::canAssignTasks($user), 403);

        $data = $request->validate([
            'title'            => 'required|string|max:255',
            'description'      => 'nullable|string|max:5000',
            'priority'         => 'required|in:low,medium,high,urgent',
            'approval_flow_id' => 'nullable|exists:approval_flows,id',
            'parent_id'        => 'nullable|exists:task_assignments,id',
            'due_date'         => 'nullable|date_format:Y-m-d\TH:i|after_or_equal:now',
            'attachments.*'    => 'nullable|file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx,xlsx,zip',
            'assignee_ids'     => 'required|array|min:1',
            'assignee_ids.*'   => 'required|integer|distinct|exists:users,id',
        ]);

        if (!empty($data['due_date'])) {
            $data['due_date'] = Carbon::createFromFormat('Y-m-d\TH:i', $data['due_date'])->format('Y-m-d H:i:s');
        }

        // Validate that chosen assignees are actually allowed for this user
        if (!empty($data['assignee_ids'])) {
            $allowed = $this->allowedAssigneesFor($user)->pluck('id')->map(fn ($id) => (int) $id)->all();
            foreach ($data['assignee_ids'] as $aid) {
                if (!in_array((int) $aid, $allowed, true)) {
                    return back()->withInput()->withErrors(['assignee_ids' => 'Bạn không có quyền giao việc cho người dùng đã chọn.']);
                }
            }
        }

        // Handle file uploads
        $paths = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $paths[] = $file->store('task_assignments/' . now()->format('Y/m'), 'public');
            }
        }

        $task = TaskAssignment::create([
            'code'             => TaskAssignment::generateCode(),
            'title'            => $data['title'],
            'description'      => $data['description'] ?? null,
            'priority'         => $data['priority'],
            'approval_flow_id' => $data['approval_flow_id'] ?? null,
            'parent_id'        => $data['parent_id'] ?? null,
            'due_date'         => $data['due_date'] ?? null,
            'created_by'       => $user->id,
            'status'           => TaskAssignment::STATUS_PENDING,
            'attachments'      => $paths ?: null,
        ]);

        // Create assignee records
        if (!empty($data['assignee_ids'])) {
            foreach ($data['assignee_ids'] as $assigneeId) {
                TaskAssignee::create([
                    'task_id' => $task->id,
                    'user_id' => $assigneeId,
                    'status'  => 'pending',
                ]);
            }
        }

        $this->approvalService->initTaskApproval($task);

        return redirect()->route('task-assignments.show', $task)
            ->with('success', 'Giao viec ' . $task->code . ' da duoc tao va gui len quy trinh phe duyet.');
    }

    // ── Show ──────────────────────────────────────────────────────────

    public function show(TaskAssignment $taskAssignment)
    {
        abort_unless($this->canViewTask($taskAssignment, auth()->user()), 403);

        $task = $taskAssignment->load([
            'creator:id,name',
            'workflow.steps',
            'parent:id,code,title',
            'subTasks.creator:id,name',
            'approvalSteps.step',
            'approvalSteps.approver:id,name',
            'assignees.user:id,name',
        ]);

        $user    = auth()->user();
        $canAct  = $this->approvalService->canApproveTaskStep($task, $user);
        $current = $this->approvalService->getCurrentPendingTaskStep($task);
        $isFrontRoles = $user && $user->isSalesFlowRole();
        $isWarehouse = $user && $user->hasRole('warehouse');

        // Current user's assignee record (if any)
        $myAssignee = $task->assignees->firstWhere('user_id', $user->id);

        $layout = $isWarehouse ? 'layouts.warehouse' : ($isFrontRoles ? 'layouts.site' : 'layouts.admin');
        $indexRoute = $isWarehouse ? 'tasks.my-tasks' : ($isFrontRoles ? 'my-tasks' : 'task-assignments.index');
        $showRoute = $isFrontRoles ? 'tasks.show' : 'task-assignments.show';
        $createRoute = $isFrontRoles ? 'tasks.create' : 'task-assignments.create';

        return view('task_assignments.show', compact('task', 'canAct', 'current', 'myAssignee', 'layout', 'indexRoute', 'showRoute', 'createRoute'));
    }

    // ── Edit ──────────────────────────────────────────────────────────

    public function edit(TaskAssignment $taskAssignment)
    {
        $user = auth()->user();
        $task = $taskAssignment;

        // Only users with configured task assignment rights can edit
        if (!$task->canBeEditedBy($user)) {
            return back()->with('error', 'Ban khong co quyen chinh sua cong viec nay.');
        }

        $isFrontRoles = $user && $user->isSalesFlowRole();
        $isFrontendRoute = false; // Edit always in admin

        $workflows = ApprovalWorkflow::where('is_active', true)
            ->where(function ($q) {
                $q->whereJsonContains('applies_to', ApprovalWorkflow::ACTIVITY_TASK_ASSIGNMENT)
                  ->orWhereNull('applies_to');
            })
            ->with('steps')
            ->get();

        $allowedAssignees = $this->allowedAssigneesFor($user);
        $currentAssigneeIds = $task->assignees->pluck('user_id')->map(fn ($id) => (int) $id);
        $allowedAssignees = User::query()
            ->whereIn('id', $allowedAssignees->pluck('id')->merge($currentAssigneeIds)->unique()->all())
            ->orderBy('name')
            ->get(['id', 'name']);
        $parentId = $task->parent_id;
        $parentTasks = TaskAssignment::query()
            ->where('status', '!=', TaskAssignment::STATUS_COMPLETED)
            ->where('status', '!=', TaskAssignment::STATUS_CANCELLED)
            ->where('created_by', auth()->id())
            ->where('id', '!=', $task->id)
            ->latest()
            ->limit(50)
            ->get(['id', 'code', 'title']);

        $layout = 'layouts.admin';
        $indexRoute = 'task-assignments.index';
        $storeRoute = 'task-assignments.update';
        $formMethod = 'PUT';

        // Load current assignees for edit form
        $currentAssigneeIds = $currentAssigneeIds->all();

        return view('task_assignments.edit', compact(
            'task',
            'workflows',
            'allowedAssignees',
            'parentId',
            'parentTasks',
            'layout',
            'indexRoute',
            'storeRoute',
            'formMethod',
            'currentAssigneeIds'
        ));
    }

    // ── Update ────────────────────────────────────────────────────────

    public function update(Request $request, TaskAssignment $taskAssignment)
    {
        $user = auth()->user();
        $task = $taskAssignment;

        // Only users with configured task assignment rights can update
        if (!$task->canBeEditedBy($user)) {
            return back()->with('error', 'Ban khong co quyen chinh sua cong viec nay.');
        }

        $data = $request->validate([
            'title'            => 'required|string|max:255',
            'description'      => 'nullable|string|max:5000',
            'priority'         => 'required|in:low,medium,high,urgent',
            'approval_flow_id' => 'nullable|exists:approval_flows,id',
            'parent_id'        => 'nullable|exists:task_assignments,id',
            'due_date'         => 'nullable|date_format:Y-m-d\TH:i',
            'attachments.*'    => 'nullable|file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx,xlsx,zip',
            'assignee_ids'     => 'required|array|min:1',
            'assignee_ids.*'   => 'required|integer|distinct|exists:users,id',
        ]);

        if (!empty($data['due_date'])) {
            $data['due_date'] = Carbon::createFromFormat('Y-m-d\TH:i', $data['due_date'])->format('Y-m-d H:i:s');
        }

        // Validate that chosen assignees are actually allowed for this user
        if (!empty($data['assignee_ids'])) {
            $allowed = $this->allowedAssigneesFor($user)->pluck('id')->map(fn ($id) => (int) $id)->all();
            foreach ($data['assignee_ids'] as $aid) {
                if (!in_array((int) $aid, $allowed, true)) {
                    return back()->withInput()->withErrors(['assignee_ids' => 'Bạn không có quyền giao việc cho người dùng đã chọn.']);
                }
            }
        }

        // Handle new file uploads
        $paths = $task->attachments ? (is_array($task->attachments) ? $task->attachments : []) : [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $paths[] = $file->store('task_assignments/' . now()->format('Y/m'), 'public');
            }
        }

        try {
            DB::transaction(function () use ($data, $paths, $task, $user) {
                // Update task
                $task->update([
                    'title'            => $data['title'],
                    'description'      => $data['description'] ?? null,
                    'priority'         => $data['priority'],
                    'approval_flow_id' => $data['approval_flow_id'] ?? null,
                    'parent_id'        => $data['parent_id'] ?? null,
                    'due_date'         => $data['due_date'] ?? null,
                    'attachments'      => $paths ?: null,
                ]);

                // Update assignees
                $newAssigneeIds = $data['assignee_ids'] ?? [];
                $currentAssigneeIds = $task->assignees->pluck('user_id')->toArray();

                // Remove assignees that are no longer selected
                $toRemove = array_diff($currentAssigneeIds, $newAssigneeIds);
                if (!empty($toRemove)) {
                    TaskAssignee::where('task_id', $task->id)
                        ->whereIn('user_id', $toRemove)
                        ->delete();
                }

                // Add new assignees
                $toAdd = array_diff($newAssigneeIds, $currentAssigneeIds);
                foreach ($toAdd as $assigneeId) {
                    TaskAssignee::create([
                        'task_id' => $task->id,
                        'user_id' => $assigneeId,
                        'status'  => 'pending',
                    ]);
                }

                // Log the update
                TaskStatusLog::log(
                    $task,
                    $task->status,
                    $user,
                    'Da chinh sua cong viec'
                );
            });

            return redirect()->route('task-assignments.show', $task)
                ->with('success', 'Cong viec ' . $task->code . ' da duoc cap nhat.');
        } catch (\Exception $e) {
            return back()->with('error', 'Loi: ' . $e->getMessage());
        }
    }

    // ── Approve ───────────────────────────────────────────────────────

    public function approve(Request $request, TaskAssignment $taskAssignment)
    {
        $request->validate(['note' => 'nullable|string|max:1000']);

        try {
            $done = $this->approvalService->approveTaskStep(
                $taskAssignment,
                auth()->user(),
                $request->note
            );
            $taskAssignment->refresh();
            $msg = $done
                ? ($taskAssignment->status === TaskAssignment::STATUS_COMPLETED
                    ? 'Đã duyệt đủ các bước và công việc đang chờ xác nhận hoàn thành.'
                    : 'Đã duyệt đủ các bước. Công việc tiếp tục chờ các thành viên hoàn thành.')
                : 'Bước phê duyệt đã được chấp nhận và chuyển sang bước tiếp theo.';
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('task-assignments.show', $taskAssignment)->with('success', $msg);
    }

    // ── Reject ────────────────────────────────────────────────────────

    public function reject(Request $request, TaskAssignment $taskAssignment)
    {
        $request->validate(['reason' => 'required|string|max:1000']);

        try {
            $this->approvalService->rejectTaskStep(
                $taskAssignment,
                auth()->user(),
                $request->reason
            );
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('task-assignments.show', $taskAssignment)
            ->with('success', 'Da tu choi cong viec ' . $taskAssignment->code . '.');
    }

    // ── Cancel ────────────────────────────────────────────────────────

    public function cancel(TaskAssignment $taskAssignment)
    {
        $user = auth()->user();

        if ($taskAssignment->created_by !== $user->id && !$user->hasRole('admin') && !$user->hasRole('manager') && !$user->hasRole('CEO')) {
            return back()->with('error', 'Ban khong co quyen huy cong viec nay.');
        }

        $taskAssignment->update(['status' => TaskAssignment::STATUS_CANCELLED]);

        return redirect()->route('task-assignments.index')
            ->with('success', 'Cong viec ' . $taskAssignment->code . ' da bi huy.');
    }

    // ── Assignee: update own status ───────────────────────────────────

    public function assigneeUpdate(Request $request, TaskAssignment $taskAssignment)
    {
        $request->validate([
            'status' => 'required|in:in_progress,processing,rejected',
            'note'   => 'nullable|string|max:1000',
        ]);

        $assigneeStatus = $request->status === 'in_progress'
            ? TaskAssignment::STATUS_PROCESSING
            : $request->status;

        $record = TaskAssignee::where('task_id', $taskAssignment->id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $record->update([
            'status'       => $assigneeStatus,
            'note'         => $request->note,
            'completed_at' => $assigneeStatus === 'completed' ? now() : null,
        ]);

        if ($assigneeStatus === TaskAssignment::STATUS_PROCESSING
            && in_array($taskAssignment->status, [TaskAssignment::STATUS_PENDING, TaskAssignment::STATUS_REJECTED], true)) {
            $taskAssignment->update([
                'status' => TaskAssignment::STATUS_PROCESSING,
                'rejected_reason' => null,
            ]);
        }

        TaskStatusLog::log(
            $taskAssignment,
            $taskAssignment->status,
            auth()->user(),
            $assigneeStatus === TaskAssignment::STATUS_PROCESSING ? 'Đã nhận việc' : 'Không thể thực hiện: '.($request->note ?: 'Không có ghi chú')
        );

        // If ALL assignees are done, mark overall task completed
        if ($assigneeStatus === 'completed') {
            $allDone = TaskAssignee::where('task_id', $taskAssignment->id)
                ->whereNotIn('status', ['completed', 'rejected'])
                ->doesntExist();

            if ($allDone && $taskAssignment->approvalSteps->where('status', 'pending')->isEmpty()) {
                $taskAssignment->update([
                    'status'       => TaskAssignment::STATUS_COMPLETED,
                    'completed_at' => now(),
                ]);
            }
        }

        return redirect()->route('task-assignments.show', $taskAssignment)
            ->with('success', 'Da cap nhat trang thai cua ban.');
    }

    // ── Complete Task with Content and Images ─────────────────────────

    public function completeForm(TaskAssignment $taskAssignment)
    {
        $user = auth()->user();
        abort_unless($this->canViewTask($taskAssignment, $user), 403);
        $isFrontRoles = $user && $user->isSalesFlowRole();
        $isWarehouse = $user && $user->hasRole('warehouse');

        $canComplete = $taskAssignment->assignees()
            ->where('user_id', $user->id)
            ->exists();

        if (!$canComplete && !$user->hasRole('admin')) {
            return back()->with('error', 'Ban khong co quyen hoan thanh cong viec nay.');
        }

        if (!$user->hasRole('admin') && $taskAssignment->assignees()
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->exists()) {
            return back()->with('success', 'Phần việc của bạn đã hoàn thành và đang chờ các thành viên còn lại.');
        }

        $layout = $isWarehouse ? 'layouts.warehouse' : ($isFrontRoles ? 'layouts.site' : 'layouts.app');
        $showRoute = $isFrontRoles ? 'tasks.show' : 'task-assignments.show';
        $submitRoute = $isFrontRoles ? 'tasks.complete' : 'task-assignments.complete-with-content';

        return view('task_assignments.complete', [
            'task' => $taskAssignment->load(['creator:id,name', 'assignees.user:id,name']),
            'layout' => $layout,
            'showRoute' => $showRoute,
            'submitRoute' => $submitRoute,
        ]);
    }

    public function completeWithContent(Request $request, TaskAssignment $taskAssignment)
    {
        $user = auth()->user();

        // Check if user can complete this task
        $canComplete = $taskAssignment->assignees()
            ->where('user_id', $user->id)
            ->exists();

        if (!$canComplete && !$user->hasRole('admin')) {
            return back()->with('error', 'Ban khong co quyen hoan thanh cong viec nay.');
        }

        if (!$user->hasRole('admin') && $taskAssignment->assignees()
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->exists()) {
            return back()->with('error', 'Phần việc của bạn đã được ghi nhận hoàn thành.');
        }

        if ($taskAssignment->status === TaskAssignment::STATUS_DONE || $taskAssignment->status === TaskAssignment::STATUS_CANCELLED) {
            return back()->with('error', 'Công việc đã kết thúc, không thể gửi hoàn thành lại.');
        }

        $request->validate([
            'completion_content' => 'required|string|min:10|max:5000',
            'completion_notes'   => 'required|string|min:5|max:2000',
            'images'             => 'required|array|min:1',
            'images.*'           => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        try {
            DB::transaction(function () use ($request, $taskAssignment, $user) {
                $assignee = TaskAssignee::where('task_id', $taskAssignment->id)
                    ->where('user_id', $user->id)
                    ->first();

                if ($assignee) {
                    $assignee->update([
                        'status' => 'completed',
                        'note' => $request->completion_content."\n\n".$request->completion_notes,
                        'completed_at' => now(),
                    ]);
                }

                $allDone = $taskAssignment->assignees()
                    ->where('status', '!=', 'completed')
                    ->doesntExist();
                $allApproved = $taskAssignment->approvalSteps()
                    ->where('status', 'pending')
                    ->doesntExist();
                $readyForVerification = $allDone && $allApproved;

                $taskAssignment->update([
                    'status' => $readyForVerification ? TaskAssignment::STATUS_COMPLETED : TaskAssignment::STATUS_PROCESSING,
                    'completion_content' => $request->completion_content,
                    'completion_notes' => $request->completion_notes,
                    'completed_at' => $readyForVerification ? now() : null,
                    'rejected_reason' => null,
                ]);

                // Log status change
                TaskStatusLog::log(
                    $taskAssignment,
                    $taskAssignment->status,
                    $user,
                    'Gửi hoàn thành công việc'
                );

                // Handle image uploads
                if ($request->hasFile('images')) {
                    foreach ($request->file('images') as $index => $image) {
                        $path = $image->store(
                            'task-completions/' . $taskAssignment->id . '/' . now()->format('Y/m/d'),
                            'public'
                        );

                        TaskCompletionImage::create([
                            'task_id'           => $taskAssignment->id,
                            'image_path'        => $path,
                            'original_filename' => $image->getClientOriginalName(),
                            'sort_order'        => $index,
                        ]);
                    }
                }

            });

            // Send notification to task creator
            // You can integrate Laravel notifications here
            // TaskCompletedNotification::dispatch($taskAssignment, $user);

            $showRoute = $user->isSalesFlowRole() ? 'tasks.show' : 'task-assignments.show';

            $taskAssignment->refresh();
            $message = $taskAssignment->status === TaskAssignment::STATUS_COMPLETED
                ? 'Đã gửi hoàn thành. Công việc đang chờ người giao xác nhận.'
                : 'Đã ghi nhận phần việc của bạn. Công việc tiếp tục chờ các thành viên còn lại.';

            return redirect()->route($showRoute, $taskAssignment)->with('success', $message);
        } catch (\Exception $e) {
            return back()->with('error', 'Loi: ' . $e->getMessage());
        }
    }

    // ── Verify/Approve Task Completion ────────────────────────────────

    public function verifyCompletion(Request $request, TaskAssignment $taskAssignment)
    {
        $user = auth()->user();

        if (!$taskAssignment->canBeVerified()) {
            return back()->with('error', 'Cong viec khong o trang thai cho xac nhan.');
        }

        if (!$this->canVerifyTask($taskAssignment, $user)) {
            return back()->with('error', 'Ban khong co quyen xac nhan cong viec nay.');
        }

        $request->validate([
            'verification_notes' => 'nullable|string|max:2000',
        ]);

        try {
            DB::transaction(function () use ($request, $taskAssignment, $user) {
                $taskAssignment->update([
                    'status'                  => TaskAssignment::STATUS_DONE,
                    'completion_verified_at'  => now(),
                    'completion_verified_by'  => $user->id,
                ]);

                TaskStatusLog::log(
                    $taskAssignment,
                    TaskAssignment::STATUS_DONE,
                    $user,
                    $request->verification_notes ?? 'Xác nhận hoàn thành'
                );
            });

            return redirect()->route('task-assignments.show', $taskAssignment)
                ->with('success', 'Cong viec da duoc xac nhan hoan thanh.');
        } catch (\Exception $e) {
            return back()->with('error', 'Loi: ' . $e->getMessage());
        }
    }

    public function verifyForm(TaskAssignment $taskAssignment)
    {
        $user = auth()->user();
        abort_unless($this->canVerifyTask($taskAssignment, $user), 403);

        if (!$taskAssignment->canBeVerified()) {
            return redirect()->route('task-assignments.show', $taskAssignment)
                ->with('error', 'Công việc không ở trạng thái chờ xác nhận hoàn thành.');
        }

        $task = $taskAssignment->load([
            'creator:id,name',
            'assignees.user:id,name',
            'completionImages',
            'statusLogs.changedBy:id,name',
        ]);

        $layout = $user->hasRole('warehouse')
            ? 'layouts.warehouse'
            : ($user->isSalesFlowRole() ? 'layouts.site' : 'layouts.admin');

        return view('task_assignments.verify', compact('task', 'layout'));
    }

    // ── Reject Task Completion ────────────────────────────────────────

    public function rejectCompletion(Request $request, TaskAssignment $taskAssignment)
    {
        $user = auth()->user();

        if (!$taskAssignment->canBeRejected()) {
            return back()->with('error', 'Cong viec khong o trang thai cho tu choi.');
        }

        if (!$this->canVerifyTask($taskAssignment, $user)) {
            return back()->with('error', 'Ban khong co quyen tu choi cong viec nay.');
        }

        $request->validate([
            'rejected_reason' => 'required|string|min:10|max:2000',
        ]);

        try {
            DB::transaction(function () use ($request, $taskAssignment, $user) {
                $taskAssignment->update([
                    'status'           => TaskAssignment::STATUS_REJECTED,
                    'rejected_reason'  => $request->rejected_reason,
                    'completed_at' => null,
                    'completion_verified_at' => null,
                    'completion_verified_by' => null,
                ]);

                TaskStatusLog::log(
                    $taskAssignment,
                    TaskAssignment::STATUS_REJECTED,
                    $user,
                    $request->rejected_reason
                );

                // Reset assignee status to allow retry
                TaskAssignee::where('task_id', $taskAssignment->id)
                    ->update(['status' => 'pending', 'completed_at' => null]);
            });

            return redirect()->route('task-assignments.show', $taskAssignment)
                ->with('success', 'Cong viec da duoc yeu cau lam lai.');
        } catch (\Exception $e) {
            return back()->with('error', 'Loi: ' . $e->getMessage());
        }
    }

    // ── Assigned To Me ────────────────────────────────────────────────

    public function assignedToMe(Request $request)
    {
        $user = auth()->user();
        $isFrontRoles = $user && $user->isSalesFlowRole();
        $isWarehouse = $user && $user->hasRole('warehouse');
        $isFrontendRoute = $request->routeIs('my-tasks');

        $tasks = TaskAssignment::whereHas('assignees', fn($q) => $q->where('user_id', $user->id))
            ->when($request->filled('search'), function ($query) use ($request) {
                $keyword = trim((string) $request->search);
                $query->where(fn ($item) => $item->where('title', 'like', "%{$keyword}%")->orWhere('code', 'like', "%{$keyword}%"));
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('priority'), fn ($query) => $query->where('priority', $request->priority))
            ->with(['creator:id,name', 'assignees.user:id,name', 'completionImages', 'statusLogs'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $layout = $isWarehouse
            ? 'layouts.warehouse'
            : (($isFrontRoles && $isFrontendRoute) ? 'layouts.site' : 'layouts.app');
        $filterRoute = $isWarehouse
            ? 'tasks.my-tasks'
            : (($isFrontRoles && $isFrontendRoute) ? 'my-tasks' : 'task-assignments.assigned-to-me');

        return view('task_assignments.assigned-to-me', compact('tasks', 'layout', 'filterRoute'));
    }

    // ── Assigned By Me ────────────────────────────────────────────────

    public function assignedByMe(Request $request)
    {
        $user = auth()->user();
        $isFrontRoles = $user && $user->isSalesFlowRole();

        $tasks = TaskAssignment::where('created_by', $user->id)
            ->with(['assignees.user:id,name', 'completionImages', 'statusLogs'])
            ->latest()
            ->paginate(20);

        $layout = $isFrontRoles ? 'layouts.site' : 'layouts.app';
        $filterRoute = 'tasks.assigned';
        $createRoute = $isFrontRoles ? 'tasks.create' : 'task-assignments.create';

        return view('task_assignments.assigned-by-me', compact('tasks', 'layout', 'filterRoute', 'createRoute'));
    }

    // ── In Progress ───────────────────────────────────────────────────

    public function inProgress(Request $request)
    {
        $user = auth()->user();
        $isFrontRoles = $user && $user->isSalesFlowRole();
        $isWarehouse = $user && $user->hasRole('warehouse');

        $tasks = TaskAssignment::whereHas('assignees', fn($q) => $q->where('user_id', $user->id))
            ->whereIn('status', ['in_progress', TaskAssignment::STATUS_PROCESSING])
            ->with(['creator:id,name', 'statusLogs'])
            ->latest()
            ->paginate(20);

        $layout = $isWarehouse ? 'layouts.warehouse' : ($isFrontRoles ? 'layouts.site' : 'layouts.app');
        $detailRoute = $isFrontRoles ? 'tasks.show' : 'task-assignments.show';

        return view('task_assignments.in-progress', compact('tasks', 'layout', 'detailRoute'));
    }

    // ── Awaiting Verification ────────────────────────────────────────

    public function awaitingVerification(Request $request)
    {
        $user = auth()->user();
        $isFrontRoles = $user && $user->isSalesFlowRole();

        $tasks = TaskAssignment::where('status', TaskAssignment::STATUS_COMPLETED)
            ->where(fn($q) => $q->where('created_by', $user->id)->orWhere('completion_verified_by', $user->id))
            ->with(['assignees.user:id,name', 'completionImages', 'statusLogs'])
            ->latest()
            ->paginate(20);

        $layout = $isFrontRoles ? 'layouts.site' : 'layouts.app';
        $detailRoute = $isFrontRoles ? 'tasks.show' : 'task-assignments.show';

        return view('task_assignments.awaiting-verification', compact('tasks', 'layout', 'detailRoute'));
    }

    // ── Verify List ───────────────────────────────────────────────────

    public function verifyList(Request $request)
    {
        $user = auth()->user();

        $tasks = TaskAssignment::where('status', TaskAssignment::STATUS_COMPLETED)
            ->when(!($user->hasRole('admin') || $user->hasRole('CEO') || $user->hasRole('manager')), fn ($query) => $query->where('created_by', $user->id))
            ->with(['creator:id,name', 'assignees.user:id,name', 'completionImages', 'statusLogs'])
            ->latest()
            ->paginate(20);

        return view('task_assignments.verify-list', compact('tasks'));
    }

    // ── History ───────────────────────────────────────────────────────

    public function history(Request $request)
    {
        $user = auth()->user();
        $isFrontRoles = $user && $user->isSalesFlowRole();

        $tasks = TaskAssignment::whereHas('assignees', fn($q) => $q->where('user_id', $user->id))
            ->whereIn('status', [TaskAssignment::STATUS_DONE, TaskAssignment::STATUS_REJECTED, TaskAssignment::STATUS_CANCELLED])
            ->with(['creator:id,name', 'statusLogs', 'completionImages'])
            ->latest()
            ->paginate(20);

        $layout = $isFrontRoles ? 'layouts.site' : 'layouts.app';
        $detailRoute = $isFrontRoles ? 'tasks.show' : 'task-assignments.show';

        return view('task_assignments.history', compact('tasks', 'layout', 'detailRoute'));
    }

    private function allowedAssigneesFor(User $user)
    {
        $delegated = TaskDelegateConfig::allowedAssignees($user);
        $isPrivileged = $user->hasRole('admin')
            || $user->hasRole('CEO')
            || $user->hasRole('manager');

        if ($isPrivileged || (TaskMenuService::canAssignTasks($user) && $delegated->isEmpty())) {
            return User::query()
                ->whereKeyNot($user->id)
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        return $delegated;
    }

    private function canViewTask(TaskAssignment $task, User $user): bool
    {
        if ($user->hasRole('admin') || $user->hasRole('CEO') || $user->hasRole('manager')) {
            return true;
        }

        return (int) $task->created_by === (int) $user->id
            || $task->assignees()->where('user_id', $user->id)->exists()
            || $task->approvalSteps()->where('approved_by', $user->id)->exists()
            || $task->approvalSteps()->whereHas('step', fn ($query) => $query->whereIn('role_slug', $user->roles->pluck('name')))->exists();
    }

    private function canVerifyTask(TaskAssignment $task, User $user): bool
    {
        return (int) $task->created_by === (int) $user->id
            || $user->hasRole('admin')
            || $user->hasRole('CEO')
            || $user->hasRole('manager');
    }
}
