<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $logs = ActivityLog::query()
            ->with('admin')
            ->when($request->filled('search'), fn ($q) => $q->where('action', 'like', '%'.$request->search.'%'))
            ->when($request->filled('admin_id'), fn ($q) => $q->where('admin_id', $request->admin_id))
            ->orderByDesc('created_at')
            ->paginate(30)
            ->withQueryString();

        return view('admin.logs.activity', compact('logs'));
    }
}
