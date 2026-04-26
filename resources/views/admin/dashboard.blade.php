@extends('layouts.admin')
@section('title', 'Dashboard')
@section('module-title', 'Dashboard')
@section('module-description', 'Live overview - interviews, recruiter performance, pending work, and attention flags.')
@section('content')

@php
    $dashboardAbbr = \Carbon\Carbon::now($dashboardTimezone ?? 'Asia/Kolkata')->format('T');
@endphp

<style>
.dw-grid-2 { display:grid; grid-template-columns:minmax(0, 1fr) minmax(0, 1fr); gap:20px; margin-bottom:20px; }
.dw-grid-1 { margin-bottom:20px; }
.dw-card-fill { height:100%; }

.dw-stats-row {
    display:grid;
    gap:16px;
    margin-bottom:20px;
    grid-template-columns:repeat(4, minmax(0, 1fr));
}

.dw-grid-1,
.dw-grid-2,
.dw-grid-2 > .card,
.dw-grid-1 > .card,
.dw-stat-card,
.dw-scope-card {
    min-width:0;
}

.dw-stat-card {
    background:var(--card-bg);
    border:1px solid var(--border-color);
    border-radius:var(--radius);
    padding:16px 20px;
    display:flex;
    align-items:center;
    gap:14px;
    transition:box-shadow .15s, background var(--transition);
}

.dw-stat-card:hover { box-shadow:0 2px 12px rgba(0, 0, 0, .07); }

.dw-stat-icon {
    width:42px;
    height:42px;
    border-radius:10px;
    flex-shrink:0;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:19px;
}

.dw-stat-body { min-width:0; }
.dw-stat-num { font-size:26px; font-weight:800; line-height:1; color:var(--text-primary); }
.dw-stat-lbl {
    font-size:12px;
    color:var(--text-muted);
    margin-top:3px;
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
}

.dw-head {
    display:flex;
    align-items:center;
    justify-content:space-between;
    margin-bottom:14px;
    flex-wrap:wrap;
    gap:8px;
}

.dw-head > div { min-width:0; }

.dw-title {
    font-size:15px;
    font-weight:700;
    color:var(--text-primary);
    display:flex;
    align-items:center;
    gap:8px;
}

.dw-title i { font-size:17px; }
.dw-sub { font-size:12px; color:var(--text-muted); margin-top:1px; line-height:1.4; }

.dw-day-tabs,
.dw-leave-tabs {
    display:flex;
    gap:0;
    border-bottom:2px solid var(--border-color);
    margin-bottom:14px;
}

.dw-day-tab,
.dw-leave-tab {
    padding:7px 18px;
    font-size:13px;
    font-weight:500;
    cursor:pointer;
    color:var(--text-muted);
    border:none;
    background:none;
    border-bottom:2px solid transparent;
    margin-bottom:-2px;
    transition:color .15s, border-color .15s;
}

.dw-day-tab.active,
.dw-leave-tab.active {
    color:var(--blue);
    border-bottom-color:var(--blue);
    font-weight:600;
}

.dw-day-panel,
.dw-leave-panel { display:none; }
.dw-day-panel.show,
.dw-leave-panel.show { display:block; }

.dw-leave-list { display:grid; gap:10px; }

.dw-leave-row {
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    padding:11px 14px;
    border:1px solid var(--border-color);
    border-radius:12px;
    background:var(--main-bg);
}

.dw-leave-name { font-size:13px; font-weight:700; color:var(--text-primary); }
.dw-leave-type {
    font-size:11px;
    font-weight:700;
    color:#2563eb;
    background:#eff6ff;
    border-radius:999px;
    padding:4px 9px;
}

.dw-table {
    width:100%;
    min-width:0 !important;
    border-collapse:collapse;
    font-size:13px;
}

.dw-table th {
    padding:8px 10px;
    text-align:left;
    font-weight:600;
    font-size:11.5px;
    text-transform:uppercase;
    letter-spacing:.4px;
    color:var(--text-muted);
    border-bottom:2px solid var(--border-color);
    background:var(--main-bg);
}

.dw-table td {
    padding:9px 10px;
    border-bottom:1px solid var(--border-color);
    vertical-align:middle;
    overflow-wrap:anywhere;
}

.dw-table th,
.dw-table td {
    max-width:240px;
}

.dw-table tr:last-child td { border-bottom:none; }
.dw-table tr:hover td { background:var(--hover-bg, rgba(0, 0, 0, .025)); }

.dw-time-main {
    font-size:12px;
    font-weight:600;
    color:var(--text-primary);
    line-height:1.2;
    white-space:nowrap;
}

.dw-time-zone {
    display:inline-flex;
    align-items:center;
    margin-left:5px;
    padding:1px 6px;
    border-radius:999px;
    background:#eff6ff;
    color:#1d4ed8;
    font-size:10px;
    font-weight:700;
}

.dw-time-sub { margin-top:3px; font-size:10.5px; color:var(--text-muted); line-height:1.2; }

.dw-top-select {
    font-size:12px;
    font-weight:500;
    padding:4px 10px;
    border:1px solid var(--border-color);
    border-radius:6px;
    background:var(--card-bg);
    color:var(--text-primary);
    cursor:pointer;
}

.dw-rank {
    display:inline-flex;
    align-items:center;
    justify-content:center;
    width:22px;
    height:22px;
    border-radius:50%;
    font-size:11px;
    font-weight:700;
    background:var(--main-bg);
    color:var(--text-muted);
    border:1px solid var(--border-color);
}

.dw-rank.gold { background:#fef9c3; color:#854d0e; border-color:#fde68a; }
.dw-rank.silver { background:#f1f5f9; color:#475569; border-color:#e2e8f0; }
.dw-rank.bronze { background:#fff7ed; color:#9a3412; border-color:#fed7aa; }

.dw-filter-bar { display:flex; align-items:center; justify-content:flex-end; gap:8px; flex-wrap:wrap; min-width:0; }
.dw-filter-bar .form-control { height:32px; font-size:13px; padding:4px 10px; }
.dw-filter-sep { color:var(--text-muted); font-size:12px; }

.badge-gap { background:#fef2f2; color:#dc2626; font-weight:700; }
.badge-logged { background:#f0fdf4; color:#16a34a; }
.badge-target { background:#eff6ff; color:#2563eb; }

.dw-empty { text-align:center; padding:24px 0; color:var(--text-muted); }
.dw-empty i { font-size:26px; display:block; margin-bottom:6px; opacity:.4; }
.dw-empty p { font-size:13px; margin:0; }

.dw-days-ago {
    font-size:11px;
    padding:2px 8px;
    border-radius:99px;
    background:#fff7ed;
    color:#c2410c;
    font-weight:600;
}

.dw-manager-header {
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:16px;
    margin-bottom:20px;
}

.dw-scope-card {
    background:var(--card-bg);
    border:1px solid var(--border-color);
    border-radius:var(--radius);
    padding:16px 20px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    text-decoration:none;
    transition:box-shadow .15s, background var(--transition);
}

.dw-scope-card:hover { box-shadow:0 2px 12px rgba(0, 0, 0, .08); text-decoration:none; }
.dw-scope-num { font-size:32px; font-weight:800; line-height:1; }
.dw-scope-lbl { font-size:13px; font-weight:600; color:var(--text-primary); margin-top:4px; }
.dw-scope-sub { font-size:11.5px; color:var(--text-muted); margin-top:2px; }

.dw-scope-icon {
    width:44px;
    height:44px;
    border-radius:10px;
    flex-shrink:0;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:20px;
}

@media (max-width:1100px) {
    .dw-stats-row { grid-template-columns:repeat(3, minmax(0, 1fr)) !important; }
}

@media (max-width:900px) {
    .dw-stats-row { grid-template-columns:repeat(2, minmax(0, 1fr)) !important; }
}

@media (max-width:768px) {
    .dw-grid-2 { grid-template-columns:1fr; }
    .dw-manager-header { grid-template-columns:1fr; }
    .dw-filter-bar {
        width:100%;
        justify-content:flex-start;
    }
    .dw-filter-bar .form-control {
        flex:1 1 140px;
        width:auto !important;
    }
}

@media (max-width:480px) {
    .dw-stats-row { grid-template-columns:1fr !important; }
}
</style>

<div class="dw-stats-row" style="grid-template-columns:repeat(5,minmax(0,1fr));">
    <div class="dw-stat-card">
        <div class="dw-stat-icon" style="background:#eff6ff;color:#2563eb;">
            <i class="bi bi-calendar2-event-fill"></i>
        </div>
        <div class="dw-stat-body">
            <div class="dw-stat-num">{{ number_format($statTodayInterviews) }}</div>
            <div class="dw-stat-lbl">Today's Interviews</div>
        </div>
    </div>
    <div class="dw-stat-card">
        <div class="dw-stat-icon" style="background:#f0fdf4;color:#16a34a;">
            <i class="bi bi-person-check-fill"></i>
        </div>
        <div class="dw-stat-body">
            <div class="dw-stat-num">{{ number_format($statActiveCandidates) }}</div>
            <div class="dw-stat-lbl">Active Candidates</div>
        </div>
    </div>
    <div class="dw-stat-card">
        <div class="dw-stat-icon" style="background:#fdf4ff;color:#a21caf;">
            <i class="bi bi-person-badge-fill"></i>
        </div>
        <div class="dw-stat-body">
            <div class="dw-stat-num">{{ number_format($statTotalRecruiters) }}</div>
            <div class="dw-stat-lbl">
                @if ($isManager) Team Recruiters @else Active Recruiters @endif
            </div>
        </div>
    </div>
    <div class="dw-stat-card">
        <div class="dw-stat-icon" style="background:#fff7ed;color:#c2410c;">
            <i class="bi bi-hourglass-split"></i>
        </div>
        <div class="dw-stat-body">
            <div class="dw-stat-num">{{ number_format($statPendingCount) }}</div>
            <div class="dw-stat-lbl">Pending Applications</div>
        </div>
    </div>
    @if ($isAdmin)
    <div class="dw-stat-card">
        <div class="dw-stat-icon" style="background:#fef2f2;color:#dc2626;">
            <i class="bi bi-person-dash-fill"></i>
        </div>
        <div class="dw-stat-body">
            <div class="dw-stat-num">{{ number_format($statUnassignedCount) }}</div>
            <div class="dw-stat-lbl">Unassigned</div>
        </div>
    </div>
    @endif
</div>

@if ($isManager ?? false)
<div class="dw-manager-header">
    <a href="{{ route('admin.candidates', ['scope' => 'mine']) }}" class="dw-scope-card" style="border-left:3px solid var(--blue);">
        <div>
            <div class="dw-scope-num" style="color:var(--blue);">{{ $managerMyCandidatesCount }}</div>
            <div class="dw-scope-lbl">My Candidates</div>
            <div class="dw-scope-sub">Directly assigned to you</div>
        </div>
        <div class="dw-scope-icon" style="background:#eff6ff;color:#2563eb;">
            <i class="bi bi-person-fill"></i>
        </div>
    </a>
    <a href="{{ route('admin.candidates', ['scope' => 'team']) }}" class="dw-scope-card" style="border-left:3px solid var(--green);">
        <div>
            <div class="dw-scope-num" style="color:var(--green);">{{ $managerAllCandidatesCount }}</div>
            <div class="dw-scope-lbl">Team Candidates</div>
            <div class="dw-scope-sub">Through your recruiters</div>
        </div>
        <div class="dw-scope-icon" style="background:#f0fdf4;color:#16a34a;">
            <i class="bi bi-people-fill"></i>
        </div>
    </a>
</div>
@endif

<div class="dw-grid-1">
    <div class="card dw-card-fill">
        <div class="dw-head">
            <div>
                <div class="dw-title"><i class="bi bi-hourglass-split" style="color:#f59e0b;"></i> Pending Applications</div>
                <div class="dw-sub">
                    Candidates who have not met their daily application target
                    @if ($pendingDays > 1)
                        <span style="margin-left:6px;">({{ $pendingDays }}-day period)</span>
                    @endif
                </div>
            </div>
            <form method="GET" action="{{ route('admin.dashboard') }}" class="dw-filter-bar">
                <input type="hidden" name="perf_month" value="{{ $performanceMonth ?? now()->format('Y-m') }}">
                <input type="date" name="pend_date" class="form-control" value="{{ $pendingDateStr }}" style="width:150px;" title="Start date">
                <span class="dw-filter-sep">to</span>
                <input type="date" name="pend_date_end" class="form-control" value="{{ $pendingDateEndStr ?? '' }}" style="width:150px;" title="End date (leave blank for single day)">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-funnel-fill"></i> Filter
                </button>
                @if ($pendingDateEndStr)
                    <a href="{{ route('admin.dashboard', ['pend_date' => today()->toDateString(), 'perf_month' => $performanceMonth ?? now()->format('Y-m')]) }}"
                        class="btn btn-outline btn-sm" title="Reset to today">
                        <i class="bi bi-x-circle"></i>
                    </a>
                @endif
            </form>
        </div>

        @if ($pendingCandidates->total() === 0)
            <div class="dw-empty">
                <i class="bi bi-check2-circle" style="color:#16a34a;opacity:1;"></i>
                <p style="color:#16a34a;font-weight:500;">All candidates met their application targets for this period.</p>
            </div>
        @else
            <div style="overflow-x:auto;">
                <table class="dw-table">
                    <thead>
                        <tr>
                            <th>Candidate</th>
                            @if (!$isRecruiter)<th>Recruiter</th>@endif
                            <th style="text-align:right;">Daily Target</th>
                            <th style="text-align:right;">Period Target</th>
                            <th style="text-align:right;">Logged</th>
                            <th style="text-align:right;">Pending</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pendingCandidates as $c)
                        <tr>
                            <td>
                                <a href="{{ route('admin.candidates.show', $c) }}" style="font-weight:600;color:var(--text-primary);text-decoration:none;">
                                    {{ $c->full_name }}
                                </a>
                            </td>
                            @if (!$isRecruiter)
                                <td class="text-sm text-muted">{{ $c->recruiter?->name ?? '-' }}</td>
                            @endif
                            <td style="text-align:right;"><span class="text-sm text-muted">{{ number_format($c->no_of_applications) }}/day</span></td>
                            <td style="text-align:right;"><span class="badge badge-target">{{ number_format($c->pending_target) }}</span></td>
                            <td style="text-align:right;"><span class="badge badge-logged">{{ number_format($c->pending_logged) }}</span></td>
                            <td style="text-align:right;"><span class="badge badge-gap">-{{ number_format($c->pending_gap) }}</span></td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr style="border-top:2px solid var(--border);">
                            <td colspan="{{ $isRecruiter ? 3 : 4 }}" style="text-align:right;font-weight:600;font-size:12px;color:var(--text-muted);padding:8px 10px;">Totals</td>
                            <td style="text-align:right;padding:8px 10px;"><span class="badge badge-logged">{{ number_format($pendingTotals['logged']) }}</span></td>
                            <td style="text-align:right;padding:8px 10px;"><span class="badge badge-gap">-{{ number_format($pendingTotals['gap']) }}</span></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif

        @if ($pendingCandidates->hasPages())
            <div class="pagination-wrap">
                <span class="pagination-info">
                    Showing {{ $pendingCandidates->firstItem() }}-{{ $pendingCandidates->lastItem() }} of {{ $pendingCandidates->total() }}
                </span>
                {{ $pendingCandidates->links('pagination.custom') }}
            </div>
        @endif
    </div>
</div>

<div class="dw-grid-2">
    <div class="card">
        <div class="dw-head">
            <div>
                <div class="dw-title"><i class="bi bi-trophy-fill" style="color:#f59e0b;"></i> Top Performance</div>
                <div class="dw-sub">
                    Ranked by valid interview count across recruiters and managers
                    for {{ $performanceMonths[$performanceMonth] ?? $performanceMonth }}
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                <form method="GET" action="{{ route('admin.dashboard') }}" id="perfMonthForm">
                    <input type="hidden" name="pend_date" value="{{ $pendingDateStr }}">
                    @if ($pendingDateEndStr)
                        <input type="hidden" name="pend_date_end" value="{{ $pendingDateEndStr }}">
                    @endif
                    <select name="perf_month" class="dw-top-select" onchange="document.getElementById('perfMonthForm').submit()">
                        @foreach ($performanceMonths as $value => $label)
                            <option value="{{ $value }}" @selected(($performanceMonth ?? now()->format('Y-m')) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </form>
                <select class="dw-top-select" onchange="filterTopRecruiters(this.value)">
                    <option value="5">Top 5</option>
                    <option value="10">Top 10</option>
                </select>
            </div>
        </div>

        @if ($topPerformers->isEmpty())
            <div class="dw-empty"><i class="bi bi-person-dash"></i><p>No performance data available.</p></div>
        @else
            <table class="dw-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Team Member</th>
                        <th style="text-align:right;">Valid Interviews</th>
                    </tr>
                </thead>
                <tbody id="topRecruiterRows">
                    @foreach ($topPerformers as $index => $r)
                    <tr class="top-rec-row" data-rank="{{ $index + 1 }}">
                        <td>
                            <span class="dw-rank {{ $index === 0 ? 'gold' : ($index === 1 ? 'silver' : ($index === 2 ? 'bronze' : '')) }}">
                                {{ $index + 1 }}
                            </span>
                        </td>
                        <td>
                            <div class="avatar-row">
                                <div class="avatar-sm">{{ $r->initials }}</div>
                                <div>
                                    <div class="avatar-name">{{ $r->name }}</div>
                                    <div class="avatar-sub">
                                        {{ $r->role_label }} - {{ $r->performance_candidates_count }} candidate{{ $r->performance_candidates_count !== 1 ? 's' : '' }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td style="text-align:right;">
                            <span class="badge badge-success" style="font-size:13px;font-weight:700;">
                                {{ number_format($r->valid_interview_count) }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="card" style="margin-top: 0">
        <div class="dw-head">
            <div>
                <div class="dw-title"><i class="bi bi-exclamation-triangle-fill" style="color:#ef4444;"></i> Attention Required</div>
                <div class="dw-sub">Active candidates with no interview in the last 20 days</div>
            </div>
            <span class="badge badge-danger" style="font-size:12px;">{{ $attentionCandidates->total() }}</span>
        </div>

        @if ($attentionCandidates->total() === 0)
            <div class="dw-empty"><i class="bi bi-check-circle-fill" style="color:#16a34a;opacity:1;"></i><p style="color:#16a34a;font-weight:500;">All candidates are up to date.</p></div>
        @else
            <div style="overflow-x:auto;">
                <table class="dw-table">
                    <thead>
                        <tr>
                            <th>Candidate</th>
                            @if (!$isRecruiter)<th>Recruiter</th>@endif
                            <th>Domain</th>
                            <th>Added</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($attentionCandidates as $c)
                        <tr>
                            <td>
                                <a href="{{ route('admin.candidates.show', $c) }}" style="font-weight:600;color:var(--text-primary);text-decoration:none;">
                                    {{ $c->full_name }}
                                </a>
                            </td>
                            @if (!$isRecruiter)
                                <td class="text-sm text-muted">{{ $c->recruiter?->name ?? '-' }}</td>
                            @endif
                            <td class="text-sm text-muted">{{ $c->domain ?: '-' }}</td>
                            <td>
                                <span class="dw-days-ago">{{ $c->created_at->diffForHumans() }}</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if ($attentionCandidates->hasPages())
            <div class="pagination-wrap">
                <span class="pagination-info">
                    Showing {{ $attentionCandidates->firstItem() }}-{{ $attentionCandidates->lastItem() }} of {{ $attentionCandidates->total() }}
                </span>
                {{ $attentionCandidates->links('pagination.custom') }}
            </div>
        @endif
    </div>
</div>

<div class="dw-grid-2">
    <div class="card" style="grid-column:1 / -1;">
        <div class="dw-head">
            <div>
                <div class="dw-title"><i class="bi bi-calendar2-event-fill" style="color:var(--blue);"></i> Interviews</div>
                <div class="dw-sub">Today and tomorrow's interviews sorted by India time ({{ $dashboardAbbr }}).</div>
            </div>
            <span class="badge badge-primary">{{ $todayInterviews->total() + $tomorrowInterviews->total() }} total</span>
        </div>

        <div class="dw-day-tabs">
            <button class="dw-day-tab active" data-day="today" onclick="switchDayTab('today', this)">
                Today
                @if ($todayInterviews->total())
                    <span class="badge badge-primary" style="margin-left:5px;font-size:11px;">{{ $todayInterviews->total() }}</span>
                @endif
            </button>
            <button class="dw-day-tab" data-day="tomorrow" onclick="switchDayTab('tomorrow', this)">
                Tomorrow
                @if ($tomorrowInterviews->total())
                    <span class="badge badge-info" style="margin-left:5px;font-size:11px;">{{ $tomorrowInterviews->total() }}</span>
                @endif
            </button>
        </div>

        <div id="panel-today" class="dw-day-panel show">
            @if ($todayInterviews->total() === 0)
                <div class="dw-empty"><i class="bi bi-calendar-x"></i><p>No interviews today.</p></div>
            @else
                <div style="overflow-x:auto;">
                    <table class="dw-table">
                        <thead>
                            <tr>
                                <th>Candidate</th>
                                <th>Company</th>
                                <th>Time ({{ $dashboardAbbr }})</th>
                                <th>Candidate Time</th>
                                @if (!$isRecruiter)<th>Recruiter</th>@endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($todayInterviews as $iv)
                            <tr>
                                <td>
                                    @if ($iv->candidate)
                                        <a href="{{ route('admin.candidates.show', $iv->candidate) }}" style="font-weight:600;color:var(--blue);text-decoration:none;font-size:12px;">
                                            {{ $iv->candidate->full_name }}
                                        </a>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-sm" style="font-size:12px;">{{ $iv->company_name }}</td>
                                <td>
                                    <div class="dw-time-main">
                                        {{ $iv->dashboard_display_time ?? 'TBD' }}
                                        @if ($iv->dashboard_display_timezone)
                                            <span class="dw-time-zone">{{ $iv->dashboard_display_timezone }}</span>
                                        @endif
                                    </div>
                                    @if ($iv->dashboard_display_date)
                                        <div class="dw-time-sub">{{ $iv->dashboard_display_date }}</div>
                                    @endif
                                </td>
                                <td>
                                    <div class="dw-time-main">
                                        {{ $iv->source_display_time ?? 'TBD' }}
                                        @if ($iv->source_display_timezone)
                                            <span class="dw-time-zone">{{ strtoupper($iv->source_display_timezone) }}</span>
                                        @endif
                                    </div>
                                    @if ($iv->source_display_date)
                                        <div class="dw-time-sub">{{ $iv->source_display_date }}</div>
                                    @endif
                                </td>
                                @if (!$isRecruiter)
                                    <td class="text-sm text-muted" style="font-size:12px;">{{ $iv->recruiter?->name ?? '-' }}</td>
                                @endif
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if ($todayInterviews->hasPages())
                <div class="pagination-wrap">
                    <span class="pagination-info">
                        Showing {{ $todayInterviews->firstItem() }}-{{ $todayInterviews->lastItem() }} of {{ $todayInterviews->total() }}
                    </span>
                    {{ $todayInterviews->links('pagination.custom') }}
                </div>
            @endif
        </div>

        <div id="panel-tomorrow" class="dw-day-panel">
            @if ($tomorrowInterviews->total() === 0)
                <div class="dw-empty"><i class="bi bi-calendar-check"></i><p>No interviews tomorrow.</p></div>
            @else
                <div style="overflow-x:auto;">
                    <table class="dw-table">
                        <thead>
                            <tr>
                                <th>Candidate</th>
                                <th>Company</th>
                                <th>Time ({{ $dashboardAbbr }})</th>
                                <th>Candidate Time</th>
                                @if (!$isRecruiter)<th>Recruiter</th>@endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($tomorrowInterviews as $iv)
                            <tr>
                                <td>
                                    @if ($iv->candidate)
                                        <a href="{{ route('admin.candidates.show', $iv->candidate) }}" style="font-weight:600;color:var(--blue);text-decoration:none;font-size:12px;">
                                            {{ $iv->candidate->full_name }}
                                        </a>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-sm" style="font-size:12px;">{{ $iv->company_name }}</td>
                                <td>
                                    <div class="dw-time-main">
                                        {{ $iv->dashboard_display_time ?? 'TBD' }}
                                        @if ($iv->dashboard_display_timezone)
                                            <span class="dw-time-zone">{{ $iv->dashboard_display_timezone }}</span>
                                        @endif
                                    </div>
                                    @if ($iv->dashboard_display_date)
                                        <div class="dw-time-sub">{{ $iv->dashboard_display_date }}</div>
                                    @endif
                                </td>
                                <td>
                                    <div class="dw-time-main">
                                        {{ $iv->source_display_time ?? 'TBD' }}
                                        @if ($iv->source_display_timezone)
                                            <span class="dw-time-zone">{{ strtoupper($iv->source_display_timezone) }}</span>
                                        @endif
                                    </div>
                                    @if ($iv->source_display_date)
                                        <div class="dw-time-sub">{{ $iv->source_display_date }}</div>
                                    @endif
                                </td>
                                @if (!$isRecruiter)
                                    <td class="text-sm text-muted" style="font-size:12px;">{{ $iv->recruiter?->name ?? '-' }}</td>
                                @endif
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if ($tomorrowInterviews->hasPages())
                <div class="pagination-wrap">
                    <span class="pagination-info">
                        Showing {{ $tomorrowInterviews->firstItem() }}-{{ $tomorrowInterviews->lastItem() }} of {{ $tomorrowInterviews->total() }}
                    </span>
                    {{ $tomorrowInterviews->links('pagination.custom') }}
                </div>
            @endif
        </div>
    </div>{{-- /interviews card --}}

    {{-- ── Unassigned Candidates ──────────────────────────────── --}}
    @if ($isAdmin)
    <div class="card" style="order:3;">
        <div class="dw-head">
            <div>
                <div class="dw-title">
                    <i class="bi bi-person-dash-fill" style="color:#dc2626;"></i> Unassigned Candidates
                </div>
                <div class="dw-sub">Candidates with no recruiter or manager assigned</div>
            </div>
            @if ($statUnassignedCount > 0)
                <span class="badge badge-danger" style="font-size:12px;">{{ $statUnassignedCount }}</span>
            @else
                <span class="badge badge-success" style="font-size:12px;">0</span>
            @endif
        </div>

        @if ($unassignedCandidates->total() === 0)
            <div class="dw-empty">
                <i class="bi bi-person-check-fill" style="color:#16a34a;opacity:1;"></i>
                <p style="color:#16a34a;font-weight:500;">All candidates are assigned.</p>
            </div>
        @else
            <div style="overflow-x:auto;">
                <table class="dw-table">
                    <thead>
                        <tr>
                            <th>Candidate</th>
                            <th>Status</th>
                            <th>Last Unassigned By</th>
                            <th>Updated</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($unassignedCandidates as $c)
                        <tr>
                            <td>
                                <a href="{{ route('admin.candidates.show', $c) }}"
                                   style="font-weight:600;color:var(--blue);text-decoration:none;font-size:13px;">
                                    {{ $c->full_name }}
                                </a>
                            </td>
                            <td>
                                @php
                                    $stColors = [
                                        'active'     => ['#f0fdf4','#16a34a'],
                                        'enrolled'   => ['#eff6ff','#2563eb'],
                                        'inactive'   => ['#f8fafc','#64748b'],
                                        'terminated' => ['#fef2f2','#dc2626'],
                                    ];
                                    [$stBg, $stClr] = $stColors[$c->status] ?? ['#f8fafc','#64748b'];
                                @endphp
                                <span class="badge" style="background:{{ $stBg }};color:{{ $stClr }};font-size:11px;">
                                    {{ ucfirst($c->status) }}
                                </span>
                            </td>
                            <td class="text-sm text-muted" style="font-size:12px;">
                                {{ $c->latestAssignmentHistory?->changer?->name ?? '—' }}
                            </td>
                            <td>
                                <span class="dw-days-ago">{{ $c->updated_at->diffForHumans() }}</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($unassignedCandidates->hasPages())
                <div class="pagination-wrap">
                    <span class="pagination-info">
                        Showing {{ $unassignedCandidates->firstItem() }}-{{ $unassignedCandidates->lastItem() }} of {{ $unassignedCandidates->total() }}
                    </span>
                    {{ $unassignedCandidates->links('pagination.custom') }}
                </div>
            @endif

            <div style="margin-top:12px;text-align:right;">
                <a href="{{ route('admin.candidates', ['ownership' => 'unassigned']) }}"
                   class="btn btn-outline btn-sm">
                    <i class="bi bi-arrow-right-circle"></i> View All Unassigned
                </a>
            </div>
        @endif
    </div>
    @else
    {{-- Non-admin filler to keep grid balanced --}}
    <div class="card" style="display:flex;align-items:center;justify-content:center;min-height:120px; margin-top:0; order:3;">
        <div class="dw-empty" style="padding:20px 0;">
            <i class="bi bi-shield-lock" style="font-size:22px;opacity:.25;display:block;margin-bottom:6px;"></i>
            <p style="font-size:12px;">Restricted to administrators</p>
        </div>
    </div>
    @endif
    <div class="card" style="order:2;">
        <div class="dw-head">
            <div>
                <div class="dw-title"><i class="bi bi-graph-up-arrow" style="color:#8b5cf6;"></i> Trending Domains</div>
                <div class="dw-sub">Top 5 domains by valid interview count</div>
            </div>
        </div>

        @if ($trendingDomains->isEmpty())
            <div class="dw-empty"><i class="bi bi-pie-chart"></i><p>No domain data yet.</p></div>
        @else
            @php $maxCnt = $trendingDomains->max('cnt'); @endphp
            <table class="dw-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Domain</th>
                        <th style="text-align:right;">Valid Interviews</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($trendingDomains as $idx => $td)
                    <tr>
                        <td>
                            <span class="dw-rank {{ $idx === 0 ? 'gold' : ($idx === 1 ? 'silver' : ($idx === 2 ? 'bronze' : '')) }}">
                                {{ $idx + 1 }}
                            </span>
                        </td>
                        <td>
                            <div style="font-weight:600;font-size:13px;">{{ $td->domain }}</div>
                            <div style="margin-top:5px;height:5px;border-radius:99px;background:var(--border-color);overflow:hidden;">
                                <div style="height:100%;border-radius:99px;background:#8b5cf6;width:{{ $maxCnt > 0 ? round(($td->cnt / $maxCnt) * 100) : 0 }}%;"></div>
                            </div>
                        </td>
                        <td style="text-align:right;">
                            <span class="badge" style="background:#f3f4ff;color:#4f46e5;font-size:13px;font-weight:700;">
                                {{ number_format($td->cnt) }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    {{--
    <div class="card dw-card-fill" style="margin-top: 0;">
        <div class="dw-head">
            <div>
                <div class="dw-title"><i class="bi bi-calendar2-week-fill" style="color:#2563eb;"></i> Who Is On Leave</div>
                <div class="dw-sub">Approved staff leave for today and tomorrow.</div>
            </div>
            <span class="badge badge-primary">{{ $todayLeaves->count() + $tomorrowLeaves->count() }} entries</span>
        </div>

        <div class="dw-leave-tabs">
            <button class="dw-leave-tab active" data-day="today" onclick="switchLeaveTab('today', this)">
                Today
                @if ($todayLeaves->count())
                    <span class="badge badge-primary" style="margin-left:5px;font-size:11px;">{{ $todayLeaves->count() }}</span>
                @endif
            </button>
            <button class="dw-leave-tab" data-day="tomorrow" onclick="switchLeaveTab('tomorrow', this)">
                Tomorrow
                @if ($tomorrowLeaves->count())
                    <span class="badge badge-info" style="margin-left:5px;font-size:11px;">{{ $tomorrowLeaves->count() }}</span>
                @endif
            </button>
        </div>

        <div id="leave-panel-today" class="dw-leave-panel show">
            @if ($todayLeaves->isEmpty())
                <div class="dw-empty"><i class="bi bi-calendar-x"></i><p>No approved leave for today.</p></div>
            @else
                <div class="dw-leave-list">
                    @foreach ($todayLeaves as $leave)
                        <div class="dw-leave-row">
                            <div class="dw-leave-name">{{ $leave->user?->name }}</div>
                            <div class="dw-leave-type">{{ $leave->leave_type_label }}</div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div id="leave-panel-tomorrow" class="dw-leave-panel">
            @if ($tomorrowLeaves->isEmpty())
                <div class="dw-empty"><i class="bi bi-calendar-check"></i><p>No approved leave for tomorrow.</p></div>
            @else
                <div class="dw-leave-list">
                    @foreach ($tomorrowLeaves as $leave)
                        <div class="dw-leave-row">
                            <div class="dw-leave-name">{{ $leave->user?->name }}</div>
                            <div class="dw-leave-type">{{ $leave->leave_type_label }}</div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
    --}}
</div>

<script>
function switchDayTab(day, button) {
    document.querySelectorAll('.dw-day-tab').forEach(function (tab) {
        tab.classList.remove('active');
    });
    document.querySelectorAll('.dw-day-panel').forEach(function (panel) {
        panel.classList.remove('show');
    });
    button.classList.add('active');
    document.getElementById('panel-' + day).classList.add('show');

    try {
        sessionStorage.setItem('dashboard_interview_day', day);
    } catch (error) {}
}

function filterTopRecruiters(limit) {
    document.querySelectorAll('.top-rec-row').forEach(function (row) {
        var rank = parseInt(row.getAttribute('data-rank'), 10);
        row.style.display = rank <= parseInt(limit, 10) ? '' : 'none';
    });
}

function switchLeaveTab(day, button) {
    document.querySelectorAll('.dw-leave-tab').forEach(function (tab) {
        tab.classList.remove('active');
    });
    document.querySelectorAll('.dw-leave-panel').forEach(function (panel) {
        panel.classList.remove('show');
    });
    button.classList.add('active');
    document.getElementById('leave-panel-' + day).classList.add('show');
}

document.addEventListener('DOMContentLoaded', function () {
    filterTopRecruiters(5);

    var params = new URLSearchParams(window.location.search);
    var savedDay = null;

    try {
        savedDay = sessionStorage.getItem('dashboard_interview_day');
    } catch (error) {}

    var initialDay = savedDay;
    if (!initialDay) {
        initialDay = params.has('tomorrow_interviews_page') ? 'tomorrow' : 'today';
    }

    var initialButton = document.querySelector('.dw-day-tab[data-day="' + initialDay + '"]');
    if (initialButton) {
        switchDayTab(initialDay, initialButton);
    }
});
</script>

@endsection
