<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalStep extends Model
{
    use HasFactory;

    protected $table = 'approval_steps';

    protected $fillable = [
        'approval_flow_id',
        'step_order',
        'role_slug',
        'can_skip',
    ];

    protected $casts = [
        'can_skip' => 'boolean',
    ];

    public function workflow()
    {
        return $this->belongsTo(ApprovalWorkflow::class, 'approval_flow_id');
    }

    public function approvalOrders()
    {
        return $this->hasMany(ApprovalOrder::class, 'approval_step_id');
    }
}
