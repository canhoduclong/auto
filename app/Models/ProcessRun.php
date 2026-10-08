<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcessRun extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['configuration' => 'array', 'finished_at' => 'datetime'];

    public function subjects()
    {
        return $this->hasMany(ProcessSubject::class, 'run_id');
    }

    public function events()
    {
        return $this->hasMany(ProcessEvent::class, 'run_id')->orderBy('id');
    }

    public function initiator()
    {
        return $this->belongsTo(User::class, 'initiator_id');
    }

    public function claim()
    {
        return $this->hasOne(ShippingExpenseClaim::class, 'process_run_id');
    }

    public function step(): ?array
    {
        return $this->configuration['steps'][$this->current_step] ?? null;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'confirmed' => 'Đã xác nhận / Chốt số liệu','rejected' => 'Đã từ chối','revision' => $this->activity === 'shipping_expense' ? 'Shipper cần điều chỉnh' : 'Cần bổ sung hồ sơ',default => 'Chờ '.($this->step()['name'] ?? 'xử lý')
        };
    }
}
