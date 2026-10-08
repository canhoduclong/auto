<?php

namespace App\Http\Controllers;

use App\Models\ProcessDefinition;
use App\Models\ProcessRun;
use App\Models\Role;
use App\Models\User;
use App\Services\ProcessEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProcessManagementController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);

        return view('processes.definitions', ['definitions' => ProcessDefinition::latest()->get(), 'users' => User::with('roles')->orderBy('name')->get(), 'roles' => Role::orderBy('name')->get()]);
    }

    private function validated(Request $request, ?ProcessDefinition $definition = null): array
    {
        $data = $request->validate([
            'code' => $definition ? 'sometimes|string|max:100' : ['required', 'string', 'max:100', 'regex:/^[a-zA-Z0-9_-]+$/', 'unique:process_definitions,code'],
            'activity' => $definition ? 'sometimes|string' : 'required|in:'.implode(',', array_keys(\App\Support\ProcessActivities::all())),
            'name' => 'required|string|max:255', 'is_active' => 'nullable|boolean', 'initiator_role' => 'required|string|exists:roles,name',
            'version' => 'nullable|integer|min:1', 'positions' => 'required|array|min:1', 'positions.*' => 'required|string',
            'steps' => 'required|array|min:1|max:20', 'steps.*.name' => 'required|string|max:100',
            'steps.*.assignment_mode' => 'required|in:user,role,user_role', 'steps.*.role' => 'nullable|string|exists:roles,name', 'steps.*.user_id' => 'nullable|integer|exists:users,id',
            'steps.*.revision_resume' => 'required|in:current,first', 'steps.*.actions' => 'required|array',
            'steps.*.actions.*.enabled' => 'nullable|boolean', 'steps.*.actions.*.label' => 'nullable|string|max:60',
            'steps.*.actions.*.note_required' => 'nullable|boolean', 'steps.*.actions.*.document_required' => 'nullable|boolean',
        ]);
        $activity = $definition?->activity ?? $data['activity'];
        $supported = \App\Support\ProcessActivities::all()[$activity];
        abort_unless(! array_diff($data['positions'], $supported['positions']), 422, 'Vị trí hiển thị không phù hợp hoạt động.');
        if ($activity === 'shipping_expense') {
            abort_unless(strtolower($data['initiator_role']) === 'shipper', 422, 'Chi phí ship phải được Shipper khởi tạo.');
        }
        $engine = app(ProcessEngine::class);
        $steps = [];
        foreach ($data['steps'] as $index => $step) {
            $mode = $step['assignment_mode'];
            $role = $step['role'] ?? null;
            $id = isset($step['user_id']) ? (int) $step['user_id'] : null;
            abort_unless($mode === 'user' || $role, 422, 'Bước '.($index + 1).' cần chọn vai trò.');
            abort_unless($mode === 'role' || $id, 422, 'Bước '.($index + 1).' cần chọn người thực hiện.');
            if ($mode === 'user_role') {
                abort_unless($engine->hasRole(User::findOrFail($id), $role), 422, 'Người được chọn phải có vai trò của bước '.($index + 1));
            }
            $actions = [];
            foreach (['approve', 'revise', 'reject'] as $key) {
                if (! empty($step['actions'][$key]['enabled'])) {
                    $o = $step['actions'][$key];
                    abort_unless(trim($o['label'] ?? ''), 422, 'Cần nhập nhãn cho thao tác được chọn.');
                    $actions[$key] = ['label' => $o['label'], 'note_required' => in_array($key, ['revise', 'reject']) || ! empty($o['note_required']), 'document_required' => ! empty($o['document_required'])];
                }
            }
            abort_unless(isset($actions['approve']), 422, 'Mỗi bước cần thao tác xác nhận hoặc hoàn tất.');
            $steps[] = ['name' => $step['name'], 'assignment_mode' => $mode, 'role' => $mode === 'user' ? null : $role, 'user_id' => $mode === 'role' ? null : $id, 'actions' => $actions, 'revision_resume' => $step['revision_resume']];
        }
        $data['configuration'] = ['initiator_role' => $data['initiator_role'], 'positions' => array_values(array_unique($data['positions'])), 'steps' => $steps];

        return $data;
    }

    public function create()
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);
        $definition = new ProcessDefinition(['name' => 'Quy trình mới', 'version' => 1, 'activity' => 'order_review', 'is_active' => false, 'configuration' => ['initiator_role' => 'sale', 'positions' => ['inbox', 'order_detail'], 'steps' => [['name' => 'Kiểm tra hồ sơ', 'role' => 'manager', 'user_id' => null, 'assignment_mode' => 'role']]]]);

        return view('processes.definitions', ['definitions' => collect([$definition]), 'users' => User::with('roles')->orderBy('name')->get(), 'roles' => Role::orderBy('name')->get(), 'creating' => true]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->hasRole('admin'), 403);
        $data = $this->validated($request);
        $definition = DB::transaction(function () use ($data, $request) {
            if (! empty($data['is_active'])) {
                ProcessDefinition::where('activity', $data['activity'])->where('is_active', true)->update(['is_active' => false]);
            }

return ProcessDefinition::create(['code' => $data['code'], 'name' => $data['name'], 'activity' => $data['activity'], 'configuration' => $data['configuration'], 'version' => 1, 'is_active' => (bool) ($data['is_active'] ?? false), 'updated_by' => $request->user()->id]);
        });

        return redirect()->route('process-management.index')->with('success', 'Đã tạo quy trình '.$definition->name);
    }

    public function save(Request $request, ProcessDefinition $definition)
    {
        abort_unless($request->user()->hasRole('admin'), 403);
        $data = $this->validated($request, $definition);
        DB::transaction(function () use ($definition, $data, $request) {
            $definition = ProcessDefinition::whereKey($definition->id)->lockForUpdate()->firstOrFail();
            if (isset($data['version'])) {
                abort_unless((int) $data['version'] === $definition->version, 422, 'Cấu hình đã được thay đổi. Vui lòng tải lại trang.');
            }if (! empty($data['is_active'])) {
                ProcessDefinition::where('activity', $definition->activity)->whereKeyNot($definition->id)->update(['is_active' => false]);
            }$definition->update(['name' => $data['name'], 'configuration' => $data['configuration'], 'is_active' => (bool) ($data['is_active'] ?? false), 'version' => $definition->version + 1, 'updated_by' => $request->user()->id]);
        });

        return back()->with('success', 'Đã công bố phiên bản mới. Hồ sơ đã khởi tạo giữ nguyên cấu hình.');
    }

    public function runs(Request $request)
    {
        abort_unless($request->user()->hasRole('admin'), 403);
        $request->validate(['status' => 'nullable|in:running,revision,rejected,confirmed', 'q' => 'nullable|string|max:200']);
        $runs = ProcessRun::with('initiator', 'claim')->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))->when($request->filled('q'), fn ($q) => $q->whereHas('initiator', fn ($s) => $s->where('name', 'like', '%'.$request->q.'%')))->latest()->paginate(20)->withQueryString();

        return view('processes.runs',compact('runs'));
    }
}
