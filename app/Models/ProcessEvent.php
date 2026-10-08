<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcessEvent extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['snapshot' => 'array'];

    public function documents()
    {
        return $this->hasMany(ProcessDocument::class, 'event_id');
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function actionLabel(): string
    {
        return match ($this->action) {
            'submit' => 'Gửi yêu cầu','resubmit' => 'Gửi lại sau điều chỉnh','approve' => 'Xác nhận bước','confirm' => 'Chốt số liệu','reject' => 'Từ chối','revise' => 'Yêu cầu điều chỉnh','payment' => 'Gửi yêu cầu thanh toán',default => $this->action
        };
    }
}
