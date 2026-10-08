<?php

namespace App\Services;

use App\Models\Order;
use App\Models\ProcessDefinition;
use App\Models\ProcessRun;
use App\Models\Product;
use App\Models\User;
use App\Support\ProcessActivities;
use App\Support\ProcessFiles;

class EntityProcessService
{
    public function __construct(public ProcessEngine $engine) {}

    public function submit(ProcessDefinition $definition, int $id, User $user, string $note, array $attachments = []): ProcessRun
    {
        return ProcessFiles::transaction(function (ProcessFiles $files) use ($definition, $id, $user, $note, $attachments) {
            $definition = ProcessDefinition::whereKey($definition->id)->lockForUpdate()->firstOrFail();
            $activity = $definition->activity;
            abort_unless(in_array($activity, ['order_review', 'product_review']), 422, 'Hoạt động cần được khởi tạo từ màn hình nghiệp vụ.');
            $type = ProcessActivities::all()[$activity]['entity'];
            $class = $type === 'order' ? Order::class : Product::class;
            $subject = $class::whereKey($id)->lockForUpdate()->firstOrFail();
            abort_unless(ProcessActivities::canSubmit($activity, $subject, $user), 403, 'Bạn không có quyền gửi hồ sơ này.');
            abort_if(ProcessRun::where('activity', $activity)->whereIn('status', ['running', 'revision'])->whereHas('subjects', fn ($q) => $q->where('subject_type', $type)->where('subject_id', $id))->exists(), 422, 'Thực thể đã có hồ sơ đang xử lý.');
            $run = $this->engine->start($definition, $user);
            $run->update(['request_note' => $note]);
            $run->subjects()->create(['subject_type' => $type, 'subject_id' => $id]);
            $event = $this->engine->record($run, $user, 'submit', $note, $this->snapshot($run));
            $files->attach($event, $user, $attachments);
            $this->engine->notify($run, 'Có hồ sơ mới cần xử lý. '.$run->statusLabel());

            return $run;
        });
    }

    public function snapshot(ProcessRun $run): array
    {
        $subjects = $run->subjects()->get()->map(function ($link) {
            if ($link->subject_type === 'order') {
                $entity = Order::with('customer')->find($link->subject_id);

                return ['subject_type' => 'order', 'subject_id' => $link->subject_id, 'label' => $entity?->code, 'customer' => $entity?->customer?->name, 'total' => $entity?->total];
            }
            $entity = Product::find($link->subject_id);

            return ['subject_type' => 'product', 'subject_id' => $link->subject_id, 'label' => $entity?->name];
        })->all();

        return ['note' => $run->request_note, 'subjects' => $subjects];
    }

    public function act(ProcessRun $run, User $user, string $action, ?string $note, array $attachments = []): void
    {
        ProcessFiles::transaction(function (ProcessFiles $files) use ($run, $user, $action, $note, $attachments) {
            $run = ProcessRun::whereKey($run->id)->lockForUpdate()->firstOrFail();
            abort_unless(in_array($run->activity, ['order_review', 'product_review']), 422);
            $this->engine->validateAction($run, $user, $action, $note, count($attachments));
            $this->engine->decide($run, $user, $action);
            $event = $this->engine->record($run, $user, $run->status === 'confirmed' ? 'confirm' : $action, $note, $this->snapshot($run));
            $files->attach($event, $user, $attachments);
            $this->engine->notify($run, 'Hồ sơ đã được xử lý. '.$run->statusLabel());
        });
    }

    public function revise(ProcessRun $run, User $user, string $note, array $attachments = []): void
    {
        ProcessFiles::transaction(function (ProcessFiles $files) use ($run, $user, $note, $attachments) {
            $run = ProcessRun::whereKey($run->id)->lockForUpdate()->firstOrFail();
            abort_unless(in_array($run->activity, ['order_review', 'product_review']), 422);
            $this->engine->resubmit($run, $user);
            $run->update(['request_note' => $note]);
            $event = $this->engine->record($run, $user, 'resubmit', $note, $this->snapshot($run));
            $files->attach($event, $user, $attachments);
            $this->engine->notify($run, 'Hồ sơ đã bổ sung. '.$run->statusLabel());
        });
    }
}
