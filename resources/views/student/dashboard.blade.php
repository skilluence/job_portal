@extends('layouts.student')
@section('title', 'Dashboard')
@section('content')

{{-- ── Hero Banner ─────────────────────────────────────────────────────────── --}}
<div class="stp-hero">
    <div class="stp-hero-left">
        <div class="stp-hero-avatar">{{ strtoupper(substr($candidate->full_name, 0, 1)) }}</div>
        <div>
            <div class="stp-hero-greeting">Welcome back</div>
            <div class="stp-hero-name">{{ $candidate->full_name }}</div>
            <div class="stp-hero-meta">
                <span><i class="bi bi-envelope-fill"></i> {{ $candidate->email_id }}</span>
                @if ($candidate->phone_number)
                    <span><i class="bi bi-telephone-fill"></i> {{ $candidate->phone_number }}</span>
                @endif
                @if ($candidate->recruiter)
                    <span><i class="bi bi-person-badge-fill"></i> {{ $candidate->recruiter->name }}</span>
                @endif
            </div>
        </div>
    </div>
    <div class="stp-hero-right">
        <span class="stp-hero-badge {{ $candidate->status }}">
            <i class="bi bi-circle-fill" style="font-size:7px;vertical-align:middle;"></i>
            {{ ucfirst($candidate->status) }}
        </span>
    </div>
</div>

{{-- ── Stat Cards ───────────────────────────────────────────────────────────── --}}
<div class="stp-stats-row">
    <div class="stp-stat-card">
        <div class="stp-stat-icon" style="background:rgba(37,99,235,0.1);color:#2563eb;">
            <i class="bi bi-file-earmark-text-fill"></i>
        </div>
        <div class="stp-stat-body">
            <div class="stp-stat-val">{{ $candidate->no_of_applications }}</div>
            <div class="stp-stat-lbl">Daily Target</div>
        </div>
    </div>
    <div class="stp-stat-card">
        <div class="stp-stat-icon" style="background:rgba(16,185,129,0.1);color:#10b981;">
            <i class="bi bi-calendar-check-fill"></i>
        </div>
        <div class="stp-stat-body">
            <div class="stp-stat-val">{{ $candidate->interviews_count }}</div>
            <div class="stp-stat-lbl">Total Interviews</div>
        </div>
    </div>
    <div class="stp-stat-card">
        <div class="stp-stat-icon" style="background:rgba(168,85,247,0.1);color:#a855f7;">
            <i class="bi bi-bar-chart-fill"></i>
        </div>
        <div class="stp-stat-body">
            <div class="stp-stat-val">{{ $profileCompletion }}%</div>
            <div class="stp-stat-lbl">Profile Complete</div>
        </div>
    </div>
    <div class="stp-stat-card">
        <div class="stp-stat-icon" style="background:rgba(245,158,11,0.1);color:#f59e0b;">
            <i class="bi bi-cpu-fill"></i>
        </div>
        <div class="stp-stat-body">
            <div class="stp-stat-val">{{ $candidate->domain ?: '—' }}</div>
            <div class="stp-stat-lbl">Domain</div>
        </div>
    </div>
</div>

{{-- ── Flash messages ───────────────────────────────────────────────────────── --}}
@if (session('success'))
    <div class="toast-container" id="flashToast">
        <div class="toast toast-success"><i class="bi bi-check-circle-fill"></i><span>{{ session('success') }}</span></div>
    </div>
    <script>setTimeout(function(){var t=document.getElementById('flashToast');if(t)t.remove();},3500);</script>
@endif

{{-- ── 3-Tab Section ────────────────────────────────────────────────────────── --}}
<style>
.sdb-tabs       { display:flex; gap:0; border-bottom:2px solid #e2e8f0; margin-bottom:0; }
.sdb-tab        {
    padding:10px 22px; font-size:14px; font-weight:500; cursor:pointer;
    color:#64748b; border:none; background:none;
    border-bottom:2px solid transparent; margin-bottom:-2px;
    transition:color .15s, border-color .15s;
}
.sdb-tab.active { color:#2563eb; border-bottom-color:#2563eb; font-weight:600; }
.sdb-tab-body   { background:#fff; border:1px solid #e2e8f0; border-top:none; border-radius:0 0 10px 10px; padding:20px; min-height:200px; }
.sdb-panel      { display:none; }
.sdb-panel.show { display:block; }

.sdb-table      { width:100%; border-collapse:collapse; font-size:13.5px; }
.sdb-table th   {
    padding:9px 12px; text-align:left; font-weight:600; font-size:11.5px;
    text-transform:uppercase; letter-spacing:.4px; color:#64748b;
    border-bottom:1px solid #e2e8f0; background:#f8fafc;
}
.sdb-table td   { padding:10px 12px; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
.sdb-table tr:last-child td { border-bottom:none; }
.sdb-empty      { text-align:center; padding:40px 0; color:#94a3b8; }
.sdb-empty i    { font-size:32px; display:block; margin-bottom:8px; }
.sdb-empty p    { font-size:14px; margin:0; }

/* interview status inline form */
.sdb-status-form  { display:inline-flex; align-items:center; gap:6px; }
.sdb-status-sel   {
    font-size:12px; padding:3px 8px; border:1px solid #e2e8f0;
    border-radius:6px; background:#fff; color:#374151; cursor:pointer;
}
.sdb-status-btn   {
    font-size:11px; padding:3px 9px; border-radius:5px; border:none; cursor:pointer;
    background:#2563eb; color:#fff; font-weight:500;
}

/* doc row */
.sdb-doc-row    { display:flex; align-items:center; gap:12px; padding:10px 0; border-bottom:1px solid #f1f5f9; }
.sdb-doc-row:last-child { border-bottom:none; }
.sdb-doc-icon   { width:36px;height:36px;border-radius:8px;background:#eff6ff;color:#2563eb;display:flex;align-items:center;justify-content:center;font-size:17px;flex-shrink:0; }
.sdb-doc-body   { flex:1;min-width:0; }
.sdb-doc-name   { font-weight:600;font-size:13px;color:#1e293b; }
.sdb-doc-meta   { font-size:12px;color:#94a3b8;margin-top:1px; }
.sdb-doc-link   {
    font-size:12px; padding:4px 12px; border-radius:6px;
    background:#eff6ff; color:#2563eb; text-decoration:none; font-weight:500;
    display:inline-flex;align-items:center;gap:4px;
}
.sdb-doc-link:hover { background:#dbeafe; }

[data-theme="dark"] .sdb-tab-body { background:#1e293b; border-color:#334155; }
[data-theme="dark"] .sdb-tab      { color:#94a3b8; }
[data-theme="dark"] .sdb-table th { background:#0f172a; color:#94a3b8; border-color:#334155; }
[data-theme="dark"] .sdb-table td { border-color:#1e293b; }
[data-theme="dark"] .sdb-doc-icon { background:#1e3a5f; }
[data-theme="dark"] .sdb-doc-name { color:#e2e8f0; }
[data-theme="dark"] .sdb-status-sel { background:#1e293b; border-color:#334155; color:#e2e8f0; }
</style>

<div class="stp-card" style="padding:0;overflow:hidden;border-radius:10px;border:1px solid #e2e8f0;">
    <div class="sdb-tabs">
        <button class="sdb-tab active" onclick="switchSdbTab('apps', this)">
            <i class="bi bi-file-earmark-text"></i> Application &amp; Assistant
        </button>
        <button class="sdb-tab" onclick="switchSdbTab('interviews', this)">
            <i class="bi bi-calendar-check"></i> Interview
            @if ($interviews->count())
                <span style="margin-left:4px;background:#dbeafe;color:#1d4ed8;border-radius:99px;padding:1px 7px;font-size:11px;font-weight:700;">{{ $interviews->count() }}</span>
            @endif
        </button>
        <button class="sdb-tab" onclick="switchSdbTab('docs', this)">
            <i class="bi bi-folder2-open"></i> Documents
        </button>
    </div>

    <div class="sdb-tab-body">

        {{-- ── Tab 1: Application & Assistant ─────────────────────── --}}
        <div id="sdb-apps" class="sdb-panel show">
            @if ($dailyLogs->isEmpty())
                <div class="sdb-empty">
                    <i class="bi bi-journal-x"></i>
                    <p>No daily logs recorded yet.</p>
                </div>
            @else
                <div style="overflow-x:auto;">
                    <table class="sdb-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th style="text-align:right;">Applications</th>
                                <th style="text-align:right;">Assistant</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($dailyLogs as $log)
                            <tr>
                                <td style="font-weight:500;">
                                    {{ \Carbon\Carbon::parse($log->log_date)->format('M d, Y') }}
                                    <span style="font-size:11px;color:#94a3b8;margin-left:4px;">
                                        {{ \Carbon\Carbon::parse($log->log_date)->format('D') }}
                                    </span>
                                </td>
                                <td style="text-align:right;">
                                    <span style="font-size:15px;font-weight:700;color:#2563eb;">{{ number_format($log->applications) }}</span>
                                </td>
                                <td style="text-align:right;">
                                    <span style="font-size:15px;font-weight:700;color:#7c3aed;">{{ number_format($log->assistant_count) }}</span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr style="border-top:2px solid #e2e8f0;">
                                <td style="font-weight:700;padding:10px 12px;">Total</td>
                                <td style="text-align:right;padding:10px 12px;font-weight:700;color:#2563eb;">
                                    {{ number_format($dailyLogs->sum('applications')) }}
                                </td>
                                <td style="text-align:right;padding:10px 12px;font-weight:700;color:#7c3aed;">
                                    {{ number_format($dailyLogs->sum('assistant_count')) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </div>

        {{-- ── Tab 2: Interviews ───────────────────────────────────── --}}
        <div id="sdb-interviews" class="sdb-panel">
            @if ($interviews->isEmpty())
                <div class="sdb-empty">
                    <i class="bi bi-calendar-x"></i>
                    <p>No interviews scheduled yet.</p>
                </div>
            @else
                <div style="overflow-x:auto;">
                    <table class="sdb-table">
                        <thead>
                            <tr>
                                <th>Company</th>
                                <th>Role</th>
                                <th>Type</th>
                                <th>Scheduled</th>
                                <th>Status</th>
                                <th>Update</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($interviews as $iv)
                            <tr>
                                <td style="font-weight:600;">{{ $iv->company_name }}</td>
                                <td class="text-sm" style="color:#64748b;">{{ $iv->role }}</td>
                                <td>
                                    <span style="font-size:11.5px;padding:2px 8px;border-radius:99px;background:#e0f2fe;color:#0369a1;font-weight:500;text-transform:capitalize;">
                                        {{ str_replace('_', ' ', $iv->interview_type) }}
                                    </span>
                                </td>
                                <td class="text-sm" style="color:#64748b;">
                                    @if ($iv->scheduled_date)
                                        {{ \Carbon\Carbon::parse($iv->scheduled_date)->format('M d, Y') }}
                                        @if ($iv->scheduled_time)
                                            <br><span style="font-size:12px;">
                                                {{ \Carbon\Carbon::parse($iv->scheduled_time)->format('h:i A') }}
                                                {{ $iv->scheduled_timezone }}
                                            </span>
                                        @endif
                                    @else
                                        <span style="opacity:.5;">TBD</span>
                                    @endif
                                </td>
                                <td>
                                    @php $isValid = $iv->interview_status === 'valid'; @endphp
                                    <span style="font-size:11.5px;padding:2px 9px;border-radius:99px;font-weight:600;
                                        {{ $isValid ? 'background:#dcfce7;color:#166534;' : 'background:#fee2e2;color:#991b1b;' }}">
                                        {{ $isValid ? 'Valid' : 'Invalid' }}
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('student.interviews.status', $iv) }}" class="sdb-status-form">
                                        @csrf
                                        @method('PATCH')
                                        <select name="interview_status" class="sdb-status-sel">
                                            <option value="valid"   {{ $iv->interview_status === 'valid'   ? 'selected' : '' }}>Valid</option>
                                            <option value="invalid" {{ $iv->interview_status === 'invalid' ? 'selected' : '' }}>Invalid</option>
                                        </select>
                                        <button type="submit" class="sdb-status-btn">Save</button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- ── Tab 3: Documents ───────────────────────────────────── --}}
        <div id="sdb-docs" class="sdb-panel">
            @php
                $hasResumes = $resumes->count() > 0;
                $hasCv      = !empty($candidate->cv_file_path);
            @endphp

            @if (!$hasResumes && !$hasCv)
                <div class="sdb-empty">
                    <i class="bi bi-folder-x"></i>
                    <p>No documents available yet.</p>
                </div>
            @else
                {{-- Main CV file --}}
                @if ($hasCv)
                    <div class="sdb-doc-row">
                        <div class="sdb-doc-icon"><i class="bi bi-file-earmark-pdf-fill"></i></div>
                        <div class="sdb-doc-body">
                            <div class="sdb-doc-name">Curriculum Vitae (CV)</div>
                            <div class="sdb-doc-meta">Primary CV document</div>
                        </div>
                        <a href="{{ route('student.files', 'cv') }}" target="_blank" class="sdb-doc-link">
                            <i class="bi bi-box-arrow-up-right"></i> View
                        </a>
                    </div>
                @endif

                {{-- Resumes with designation --}}
                @foreach ($resumes as $resume)
                    <div class="sdb-doc-row">
                        <div class="sdb-doc-icon" style="background:#f5f3ff;color:#7c3aed;">
                            <i class="bi bi-file-earmark-person-fill"></i>
                        </div>
                        <div class="sdb-doc-body">
                            <div class="sdb-doc-name">{{ $resume->designation }}</div>
                            <div class="sdb-doc-meta">
                                {{ $resume->original_filename }} &bull;
                                {{ $resume->created_at->format('M d, Y') }}
                            </div>
                        </div>
                        <a href="{{ route('student.resumes.download', $resume) }}" target="_blank" class="sdb-doc-link">
                            <i class="bi bi-box-arrow-up-right"></i> View
                        </a>
                    </div>
                @endforeach
            @endif
        </div>

    </div>{{-- /sdb-tab-body --}}
</div>

<script>
function switchSdbTab(tab, btn) {
    document.querySelectorAll('.sdb-tab').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.sdb-panel').forEach(p => p.classList.remove('show'));
    btn.classList.add('active');
    document.getElementById('sdb-' + tab).classList.add('show');
}
</script>

@endsection
