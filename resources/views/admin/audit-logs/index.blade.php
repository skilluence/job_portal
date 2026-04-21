@extends('layouts.admin')
@section('title', 'Audit Logs')
@section('module-title', 'Audit Logs')
@section('module-description', 'Track all user actions with actor details and timestamped changes.')
@section('content')

<style>
/* ── Data changes truncation + hover tooltip ─────────────── */
.audit-changes-wrap {
    position: relative;
}
.audit-changes-preview {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    max-height: 3.6em;
    cursor: default;
}
.audit-changes-wrap:hover .audit-changes-tooltip {
    display: block;
}
.audit-changes-tooltip {
    display: none;
    position: absolute;
    z-index: 1200;
    left: 0; top: calc(100% + 6px);
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-sm);
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    padding: 10px 12px;
    min-width: 260px;
    max-width: 380px;
    max-height: 260px;
    overflow-y: auto;
    white-space: normal;
    line-height: 1.65;
    font-size: 12px;
}
/* Keep tooltip on screen if near right edge */
.audit-changes-wrap:last-child .audit-changes-tooltip,
td:last-child .audit-changes-wrap .audit-changes-tooltip {
    left: auto; right: 0;
}
</style>

<div class="card">
    <div class="card-header" style="flex-wrap:wrap;gap:12px;">
        <div>
            <div class="card-title">Audit Logs</div>
            <div class="card-subtitle">{{ $logs->total() }} total entries</div>
        </div>

        <div class="d-flex gap-8" style="flex-wrap:wrap;">
            <a href="{{ route('admin.audit-logs', array_merge(request()->except('tab'), ['tab' => 'recruiters'])) }}"
                class="btn {{ $tab === 'recruiters' ? 'btn-primary' : 'btn-outline' }}">Recruiters</a>
            <a href="{{ route('admin.audit-logs', array_merge(request()->except('tab'), ['tab' => 'students'])) }}"
                class="btn {{ $tab === 'students' ? 'btn-primary' : 'btn-outline' }}">Students</a>
            <a href="{{ route('admin.audit-logs', array_merge(request()->except('tab'), ['tab' => 'all'])) }}"
                class="btn {{ $tab === 'all' ? 'btn-primary' : 'btn-outline' }}">All</a>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.audit-logs') }}" class="d-flex gap-8 mb-16" style="flex-wrap:wrap;">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <input type="text" name="search" class="form-control" placeholder="Search actor, module, description..."
            value="{{ $search }}" style="width:260px;">
        <select name="action" class="form-control" style="width:180px;" onchange="this.form.submit()">
            <option value="">All Actions</option>
            @foreach ($actionOptions as $actionOption)
                <option value="{{ $actionOption }}" @selected($action === $actionOption)>{{ ucfirst(str_replace('_', ' ', $actionOption)) }}
                </option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-outline"><i class="bi bi-search"></i></button>
        @if ($search || $action)
            <a href="{{ route('admin.audit-logs', ['tab' => $tab]) }}" class="btn btn-outline" title="Clear filters">
                <i class="bi bi-x-lg"></i>
            </a>
        @endif
    </form>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>User / Recruiter</th>
                    <th>Action</th>
                    <th>Module</th>
                    <th>Description</th>
                    <th>Data Changes</th>
                    <th>When</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($logs as $i => $log)
                    <tr>
                        <td class="text-muted text-sm">{{ $logs->firstItem() + $i }}</td>
                        <td>
                            <div class="avatar-row">
                                <div class="avatar-sm" style="width:26px;height:26px;font-size:10px;">
                                    {{ strtoupper(substr($log->actor_name ?? 'S', 0, 1)) }}
                                </div>
                                <div>
                                    <div class="avatar-name">{{ $log->actor_name ?? 'System' }}</div>
                                    <div class="avatar-sub">{{ ucfirst($log->actor_type) }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge {{ in_array($log->action, ['created', 'login', 'downloaded']) ? 'badge-success' : (in_array($log->action, ['updated', 'imported', 'exported']) ? 'badge-primary' : (in_array($log->action, ['deleted', 'delete_blocked']) ? 'badge-danger' : 'badge-warning')) }}">
                                {{ ucfirst(str_replace('_', ' ', $log->action)) }}
                            </span>
                        </td>
                        <td><span class="badge badge-info">{{ ucfirst(str_replace('_', ' ', $log->module)) }}</span></td>
                        <td style="max-width:280px;">{{ $log->description }}</td>
                        <td style="max-width:240px;">
                            @php
                                $oldVals  = is_array($log->old_values) ? $log->old_values : [];
                                $newVals  = is_array($log->new_values) ? $log->new_values : [];
                                $skipKeys = [
                                    'password', 'login_password', 'login_password_plain',
                                    'email_password', 'linkedin_password', 'remember_token',
                                    'updated_at', 'created_at', 'deleted_at',
                                ];

                                // For updates, compare keys that actually came in new_values.
                                // This avoids noisy "-> cleared" rows for untouched fields.
                                $keysSource = !empty($newVals) ? array_keys($newVals) : array_keys($oldVals);
                                $allKeys = array_values(array_diff($keysSource, $skipKeys));

                                $labelMap = [
                                    'id' => 'Record ID',
                                    'candidate_id' => 'Candidate ID',
                                    'recruiter_id' => 'Recruiter ID',
                                    'team_manager_id' => 'Team Manager ID',
                                    'created_by' => 'Created By',
                                    'full_name' => 'Full Name',
                                    'email_id' => 'Email',
                                    'phone_number' => 'Phone',
                                    'sub_domain' => 'Sub Domain',
                                    'no_of_applications' => 'Applications Target',
                                    'interviews_count' => 'Interviews Count',
                                    'interview_status' => 'Interview Status',
                                    'scheduled_date' => 'Scheduled Date',
                                    'scheduled_time' => 'Scheduled Time',
                                    'scheduled_timezone' => 'Scheduled Timezone',
                                    'marketing_email_password' => 'Marketing Email Password',
                                    'marketing_linkedin_password' => 'Marketing LinkedIn Password',
                                ];

                                $formatLabel = function (string $key) use ($labelMap): string {
                                    if (isset($labelMap[$key])) {
                                        return $labelMap[$key];
                                    }
                                    return ucwords(str_replace('_', ' ', $key));
                                };

                                $formatValue = function ($value, string $key = '') {
                                    if ($value === null) {
                                        return null;
                                    }

                                    if (is_bool($value)) {
                                        return $value ? 'Yes' : 'No';
                                    }

                                    if (is_array($value)) {
                                        if ($key === 'resumes') {
                                            $count = count($value);
                                            return $count . ' resume ' . ($count === 1 ? 'entry' : 'entries');
                                        }
                                        return Str::limit(json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 80);
                                    }

                                    $text = trim((string) $value);
                                    if ($text === '') {
                                        return null;
                                    }

                                    // Humanize common coded values
                                    if (in_array($key, ['status', 'interview_status', 'interview_type', 'actor_type', 'module', 'action', 'visa_immigration_status', 'work_auth_status'], true)) {
                                        return ucwords(str_replace('_', ' ', $text));
                                    }

                                    // Convert IDs to a cleaner display
                                    if ($key !== 'id' && str_ends_with($key, '_id') && ctype_digit($text)) {
                                        return '#' . $text;
                                    }

                                    // Handle JSON-ish strings
                                    if (($text[0] ?? '') === '{' || ($text[0] ?? '') === '[') {
                                        $decoded = json_decode($text, true);
                                        if (json_last_error() === JSON_ERROR_NONE) {
                                            if ($key === 'resumes' && is_array($decoded)) {
                                                $count = count($decoded);
                                                return $count . ' resume ' . ($count === 1 ? 'entry' : 'entries');
                                            }
                                            return Str::limit(json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 80);
                                        }
                                    }

                                    // Normalize date & datetime values
                                    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $text)) {
                                        try {
                                            return \Carbon\Carbon::parse($text)->format('M d, Y');
                                        } catch (\Throwable) {
                                        }
                                    }
                                    if (preg_match('/^\d{4}-\d{2}-\d{2}T/', $text)) {
                                        try {
                                            return \Carbon\Carbon::parse($text)->format('M d, Y');
                                        } catch (\Throwable) {
                                        }
                                    }

                                    return Str::limit($text, 80);
                                };

                                $diffs = [];
                                foreach ($allKeys as $key) {
                                    $old = array_key_exists($key, $oldVals) ? $oldVals[$key] : null;
                                    $new = array_key_exists($key, $newVals) ? $newVals[$key] : null;
                                    $oldText = $formatValue($old, $key);
                                    $newText = $formatValue($new, $key);
                                    if (($oldText ?? '') !== ($newText ?? '')) {
                                        $diffs[] = ['key' => $key, 'old' => $oldText, 'new' => $newText];
                                    }
                                }
                                $hasChanges = !empty($diffs);
                            @endphp
                            @if ($hasChanges)
                                @php
                                    // Build a shared HTML fragment used both in preview and tooltip
                                    $changeHtml = '';
                                    if (!empty($diffs)) {
                                        foreach ($diffs as $diff) {
                                            $changeHtml .= '<div style="margin-bottom:4px;">';
                                            $changeHtml .= '<span style="color:#475569;font-weight:600;font-size:11px;">' . e($formatLabel($diff['key'])) . ':</span><br>';
                                            if (!empty($diff['old']) && !empty($diff['new'])) {
                                                $changeHtml .= '<span style="color:#dc2626;background:#fef2f2;padding:1px 4px;border-radius:3px;font-size:11px;">' . e(Str::limit($diff['old'], 50)) . '</span>';
                                                $changeHtml .= '<span style="color:#94a3b8;font-size:11px;"> → </span>';
                                                $changeHtml .= '<span style="color:#16a34a;background:#f0fdf4;padding:1px 4px;border-radius:3px;font-size:11px;">' . e(Str::limit($diff['new'], 50)) . '</span>';
                                            } elseif (empty($diff['old']) && !empty($diff['new'])) {
                                                $changeHtml .= '<span style="color:#16a34a;background:#f0fdf4;padding:1px 4px;border-radius:3px;font-size:11px;">Set to ' . e(Str::limit($diff['new'], 50)) . '</span>';
                                            } elseif (!empty($diff['old']) && empty($diff['new'])) {
                                                $changeHtml .= '<span style="color:#dc2626;background:#fef2f2;padding:1px 4px;border-radius:3px;font-size:11px;">Removed (' . e(Str::limit($diff['old'], 50)) . ')</span>';
                                            } else {
                                                $changeHtml .= '<span style="color:#94a3b8;font-style:italic;font-size:11px;">No visible change</span>';
                                            }
                                            $changeHtml .= '</div>';
                                        }
                                    } else {
                                        $showVals = array_intersect_key($newVals ?: $oldVals, array_flip($allKeys));
                                        foreach ($showVals as $key => $val) {
                                            $formatted = $formatValue($val, (string) $key);
                                            if (empty($formatted)) {
                                                continue;
                                            }
                                            $changeHtml .= '<div style="margin-bottom:4px;">';
                                            $changeHtml .= '<span style="color:#475569;font-weight:600;font-size:11px;">' . e($formatLabel((string) $key)) . ':</span><br>';
                                            $changeHtml .= '<span style="color:#16a34a;background:#f0fdf4;padding:1px 4px;border-radius:3px;font-size:11px;">' . e(Str::limit($formatted, 50)) . '</span>';
                                            $changeHtml .= '</div>';
                                        }
                                    }
                                @endphp
                                <div class="audit-changes-wrap">
                                    <div class="text-sm audit-changes-preview" style="line-height:1.65;">
                                        {!! $changeHtml !!}
                                    </div>
                                    <div class="audit-changes-tooltip text-sm">
                                        {!! $changeHtml !!}
                                    </div>
                                </div>
                            @else
                                <span class="text-muted text-sm">—</span>
                            @endif
                        </td>
                        <td class="text-muted text-sm" title="{{ $log->created_at->format('Y-m-d H:i:s') }}">
                            {{ $log->created_at->format('M d, Y h:i A') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="page-empty mb-0">
                                <i class="bi bi-journal-text"></i>
                                <p>No audit entries found{{ ($search || $action) ? ' matching your filters' : '' }}.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($logs->hasPages())
        <div class="pagination-wrap">
            <span class="pagination-info">
                Showing {{ $logs->firstItem() }}-{{ $logs->lastItem() }} of {{ $logs->total() }} entries
            </span>
            {{ $logs->links('pagination.custom') }}
        </div>
    @endif
</div>

@endsection
