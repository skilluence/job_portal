@extends('layouts.admin')
@section('title', $user->name . ' — Monthly Report')
@section('module-title', 'Monthly Report')
@section('module-description', 'Applications, assisment activity, and scheduled interviews for the selected month.')
@section('content')

<style>
/* ── Report header card ─────────────────────────────────── */
.rpt-header {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: var(--radius);
    padding: 20px 24px;
    display: flex;
    align-items: center;
    gap: 20px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}
.rpt-avatar {
    width: 56px; height: 56px; border-radius: 50%;
    background: linear-gradient(135deg, var(--blue), #3b82f6);
    color: #fff; font-size: 20px; font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; text-transform: uppercase;
    box-shadow: 0 3px 10px rgba(37,99,235,0.25);
}
.rpt-user-info { flex: 1; min-width: 0; }
.rpt-user-name {
    font-size: 18px; font-weight: 700;
    color: var(--text-primary); line-height: 1.2;
    display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
}
.rpt-user-meta {
    font-size: 12.5px; color: var(--text-muted);
    margin-top: 5px; display: flex; gap: 14px; flex-wrap: wrap;
}
.rpt-user-meta span { display: flex; align-items: center; gap: 4px; }

/* ── Month picker ───────────────────────────────────────── */
.rpt-month-form { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }

/* ── Stat cards ─────────────────────────────────────────── */
.rpt-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
    margin-bottom: 20px;
}
.rpt-stat {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: var(--radius);
    padding: 16px 18px;
    display: flex;
    align-items: center;
    gap: 14px;
    transition: box-shadow 0.15s;
}
.rpt-stat:hover { box-shadow: 0 2px 12px rgba(0,0,0,0.08); }
.rpt-stat-icon {
    width: 44px; height: 44px; border-radius: var(--radius-sm);
    display: flex; align-items: center; justify-content: center;
    font-size: 20px; flex-shrink: 0;
}
.rpt-stat-body { min-width: 0; }
.rpt-stat-val { font-size: 24px; font-weight: 700; line-height: 1.1; }
.rpt-stat-lbl { font-size: 11.5px; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; color: var(--text-muted); margin-top: 3px; }

/* ── Tab bar ────────────────────────────────────────────── */
.rpt-tabs {
    display: flex;
    gap: 0;
    border-bottom: 2px solid var(--border-color);
    margin-bottom: 0;
}
.rpt-tab {
    padding: 10px 22px;
    font-size: 13.5px; font-weight: 500;
    color: var(--text-secondary);
    border: none; border-bottom: 2px solid transparent;
    background: transparent; cursor: pointer;
    margin-bottom: -2px;
    transition: color 0.15s, border-color 0.15s;
    display: flex; align-items: center; gap: 7px;
    font-family: inherit;
}
.rpt-tab:hover { color: var(--text-primary); }
.rpt-tab.active { color: var(--blue); border-bottom-color: var(--blue); font-weight: 600; }

/* ── Tab content wrapper ────────────────────────────────── */
.rpt-tab-body {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-top: none;
    border-radius: 0 0 var(--radius) var(--radius);
    padding: 22px;
}
.rpt-tab-panel { display: none; }
.rpt-tab-panel.active { display: block; }

/* ── Chart wrapper ──────────────────────────────────────── */
.rpt-chart-wrap {
    position: relative;
    height: 280px;
    margin-bottom: 22px;
}

/* ── Detail table ───────────────────────────────────────── */
.rpt-tbl th { font-size: 11.5px; }
.rpt-tbl td { font-size: 13px; }

/* Responsive */
@media (max-width: 900px) {
    .rpt-stats { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 600px) {
    .rpt-stats { grid-template-columns: 1fr 1fr; }
    .rpt-header { flex-direction: column; align-items: flex-start; }
}
</style>

{{-- ── Back ─────────────────────────────────────────────────────── --}}
<div class="mb-16">
    <a href="{{ route('admin.users') }}" class="btn btn-outline btn-sm">
        <i class="bi bi-arrow-left"></i> Back to Recruiters
    </a>
</div>

{{-- ── Header card ─────────────────────────────────────────────── --}}
<div class="rpt-header">
    <div class="rpt-avatar">{{ $user->initials }}</div>
    <div class="rpt-user-info">
        <div class="rpt-user-name">
            {{ $user->name }}
            <span class="badge {{ $user->role_badge_color }}">{{ $user->role_label }}</span>
            <span class="badge {{ $user->status === 'active' ? 'badge-success' : 'badge-danger' }}" style="font-size:10.5px;">{{ ucfirst($user->status) }}</span>
        </div>
        <div class="rpt-user-meta">
            <span><i class="bi bi-envelope"></i> {{ $user->email }}</span>
            @if ($user->teamManager)
                <span><i class="bi bi-person-badge"></i> Manager: {{ $user->teamManager->name }}</span>
            @endif
            <span><i class="bi bi-people"></i> {{ $user->candidates_count ?? 0 }} candidates</span>
        </div>
    </div>
    {{-- Month selector --}}
    <form method="GET" action="{{ route('admin.users.report', $user) }}" class="rpt-month-form">
        <label style="font-size:12.5px;font-weight:500;color:var(--text-secondary);white-space:nowrap;">
            <i class="bi bi-calendar3"></i> Month:
        </label>
        <select name="month" class="form-control" style="width:152px;" onchange="this.form.submit()">
            @foreach ($months as $val => $label)
                <option value="{{ $val }}" @selected($month === $val)>{{ $label }}</option>
            @endforeach
        </select>
    </form>
</div>

{{-- ── Stat cards ──────────────────────────────────────────────── --}}
<div class="rpt-stats">
    <div class="rpt-stat">
        <div class="rpt-stat-icon" style="background:var(--blue-light);">
            <i class="bi bi-send-fill" style="color:var(--blue);"></i>
        </div>
        <div class="rpt-stat-body">
            <div class="rpt-stat-val" style="color:var(--blue);">{{ $totals['applications'] }}</div>
            <div class="rpt-stat-lbl">Applications</div>
        </div>
    </div>
    <div class="rpt-stat">
        <div class="rpt-stat-icon" style="background:var(--purple-light);">
            <i class="bi bi-people-fill" style="color:var(--purple-text);"></i>
        </div>
        <div class="rpt-stat-body">
            <div class="rpt-stat-val" style="color:var(--purple-text);">{{ $totals['assisment'] }}</div>
            <div class="rpt-stat-lbl">Assisment</div>
        </div>
    </div>
    <div class="rpt-stat">
        <div class="rpt-stat-icon" style="background:var(--green-light);">
            <i class="bi bi-graph-up-arrow" style="color:var(--green-text);"></i>
        </div>
        <div class="rpt-stat-body">
            <div class="rpt-stat-val" style="color:var(--green-text);">{{ $totals['log_interviews'] }}</div>
            <div class="rpt-stat-lbl">Log Interviews</div>
        </div>
    </div>
    <div class="rpt-stat">
        <div class="rpt-stat-icon" style="background:var(--yellow-light);">
            <i class="bi bi-calendar-check-fill" style="color:var(--yellow-text);"></i>
        </div>
        <div class="rpt-stat-body">
            <div class="rpt-stat-val" style="color:var(--yellow-text);">{{ $interviews->count() }}</div>
            <div class="rpt-stat-lbl">Scheduled</div>
        </div>
    </div>
</div>

{{-- ── Tabs ─────────────────────────────────────────────────────── --}}
<div class="rpt-tabs">
    <button type="button" class="rpt-tab active" id="tabBtnApps" onclick="switchRptTab('apps')">
        <i class="bi bi-bar-chart-fill"></i> Applications &amp; Assisment
    </button>
    <button type="button" class="rpt-tab" id="tabBtnInterviews" onclick="switchRptTab('interviews')">
        <i class="bi bi-calendar2-check-fill"></i> Interviews
        @if ($interviews->count())
            <span class="badge badge-warning" style="font-size:10px;padding:1px 7px;">{{ $interviews->count() }}</span>
        @endif
    </button>
</div>

<div class="rpt-tab-body">

    {{-- ════ TAB: Applications & Assisment ════ --}}
    <div id="tabApps" class="rpt-tab-panel active">

        @if ($totals['applications'] === 0 && $totals['assisment'] === 0)
            <div style="text-align:center;padding:48px 0;color:var(--text-muted);">
                <i class="bi bi-bar-chart" style="font-size:36px;margin-bottom:12px;display:block;opacity:.4;"></i>
                <div style="font-size:14px;font-weight:500;">No activity recorded</div>
                <div style="font-size:12.5px;margin-top:4px;">No daily logs found for {{ $startDate->format('F Y') }}.</div>
            </div>
        @else
            {{-- Chart --}}
            <div class="rpt-chart-wrap">
                <canvas id="activityChart"></canvas>
            </div>
        @endif

        {{-- Daily detail table --}}
        @if (count($detailRows) > 0)
            <div style="font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);margin-bottom:10px;">
                Daily Breakdown — {{ $startDate->format('F Y') }}
            </div>
            <div class="table-wrapper" style="margin-top:0;">
                <table class="rpt-tbl">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Day</th>
                            <th style="text-align:center;">
                                <span style="color:var(--blue);">●</span> Applications
                            </th>
                            <th style="text-align:center;">
                                <span style="color:var(--purple-text);">●</span> Assisment
                            </th>
                            <th style="text-align:center;">
                                <span style="color:var(--green-text);">●</span> Interviews
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($detailRows as $row)
                        <tr>
                            <td style="white-space:nowrap;font-weight:500;">{{ $row['date'] }}</td>
                            <td class="text-muted" style="font-size:12px;">{{ $row['day_name'] }}</td>
                            <td style="text-align:center;">
                                @if ($row['applications'] > 0)
                                    <span class="badge badge-info" style="min-width:32px;">{{ $row['applications'] }}</span>
                                @else
                                    <span style="color:var(--text-muted);font-size:12px;">—</span>
                                @endif
                            </td>
                            <td style="text-align:center;">
                                @if ($row['assisment'] > 0)
                                    <span class="badge" style="background:var(--purple-light);color:var(--purple-text);min-width:32px;">{{ $row['assisment'] }}</span>
                                @else
                                    <span style="color:var(--text-muted);font-size:12px;">—</span>
                                @endif
                            </td>
                            <td style="text-align:center;">
                                @if ($row['interviews'] > 0)
                                    <span class="badge badge-success" style="min-width:32px;">{{ $row['interviews'] }}</span>
                                @else
                                    <span style="color:var(--text-muted);font-size:12px;">—</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr style="background:var(--main-bg);border-top:2px solid var(--border-color);">
                            <td colspan="2" style="font-weight:700;font-size:12px;">
                                Total ({{ count($detailRows) }} active day{{ count($detailRows) !== 1 ? 's' : '' }})
                            </td>
                            <td style="text-align:center;"><span class="badge badge-info" style="min-width:32px;font-weight:700;">{{ $totals['applications'] }}</span></td>
                            <td style="text-align:center;"><span class="badge" style="background:var(--purple-light);color:var(--purple-text);min-width:32px;font-weight:700;">{{ $totals['assisment'] }}</span></td>
                            <td style="text-align:center;"><span class="badge badge-success" style="min-width:32px;font-weight:700;">{{ $totals['log_interviews'] }}</span></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </div>

    {{-- ════ TAB: Interviews ════ --}}
    <div id="tabInterviews" class="rpt-tab-panel">

        @if ($interviews->isEmpty())
            <div style="text-align:center;padding:48px 0;color:var(--text-muted);">
                <i class="bi bi-calendar-x" style="font-size:36px;margin-bottom:12px;display:block;opacity:.4;"></i>
                <div style="font-size:14px;font-weight:500;">No interviews scheduled</div>
                <div style="font-size:12.5px;margin-top:4px;">No interviews found with a scheduled date in {{ $startDate->format('F Y') }}.</div>
            </div>
        @else
            <div class="table-wrapper" style="margin-top:0;">
                <table class="rpt-tbl">
                    <thead>
                        <tr>
                            <th>Scheduled Date</th>
                            <th>Time</th>
                            <th>Candidate</th>
                            <th>Company</th>
                            <th>Role</th>
                            <th>Type</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($interviews as $iv)
                        @php
                            $typeLabels = ['phone_call' => 'Phone Call', 'virtual' => 'Virtual', 'on_site' => 'On Site'];
                            $typeBadges = ['phone_call' => 'badge-info', 'virtual'  => 'badge-primary', 'on_site'  => 'badge-warning'];
                        @endphp
                        <tr>
                            <td style="white-space:nowrap;font-weight:500;">
                                {{ $iv->scheduled_date?->format('M d, Y') ?? '—' }}
                            </td>
                            <td class="text-muted" style="white-space:nowrap;font-size:12px;">
                                @if ($iv->scheduled_time && $iv->scheduled_timezone)
                                    {{ \Carbon\Carbon::parse($iv->scheduled_time)->format('g:i A') }}
                                    <span style="font-size:10px;background:var(--main-bg);padding:1px 5px;border-radius:3px;margin-left:3px;">{{ $iv->scheduled_timezone }}</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                @if ($iv->candidate)
                                    <a href="{{ route('admin.candidates.show', $iv->candidate) }}"
                                       style="color:var(--blue);font-weight:500;">
                                        {{ $iv->candidate->full_name }}
                                    </a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if ($iv->company_domain)
                                    <a href="{{ $iv->company_domain }}" target="_blank" rel="noopener"
                                       style="color:var(--blue);">{{ $iv->company_name }}</a>
                                @else
                                    {{ $iv->company_name }}
                                @endif
                            </td>
                            <td style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $iv->role }}">
                                {{ $iv->role }}
                            </td>
                            <td>
                                <span class="badge {{ $typeBadges[$iv->interview_type] ?? 'badge-neutral' }}" style="font-size:11px;">
                                    {{ $typeLabels[$iv->interview_type] ?? ucfirst($iv->interview_type) }}
                                </span>
                            </td>
                            <td>
                                <span class="badge {{ $iv->interview_status === 'valid' ? 'badge-success' : 'badge-danger' }}" style="font-size:11px;">
                                    {{ ucfirst($iv->interview_status) }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

</div>{{-- end rpt-tab-body --}}

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<script>
(function () {
    /* ── Tab switching ─────────────────────────────────────────── */
    window.switchRptTab = function (tab) {
        ['apps', 'interviews'].forEach(function (t) {
            var panel = document.getElementById('tabPanel' + t.charAt(0).toUpperCase() + t.slice(1))
                     || document.getElementById('tab' + t.charAt(0).toUpperCase() + t.slice(1));
            var btn   = document.getElementById('tabBtn' + t.charAt(0).toUpperCase() + t.slice(1));
            if (panel) panel.style.display = t === tab ? 'block' : 'none';
            if (btn)   btn.classList.toggle('active', t === tab);
        });
        // Also handle by id directly
        document.getElementById('tabApps').style.display        = tab === 'apps'       ? 'block' : 'none';
        document.getElementById('tabInterviews').style.display  = tab === 'interviews' ? 'block' : 'none';
        document.getElementById('tabBtnApps').classList.toggle('active',       tab === 'apps');
        document.getElementById('tabBtnInterviews').classList.toggle('active', tab === 'interviews');
    };

    /* ── Bar chart ─────────────────────────────────────────────── */
    var ctx = document.getElementById('activityChart');
    if (!ctx || typeof Chart === 'undefined') return;

    var isDark    = document.documentElement.getAttribute('data-theme') === 'dark';
    var gridColor = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.05)';
    var tickColor = isDark ? '#94a3b8' : '#64748b';
    var barRadius = 4;

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: @json($chartLabels),
            datasets: [
                {
                    label: 'Applications',
                    data: @json($chartApps),
                    backgroundColor: 'rgba(37,99,235,0.80)',
                    borderColor: '#2563eb',
                    borderWidth: 0,
                    borderRadius: barRadius,
                    borderSkipped: 'bottom',
                },
                {
                    label: 'Assisment',
                    data: @json($chartAssisment),
                    backgroundColor: 'rgba(139,92,246,0.75)',
                    borderColor: '#8b5cf6',
                    borderWidth: 0,
                    borderRadius: barRadius,
                    borderSkipped: 'bottom',
                },
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: {
                    position: 'top',
                    align: 'end',
                    labels: { color: tickColor, boxWidth: 12, padding: 18, usePointStyle: true, pointStyle: 'rectRounded' }
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    callbacks: {
                        title: function (ctx) { return 'Day ' + ctx[0].label; }
                    }
                }
            },
            scales: {
                x: {
                    grid: { color: gridColor },
                    ticks: {
                        color: tickColor,
                        maxRotation: 0,
                        callback: function (val, idx) {
                            var day = idx + 1;
                            // Show 1, 5, 10, 15, 20, 25, and last day
                            return (day === 1 || day % 5 === 0 || day === @json($daysInMonth)) ? day : '';
                        }
                    }
                },
                y: {
                    grid: { color: gridColor },
                    ticks: { color: tickColor, stepSize: 1 },
                    beginAtZero: true
                }
            }
        }
    });
})();
</script>
@endpush

@endsection
