<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\DatabaseBackupJob;
use App\Models\Backup;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BackupController extends Controller
{
    public function index(): View
    {
        $backups = Backup::query()
            ->with('creator')
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('admin.backups.index', compact('backups'));
    }

    public function create(): RedirectResponse
    {
        DatabaseBackupJob::dispatch(auth('admin')->id());

        return back()->with('success', 'Backup job queued successfully.');
    }
}
