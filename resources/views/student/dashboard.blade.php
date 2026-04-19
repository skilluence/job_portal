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

{{-- ── Stat Cards — 3 cards (Daily Target removed) ───────────────────────── --}}
<div class="stp-stats-row" style="grid-template-columns:repeat(3,1fr);">
    <div class="stp-stat-card">
        <div class="stp-stat-icon" style="background:rgba(16,185,129,0.1);color:#10b981;">
            <i class="bi bi-calendar-event-fill"></i>
        </div>
        <div class="stp-stat-body">
            <div class="stp-stat-val">{{ $todayInterviewsCount }}</div>
            <div class="stp-stat-lbl">Today's Interviews</div>
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
            <div class="stp-stat-val" style="font-size:16px;line-height:1.3;">{{ $candidate->domain ?: '—' }}</div>
            <div class="stp-stat-lbl">Domain</div>
        </div>
    </div>
</div>

{{-- ── Flash toast ─────────────────────────────────────────────────────────── --}}
@if (session('success'))
    <div class="toast-container" id="flashToast">
        <div class="toast toast-success"><i class="bi bi-check-circle-fill"></i><span>{{ session('success') }}</span></div>
    </div>
    <script>setTimeout(function(){var t=document.getElementById('flashToast');if(t)t.remove();},3500);</script>
@endif

{{-- ── 3-Tab Section ────────────────────────────────────────────────────────── --}}
<style>
/* ── Tab shell ─────────────────────────────────────────────── */
.sdb-tabs       { display:flex; gap:0; border-bottom:2px solid #e2e8f0; }
.sdb-tab        {
    padding:11px 22px; font-size:13.5px; font-weight:500; cursor:pointer;
    color:#64748b; border:none; background:none;
    border-bottom:2px solid transparent; margin-bottom:-2px;
    transition:color .15s, border-color .15s; display:flex; align-items:center; gap:7px;
}
.sdb-tab.active { color:#2563eb; border-bottom-color:#2563eb; font-weight:600; }
.sdb-tab-body   { background:#fff; border:1px solid #e2e8f0; border-top:none; border-radius:0 0 12px 12px; padding:22px; min-height:200px; }
.sdb-panel      { display:none; }
.sdb-panel.show { display:block; }
.sdb-tab-count  { background:#dbeafe; color:#1d4ed8; border-radius:99px; padding:1px 7px; font-size:11px; font-weight:700; }

/* ── Tables ─────────────────────────────────────────────────── */
.sdb-table      { width:100%; border-collapse:collapse; font-size:13.5px; }
.sdb-table th   {
    padding:9px 14px; text-align:left; font-weight:600; font-size:11px;
    text-transform:uppercase; letter-spacing:.5px; color:#94a3b8;
    border-bottom:2px solid #f1f5f9; background:#fafbfc;
}
.sdb-table td   { padding:12px 14px; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
.sdb-table tr:last-child td { border-bottom:none; }
.sdb-empty      { text-align:center; padding:48px 0; color:#94a3b8; }
.sdb-empty i    { font-size:36px; display:block; margin-bottom:10px; }
.sdb-empty p    { font-size:14px; margin:0; }

/* ── Interview status toggle ─────────────────────────────────── */
.iv-toggle-wrap   { display:inline-flex; border-radius:8px; overflow:hidden; border:1px solid #e2e8f0; box-shadow:0 1px 3px rgba(0,0,0,.06); }
.iv-toggle-btn    {
    padding:6px 14px; font-size:12px; font-weight:500; border:none; cursor:pointer;
    background:#f8fafc; color:#94a3b8; display:inline-flex; align-items:center; gap:5px;
    transition:background .15s, color .15s; white-space:nowrap;
}
.iv-toggle-btn.active.valid   { background:#dcfce7; color:#16a34a; font-weight:600; }
.iv-toggle-btn.active.invalid { background:#fee2e2; color:#dc2626; font-weight:600; }
.iv-toggle-btn:not(.active):hover { background:#f1f5f9; color:#475569; }
.iv-toggle-btn:disabled { opacity:.55; cursor:not-allowed; }
.iv-toggle-sep  { width:1px; background:#e2e8f0; flex-shrink:0; }

/* saving spinner */
.iv-saving-dot { display:none; width:6px; height:6px; border-radius:50%; background:currentColor; margin-left:3px; animation:ivPulse 1s infinite; }
.iv-toggle-btn.saving .iv-saving-dot { display:inline-block; }
@keyframes ivPulse { 0%,100%{opacity:1} 50%{opacity:.3} }

/* ── Interview status badge ──────────────────────────────────── */
.iv-badge        { display:inline-flex; align-items:center; gap:5px; padding:3px 10px; border-radius:99px; font-size:12px; font-weight:600; }
.iv-badge.valid  { background:#dcfce7; color:#16a34a; }
.iv-badge.invalid{ background:#fee2e2; color:#dc2626; }

/* ── Doc rows ─────────────────────────────────────────────────── */
.sdb-doc-row    { display:flex; align-items:center; gap:14px; padding:12px 0; border-bottom:1px solid #f1f5f9; }
.sdb-doc-row:last-child { border-bottom:none; }
.sdb-doc-icon   { width:40px;height:40px;border-radius:10px;background:#eff6ff;color:#2563eb;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0; }
.sdb-doc-body   { flex:1;min-width:0; }
.sdb-doc-name   { font-weight:600;font-size:13.5px;color:#1e293b; }
.sdb-doc-meta   { font-size:12px;color:#94a3b8;margin-top:2px; }
.sdb-view-btn   {
    display:inline-flex; align-items:center; gap:5px; padding:5px 14px; border-radius:7px;
    background:#eff6ff; color:#2563eb; text-decoration:none; font-size:12px; font-weight:500;
    transition:background .15s; white-space:nowrap;
}
.sdb-view-btn:hover { background:#dbeafe; }

/* Dark mode */
[data-theme="dark"] .sdb-tab-body  { background:#1e293b; border-color:#334155; }
[data-theme="dark"] .sdb-tab       { color:#94a3b8; }
[data-theme="dark"] .sdb-table th  { background:#0f172a; color:#64748b; border-color:#1e293b; }
[data-theme="dark"] .sdb-table td  { border-color:#1e293b; }
[data-theme="dark"] .sdb-doc-icon  { background:#1e3a5f; }
[data-theme="dark"] .sdb-doc-name  { color:#e2e8f0; }
[data-theme="dark"] .iv-toggle-btn { background:#1e293b; border-color:#334155; }
[data-theme="dark"] .iv-toggle-sep { background:#334155; }
</style>

<div class="stp-card" style="padding:0;overflow:hidden;border-radius:12px;border:1px solid #e2e8f0;box-shadow:0 1px 4px rgba(0,0,0,.06);">
    <div class="sdb-tabs">
        <button class="sdb-tab active" onclick="switchSdbTab('apps', this)">
            <i class="bi bi-file-earmark-text"></i> Application &amp; Assistant
        </button>
        <button class="sdb-tab" onclick="switchSdbTab('interviews', this)">
            <i class="bi bi-calendar-check"></i> Interview
            @if ($interviews->count())
                <span class="sdb-tab-count">{{ $interviews->count() }}</span>
            @endif
        </button>
        <button class="sdb-tab" onclick="switchSdbTab('docs', this)">
            <i class="bi bi-folder2-open"></i> Documents
        </button>
    </div>

    <div class="sdb-tab-body">

        {{-- ── Tab 1: Application & Assistant ──────────────────── --}}
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
                                <td>
                                    <span style="font-weight:600;">{{ \Carbon\Carbon::parse($log->log_date)->format('M d, Y') }}</span>
                                    <span style="font-size:11.5px;color:#94a3b8;margin-left:5px;">
                                        {{ \Carbon\Carbon::parse($log->log_date)->format('D') }}
                                    </span>
                                </td>
                                <td style="text-align:right;">
                                    <span style="font-size:16px;font-weight:700;color:#2563eb;">{{ number_format($log->applications) }}</span>
                                </td>
                                <td style="text-align:right;">
                                    <span style="font-size:16px;font-weight:700;color:#7c3aed;">{{ number_format($log->assistant_count) }}</span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr style="border-top:2px solid #e2e8f0;">
                                <td style="font-weight:700;padding:11px 14px;color:#374151;">Total</td>
                                <td style="text-align:right;padding:11px 14px;font-weight:700;color:#2563eb;">
                                    {{ number_format($dailyLogs->sum('applications')) }}
                                </td>
                                <td style="text-align:right;padding:11px 14px;font-weight:700;color:#7c3aed;">
                                    {{ number_format($dailyLogs->sum('assistant_count')) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </div>

        {{-- ── Tab 2: Interviews ───────────────────────────────── --}}
        <div id="sdb-interviews" class="sdb-panel">
            @if ($interviews->isEmpty())
                <div class="sdb-empty">
                    <i class="bi bi-calendar-x"></i>
                    <p>No interviews scheduled yet.</p>
                </div>
            @else
                {{-- Helper note --}}
                <div style="display:flex;align-items:center;gap:8px;padding:10px 14px;background:#eff6ff;border-radius:8px;margin-bottom:16px;font-size:12.5px;color:#1d4ed8;">
                    <i class="bi bi-info-circle-fill" style="font-size:14px;flex-shrink:0;"></i>
                    <span>Tap <strong>Valid</strong> or <strong>Invalid</strong> on any interview to update its status — changes save instantly.</span>
                </div>
                <div style="overflow-x:auto;">
                    <table class="sdb-table">
                        <thead>
                            <tr>
                                <th>Company &amp; Role</th>
                                <th>Type</th>
                                <th>Scheduled</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($interviews as $iv)
                            @php $isValid = $iv->interview_status === 'valid'; @endphp
                            <tr>
                                <td>
                                    <div style="font-weight:600;color:#1e293b;">{{ $iv->company_name }}</div>
                                    <div style="font-size:12px;color:#94a3b8;margin-top:2px;">{{ $iv->role }}</div>
                                </td>
                                <td>
                                    <span style="font-size:12px;padding:3px 10px;border-radius:99px;font-weight:500;
                                        background:#e0f2fe;color:#0369a1;text-transform:capitalize;white-space:nowrap;">
                                        {{ str_replace('_', ' ', $iv->interview_type) }}
                                    </span>
                                </td>
                                <td style="color:#64748b;font-size:13px;">
                                    @if ($iv->scheduled_date)
                                        <div style="font-weight:500;color:#374151;">{{ \Carbon\Carbon::parse($iv->scheduled_date)->format('M d, Y') }}</div>
                                        @if ($iv->scheduled_time)
                                            <div style="font-size:12px;color:#94a3b8;margin-top:2px;">
                                                {{ \Carbon\Carbon::parse($iv->scheduled_time)->format('h:i A') }}
                                                {{ $iv->scheduled_timezone }}
                                            </div>
                                        @endif
                                    @else
                                        <span style="color:#cbd5e1;">TBD</span>
                                    @endif
                                </td>
                                <td>
                                    {{-- Toggle button: no form submit, pure AJAX --}}
                                    <div class="iv-toggle-wrap"
                                        data-url="{{ route('student.interviews.status', $iv) }}"
                                        data-token="{{ csrf_token() }}">
                                        <button type="button"
                                            class="iv-toggle-btn valid {{ $isValid ? 'active' : '' }}"
                                            onclick="updateIvStatus(this,'valid')">
                                            <i class="bi bi-check-circle-fill"></i> Valid
                                            <span class="iv-saving-dot"></span>
                                        </button>
                                        <span class="iv-toggle-sep"></span>
                                        <button type="button"
                                            class="iv-toggle-btn invalid {{ !$isValid ? 'active' : '' }}"
                                            onclick="updateIvStatus(this,'invalid')">
                                            <i class="bi bi-x-circle-fill"></i> Invalid
                                            <span class="iv-saving-dot"></span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- ── Tab 3: Documents ───────────────────────────────── --}}
        <div id="sdb-docs" class="sdb-panel">
            @php
                $hasResumes = $resumes->count() > 0;
                $hasCv      = !empty($candidate->cv_file_path);
            @endphp

            @if (!$hasResumes && !$hasCv)
                <div class="sdb-empty">
                    <i class="bi bi-folder-x"></i>
                    <p>No documents available yet. Your recruiter will upload them for you.</p>
                </div>
            @else
                @if ($hasCv)
                    <div class="sdb-doc-row">
                        <div class="sdb-doc-icon"><i class="bi bi-file-earmark-pdf-fill"></i></div>
                        <div class="sdb-doc-body">
                            <div class="sdb-doc-name">Curriculum Vitae (CV)</div>
                            <div class="sdb-doc-meta">Primary CV document</div>
                        </div>
                        <a href="{{ route('student.files', 'cv') }}" target="_blank" class="sdb-view-btn">
                            <i class="bi bi-box-arrow-up-right"></i> View
                        </a>
                    </div>
                @endif

                @foreach ($resumes as $resume)
                    <div class="sdb-doc-row">
                        <div class="sdb-doc-icon" style="background:#f5f3ff;color:#7c3aed;">
                            <i class="bi bi-file-earmark-person-fill"></i>
                        </div>
                        <div class="sdb-doc-body">
                            <div class="sdb-doc-name">{{ $resume->designation }}</div>
                            <div class="sdb-doc-meta">
                                {{ $resume->original_filename }}
                                &bull; {{ $resume->created_at->format('M d, Y') }}
                            </div>
                        </div>
                        <a href="{{ route('student.resumes.download', $resume) }}" target="_blank" class="sdb-view-btn">
                            <i class="bi bi-box-arrow-up-right"></i> View
                        </a>
                    </div>
                @endforeach
            @endif
        </div>

    </div>{{-- /sdb-tab-body --}}
</div>

<script>
/* ── Tab switch ──────────────────────────────────────────────────────────── */
function switchSdbTab(tab, btn) {
    document.querySelectorAll('.sdb-tab').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.sdb-panel').forEach(p => p.classList.remove('show'));
    btn.classList.add('active');
    document.getElementById('sdb-' + tab).classList.add('show');
}

/* ── Interview status AJAX toggle ────────────────────────────────────────── */
function updateIvStatus(btn, newStatus) {
    var wrap  = btn.closest('.iv-toggle-wrap');
    var url   = wrap.dataset.url;
    var token = wrap.dataset.token;
    var btns  = wrap.querySelectorAll('.iv-toggle-btn');

    // Already the active status — nothing to do
    if (btn.classList.contains('active')) return;

    // Show saving state
    btns.forEach(b => { b.disabled = true; b.classList.add('saving'); });

    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: new URLSearchParams({
            '_method': 'PATCH',
            '_token': token,
            'interview_status': newStatus,
        }),
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            // Update active button
            btns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            showSdbToast('Interview marked as ' + newStatus, 'success');
        } else {
            showSdbToast('Could not update status. Try again.', 'error');
        }
    })
    .catch(function() {
        showSdbToast('Network error. Please try again.', 'error');
    })
    .finally(function() {
        btns.forEach(b => { b.disabled = false; b.classList.remove('saving'); });
    });
}

/* ── Small inline toast ──────────────────────────────────────────────────── */
function showSdbToast(msg, type) {
    var existing = document.getElementById('sdbToast');
    if (existing) existing.remove();

    var t = document.createElement('div');
    t.id = 'sdbToast';
    t.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:9999;display:flex;align-items:center;gap:8px;' +
        'padding:12px 18px;border-radius:10px;font-size:13px;font-weight:500;box-shadow:0 4px 16px rgba(0,0,0,.15);' +
        (type === 'success'
            ? 'background:#dcfce7;color:#15803d;border:1px solid #bbf7d0;'
            : 'background:#fee2e2;color:#dc2626;border:1px solid #fecaca;');
    t.innerHTML = '<i class="bi ' + (type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill') + '"></i>' + msg;
    document.body.appendChild(t);
    setTimeout(function() { if (t.parentNode) t.remove(); }, 2800);
}
</script>

@endsection
