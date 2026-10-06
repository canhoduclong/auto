<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class OperatingProposal extends Model {
    protected $guarded = ['id'];
    protected $casts = ['closes_at'=>'datetime','closed_at'=>'datetime'];
    public function statusLabel(): string {
        return $this->status==='open' && $this->closes_at->isPast() ? 'Đã hết hạn — chờ chốt'
            : (['open'=>'Đang mở','approved'=>'Thông qua','rejected'=>'Không thông qua','no_quorum'=>'Không đủ số phiếu'][$this->status] ?? $this->status);
    }
    public function creator(){return $this->belongsTo(User::class,'created_by');}
    public function votes(){return $this->hasMany(OperatingVote::class,'proposal_id');}
    public function tasks(){return $this->hasMany(TaskAssignment::class,'proposal_id');}
}
