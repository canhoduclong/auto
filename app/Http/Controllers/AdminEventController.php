<?php

namespace App\Http\Controllers;

use App\Models\AdminEvent;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminEventController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->hasRole('admin'), 403);

        $query = AdminEvent::query()
            ->with('actor')
            ->latest();

        if ($request->filled('event_type')) {
            $query->where('event_type', $request->string('event_type'));
        }

        if ($request->filled('action')) {
            $query->where('action', $request->string('action'));
        }

        if ($request->filled('q')) {
            $keyword = (string) $request->string('q');
            $query->where(function ($subQuery) use ($keyword) {
                $subQuery->where('title', 'like', '%' . $keyword . '%')
                    ->orWhere('message', 'like', '%' . $keyword . '%');
            });
        }

        $events = $query->paginate(20)->appends($request->query());

        $eventTypes = AdminEvent::query()
            ->select('event_type')
            ->distinct()
            ->orderBy('event_type')
            ->pluck('event_type');

        $actions = AdminEvent::query()
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        return view('admin.events.index', compact('events', 'eventTypes', 'actions'));
    }
}
