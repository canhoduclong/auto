<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class OrderHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'action',
        'user_id',
        'role',
        'status_before',
        'status_after',
        'note',
        'schedule_snapshot_hash',
        'schedule_snapshot',
        'source',
        'ip_address',
        'request_method',
        'route_name',
        'request_path',
        'user_agent',
    ];

    protected static function booted(): void
    {
        static::creating(function (OrderHistory $history): void {
            if (app()->runningInConsole()) {
                $history->source ??= 'system';
                return;
            }

            $request = request();
            $history->user_id ??= Auth::id();
            $history->source ??= $request->is('api/*')
                ? 'api'
                : ($request->is('mobile/*') ? 'mobile' : 'web');
            $history->ip_address ??= $request->ip();
            $history->request_method ??= $request->method();
            $history->route_name ??= $request->route()?->getName();
            $history->request_path ??= $request->path();
            $history->user_agent ??= $request->userAgent();
        });
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
