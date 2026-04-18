<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogsController extends Controller
{
    public function index(Request $request)
    {
        $user      = $request->user();
        $search    = trim((string) $request->get('search'));
        $action    = $request->get('action');
        $tab       = $request->get('tab', 'recruiters');
        $isRecruiter = $user->isRecruiter();

        $logs = AuditLog::query()
            ->with('user')
            ->when($tab === 'students', fn ($q) => $q->where('actor_type', 'student'))
            ->when($tab === 'recruiters', fn ($q) => $q->whereIn('actor_type', ['staff', 'system']))
            ->when($isRecruiter, fn ($q) => $q->where('user_id', $user->id))
            ->when($search, function ($q) use ($search) {
                $like = '%' . str_replace('%', '\%', $search) . '%';
                $q->where(function ($query) use ($like) {
                    $query->where('description', 'like', $like)
                        ->orWhere('module', 'like', $like)
                        ->orWhere('actor_name', 'like', $like);
                });
            })
            ->when($action, fn ($q) => $q->where('action', $action))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $actionOptions = [
            'created', 'updated', 'deleted', 'delete_blocked',
            'login', 'logout', 'imported', 'exported',
            'downloaded', 'revealed', 'uploaded',
        ];

        return view('admin.audit-logs.index', compact('logs', 'search', 'action', 'tab', 'actionOptions'));
    }
}
