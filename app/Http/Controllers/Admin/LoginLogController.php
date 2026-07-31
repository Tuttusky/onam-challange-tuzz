<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoginLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoginLogController extends Controller
{
    public function index(Request $request): View
    {
        $logs = LoginLog::query()
            ->with('admin')
            ->when($request->filled('email'), fn ($q) => $q->where('email', 'like', '%'.$request->email.'%'))
            ->when($request->filled('success'), fn ($q) => $q->where('success', $request->boolean('success')))
            ->orderByDesc('created_at')
            ->paginate(30)
            ->withQueryString();

        return view('admin.logs.login', compact('logs'));
    }
}
