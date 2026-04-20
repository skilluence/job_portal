@extends('layouts.admin')
@section('title', 'Dashboard')
@section('module-title', 'Dashboard')
@section('module-description', 'Live overview — interviews, recruiter performance, pending work, and attention flags.')
@section('content')

<style>
/* ── Dashboard Widget Layout ─────────────────────────────── */
.dw-grid-2   { display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:20px; }
.dw-grid-1   { margin-bottom:20px; }

/* ── Summary stat cards ──────────────────────────────────── */
.dw-stats-row {
    display:grid; gap:16px; margin-bottom:20px;
    grid-template-columns: repeat(4, 1fr);
}
.dw-stat-card {
    background:var(--card-bg); border:1px solid var(--border-color);
    border-radius:var(--radius); padding:16px 20px;
    display:flex; align-items:center; gap:14px;
    transition:box-shadow .15s, background var(--transition);
}
.dw-stat-card:hover { box-shadow:0 2px 12px rgba(0,0,0,.07); }
.dw-stat-icon {
    width:42px; height:42px; border-radius:10px; flex-shrink:0;
    display:flex; align-items:center; justify-content:center;
    font-size:19px;
}
.dw-stat-body { min-width:0; }
.dw-stat-num  { font-size:26px; font-weight:800; line-height:1; color:var(--text-primary); }
.dw-stat-lbl  { font-size:12px; color:var(--text-muted); margin-top:3px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }

/* ── Section header ──────────────────────────────────────── */
.dw-head     { display:flex; align-items:center; justify-content:space-between; margin-bottom:14px; flex-wrap:wrap; gap:8px; }
.dw-title    { font-size:15px; font-weight:700; color:var(--text-primary); display:flex; align-items:center; gap:8px; }
.dw-title i  { font-size:17px; }
.dw-sub      { font-size:12px; color:var(--text-muted); margin-top:1px; }

/* ── Interview day tabs ───────────────────────────────────── */
.dw-day-tabs { display:flex; gap:0; border-bottom:2px solid var(--border-color); margin-bottom:14px; }
.dw-day-tab  {
    padding:7px 18px; font-size:13px; font-weight:500; cursor:pointer;
    color:var(--text-muted); border:none; background:none;
    border-bottom:2px solid transparent; margin-bottom:-2px;
    transition:color .15s, border-color .15s;
}
.dw-day-tab.active { color:var(--blue); border-bottom-color:var(--blue); font-weight:600; }
.dw-day-panel      { display:none; }
.dw-day-panel.show { display:block; }

/* ── Tables ──────────────────────────────────────────────── */
.dw-table { width:100%; border-collapse:collapse; font-size:13px; }
.dw-table th {
    padding:8px 10px; text-align:left; font-weight:600; font-size:11.5px;
    text-transform:uppercase; letter-spacing:.4px;
    color:var(--text-muted); border-bottom:2px solid var(--border-color);
    background:var(--main-bg);
}
.dw-table td { padding:9px 10px; border-bottom:1px solid var(--border-color); vertical-align:middle; }
.dw-table tr:last-child td { border-bottom:none; }
.dw-table tr:hover td { background:var(--hover-bg,rgba(0,0,0,.025)); }

/* ── Top performer dropdown ──────────────────────────────── */
.dw-top-select {
    font-size:12px; font-weight:500; padding:4px 10px; border:1px solid var(--border-color);
    border-radius:6px; background:var(--card-bg); color:var(--text-primary); cursor:pointer;
}

/* ── Rank badge ──────────────────────────────────────────── */
.dw-rank {
    display:inline-flex; align-items:center; justify-content:center;
    width:22px; height:22px; border-radius:50%; font-size:11px; font-weight:700;
    background:var(--main-bg); color:var(--text-muted);
    border:1px solid var(--border-color);
}
.dw-rank.gold   { background:#fef9c3; color:#854d0e; border-color:#fde68a; }
.dw-rank.silver { background:#f1f5f9; color:#475569; border-color:#e2e8f0; }
.dw-rank.bronze { background:#fff7ed; color:#9a3412; border-color:#fed7aa; }

/* ── Pending filter bar ──────────────────────────────────── */
.dw-filter-bar {
    display:flex; align-items:center; gap:8px; flex-wrap:wrap;
}
.dw-filter-bar .form-control { height:32px; font-size:13px; padding:4px 10px; }
.dw-filter-sep { color:var(--text-muted); font-size:12px; }

/* ── Badge extras ────────────────────────────────────────── */
.badge-gap    { background:#fef2f2; color:#dc2626; font-weight:700; }
.badge-logged { background:#f0fdf4; color:#16a34a; }
.badge-target { background:#eff6ff; color:#2563eb; }

/* ── Empty state mini ────────────────────────────────────── */
.dw-empty { text-align:center; padding:24px 0; color:var(--text-muted); }
.dw-empty i { font-size:26px; display:block; margin-bottom:6px; opacity:.4; }
.dw-empty p { font-size:13px; margin:0; }

/* ── Attention badges ────────────────────────────────────── */
.dw-days-ago {
    font-size:11px; padding:2px 8px; border-radius:99px;
    background:#fff7ed; color:#c2410c; font-weight:600;
}

/* ── Manager scope cards ──────────────────────────────────── */
.dw-manager-header {
    display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:20px;
}
.dw-scope-card {
    background:var(--card-bg); border:1px solid var(--border-color);
    border-radius:var(--radius); padding:16px 20px;
    display:flex; align-items:center; justify-content:space-between;
    gap:12px; text-decoration:none;
    transition:box-shadow .15s, background var(--transition);
}
.dw-scope-card:hover { box-shadow:0 2px 12px rgba(0,0,0,.08); text-decoration:none; }
.dw-scope-num { font-size:32px; font-weight:800; line-height:1; }
.dw-scope-lbl { font-size:13px; font-weight:600; color:var(--text-primary); margin-top:4px; }
.dw-scope-sub { font-size:11.5px; color:var(--text-muted); margin-top:2px; }
.dw-scope-icon {
    width:44px; height:44px; border-radius:10px; flex-shrink:0;
    display:flex; align-items:center; justify-content:center; font-size:20px;
}

@media (max-width:900px) {
    .dw-stats-row { grid-template-columns:repeat(2,1fr); }
}
@media (max-width:768px) {
    .dw-grid-2 { grid-template-columns:1fr; }
    .dw-manager-header { grid-template-columns:1fr; }
}
@media (max-width:480px) {
    .dw-stats-row { grid-template-columns:1fr 1fr; }
}
</style>

{{-- ── Summary stat cards (all roles) ─────────────────────────────────────── --}}
<div class="dw-stats-row">
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
                @if ($isManager) Team Recruiters
                @else Active Recruiters
                @endif
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
</div>

{{-- ── Pending Applications (moved to top) ─────────────────────────────── --}}
<div class="dw-grid-1">
    <div class="card">
        <div class="dw-head">
            <div>
                <div class="dw-title"><i class="bi bi-hourglass-split" style="color:#f59e0b;"></i> Pending Applications</div>
                <div class="dw-sub">
                    Candidates who haven't met their daily application target
                    @if ($pendingDays > 1)
                        <span style="margin-left:6px;">({{ $pendingDays }}-day period)</span>
                    @endif
                </div>
            </div>
            {{-- Date filter form --}}
            <form method="GET" action="{{ route('admin.dashboard') }}" class="dw-filter-bar">
                <input type="date" name="pend_date" class="form-control"
                    value="{{ $pendingDateStr }}"
                    style="width:150px;"
                    title="Start date">
                <span class="dw-filter-sep">to</span>
                <input type="date" name="pend_date_end" class="form-control"
                    value="{{ $pendingDateEndStr ?? '' }}"
                    style="width:150px;"
                    title="End date (leave blank for single day)">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-funnel-fill"></i> Filter
                </button>
                @if ($pendingDateEndStr)
                    <a href="{{ route('admin.dashboard', ['pend_date' => today()->toDateString()]) }}"
                        class="btn btn-outline btn-sm" title="Reset to today">
                        <i class="bi bi-x-circle"></i>
                    </a>
                @endif
            </form>
        </div>

        @if ($pendingCandidates->isEmpty())
            <div class="dw-empty">
                <i class="bi bi-check2-circle" style="color:#16a34a;opacity:1;"></i>
                <p style="color:#16a34a;font-weight:500;">All candidates met their application targets for this period!</p>
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
                                <td class="text-sm text-muted">{{ $c->recruiter?->name ?? '—' }}</td>
                            @endif
                            <td style="text-align:right;"><span class="text-sm text-muted">{{ number_format($c->no_of_applications) }}/day</span></td>
                            <td style="text-align:right;"><span class="badge badge-target">{{ number_format($c->pending_target) }}</span></td>
                            <td style="text-align:right;"><span class="badge badge-logged">{{ number_format($c->pending_logged) }}</span></td>
                            <td style="text-align:right;"><span class="badge badge-gap">–{{ number_format($c->pending_gap) }}</span></td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr style="border-top:2px solid var(--border);">
                            <td colspan="{{ $isRecruiter ? 3 : 4 }}" style="text-align:right;font-weight:600;font-size:12px;color:var(--text-muted);padding:8px 10px;">Totals</td>
                            <td style="text-align:right;padding:8px 10px;"><span class="badge badge-logged">{{ number_format($pendingCandidates->sum('pending_logged')) }}</span></td>
                            <td style="text-align:right;padding:8px 10px;"><span class="badge badge-gap">–{{ number_format($pendingCandidates->sum('pending_gap')) }}</span></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </div>
</div>

{{-- ── Manager context header ──────────────────────────────────────────── --}}
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

{{-- ── Row 2: Top Performance (all roles) + Attention Required ─────────────── --}}
<div class="dw-grid-2">

    {{-- Top Performance --}}
    <div class="card">
        <div class="dw-head">
            <div>
                <div class="dw-title"><i class="bi bi-trophy-fill" style="color:#f59e0b;"></i> Top Performance</div>
                <div class="dw-sub">Ranked by valid interview count</div>
            </div>
            <select class="dw-top-select" onchange="filterTopRecruiters(this.value)">
                <option value="5">Top 5</option>
                <option value="10">Top 10</option>
            </select>
        </div>

        @if ($topRecruiters->isEmpty())
            <div class="dw-empty"><i class="bi bi-person-dash"></i><p>No recruiter data available.</p></div>
        @else
            <table class="dw-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Recruiter</th>
                        <th style="text-align:right;">Valid Interviews</th>
                    </tr>
                </thead>
                <tbody id="topRecruiterRows">
                    @foreach ($topRecruiters as $index => $r)
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
                                    <div class="avatar-sub">{{ $r->candidates_count }} candidate{{ $r->candidates_count !== 1 ? 's' : '' }}</div>
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

    {{-- Attention Required --}}
    <div class="card">
        <div class="dw-head">
            <div>
                <div class="dw-title"><i class="bi bi-exclamation-triangle-fill" style="color:#ef4444;"></i> Attention Required</div>
                <div class="dw-sub">Active candidates with no interview in last 20 days</div>
            </div>
            <span class="badge badge-danger" style="font-size:12px;">{{ $attentionCandidates->count() }}</span>
        </div>

        @if ($attentionCandidates->isEmpty())
            <div class="dw-empty"><i class="bi bi-check-circle-fill" style="color:#16a34a;opacity:1;"></i><p style="color:#16a34a;font-weight:500;">All candidates are up to date!</p></div>
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
                                <td class="text-sm text-muted">{{ $c->recruiter?->name ?? '—' }}</td>
                            @endif
                            <td class="text-sm text-muted">{{ $c->domain ?: '—' }}</td>
                            <td>
                                <span class="dw-days-ago">
                                    {{ $c->created_at->diffForHumans() }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

{{-- ── Row 3: Trending Domains + Interviews side-by-side ─────────────────── --}}
<div class="dw-grid-2">

    {{-- Trending Domains --}}
    <div class="card">
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

    {{-- Interviews (Today & Tomorrow) --}}
    <div class="card">
        <div class="dw-head">
            <div>
                <div class="dw-title"><i class="bi bi-calendar2-event-fill" style="color:var(--blue);"></i> Interviews</div>
                <div class="dw-sub">Today and tomorrow's scheduled interviews</div>
            </div>
            <span class="badge badge-primary">
                {{ $todayInterviews->count() + $tomorrowInterviews->count() }} total
            </span>
        </div>

        <div class="dw-day-tabs">
            <button class="dw-day-tab active" onclick="switchDayTab('today', this)">
                Today
                @if ($todayInterviews->count())
                    <span class="badge badge-primary" style="margin-left:5px;font-size:11px;">{{ $todayInterviews->count() }}</span>
                @endif
            </button>
            <button class="dw-day-tab" onclick="switchDayTab('tomorrow', this)">
                Tomorrow
                @if ($tomorrowInterviews->count())
                    <span class="badge badge-info" style="margin-left:5px;font-size:11px;">{{ $tomorrowInterviews->count() }}</span>
                @endif
            </button>
        </div>

        {{-- Today panel --}}
        <div id="panel-today" class="dw-day-panel show">
            @if ($todayInterviews->isEmpty())
                <div class="dw-empty"><i class="bi bi-calendar-x"></i><p>No interviews today.</p></div>
            @else
                <div style="overflow-x:auto;">
                    <table class="dw-table">
                        <thead>
                            <tr>
                                <th>Candidate</th>
                                <th>Company</th>
                                <th>Time</th>
                                @if(!$isRecruiter)<th>Recruiter</th>@endif
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
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-sm" style="font-size:12px;">{{ $iv->company_name }}</td>
                                <td class="text-sm text-muted" style="font-size:12px;">
                                    @if ($iv->scheduled_time)
                                        {{ \Carbon\Carbon::parse($iv->scheduled_time)->format('h:i A') }}
                                        @if ($iv->scheduled_timezone)<span style="font-size:11px;"> {{ $iv->scheduled_timezone }}</span>@endif
                                    @else
                                        <span style="opacity:.5;">TBD</span>
                                    @endif
                                </td>
                                @if(!$isRecruiter)
                                    <td class="text-sm text-muted" style="font-size:12px;">{{ $iv->recruiter?->name ?? '—' }}</td>
                                @endif
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- Tomorrow panel --}}
        <div id="panel-tomorrow" class="dw-day-panel">
            @if ($tomorrowInterviews->isEmpty())
                <div class="dw-empty"><i class="bi bi-calendar-check"></i><p>No interviews tomorrow.</p></div>
            @else
                <div style="overflow-x:auto;">
                    <table class="dw-table">
                        <thead>
                            <tr>
                                <th>Candidate</th>
                                <th>Company</th>
                                <th>Time</th>
                                @if(!$isRecruiter)<th>Recruiter</th>@endif
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
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-sm" style="font-size:12px;">{{ $iv->company_name }}</td>
                                <td class="text-sm text-muted" style="font-size:12px;">
                                    @if ($iv->scheduled_time)
                                        {{ \Carbon\Carbon::parse($iv->scheduled_time)->format('h:i A') }}
                                        @if ($iv->scheduled_timezone)<span style="font-size:11px;"> {{ $iv->scheduled_timezone }}</span>@endif
                                    @else
                                        <span style="opacity:.5;">TBD</span>
                                    @endif
                                </td>
                                @if(!$isRecruiter)
                                    <td class="text-sm text-muted" style="font-size:12px;">{{ $iv->recruiter?->name ?? '—' }}</td>
                                @endif
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
// ── Day tab toggle ────────────────────────────────────────────────────────
function switchDayTab(day, btn) {
    document.querySelectorAll('.dw-day-tab').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.dw-day-panel').forEach(p => p.classList.remove('show'));
    btn.classList.add('active');
    document.getElementById('panel-' + day).classList.add('show');
}

// ── Top recruiter filter (Top 5 / Top 10) ────────────────────────────────
function filterTopRecruiters(limit) {
    var rows = document.querySelectorAll('.top-rec-row');
    rows.forEach(function(row) {
        var rank = parseInt(row.getAttribute('data-rank'), 10);
        row.style.display = rank <= parseInt(limit) ? '' : 'none';
    });
}
// Apply default Top 5 on load
document.addEventListener('DOMContentLoaded', function() {
    filterTopRecruiters(5);
});
</script>

@endsection
