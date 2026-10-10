<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = ActivityLog::with('user')
            ->when($request->action, fn ($q, $a) => $q->where('action', $a))
            ->when($request->user_id, fn ($q, $u) => $q->where('user_id', $u))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('activity.index', [
            'logs' => $logs,
            'actions' => ActivityLog::labels(),
            'users' => \App\Models\User::orderBy('name')->get(['id', 'name']),
        ]);
    }
}