<?php

namespace App\Http\Controllers;

use App\Models\ApprovalWorkflow;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ApprovalWorkflowController extends Controller
{
    public function index(): View
    {
        $workflows = ApprovalWorkflow::with('steps')->latest()->paginate(15);

        return view('approval_workflows.index', compact('workflows'));
    }

    public function create(): View
    {
        $roles = Role::query()->orderBy('name')->pluck('name');
        $activities = ApprovalWorkflow::availableActivities();

        return view('approval_workflows.create', compact('roles', 'activities'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => 'required|string|max:100|unique:approval_flows,code',
            'name' => 'required|string|max:255',
            'is_active' => 'nullable|boolean',
            'applies_to' => 'required|array|min:1',
            'applies_to.*' => ['required', 'string', Rule::in(array_keys(ApprovalWorkflow::availableActivities()))],
            'steps' => 'required|array|min:1',
            'steps.*.role_slug' => 'required|string|max:100|exists:roles,name',
            'steps.*.can_skip' => 'nullable|boolean',
        ]);

        DB::transaction(function () use ($data): void {
            $activities = array_values(array_unique($data['applies_to']));

            if (!empty($data['is_active'])) {
                $this->deactivateOverlappingWorkflows($activities);
            }

            $workflow = ApprovalWorkflow::create([
                'code' => $data['code'],
                'name' => $data['name'],
                'is_active' => (bool) ($data['is_active'] ?? false),
                'applies_to' => $activities,
            ]);

            foreach (array_values($data['steps']) as $index => $step) {
                $workflow->steps()->create([
                    'step_order' => $index + 1,
                    'role_slug' => $step['role_slug'],
                    'can_skip' => (bool) ($step['can_skip'] ?? false),
                ]);
            }
        });

        return redirect()->route('approval-workflows.index')->with('success', 'Đã tạo quy trình xét duyệt thành công.');
    }

    public function edit(ApprovalWorkflow $approvalWorkflow): View
    {
        $roles = Role::query()->orderBy('name')->pluck('name');
        $activities = ApprovalWorkflow::availableActivities();
        $approvalWorkflow->load(['steps' => function ($query) {
            $query->orderBy('step_order');
        }]);

        return view('approval_workflows.edit', compact('approvalWorkflow', 'roles', 'activities'));
    }

    public function update(Request $request, ApprovalWorkflow $approvalWorkflow): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:100', Rule::unique('approval_flows', 'code')->ignore($approvalWorkflow->id)],
            'name' => 'required|string|max:255',
            'is_active' => 'nullable|boolean',
            'applies_to' => 'required|array|min:1',
            'applies_to.*' => ['required', 'string', Rule::in(array_keys(ApprovalWorkflow::availableActivities()))],
            'steps' => 'required|array|min:1',
            'steps.*.role_slug' => 'required|string|max:100|exists:roles,name',
            'steps.*.can_skip' => 'nullable|boolean',
        ]);

        DB::transaction(function () use ($data, $approvalWorkflow): void {
            $activities = array_values(array_unique($data['applies_to']));

            if (!empty($data['is_active'])) {
                $this->deactivateOverlappingWorkflows($activities, $approvalWorkflow->id);
            }

            $approvalWorkflow->update([
                'code' => $data['code'],
                'name' => $data['name'],
                'is_active' => (bool) ($data['is_active'] ?? false),
                'applies_to' => $activities,
            ]);

            $approvalWorkflow->steps()->delete();

            foreach (array_values($data['steps']) as $index => $step) {
                $approvalWorkflow->steps()->create([
                    'step_order' => $index + 1,
                    'role_slug' => $step['role_slug'],
                    'can_skip' => (bool) ($step['can_skip'] ?? false),
                ]);
            }
        });

        return redirect()->route('approval-workflows.index')->with('success', 'Đã cập nhật quy trình xét duyệt thành công.');
    }

    private function deactivateOverlappingWorkflows(array $activities, ?int $exceptWorkflowId = null): void
    {
        $query = ApprovalWorkflow::query();

        if ($exceptWorkflowId !== null) {
            $query->where('id', '!=', $exceptWorkflowId);
        }

        $query->where(function ($overlapQuery) use ($activities): void {
            foreach ($activities as $activity) {
                $overlapQuery->orWhereJsonContains('applies_to', $activity);
            }
        })->update(['is_active' => false]);
    }
}
