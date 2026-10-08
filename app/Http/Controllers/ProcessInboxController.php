<?php

namespace App\Http\Controllers;

use App\Models\ProcessDefinition;
use App\Models\ProcessDocument;
use App\Models\ProcessRun;
use App\Services\EntityProcessService;
use App\Services\ProcessEngine;
use App\Services\ShippingExpenseService;
use App\Support\ProcessActivities;
use App\Support\TaskWorkspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProcessInboxController extends Controller
{
    public function __construct(private ProcessEngine $engine) {}

    public function index(Request $request)
    {
        $request->validate(['scope' => 'nullable|in:pending,mine,history', 'q' => 'nullable|string|max:200']);
        $user = $request->user();
        $scope = $request->input('scope', 'pending');
        $roles = $user->roles->pluck('name')->map(fn ($r) => strtolower($r))->all();
        $runs = ProcessRun::with('initiator', 'claim')->where(fn ($q) => $q->whereJsonContains('configuration->positions', 'inbox')->orWhereNull('configuration->positions'))->where(function ($q) use ($user, $scope, $roles) {
            if ($scope === 'mine') {
                $q->where('initiator_id', $user->id);
            } elseif ($scope === 'history') {
                $q->whereHas('events', fn ($e) => $e->where('actor_id', $user->id));
            } else {
                $q->where('status', 'running')->where(function ($q) use ($user, $roles) {
                    $q->where(fn ($q) => $q->where('assignee_mode', 'user')->where('assignee_user_id', $user->id))->orWhere(fn ($q) => $q->where('assignee_mode', 'user_role')->where('assignee_user_id', $user->id)->whereIn(\DB::raw('LOWER(assignee_role)'), $roles))->orWhere(fn ($q) => $q->where('assignee_mode', 'role')->whereIn(\DB::raw('LOWER(assignee_role)'), $roles));
                })->orWhere(fn ($q) => $q->where('status', 'revision')->where('initiator_id', $user->id));
            }
        })->when($scope === 'pending', fn ($q) => $q->whereIn('status', ['running', 'revision']))->when($scope === 'history', fn ($q) => $q->whereIn('status', ['confirmed', 'rejected']))->when($request->filled('q'), fn ($q) => $q->whereHas('initiator', fn ($u) => $u->where('name', 'like', '%'.$request->q.'%')))->latest()->paginate(20)->withQueryString();

        return view('processes.inbox', ['runs' => $runs, 'scope' => $scope, 'layout' => TaskWorkspace::layout($user)]);
    }

    public function show(ProcessRun $run)
    {
        abort_unless($this->engine->canView($run, auth()->user()), 403);
        if ($run->activity === 'shipping_expense') {
            return redirect()->route('shipping-expenses.show', $run->claim);
        }$run->load('initiator', 'subjects', 'events.actor', 'events.documents.uploader');

        return view('processes.entity-show', ['run' => $run, 'layout' => TaskWorkspace::layout(auth()->user()), 'canHandle' => $this->engine->canHandle($run, auth()->user())]);
    }

    private function data(Request $request): array
    {
        return $request->validate(['note' => 'nullable|string|max:5000', 'attachments' => 'nullable|array|max:10', 'attachments.*' => 'file|max:20480']);
    }

    public function action(Request $request, ProcessRun $run)
    {
        $this->data($request);
        $request->validate(['action' => 'required|in:approve,reject,revise']);
        abort_unless($this->engine->canView($run, $request->user()), 403);
        if ($run->activity === 'shipping_expense') {
            app(ShippingExpenseService::class)->act($run->claim, $request->user(), $request->action, $request->note, $request->file('attachments', []));
        } else {
            app(EntityProcessService::class)->act($run, $request->user(), $request->action, $request->note, $request->file('attachments', []));
        }

        return back()->with('success', 'Đã xử lý và thông báo cho các bên.');
    }

    public function revise(Request $request, ProcessRun $run)
    {
        $this->data($request);
        $request->validate(['note' => 'required|string|max:5000']);
        app(EntityProcessService::class)->revise($run, $request->user(), $request->note, $request->file('attachments', []));

        return back()->with('success', 'Đã gửi bổ sung về bước được cấu hình.');
    }

    public function requestForm(ProcessDefinition $definition, int $subject)
    {
        abort_unless(in_array($definition->activity, ['order_review', 'product_review']), 422);
        $type = ProcessActivities::all()[$definition->activity]['entity'];
        $class = $type === 'order' ? \App\Models\Order::class : \App\Models\Product::class;
        $entity = $class::findOrFail($subject);
        abort_unless($definition->is_active && $this->engine->hasRole(auth()->user(), $definition->configuration['initiator_role']) && ProcessActivities::canSubmit($definition->activity, $entity, auth()->user()), 403);

        return view('processes.entity-request', ['definition' => $definition, 'entity' => $entity, 'layout' => TaskWorkspace::layout(auth()->user())]);
    }

    public function submit(Request $request, ProcessDefinition $definition)
    {
        $this->data($request);
        $request->validate(['subject_id' => 'required|integer|min:1', 'note' => 'required|string|max:5000']);
        $run = app(EntityProcessService::class)->submit($definition, $request->integer('subject_id'), $request->user(), $request->note, $request->file('attachments', []));

        return redirect()->route('process-inbox.show', $run)->with('success', 'Đã gửi hồ sơ xét duyệt.');
    }

    public function document(ProcessDocument $document)
    {
        $run = ProcessRun::findOrFail($document->event->run_id);
        abort_unless($this->engine->canView($run, auth()->user()), 403);
        abort_unless(Storage::disk('local')->exists($document->path), 404);

        return Storage::disk('local')->download($document->path, $document->original_name);
    }
}
