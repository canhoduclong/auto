<?php

namespace App\Services;

use App\Models\ProcessDefinition;
use App\Models\ProcessEvent;
use App\Models\ProcessRun;
use App\Models\User;
use App\Notifications\OperatingNotification;
use Illuminate\Support\Facades\Notification;

class ProcessEngine
{
    public function hasRole(User $user, string $role): bool
    {
        return $user->roles->contains(fn ($r) => strtolower($r->name) === strtolower($role));
    }

    public function matches(array $step, User $user): bool
    {
        $mode = $step['assignment_mode'] ?? 'user_role';

        return match ($mode) {
            'user' => (int) ($step['user_id'] ?? 0) === (int) $user->id,'role' => $this->hasRole($user, $step['role'] ?? ''),default => (int) ($step['user_id'] ?? 0) === (int) $user->id && $this->hasRole($user, $step['role'] ?? '')
        };
    }

    public function isReviewer(User $user, string $role): bool
    {
        if (! $this->hasRole($user, $role)) {
            return false;
        }

        return ProcessDefinition::where('activity', 'shipping_expense')->where('is_active', true)->get()->contains(fn ($d) => collect($d->configuration['steps'])->contains(fn ($s) => ($s['role'] ?? null) === $role && $this->matches($s, $user))) || ProcessRun::where('activity', 'shipping_expense')->whereJsonContains('configuration->steps', ['role' => $role])->get()->contains(fn ($r) => collect($r->configuration['steps'])->contains(fn ($s) => ($s['role'] ?? null) === $role && $this->matches($s, $user)));
    }

    public function canHandle(ProcessRun $run, User $user): bool
    {
        return $run->status === 'running' && $run->step() && $this->matches($run->step(), $user);
    }

    public function canView(ProcessRun $run, User $user): bool
    {
        return $user->hasRole('admin') || (int) $run->initiator_id === (int) $user->id || collect($run->configuration['steps'])->contains(fn ($s) => $this->matches($s, $user));
    }

    public function actions(ProcessRun $run): array
    {
        $s = $run->step();
        $last = $run->current_step + 1 === count($run->configuration['steps']);

        return $s['actions'] ?? ['approve' => ['label' => $last ? 'Hoàn tất' : 'Xác nhận', 'note_required' => false, 'document_required' => false], 'revise' => ['label' => 'Yêu cầu bổ sung', 'note_required' => true, 'document_required' => false], 'reject' => ['label' => 'Từ chối', 'note_required' => true, 'document_required' => false]];
    }

    public function syncAssignment(ProcessRun $run): void
    {
        $s = $run->step();
        $mode = $s['assignment_mode'] ?? 'user_role';
        $run->update(['assignee_mode' => $mode, 'assignee_user_id' => $mode === 'role' ? null : ($s['user_id'] ?? null), 'assignee_role' => $mode === 'user' ? null : ($s['role'] ?? null)]);
    }

    public function start(ProcessDefinition $definition, User $user): ProcessRun
    {
        abort_unless($definition->is_active && $this->hasRole($user, $definition->configuration['initiator_role']), 422, 'Quy trình chưa kích hoạt hoặc bạn không có quyền khởi tạo.');
        foreach ($definition->configuration['steps'] as $s) {
            if (($s['assignment_mode'] ?? 'user_role') === 'role') {
                abort_unless(User::whereHas('roles', fn ($q) => $q->whereRaw('LOWER(name)=?', [strtolower($s['role'])]))->exists(), 422, 'Vai trò của bước '.$s['name'].' chưa có người thực hiện.');
            } else {
                $u = User::find($s['user_id']);
                abort_unless($u && $this->matches($s, $u), 422, 'Người xử lý '.$s['name'].' không còn phù hợp.');
            }
        }
        $run = ProcessRun::create(['definition_id' => $definition->id, 'definition_version' => $definition->version, 'activity' => $definition->activity, 'initiator_id' => $user->id, 'configuration' => $definition->configuration, 'status' => 'running', 'current_step' => 0]);
        $this->syncAssignment($run);

        return $run;
    }

    public function validateAction(ProcessRun $run, User $user, string $action, ?string $note, int $fileCount = 0): void
    {
        abort_unless($this->canHandle($run, $user), 403, 'Bạn không được giao bước hiện tại.');
        $options = $this->actions($run);
        abort_unless(isset($options[$action]), 422, 'Thao tác không được phép tại bước này.');
        $o = $options[$action];
        abort_if(($o['note_required'] ?? in_array($action, ['revise', 'reject'])) && ! trim($note ?? ''), 422, 'Thao tác này yêu cầu nhập diễn giải hoặc lý do.');
        abort_if(($o['document_required'] ?? false) && ! $fileCount, 422, 'Thao tác này yêu cầu tài liệu đính kèm.');
    }

    public function decide(ProcessRun $run, User $user, string $action): void
    {
        abort_unless($this->canHandle($run, $user), 403, 'Bạn không được giao bước hiện tại.');
        abort_unless(isset($this->actions($run)[$action]), 422, 'Thao tác không được phép tại bước này.');
        if ($action === 'revise') {
            $target = $run->step()['revision_resume'] ?? 'current';
            $run->update(['status' => 'revision', 'resume_step' => $target === 'first' ? 0 : $run->current_step]);
        } elseif ($action === 'reject') {
            $run->update(['status' => 'rejected', 'finished_at' => now()]);
        } elseif ($action === 'approve') {
            if ($run->current_step + 1 < count($run->configuration['steps'])) {
                $run->update(['current_step' => $run->current_step + 1]);
            } else {
                $run->update(['status' => 'confirmed', 'finished_at' => now()]);
            }
        } else {
            abort(422, 'Thao tác không hợp lệ.');
        }
        $this->syncAssignment($run);
    }

    public function resubmit(ProcessRun $run, User $user): void
    {
        abort_unless($run->status === 'revision' && (int) $run->initiator_id === (int) $user->id, 403);
        $run->update(['status' => 'running', 'current_step' => $run->resume_step, 'resume_step' => null]);
        $this->syncAssignment($run);
    }

    public function record(ProcessRun $run, User $user, string $action, ?string $note, array $snapshot): ProcessEvent
    {
        return ProcessEvent::create(['run_id' => $run->id, 'actor_id' => $user->id, 'action' => $action, 'note' => $note, 'snapshot' => $snapshot]);
    }

    public function notify(ProcessRun $run, string $message): void
    {
        $ids = collect($run->configuration['steps'])->pluck('user_id')->filter()->push($run->initiator_id);
        foreach ($run->configuration['steps'] as $s) {
            if (($s['assignment_mode'] ?? 'user_role') === 'role') {
                $ids = $ids->merge(User::whereHas('roles', fn ($q) => $q->whereRaw('LOWER(name)=?', [strtolower($s['role'])]))->pluck('id'));
            }
        }
        $url = route('process-inbox.show',$run);
        Notification::send(User::whereIn('id',$ids->unique())->get(),new OperatingNotification('Quy trình #'.$run->id,$message,$url));
    }
}
