@extends('layouts.student')
@section('title', 'Dashboard')
@section('content')

{{-- ── Hero Banner ─────────────────────────────────────────────────────────── --}}
<div class="stp-hero">
    <div class="stp-hero-left">
        <div class="stp-hero-avatar">{{ $candidate->initials }}</div>
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
<div class="stp-stats-row">
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
.sdb-tabs {
    display: flex; gap: 0;
    border-bottom: 2px solid #e2e8f0;
    background: #f8fafc;
    border-radius: 12px 12px 0 0;
    overflow-x: auto; -webkit-overflow-scrolling: touch; scrollbar-width: none;
}
.sdb-tabs::-webkit-scrollbar { display: none; }
.sdb-tab {
    flex-shrink: 0;
    padding: 13px 22px; font-size: 13.5px; font-weight: 500; cursor: pointer;
    color: #64748b; border: none; background: none;
    border-bottom: 3px solid transparent; margin-bottom: -2px;
    transition: color .18s, border-color .18s, background .18s;
    display: flex; align-items: center; gap: 7px;
    white-space: nowrap;
}
.sdb-tab:hover:not(.active) { color: #334155; background: rgba(37,99,235,.04); }
.sdb-tab.active {
    color: #2563eb; border-bottom-color: #2563eb; font-weight: 700;
    background: #fff;
}
.sdb-tab-body {
    background: #fff; border: 1px solid #e2e8f0; border-top: none;
    border-radius: 0 0 12px 12px; padding: 24px;
    min-height: 200px;
}
.sdb-panel      { display: none; }
.sdb-panel.show { display: block; }
.sdb-tab-count  {
    background: #2563eb; color: #fff;
    border-radius: 99px; padding: 1px 8px; font-size: 10.5px; font-weight: 700;
    min-width: 18px; text-align: center;
}
.sdb-tab.active .sdb-tab-count { background: #1d4ed8; }

/* ── Inline info banner ─────────────────────────────────────── */
.sdb-info-banner {
    display: flex; align-items: flex-start; gap: 10px;
    padding: 11px 14px; background: #eff6ff; border: 1px solid #bfdbfe;
    border-radius: 8px; margin-bottom: 18px; font-size: 12.5px; color: #1d4ed8;
    line-height: 1.5;
}
.sdb-info-banner i { font-size: 14px; flex-shrink: 0; margin-top: 1px; }

/* ── Application log table ──────────────────────────────────── */
.sdb-table      { width: 100%; border-collapse: collapse; font-size: 13.5px; }
.sdb-table thead th {
    padding: 10px 16px; text-align: left; font-weight: 600; font-size: 11px;
    text-transform: uppercase; letter-spacing: .6px; color: #94a3b8;
    border-bottom: 2px solid #f1f5f9; background: #fafbfc;
}
.sdb-table tbody td { padding: 13px 16px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
.sdb-table tbody tr:last-child td { border-bottom: none; }
.sdb-table tfoot td { padding: 12px 16px; background: #f8fafc; font-weight: 700; }
.sdb-table tbody tr:hover td { background: #fafbfc; }
.sdb-empty      { text-align: center; padding: 52px 0; color: #94a3b8; }
.sdb-empty i    { font-size: 40px; display: block; margin-bottom: 12px; opacity: .6; }
.sdb-empty p    { font-size: 14px; margin: 0; }

/* ── Interview cards (desktop table view) ───────────────────── */
.sdb-iv-table   { width: 100%; border-collapse: collapse; font-size: 13.5px; }
.sdb-iv-table th {
    padding: 10px 16px; text-align: left; font-weight: 600; font-size: 11px;
    text-transform: uppercase; letter-spacing: .6px; color: #94a3b8;
    border-bottom: 2px solid #f1f5f9; background: #fafbfc;
}
.sdb-iv-table td { padding: 14px 16px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
.sdb-iv-table tr:last-child td { border-bottom: none; }

/* ── Company/role in desktop table ─────────────────────────── */
.iv-company { font-weight: 600; color: #1e293b; font-size: 13.5px; }
.iv-role    { font-size: 12px; color: #94a3b8; margin-top: 3px; }
[data-theme="dark"] .iv-company { color: #e2e8f0; }

/* ── Desktop table hover (exclude mobile gradient header) ───── */
@media (min-width: 641px) {
    .sdb-iv-table tbody tr:hover td { background: #fafbfc; }
    [data-theme="dark"] .sdb-iv-table tbody tr:hover td { background: #0f172a; }
}

/* ── Type badge ─────────────────────────────────────────────── */
.sdb-type-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 10px; border-radius: 99px; font-size: 12px; font-weight: 600;
    background: #e0f2fe; color: #0369a1; text-transform: capitalize; white-space: nowrap;
}
.sdb-type-badge.on_site   { background: #d1fae5; color: #065f46; }
.sdb-type-badge.virtual   { background: #e0f2fe; color: #0369a1; }
.sdb-type-badge.phone     { background: #fef9c3; color: #854d0e; }

/* ── Interview status toggle ─────────────────────────────────── */
.iv-toggle-wrap {
    display: inline-flex; border-radius: 8px; overflow: hidden;
    border: 1.5px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,.06);
}
.iv-toggle-btn {
    padding: 7px 16px; font-size: 12.5px; font-weight: 500; border: none; cursor: pointer;
    background: #f8fafc; color: #94a3b8;
    display: inline-flex; align-items: center; gap: 5px;
    transition: background .15s, color .15s; white-space: nowrap;
}
.iv-toggle-btn.active.valid   { background: #dcfce7; color: #15803d; font-weight: 700; }
.iv-toggle-btn.active.invalid { background: #fee2e2; color: #dc2626; font-weight: 700; }
.iv-toggle-btn:not(.active):hover { background: #f1f5f9; color: #475569; }
.iv-toggle-btn:disabled { opacity: .5; cursor: not-allowed; }
.iv-toggle-sep  { width: 1px; background: #e2e8f0; flex-shrink: 0; }
.iv-status-hint { margin-top: 7px; font-size: 11.5px; color: #94a3b8; line-height: 1.35; }

/* saving spinner */
.iv-saving-dot { display: none; width: 6px; height: 6px; border-radius: 50%; background: currentColor; margin-left: 2px; animation: ivPulse 1s infinite; }
.iv-toggle-btn.saving .iv-saving-dot { display: inline-block; }
@keyframes ivPulse { 0%,100%{opacity:1} 50%{opacity:.3} }

/* ── Doc rows ─────────────────────────────────────────────────── */
.sdb-doc-row    { display: flex; align-items: center; gap: 14px; padding: 14px 0; border-bottom: 1px solid #f1f5f9; }
.sdb-doc-row:last-child { border-bottom: none; }
.sdb-doc-icon   { width: 42px; height: 42px; border-radius: 10px; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; }
.sdb-doc-body   { flex: 1; min-width: 0; overflow: hidden; }
.sdb-doc-name   { font-weight: 600; font-size: 13.5px; color: #1e293b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.sdb-doc-meta   { font-size: 12px; color: #94a3b8; margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.sdb-view-btn   {
    display: inline-flex; align-items: center; gap: 5px; padding: 6px 14px; border-radius: 8px;
    background: #eff6ff; color: #2563eb; text-decoration: none; font-size: 12px; font-weight: 600;
    border: 1px solid #bfdbfe; transition: background .15s, border-color .15s; white-space: nowrap;
}
.sdb-view-btn:hover { background: #dbeafe; border-color: #93c5fd; }

/* ── Dark mode ─────────────────────────────────────────────── */
[data-theme="dark"] .sdb-tabs            { background: #0f172a; border-color: #1e293b; }
[data-theme="dark"] .sdb-tab             { color: #94a3b8; }
[data-theme="dark"] .sdb-tab.active      { background: #1e293b; color: #60a5fa; border-bottom-color: #3b82f6; }
[data-theme="dark"] .sdb-tab:hover:not(.active) { background: rgba(255,255,255,.04); }
[data-theme="dark"] .sdb-tab-body        { background: #1e293b; border-color: #334155; }
[data-theme="dark"] .sdb-table thead th  { background: #0f172a; color: #64748b; border-color: #1e293b; }
[data-theme="dark"] .sdb-table tbody td  { border-color: #1e293b; }
[data-theme="dark"] .sdb-table tfoot td  { background: #0f172a; }
[data-theme="dark"] .sdb-table tbody tr:hover td { background: #0f172a; }
[data-theme="dark"] .sdb-iv-table th     { background: #0f172a; color: #64748b; border-color: #1e293b; }
[data-theme="dark"] .sdb-iv-table td     { border-color: #1e293b; }
[data-theme="dark"] .sdb-doc-icon        { background: #1e3a5f; }
[data-theme="dark"] .sdb-doc-name        { color: #e2e8f0; }
[data-theme="dark"] .sdb-doc-row         { border-color: #1e293b; }
[data-theme="dark"] .iv-toggle-btn       { background: #1e293b; color: #64748b; border-color: #334155; }
[data-theme="dark"] .iv-toggle-sep       { background: #334155; }
[data-theme="dark"] .sdb-info-banner     { background: #1e3a5f; border-color: #1d4ed8; color: #93c5fd; }

/* ── Mobile: interview cards ────────────────────────────────── */
@media (max-width: 640px) {
    .sdb-iv-table thead { display: none; }
    .sdb-iv-table tbody { display: flex; flex-direction: column; gap: 14px; }
    .sdb-iv-table tbody tr {
        display: grid;
        grid-template-columns: 1fr 1fr;
        grid-template-rows: auto auto auto;
        border: 1px solid #e2e8f0; border-radius: 14px;
        overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.06);
        background: #fff;
    }
    /* Company & Role → full-width header */
    .sdb-iv-table td:nth-child(1) {
        grid-column: 1 / -1;
        background: linear-gradient(135deg, #1e3a6e 0%, #2563eb 100%);
        padding: 12px 16px; border: none;
    }
    .sdb-iv-table td:nth-child(1) .iv-company { color: #fff; font-weight: 700; font-size: 14.5px; }
    .sdb-iv-table td:nth-child(1) .iv-role    { color: rgba(255,255,255,.75); font-size: 12px; margin-top: 2px; }
    /* Type → left cell */
    .sdb-iv-table td:nth-child(2) {
        grid-column: 1; padding: 12px 14px; border: none;
        border-bottom: 1px solid #f1f5f9;
    }
    /* Scheduled → right cell */
    .sdb-iv-table td:nth-child(3) {
        grid-column: 2; padding: 12px 14px; border: none;
        border-left: 1px solid #f1f5f9; border-bottom: 1px solid #f1f5f9;
    }
    /* Status → full-width footer */
    .sdb-iv-table td:nth-child(4) {
        grid-column: 1 / -1; padding: 12px 14px; border: none;
        background: #f8fafc;
    }
    .sdb-iv-table td:nth-child(2)::before,
    .sdb-iv-table td:nth-child(3)::before,
    .sdb-iv-table td:nth-child(4)::before {
        content: attr(data-label);
        display: block; font-size: 10px; font-weight: 700;
        color: #94a3b8; text-transform: uppercase; letter-spacing: .6px;
        margin-bottom: 5px;
    }
    /* Full-width toggle on mobile */
    .sdb-iv-table .iv-toggle-wrap { display: flex; width: 100%; }
    .sdb-iv-table .iv-toggle-btn  { flex: 1; justify-content: center; padding: 9px 8px; font-size: 13px; }
    .sdb-iv-table tbody tr.is-schedule-editing {
        grid-template-columns: 1fr;
    }
    .sdb-iv-table tbody tr.is-schedule-editing td:nth-child(2),
    .sdb-iv-table tbody tr.is-schedule-editing td:nth-child(3),
    .sdb-iv-table tbody tr.is-schedule-editing td:nth-child(4) {
        grid-column: 1 / -1;
        border-left: none;
    }
    .sdb-iv-table tbody tr.is-schedule-editing td:nth-child(3) {
        background: #fff;
    }
    .sdb-iv-table tbody tr.is-schedule-editing .iv-sched-form-stack {
        max-width: none;
    }
    /* Dark mode cards */
    [data-theme="dark"] .sdb-iv-table tbody tr { background: #1e293b; border-color: #334155; }
    [data-theme="dark"] .sdb-iv-table td:nth-child(2),
    [data-theme="dark"] .sdb-iv-table td:nth-child(3) { border-color: #334155; }
    [data-theme="dark"] .sdb-iv-table td:nth-child(4) { background: #0f172a; }
    [data-theme="dark"] .sdb-iv-table tbody tr.is-schedule-editing td:nth-child(3) { background: #1e293b; }
}

/* ── Schedule edit buttons ──────────────────────────────────── */
.iv-sched-set-btn, .iv-sched-edit-btn {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 5px 11px; border-radius: 7px; font-size: 12px; font-weight: 600;
    border: 1.5px solid; cursor: pointer; transition: background .15s, border-color .15s;
    white-space: nowrap; margin-top: 5px;
}
.iv-sched-set-btn  { background: #eff6ff; color: #2563eb; border-color: #bfdbfe; }
.iv-sched-set-btn:hover  { background: #dbeafe; border-color: #93c5fd; }
.iv-sched-edit-btn { background: #f8fafc; color: #475569; border-color: #e2e8f0; }
.iv-sched-edit-btn:hover { background: #f1f5f9; border-color: #cbd5e1; }
.iv-sched-save-btn {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 5px 12px; border-radius: 7px; font-size: 12px; font-weight: 600;
    background: #dcfce7; color: #15803d; border: 1.5px solid #bbf7d0;
    cursor: pointer; transition: background .15s; white-space: nowrap;
}
.iv-sched-save-btn:hover    { background: #bbf7d0; }
.iv-sched-save-btn:disabled { opacity: .5; cursor: not-allowed; }
.iv-sched-cancel-btn {
    display: inline-flex; align-items: center;
    padding: 5px 12px; border-radius: 7px; font-size: 12px; font-weight: 600;
    background: #f1f5f9; color: #64748b; border: 1.5px solid #e2e8f0;
    cursor: pointer; transition: background .15s; white-space: nowrap;
}
.iv-sched-cancel-btn:hover { background: #e2e8f0; }
.iv-sched-form-stack {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    gap: 8px;
    width: 100%;
    min-width: 0;
    max-width: 320px;
}
.iv-sched-field label {
    display: block;
    margin-bottom: 4px;
    font-size: 10.5px;
    font-weight: 700;
    letter-spacing: .5px;
    color: #94a3b8;
    text-transform: uppercase;
}
.iv-sched-field .stp-input {
    width: 100%;
    height: 36px;
    padding: 7px 10px;
    font-size: 12.5px;
}
.iv-sched-actions { display: flex; gap: 7px; flex-wrap: wrap; }
.iv-sched-field .flatpickr-input[readonly],
.iv-sched-field .flatpickr-input + input {
    width: 100%;
}
.iv-sched-locked {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    margin-top: 6px;
    color: #94a3b8;
    font-size: 11.5px;
    font-weight: 600;
}
[data-theme="dark"] .iv-sched-set-btn    { background: #1e3a5f; color: #93c5fd; border-color: #1d4ed8; }
[data-theme="dark"] .iv-sched-set-btn:hover  { background: #1d4ed8; }
[data-theme="dark"] .iv-sched-edit-btn   { background: #1e293b; color: #94a3b8; border-color: #334155; }
[data-theme="dark"] .iv-sched-save-btn   { background: #14532d; color: #86efac; border-color: #166534; }
[data-theme="dark"] .iv-sched-cancel-btn { background: #1e293b; color: #94a3b8; border-color: #334155; }
[data-theme="dark"] .iv-sched-locked,
[data-theme="dark"] .iv-status-hint { color: #64748b; }

/* ── Mobile: app log → card rows ────────────────────────────── */
@media (max-width: 540px) {
    .sdb-table thead { display: none; }
    .sdb-table tbody tr {
        display: flex; flex-direction: row; flex-wrap: wrap; align-items: center;
        gap: 0; padding: 0;
        border: 1px solid #e2e8f0; border-radius: 12px; margin-bottom: 10px;
        overflow: hidden;
    }
    .sdb-table tbody tr:last-child { margin-bottom: 0; }
    .sdb-table td { border-bottom: none !important; padding: 12px 14px; }
    /* Date cell — full width */
    .sdb-table td:nth-child(1) { width: 100%; border-bottom: 1px solid #f1f5f9 !important; padding-bottom: 10px; }
    /* Applications + Assessment — half each */
    .sdb-table td:nth-child(2), .sdb-table td:nth-child(3) { width: 50%; }
    .sdb-table td:nth-child(2), .sdb-table td:nth-child(3) { text-align: left !important; }
    .sdb-table td:nth-child(2)::before { content: "Applications"; display: block; font-size: 10px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 4px; }
    .sdb-table td:nth-child(3)::before { content: "Assessment"; display: block; font-size: 10px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 4px; }
    .sdb-table td:nth-child(2) span, .sdb-table td:nth-child(3) span { font-size: 20px; }
    .sdb-table tfoot { display: block; }
    .sdb-table tfoot tr {
        display: flex; flex-direction: row; flex-wrap: wrap;
        border: 2px solid #2563eb; border-radius: 12px; margin-top: 6px; overflow: hidden; background: #eff6ff;
    }
    .sdb-table tfoot td { padding: 10px 14px; }
    .sdb-table tfoot td:nth-child(1) { width: 100%; border-bottom: 1px solid #bfdbfe !important; padding-bottom: 8px; color: #1d4ed8; }
    .sdb-table tfoot td:nth-child(2), .sdb-table tfoot td:nth-child(3) { width: 50%; background: transparent; }
    [data-theme="dark"] .sdb-table tbody tr { background: #1e293b; border-color: #334155; }
    [data-theme="dark"] .sdb-table td:nth-child(1) { border-color: #334155 !important; }
    [data-theme="dark"] .sdb-table tfoot tr { background: #1e3a5f; border-color: #3b82f6; }
    [data-theme="dark"] .sdb-table tfoot td:nth-child(1) { border-color: #1d4ed8 !important; color: #93c5fd; }
}
</style>

<div id="sdb-section" class="stp-card" style="padding:0;overflow:hidden;border-radius:12px;border:1px solid #e2e8f0;box-shadow:0 2px 8px rgba(37,99,235,.07);">
    <div class="sdb-tabs">
        <button class="sdb-tab active" data-sdb-tab="apps" onclick="switchSdbTab('apps', this)">
            <i class="bi bi-file-earmark-text-fill"></i> Application &amp; Assessment
        </button>
        <button class="sdb-tab" data-sdb-tab="interviews" onclick="switchSdbTab('interviews', this)">
            <i class="bi bi-calendar-check-fill"></i> Interviews
            @if ($interviews->count())
                <span class="sdb-tab-count">{{ $interviews->count() }}</span>
            @endif
        </button>
        <button class="sdb-tab" data-sdb-tab="docs" onclick="switchSdbTab('docs', this)">
            <i class="bi bi-folder2-open"></i> Documents
        </button>
    </div>

    <div class="sdb-tab-body">

        {{-- ── Tab 1: Application & Assessment ──────────────────── --}}
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
                                <th style="text-align:right;">Assessment</th>
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
                    <p>No interviews added yet.</p>
                </div>
            @else
                <div class="sdb-info-banner">
                    <i class="bi bi-info-circle-fill"></i>
                    <span>
                        Set the interview schedule before the scheduled time starts.
                        After the scheduled time passes, the schedule locks and <strong>Valid</strong> or <strong>Invalid</strong> can be submitted one time only.
                        The table shows the exact date, time, and timezone you selected.
                    </span>
                </div>
                <div style="overflow-x:auto;">
                    <table class="sdb-iv-table">
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
                            @php
                                $ivStatus = $iv->interview_status;
                                $typeSlug = str_replace(['_', ' '], '_', strtolower($iv->interview_type ?? ''));
                                $scheduleLocked = !($iv->can_update_schedule ?? true);
                                $statusDisabled = !($iv->can_update_status ?? false);
                                $statusHint = $iv->status_lock_reason ?? '';
                                $rawScheduledTime = $iv->scheduled_time ? substr((string) $iv->scheduled_time, 0, 5) : '';
                            @endphp
                            <tr id="iv-row-{{ $iv->id }}"
                                data-scheduled-at-ms="{{ $iv->scheduled_at_ms }}"
                                data-scheduled-date="{{ $iv->scheduled_date?->format('Y-m-d') }}"
                                data-scheduled-time="{{ $rawScheduledTime }}"
                                data-scheduled-timezone="{{ $iv->scheduled_timezone ?? '' }}"
                                data-status-submitted="{{ $ivStatus ? '1' : '0' }}">
                                <td>
                                    <div class="iv-company">{{ $iv->company_name }}</div>
                                    <div class="iv-role">{{ $iv->role }}</div>
                                </td>
                                <td data-label="Type">
                                    <span class="sdb-type-badge {{ $typeSlug }}">
                                        {{ ucwords(str_replace('_', ' ', $iv->interview_type)) }}
                                    </span>
                                </td>
                                <td data-label="Scheduled">
                                    {{-- Display mode --}}
                                    <div id="iv-sched-display-{{ $iv->id }}">
                                        @if ($iv->candidate_display_date)
                                            <div style="font-weight:600;color:#374151;font-size:13px;" id="iv-date-text-{{ $iv->id }}">
                                                {{ $iv->candidate_display_date }}
                                            </div>
                                            @if ($iv->candidate_display_time)
                                                <div style="font-size:12px;color:#94a3b8;margin-top:1px;" id="iv-time-text-{{ $iv->id }}">
                                                    {{ $iv->candidate_display_time }}
                                                    {{ $iv->candidate_display_timezone }}
                                                </div>
                                            @else
                                                <div style="font-size:12px;color:#94a3b8;margin-top:1px;" id="iv-time-text-{{ $iv->id }}"></div>
                                            @endif
                                            @if ($scheduleLocked)
                                                <div class="iv-sched-locked">
                                                    <i class="bi bi-lock-fill"></i> Schedule locked
                                                </div>
                                            @else
                                                <button type="button" class="iv-sched-edit-btn"
                                                    onclick="openSchedEdit({{ $iv->id }},'{{ $iv->scheduled_date->format('Y-m-d') }}','{{ $rawScheduledTime }}','{{ $iv->scheduled_timezone ?? '' }}')">
                                                    <i class="bi bi-pencil-fill"></i> Edit
                                                </button>
                                            @endif
                                        @else
                                            <div id="iv-date-text-{{ $iv->id }}" style="display:none;font-weight:600;color:#374151;font-size:13px;"></div>
                                            <div id="iv-time-text-{{ $iv->id }}" style="display:none;font-size:12px;color:#94a3b8;margin-top:1px;"></div>
                                            @if ($scheduleLocked)
                                                <div class="iv-sched-locked">
                                                    <i class="bi bi-lock-fill"></i> Schedule locked
                                                </div>
                                            @else
                                                <button type="button" class="iv-sched-set-btn"
                                                    onclick="openSchedEdit({{ $iv->id }},'','','')">
                                                    <i class="bi bi-calendar-plus"></i> Set Schedule
                                                </button>
                                            @endif
                                        @endif
                                    </div>
                                    {{-- Inline edit form (hidden by default) --}}
                                    <div id="iv-sched-form-{{ $iv->id }}" style="display:none;margin-top:6px;">
                                        <div class="iv-sched-form-stack">
                                            <div class="iv-sched-field">
                                                <label for="iv-date-input-{{ $iv->id }}">Date</label>
                                                <input type="text" id="iv-date-input-{{ $iv->id }}"
                                                    class="stp-input js-iv-date"
                                                    value="{{ $iv->scheduled_date?->format('Y-m-d') }}"
                                                    min="{{ \Carbon\Carbon::now($candidateTimezone)->format('Y-m-d') }}">
                                            </div>
                                            <div class="iv-sched-field">
                                                <label for="iv-time-input-{{ $iv->id }}">Time</label>
                                                <input type="text" id="iv-time-input-{{ $iv->id }}"
                                                    class="stp-input js-iv-time"
                                                    value="{{ $rawScheduledTime }}">
                                            </div>
                                            <div class="iv-sched-field">
                                                <label for="iv-tz-input-{{ $iv->id }}">Timezone</label>
                                                <select id="iv-tz-input-{{ $iv->id }}" class="stp-input js-iv-tz">
                                                    <option value="">Select timezone</option>
                                                    @foreach (['EST','CST','MST','PST','AKST','HST','EDT','CDT','MDT','PDT'] as $tz)
                                                        <option value="{{ $tz }}" @selected(($iv->scheduled_timezone ?? '') === $tz)>{{ $tz }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="iv-sched-actions">
                                                <button type="button" class="iv-sched-save-btn"
                                                    onclick="saveSchedEdit({{ $iv->id }},'{{ route('student.interviews.schedule', $iv) }}','{{ csrf_token() }}')">
                                                    <i class="bi bi-check-lg"></i> Save
                                                </button>
                                                <button type="button" class="iv-sched-cancel-btn"
                                                    onclick="closeSchedEdit({{ $iv->id }})">
                                                    Cancel
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td data-label="Status">
                                    <div class="iv-toggle-wrap"
                                        data-url="{{ route('student.interviews.status', $iv) }}"
                                        data-token="{{ csrf_token() }}"
                                        data-locked="{{ $statusDisabled ? '1' : '0' }}">
                                        <button type="button"
                                            class="iv-toggle-btn valid {{ $ivStatus === 'valid' ? 'active' : '' }}"
                                            onclick="updateIvStatus(this,'valid')"
                                            @disabled($statusDisabled)>
                                            <svg width="13" height="13" fill="currentColor" viewBox="0 0 16 16" style="flex-shrink:0;"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/></svg>
                                            Valid
                                            <span class="iv-saving-dot"></span>
                                        </button>
                                        <span class="iv-toggle-sep"></span>
                                        <button type="button"
                                            class="iv-toggle-btn invalid {{ $ivStatus === 'invalid' ? 'active' : '' }}"
                                            onclick="updateIvStatus(this,'invalid')"
                                            @disabled($statusDisabled)>
                                            <svg width="13" height="13" fill="currentColor" viewBox="0 0 16 16" style="flex-shrink:0;"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM5.354 4.646a.5.5 0 1 0-.708.708L7.293 8l-2.647 2.646a.5.5 0 0 0 .708.708L8 8.707l2.646 2.647a.5.5 0 0 0 .708-.708L8.707 8l2.647-2.646a.5.5 0 0 0-.708-.708L8 7.293 5.354 4.646z"/></svg>
                                            Invalid
                                            <span class="iv-saving-dot"></span>
                                        </button>
                                    </div>
                                    @if ($statusHint)
                                        <div class="iv-status-hint">{{ $statusHint }}</div>
                                    @endif
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
    localStorage.setItem('studentDashboardTab', tab);
    if (history.replaceState) {
        history.replaceState(null, '', '#tab=' + tab);
    }

    // Scroll tab button into view (horizontal scroll within tab bar)
    btn.scrollIntoView({ inline: 'nearest', block: 'nearest', behavior: 'smooth' });

    // On mobile: scroll page so the tab content is visible
    var section = document.getElementById('sdb-section');
    if (section && window.innerWidth < 768) {
        setTimeout(function () {
            var top = section.getBoundingClientRect().top + window.pageYOffset - 12;
            window.scrollTo({ top: top, behavior: 'smooth' });
        }, 50);
    }
}

function initSdbTabs() {
    var hashMatch = window.location.hash.match(/tab=([a-z]+)/);
    var savedTab = hashMatch ? hashMatch[1] : localStorage.getItem('studentDashboardTab');
    if (!['apps', 'interviews', 'docs'].includes(savedTab)) return;
    var btn = document.querySelector('.sdb-tab[data-sdb-tab="' + savedTab + '"]');
    if (btn) switchSdbTab(savedTab, btn);
}

function initInterviewPickers(scope) {
    var root = scope || document;
    if (typeof flatpickr !== 'undefined') {
        root.querySelectorAll('.js-iv-date').forEach(function (input) {
            if (input._flatpickr) return;
            flatpickr(input, {
                dateFormat: 'd-m-Y',
                minDate: 'today',
                allowInput: false
            });
            if (input.value && /^\d{4}-\d{2}-\d{2}$/.test(input.value)) {
                input._flatpickr.setDate(input.value, false, 'Y-m-d');
            }
        });
        root.querySelectorAll('.js-iv-time').forEach(function (input) {
            if (input._flatpickr) return;
            flatpickr(input, {
                enableTime: true,
                noCalendar: true,
                dateFormat: 'h:i K',
                time_24hr: false,
                minuteIncrement: 5,
                allowInput: false
            });
            if (input.value && /^\d{2}:\d{2}/.test(input.value)) {
                input._flatpickr.setDate(normalizeIvTime(input.value), false, 'H:i');
            }
        });
    }

    if (window.jQuery && jQuery.fn.select2) {
        jQuery(root).find('.js-iv-tz').each(function () {
            var $select = jQuery(this);
            if ($select.data('select2')) return;
            $select.select2({
                width: '100%',
                minimumResultsForSearch: Infinity,
                dropdownCssClass: 'candidate-select2-dropdown',
                selectionCssClass: 'stp-select2-selection'
            });
        });
    }
}

/* ── Interview status AJAX toggle ────────────────────────────────────────── */
function updateIvStatus(btn, newStatus) {
    var wrap  = btn.closest('.iv-toggle-wrap');
    var url   = wrap.dataset.url;
    var token = wrap.dataset.token;
    var btns  = wrap.querySelectorAll('.iv-toggle-btn');

    if (wrap.dataset.locked === '1') return;

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
    .then(function(res) {
        return res.json().catch(function () { return {}; }).then(function (data) {
            if (!res.ok) data.success = false;
            return data;
        });
    })
    .then(function(data) {
        if (data.success) {
            // Update active button
            btns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            wrap.dataset.locked = '1';
            var hint = wrap.parentNode ? wrap.parentNode.querySelector('.iv-status-hint') : null;
            if (!hint && wrap.parentNode) {
                hint = document.createElement('div');
                hint.className = 'iv-status-hint';
                wrap.parentNode.appendChild(hint);
            }
            if (hint) hint.textContent = 'Status already submitted.';
            applyInterviewUiState(wrap.closest('tr'), data);
            showSdbToast('Interview status saved as ' + newStatus + '.', 'success');
        } else {
            showSdbToast(data.message || data.error || 'Could not update status. Try again.', 'error');
        }
    })
    .catch(function() {
        showSdbToast('Network error. Please try again.', 'error');
    })
    .finally(function() {
        btns.forEach(b => {
            b.classList.remove('saving');
            b.disabled = wrap.dataset.locked === '1';
        });
    });
}

/* ── Interview schedule inline edit ─────────────────────────────────────── */
function openSchedEdit(ivId, date, time, tz) {
    var row = document.getElementById('iv-row-' + ivId);
    if (row) {
        date = row.dataset.scheduledDate || date || '';
        time = row.dataset.scheduledTime || time || '';
        tz = row.dataset.scheduledTimezone || tz || '';
        row.classList.add('is-schedule-editing');
    }

    document.getElementById('iv-sched-display-' + ivId).style.display = 'none';
    var form = document.getElementById('iv-sched-form-' + ivId);
    form.style.display = 'block';
    initInterviewPickers(form);

    var d = document.getElementById('iv-date-input-' + ivId);
    var t = document.getElementById('iv-time-input-' + ivId);
    var z = document.getElementById('iv-tz-input-'   + ivId);
    if (d) {
        if (d._flatpickr) d._flatpickr.setDate(date || '', false, 'Y-m-d');
        else d.value = date || '';
    }
    if (t) {
        var cleanTime = normalizeIvTime(time);
        if (t._flatpickr) t._flatpickr.setDate(cleanTime || '', false, 'H:i');
        else t.value = cleanTime;
    }
    if (z) {
        z.value = tz || '';
        if (window.jQuery && jQuery(z).data('select2')) jQuery(z).trigger('change.select2');
    }
}

function closeSchedEdit(ivId) {
    document.getElementById('iv-sched-form-'    + ivId).style.display = 'none';
    document.getElementById('iv-sched-display-' + ivId).style.display = 'block';
    var row = document.getElementById('iv-row-' + ivId);
    if (row) row.classList.remove('is-schedule-editing');
}

function getFlatpickrValue(input, format) {
    if (!input) return '';
    if (input._flatpickr && input._flatpickr.selectedDates.length) {
        return input._flatpickr.formatDate(input._flatpickr.selectedDates[0], format);
    }
    var value = String(input.value || '').trim();
    if (format === 'Y-m-d' && /^\d{2}-\d{2}-\d{4}$/.test(value)) {
        var dateParts = value.split('-');
        return dateParts[2] + '-' + dateParts[1] + '-' + dateParts[0];
    }
    if (format === 'H:i') {
        var match = value.match(/^(\d{1,2}):(\d{2})\s*(AM|PM)$/i);
        if (match) {
            var hour = Number(match[1]);
            var minute = match[2];
            var period = match[3].toUpperCase();
            if (period === 'PM' && hour < 12) hour += 12;
            if (period === 'AM' && hour === 12) hour = 0;
            return String(hour).padStart(2, '0') + ':' + minute;
        }
    }
    return value;
}

function saveSchedEdit(ivId, url, token) {
    var dateInput  = document.getElementById('iv-date-input-' + ivId);
    var timeInput  = document.getElementById('iv-time-input-' + ivId);
    var tzInput    = document.getElementById('iv-tz-input-'   + ivId);
    var formDiv    = document.getElementById('iv-sched-form-' + ivId);
    var saveBtn    = formDiv ? formDiv.querySelector('.iv-sched-save-btn')   : null;
    var cancelBtn  = formDiv ? formDiv.querySelector('.iv-sched-cancel-btn') : null;

    var date = getFlatpickrValue(dateInput, 'Y-m-d');
    var time = getFlatpickrValue(timeInput, 'H:i');
    var tz   = tzInput   ? tzInput.value.trim()   : '';

    if (!date || !time || !tz) {
        showSdbToast('Please select date, time, and timezone.', 'error');
        return;
    }

    if (saveBtn)  { saveBtn.disabled = true;   saveBtn.innerHTML   = '<i class="bi bi-hourglass-split"></i> Saving…'; }
    if (cancelBtn) cancelBtn.disabled = true;

    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: new URLSearchParams({
            '_method':            'PATCH',
            '_token':             token,
            'scheduled_date':     date,
            'scheduled_time':     time,
            'scheduled_timezone': tz,
        }),
    })
    .then(function(res) {
        return res.json().catch(function () { return {}; }).then(function (data) {
            if (!res.ok) data.success = false;
            return data;
        });
    })
    .then(function(data) {
        if (data.success) {
            // ── Update displayed date/time texts ──
            syncInterviewScheduleDisplay(ivId, date, time, tz);
            /*
            var dateText = document.getElementById('iv-date-text-' + ivId);
            var timeText = document.getElementById('iv-time-text-' + ivId);

            if (dateText) {
                if (data.scheduled_date) {
                    dateText.textContent   = data.scheduled_date;
                    dateText.style.display = '';
                } else {
                    dateText.textContent   = '';
                    dateText.style.display = 'none';
                }
            }
            if (timeText) {
                var timeLine = '';
                if (data.scheduled_time_fmt) timeLine += data.scheduled_time_fmt;
                if (data.scheduled_timezone) timeLine += (timeLine ? ' ' : '') + data.scheduled_timezone;
                timeText.textContent   = timeLine;
                timeText.style.display = (timeLine || data.scheduled_date) ? '' : 'none';
            }

            // ── Swap Set Schedule ↔ Edit button ──
            var displayDiv = document.getElementById('iv-sched-display-' + ivId);
            var row = document.getElementById('iv-row-' + ivId);
            if (row) {
                row.dataset.scheduledDate = data.scheduled_date_raw || date;
                row.dataset.scheduledTime = normalizeIvTime(data.scheduled_time || time);
                row.dataset.scheduledTimezone = data.source_timezone || tz;
            }
            var oldBtn = displayDiv ? displayDiv.querySelector('.iv-sched-edit-btn, .iv-sched-set-btn') : null;
            if (oldBtn) {
                if (data.scheduled_date) {
                    oldBtn.className = 'iv-sched-edit-btn';
                    oldBtn.innerHTML = '<i class="bi bi-pencil-fill"></i> Edit';
                    var rawDate = data.scheduled_date_raw || date;
                    var rawTime = normalizeIvTime(data.scheduled_time || time);
                    var rawTz   = data.source_timezone || tz;
                    oldBtn.onclick = function () { openSchedEdit(ivId, rawDate, rawTime, rawTz); };
                } else {
                    oldBtn.className = 'iv-sched-set-btn';
                    oldBtn.innerHTML = '<i class="bi bi-calendar-plus"></i> Set Schedule';
                    oldBtn.onclick   = function () { openSchedEdit(ivId, '', '', ''); };
                }
            }
            */

            applyInterviewUiState(document.getElementById('iv-row-' + ivId), data);
            closeSchedEdit(ivId);
            showSdbToast('Interview schedule saved. Status will unlock after the scheduled time.', 'success');
        } else {
            var msg = (data.errors ? Object.values(data.errors).flat().join(' ') : null)
                   || data.message || data.error || 'Could not save. Try again.';
            showSdbToast(msg, 'error');
        }
    })
    .catch(function() {
        showSdbToast('Network error. Please try again.', 'error');
    })
    .finally(function() {
        if (saveBtn)  { saveBtn.disabled = false;  saveBtn.innerHTML   = '<i class="bi bi-check-lg"></i> Save'; }
        if (cancelBtn) cancelBtn.disabled = false;
    });
}

var interviewUnlockTimers = {};

function applyInterviewUiState(row, data) {
    if (!row || !data) return;

    if (data.scheduled_at_ms !== undefined && data.scheduled_at_ms !== null) {
        row.dataset.scheduledAtMs = data.scheduled_at_ms;
    }
    if (data.interview_status) {
        row.dataset.statusSubmitted = '1';
    }

    var statusSubmitted = row.dataset.statusSubmitted === '1';
    var canUpdateStatus = !!data.can_update_status && !statusSubmitted;
    var statusWrap = row.querySelector('.iv-toggle-wrap');
    var hint = row.querySelector('.iv-status-hint');

    if (statusWrap) {
        statusWrap.dataset.locked = canUpdateStatus ? '0' : '1';
        statusWrap.querySelectorAll('.iv-toggle-btn').forEach(function (button) {
            button.disabled = !canUpdateStatus;
        });
    }

    if (!hint && statusWrap && statusWrap.parentNode) {
        hint = document.createElement('div');
        hint.className = 'iv-status-hint';
        statusWrap.parentNode.appendChild(hint);
    }
    if (hint) {
        hint.textContent = data.status_lock_reason || '';
        hint.style.display = hint.textContent ? '' : 'none';
    }

    if (data.schedule_has_passed) {
        lockScheduleCell(row);
    } else {
        scheduleInterviewUnlock(row);
    }
}

function lockScheduleCell(row) {
    var display = row.querySelector('[id^="iv-sched-display-"]');
    if (!display) return;
    display.querySelectorAll('.iv-sched-edit-btn,.iv-sched-set-btn').forEach(function (button) {
        button.remove();
    });
    if (!display.querySelector('.iv-sched-locked')) {
        var locked = document.createElement('div');
        locked.className = 'iv-sched-locked';
        locked.innerHTML = '<i class="bi bi-lock-fill"></i> Schedule locked';
        display.appendChild(locked);
    }
}

function scheduleInterviewUnlock(row) {
    if (!row || !row.id || !row.dataset.scheduledAtMs) return;
    if (interviewUnlockTimers[row.id]) clearTimeout(interviewUnlockTimers[row.id]);

    var delay = Number(row.dataset.scheduledAtMs) - Date.now();
    if (Number.isNaN(delay)) return;

    if (delay <= 0) {
        applyInterviewUiState(row, {
            schedule_has_passed: true,
            can_update_status: row.dataset.statusSubmitted !== '1',
            status_lock_reason: row.dataset.statusSubmitted === '1' ? 'Status already submitted.' : ''
        });
        return;
    }

    interviewUnlockTimers[row.id] = setTimeout(function () {
        applyInterviewUiState(row, {
            schedule_has_passed: true,
            can_update_status: row.dataset.statusSubmitted !== '1',
            status_lock_reason: row.dataset.statusSubmitted === '1' ? 'Status already submitted.' : ''
        });
        showSdbToast('An interview status is now available to update.', 'success');
    }, Math.min(delay + 500, 2147483647));
}

/* ── Small inline toast ──────────────────────────────────────────────────── */
function normalizeIvTime(value) {
    return value ? String(value).slice(0, 5) : '';
}

function normalizeIvDate(value) {
    return value ? String(value).slice(0, 10) : '';
}

function formatIvDate(value) {
    var raw = normalizeIvDate(value);
    if (!raw) return '';
    var parts = raw.split('-');
    if (parts.length !== 3) return raw;
    var date = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
    if (Number.isNaN(date.getTime())) return raw;
    return date.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
}

function formatIvTime(value) {
    var raw = normalizeIvTime(value);
    if (!raw) return '';
    var parts = raw.split(':');
    var hour = Number(parts[0]);
    var minute = Number(parts[1]);
    if (Number.isNaN(hour) || Number.isNaN(minute)) return raw;
    var suffix = hour >= 12 ? 'PM' : 'AM';
    var displayHour = hour % 12 || 12;
    return String(displayHour).padStart(2, '0') + ':' + String(minute).padStart(2, '0') + ' ' + suffix;
}

function syncInterviewScheduleDisplay(ivId, date, time, tz) {
    var rawDate = normalizeIvDate(date);
    var rawTime = normalizeIvTime(time);
    var rawTz = String(tz || '').trim();

    var row = document.getElementById('iv-row-' + ivId);
    if (row) {
        row.dataset.scheduledDate = rawDate;
        row.dataset.scheduledTime = rawTime;
        row.dataset.scheduledTimezone = rawTz;
    }

    var dateText = document.getElementById('iv-date-text-' + ivId);
    var timeText = document.getElementById('iv-time-text-' + ivId);
    if (dateText) {
        dateText.textContent = formatIvDate(rawDate);
        dateText.style.display = rawDate ? '' : 'none';
    }
    if (timeText) {
        var line = [formatIvTime(rawTime), rawTz].filter(Boolean).join(' ');
        timeText.textContent = line;
        timeText.style.display = line ? '' : 'none';
    }

    var displayDiv = document.getElementById('iv-sched-display-' + ivId);
    var oldBtn = displayDiv ? displayDiv.querySelector('.iv-sched-edit-btn, .iv-sched-set-btn') : null;
    if (oldBtn) {
        oldBtn.className = 'iv-sched-edit-btn';
        oldBtn.innerHTML = '<i class="bi bi-pencil-fill"></i> Edit';
        oldBtn.onclick = function () { openSchedEdit(ivId, rawDate, rawTime, rawTz); };
    }
}

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

document.addEventListener('DOMContentLoaded', function () {
    initSdbTabs();
    initInterviewPickers(document);
    document.querySelectorAll('.sdb-iv-table tbody tr[id^="iv-row-"]').forEach(scheduleInterviewUnlock);
});
</script>

@endsection
