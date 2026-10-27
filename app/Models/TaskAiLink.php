<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class TaskAiLink extends Model
{
    protected $fillable = ['task_assignee_id', 'task_id', 'user_id', 'token_hash', 'expires_at', 'revoked_at', 'last_used_at'];
    protected $hidden = ['token_hash'];
    protected $casts = ['expires_at' => 'datetime', 'revoked_at' => 'datetime', 'last_used_at' => 'datetime'];
    public function assignee() { return $this->belongsTo(TaskAssignee::class, 'task_assignee_id'); }
}
