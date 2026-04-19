@extends('layouts.admin')
@section('title', $user->name . ' — Monthly Report')
@section('module-title', 'Monthly Report')
@section('module-description', 'Applications, assistant activity, and scheduled interviews for the selected month.')
@section('content')

{{-- ── Back + Header ─────────────────────────────────────────────── --}}
<div class="d-flex align-center gap-12 mb-20" style="flex-wrap:wrap;">
    <a href="{{ route('admin.users') }}" class="btn btn-outline btn-sm">
        <i class="bi bi-arrow-left"></i> Back
    </a>
    <div style="flex:1;min-width:0;">
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
            <div class="avatar-sm" style="width:40px;height:40px;font-size:16px;border-radius:50%;background:var(--blue);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;flex-shrink:0;">
                {{ $user->initials }}
            </div>
            <div>
                <div style="font-size:18px;font-weight:700;color:var(--text-primary);line-height:1.2;">
                    {{ $user->name }}
                    <span class="badge {{ $user->role_badge_color }}" style="margin-left:6px;font-size:11px;vertical-align:middle;">{{ $user->role_label }}</span>
                </div>
                <div style="font-size:12.5px;color:var(--text-muted);margin-top:2px;">{{ $user->email }}</div>
            </div>
        </div>
    </div>

    {{-- Month selector --}}
    <form method="GET" action="{{ route('admin.users.report', $user) }}" style="display:flex;align-items:center;gap:8px;">
        <label style="font-size:13px;font-weight:500;color:var(--text-secondary);white-space:nowrap;">
            <i class="bi bi-calendar3"></i> Month:
        </label>
        <select name="month" class="form-control" style="width:160px;" onchange="this.form.submit()">
            @foreach ($months as $val => $label)
                <option value="{{ $val }}" @selected($month === $val)>{{ $label }}</option>
            @endforeach
        </select>
    </form>
</div>

{{-- ── Summary stats ───────────────────────────────────────────────── --}}
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px;">
    <div class="card" style="padding:16px 18px;text-align:center;">
        <div style="font-size:26px;font-weight:700;color:var(--blue);">{{ $totals['applications'] }}</div>
        <div style="font-size:11.5px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:var(--text-muted);margin-top:4px;">Applications</div>
    </div>
    <div class="card" style="padding:16px 18px;text-align:center;">
        <div style="font-size:26px;font-weight:700;color:#8b5cf6;">{{ $totals['assistant'] }}</div>
        <div style="font-size:11.5px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:var(--text-muted);margin-top:4px;">Assistant</div>
    </div>
    <div class="card" style="padding:16px 18px;text-align:center;">
        <div style="font-size:26px;font-weight:700;color:#16a34a;">{{ $totals['log_interviews'] }}</div>
        <div style="font-size:11.5px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:var(--text-muted);margin-top:4px;">Log Interviews</div>
    </div>
    <div class="card" style="padding:16px 18px;text-align:center;">
        <div style="font-size:26px;font-weight:700;color:#f59e0b;">{{ $interviews->count() }}</div>
        <div style="font-size:11.5px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:var(--text-muted);margin-top:4px;">Scheduled Interviews</div>
    </div>
</div>

{{-- ── Tab navigation ──────────────────────────────────────────────── --}}
<div class="modal-tabs" id="reportTabs" style="background:var(--card-bg);border:1px solid var(--border-color);border-radius:var(--radius) var(--radius) 0 0;padding:0 16px;margin-bottom:0;">
    <button type="button" class="modal-tab-btn active" id="tabBtnApps"
        onclick="switchReportTab('apps')">
        <i class="bi bi-bar-chart-fill"></i> Applications &amp; Assistant
    </button>
    <button type="button" class="modal-tab-btn" id="tabBtnInterviews"
        onclick="switchReportTab('interviews')">
        <i class="bi bi-calendar-check-fill"></i> Interviews
        @if($interviews->count())
            <span class="badge badge-info" style="margin-left:6px;font-size:10px;padding:1px 6px;">{{ $interviews->count() }}</span>
        @endif
    </button>
</div>

{{-- ════ TAB: Applications & Assistant ════ --}}
<div id="tabApps" style="background:var(--card-bg);border:1px solid var(--border-color);border-top:none;border-radius:0 0 var(--radius) var(--radius);padding:20px;">

    {{-- Chart --}}
    <div style="position:relative;height:260px;margin-bottom:20px;">
        <canvas id="activityChart"></canvas>
    </div>

    {{-- Daily detail table --}}
    @if (count($detailRows) > 0)
    <div class="table-wrapper" style="margin-top:0;">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Day</th>
                    <th style="text-align:center;">Applications</th>
                    <th style="text-align:center;">Assistant</th>
                    <th style="text-align:center;">Log Interviews</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($detailRows as $row)
                <tr>
                    <td class="text-sm">{{ $row['date'] }}</td>
                    <td class="text-sm text-muted">{{ $row['day_name'] }}</td>
                    <td style="text-align:center;">
                        @if ($row['applications'] > 0)
                            <span class="badge badge-info">{{ $row['applications'] }}</span>
                        @else
                            <span class="text-muted text-sm">—</span>
                        @endif
                    </td>
                    <td style="text-align:center;">
                        @if ($row['assistant'] > 0)
                            <span class="badge" style="background:var(--purple-light);color:var(--purple-text);">{{ $row['assistant'] }}</span>
                        @else
                            <span class="text-muted text-sm">—</span>
                        @endif
                    </td>
                    <td style="text-align:center;">
                        @if ($row['interviews'] > 0)
                            <span class="badge badge-success">{{ $row['interviews'] }}</span>
                        @else
                            <span class="text-muted text-sm">—</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background:var(--main-bg);font-weight:600;">
                    <td colspan="2" class="text-sm">Total</td>
                    <td style="text-align:center;"><span class="badge badge-info">{{ $totals['applications'] }}</span></td>
                    <td style="text-align:center;"><span class="badge" style="background:var(--purple-light);color:var(--purple-text);">{{ $totals['assistant'] }}</span></td>
                    <td style="text-align:center;"><span class="badge badge-success">{{ $totals['log_interviews'] }}</span></td>
                </tr>
            </tfoot>
        </table>
    </div>
    @else
        <div style="text-align:center;padding:36px 0;color:var(--text-muted);">
            <i class="bi bi-bar-chart" style="font-size:32px;margin-bottom:10px;display:block;"></i>
            No daily log activity recorded for {{ $startDate->format('F Y') }}.
        </div>
    @endif
</div>

{{-- ════ TAB: Interviews ════ --}}
<div id="tabInterviews" style="display:none;background:var(--card-bg);border:1px solid var(--border-color);border-top:none;border-radius:0 0 var(--radius) var(--radius);padding:20px;">

    @if ($interviews->isEmpty())
        <div style="text-align:center;padding:36px 0;color:var(--text-muted);">
            <i class="bi bi-calendar-x" style="font-size:32px;margin-bottom:10px;display:block;"></i>
            No interviews scheduled for {{ $startDate->format('F Y') }}.
        </div>
    @else
        <div class="table-wrapper" style="margin-top:0;">
            <table>
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
                        $typeColors = ['phone_call' => 'badge-info', 'virtual' => 'badge-primary', 'on_site' => 'badge-warning'];
                    @endphp
                    <tr>
                        <td class="text-sm">
                            {{ $iv->scheduled_date?->format('M d, Y') ?? '—' }}
                        </td>
                        <td class="text-sm text-muted">
                            @if ($iv->scheduled_time && $iv->scheduled_timezone)
                                {{ \Carbon\Carbon::parse($iv->scheduled_time)->format('g:i A') }}
                                <span style="font-size:10.5px;background:var(--main-bg);padding:1px 5px;border-radius:3px;">{{ $iv->scheduled_timezone }}</span>
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            @if ($iv->candidate)
                                <a href="{{ route('admin.candidates.show', $iv->candidate) }}"
                                   style="color:var(--blue);font-weight:500;font-size:13px;">
                                    {{ $iv->candidate->full_name }}
                                </a>
                            @else
                                <span class="text-muted text-sm">—</span>
                            @endif
                        </td>
                        <td class="text-sm">
                            @if ($iv->company_domain)
                                <a href="{{ $iv->company_domain }}" target="_blank" rel="noopener"
                                   style="color:var(--blue);" title="{{ $iv->company_domain }}">
                                    {{ $iv->company_name }}
                                </a>
                            @else
                                {{ $iv->company_name }}
                            @endif
                        </td>
                        <td class="text-sm">{{ $iv->role }}</td>
                        <td>
                            <span class="badge {{ $typeColors[$iv->interview_type] ?? 'badge-neutral' }}" style="font-size:10.5px;">
                                {{ $typeLabels[$iv->interview_type] ?? ucfirst($iv->interview_type) }}
                            </span>
                        </td>
                        <td>
                            <span class="badge {{ $iv->interview_status === 'valid' ? 'badge-success' : 'badge-danger' }}" style="font-size:10.5px;">
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

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<script>
(function () {
    /* ── Tab switching ── */
    window.switchReportTab = function (tab) {
        document.getElementById('tabApps').style.display        = tab === 'apps'       ? 'block' : 'none';
        document.getElementById('tabInterviews').style.display  = tab === 'interviews' ? 'block' : 'none';
        document.getElementById('tabBtnApps').classList.toggle('active',       tab === 'apps');
        document.getElementById('tabBtnInterviews').classList.toggle('active', tab === 'interviews');
    };

    /* ── Bar chart ── */
    var ctx = document.getElementById('activityChart');
    if (!ctx || typeof Chart === 'undefined') return;

    var isDark    = document.documentElement.getAttribute('data-theme') === 'dark';
    var gridColor = isDark ? 'rgba(255,255,255,0.07)' : 'rgba(0,0,0,0.05)';
    var tickColor = isDark ? '#94a3b8' : '#64748b';

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: @json($chartLabels),
            datasets: [
                {
                    label: 'Applications',
                    data: @json($chartApps),
                    backgroundColor: 'rgba(37,99,235,0.75)',
                    borderColor: '#2563eb',
                    borderWidth: 1,
                    borderRadius: 3,
                },
                {
                    label: 'Assistant',
                    data: @json($chartAssistant),
                    backgroundColor: 'rgba(139,92,246,0.70)',
                    borderColor: '#8b5cf6',
                    borderWidth: 1,
                    borderRadius: 3,
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
                    labels: { color: tickColor, boxWidth: 12, padding: 16 }
                },
                tooltip: { mode: 'index', intersect: false }
            },
            scales: {
                x: {
                    grid: { color: gridColor },
                    ticks: {
                        color: tickColor,
                        maxRotation: 0,
                        callback: function (val, idx) {
                            // Show every 5th label to avoid crowding on small screens
                            return (idx + 1) % 5 === 1 || (idx + 1) === @json($daysInMonth) ? (idx + 1) : '';
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
