<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class FinanceRequestVisibilityService
{
    public function query(User $user, string $source, bool $ownOnly = false): Builder
    {
        return Transaction::query()->whereNotNull('request_source')->where(function (Builder $visible) use ($user, $source, $ownOnly) {
            $visible->where('submitted_by', $user->id);
            if ($user->isAdmin()) {
                if (!$ownOnly) $visible->orWhere('request_source', $source);
            } elseif ($source === 'warehouse') {
                if ($user->warehouse_id) {
                    $visible->orWhereHas('submitter', fn ($submitter) => $submitter->where('warehouse_id', $user->warehouse_id));
                }
            } elseif ($source === 'manager') {
                $blockId = $user->block_id ?: $user->department?->block_id;
                if ($blockId) {
                    $visible->orWhereHas('submitter', fn ($submitter) => $submitter->where(function ($scope) use ($blockId) {
                        $scope->where('block_id', $blockId)->orWhere(function ($fallback) use ($blockId) {
                            $fallback->whereNull('block_id')->whereHas('department', fn ($department) => $department->where('block_id', $blockId));
                        });
                    }));
                }
            } elseif (!$ownOnly) {
                $visible->orWhere('request_source', $source);
            }
        });
    }
}
