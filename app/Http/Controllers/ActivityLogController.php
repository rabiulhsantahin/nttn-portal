<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::with('user.role');

        if ($request->filled('entity_type')) {
            $query->where('entity_type', $request->entity_type);
        }

        if ($request->filled('action')) {
            $query->where('action', 'like', "%{$request->action}%");
        }

        $logs = $query->latest('created_at')->paginate(25);

        return view('activity_logs.index', compact('logs'));
    }
}
