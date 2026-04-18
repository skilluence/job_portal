@extends('layouts.admin')
@section('title', 'Dashboard')
@section('module-title', 'Dashboard')
@section('module-description', 'Real-time analytics for candidates, recruiters, and daily performance.')
@section('content')

<div class="stats-grid mb-24">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="bi bi-people-fill"></i></div>
        <div>
            <div class="stat-value">{{ $stats['active_candidates'] }}</div>
            <div class="stat-label">Active Candidates</div>
        </div>
    </div>
    @if ($isAdmin)
    <div class="stat-card">
        <div class="stat-icon green"><i class="bi bi-person-check-fill"></i></div>
        <div>
            <div class="stat-value">{{ $stats['active_recruiters'] }}</div>
            <div class="stat-label">Active Recruiters</div>
        </div>
    </div>
    @endif
    <div class="stat-card">
        <div class="stat-icon orange"><i class="bi bi-file-earmark-text-fill"></i></div>
        <div>
            <div class="stat-value">{{ $stats['today_applications'] }}</div>
            <div class="stat-label">Today's Applications</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple"><i class="bi bi-calendar-check-fill"></i></div>
        <div>
            <div class="stat-value">{{ $stats['today_interviews'] }}</div>
            <div class="stat-label">Today's Interviews</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue"><i class="bi bi-calendar2-event-fill"></i></div>
        <div>
            <div class="stat-value">{{ $stats['today_scheduled_interviews'] }}</div>
            <div class="stat-label">Today Scheduled Interview</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="bi bi-hourglass-split"></i></div>
        <div>
            <div class="stat-value">{{ $stats['today_pending_applications'] }}</div>
            <div class="stat-label">Today's Pending Application</div>
        </div>
    </div>
</div>

@if ($isAdmin)
<div class="content-grid mb-24">
    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title">Top Recruiters</div>
                <div class="card-subtitle">Ranked by applications this month</div>
            </div>
        </div>
        @forelse ($topPerformers as $index => $performer)
            <div class="d-flex align-center gap-12 {{ $index < $topPerformers->count() - 1 ? 'mb-12' : '' }}">
                <span class="text-sm text-muted font-600" style="width:18px;text-align:right;flex-shrink:0;">{{ $index + 1 }}</span>
                <div class="avatar-sm">{{ $performer->initials }}</div>
                <div style="flex:1;min-width:0;">
                    <div class="avatar-name">{{ $performer->name }}</div>
                    <div class="avatar-sub">{{ $performer->candidates_count }} total candidates</div>
                </div>
                <span class="badge badge-primary">{{ number_format($performer->monthly_applications ?? 0) }} apps</span>
            </div>
        @empty
            <div class="page-empty mb-0">
                <i class="bi bi-person-badge"></i>
                <p>No recruiter data available.</p>
            </div>
        @endforelse
    </div>

    <div class="card">
        <div class="card-header" style="align-items:flex-start;flex-direction:column;gap:10px;">
            <div>
                <div class="card-title">Charts — {{ $rangeLabel }}</div>
                <div class="card-subtitle">Candidate trend and status distribution</div>
            </div>
            <form method="GET" action="{{ route('admin.dashboard') }}" class="d-flex gap-6" style="flex-wrap:wrap;align-items:center;" id="chartRangeForm">
                <div class="range-btn-group">
                    @foreach (['today'=>'Today','week'=>'Week','month'=>'Month','year'=>'Year'] as $val => $label)
                        <button type="submit" name="range" value="{{ $val }}"
                            class="range-btn {{ $activeRange === $val ? 'active' : '' }}">{{ $label }}</button>
                    @endforeach
                    <button type="button" class="range-btn {{ $activeRange === 'custom' ? 'active' : '' }}"
                        onclick="document.getElementById('customRangeRow').classList.toggle('hidden')">Custom</button>
                </div>
                <div id="customRangeRow" class="d-flex gap-6 align-center {{ $activeRange !== 'custom' ? 'hidden' : '' }}">
                    <input type="hidden" name="range" value="custom" id="customRangeInput">
                    <input type="date" name="custom_start" class="form-control" style="width:140px;"
                        value="{{ $customStart }}">
                    <span class="text-muted text-sm">to</span>
                    <input type="date" name="custom_end" class="form-control" style="width:140px;"
                        value="{{ $customEnd }}">
                    <button type="submit" class="btn btn-primary btn-sm">Go</button>
                </div>
            </form>
        </div>
        <div style="height:200px;margin-bottom:16px;">
            <canvas id="candidateTrendChart"></canvas>
        </div>
        <div style="height:200px;">
            <canvas id="candidateStatusChart"></canvas>
        </div>
    </div>
</div>
@else
{{-- Recruiter: just charts --}}
<div class="card mb-24">
    <div class="card-header" style="align-items:flex-start;flex-direction:column;gap:10px;">
        <div>
            <div class="card-title">Charts — {{ $rangeLabel }}</div>
            <div class="card-subtitle">Your candidate trend and status distribution</div>
        </div>
        <form method="GET" action="{{ route('admin.dashboard') }}" class="d-flex gap-6" style="flex-wrap:wrap;align-items:center;">
            <div class="range-btn-group">
                @foreach (['today'=>'Today','week'=>'Week','month'=>'Month','year'=>'Year'] as $val => $label)
                    <button type="submit" name="range" value="{{ $val }}"
                        class="range-btn {{ $activeRange === $val ? 'active' : '' }}">{{ $label }}</button>
                @endforeach
                <button type="button" class="range-btn {{ $activeRange === 'custom' ? 'active' : '' }}"
                    onclick="document.getElementById('customRangeRow').classList.toggle('hidden')">Custom</button>
            </div>
            <div id="customRangeRow" class="d-flex gap-6 align-center {{ $activeRange !== 'custom' ? 'hidden' : '' }}">
                <input type="hidden" name="range" value="custom">
                <input type="date" name="custom_start" class="form-control" style="width:140px;" value="{{ $customStart }}">
                <span class="text-muted text-sm">to</span>
                <input type="date" name="custom_end" class="form-control" style="width:140px;" value="{{ $customEnd }}">
                <button type="submit" class="btn btn-primary btn-sm">Go</button>
            </div>
        </form>
    </div>
    <div class="content-grid" style="padding:0 16px 16px;">
        <div style="height:220px;"><canvas id="candidateTrendChart"></canvas></div>
        <div style="height:220px;"><canvas id="candidateStatusChart"></canvas></div>
    </div>
</div>
@endif

<div class="card mb-24">
    <div class="card-header">
        <div>
            <div class="card-title">{{ $rangeLabel }} — New Candidates</div>
            <div class="card-subtitle">Candidates added in this period</div>
        </div>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Candidate Name</th>
                    <th>Status</th>
                    @if ($isAdmin)<th>Recruiter</th>@endif
                    <th>Added On</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($last7DaysCandidates as $i => $c)
                    <tr>
                        <td class="text-muted text-sm">{{ $i + 1 }}</td>
                        <td class="avatar-name">{{ $c->full_name }}</td>
                        <td><span class="badge {{ $c->status_badge }}">{{ ucfirst($c->status) }}</span></td>
                        @if ($isAdmin)<td class="text-muted text-sm">{{ $c->recruiter?->name ?? '-' }}</td>@endif
                        <td class="text-muted text-sm">{{ $c->created_at->format('M d, Y') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $isAdmin ? 5 : 4 }}">
                            <div class="page-empty mb-0">
                                <i class="bi bi-person-plus"></i>
                                <p>No candidates added in this period.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if ($isAdmin)
<div class="card mb-24">
    <div class="card-header">
        <div>
            <div class="card-title">Recruiter Performance</div>
            <div class="card-subtitle">Candidates, applications, interviews, and placement outcomes</div>
        </div>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Recruiter</th>
                    <th>Candidates</th>
                    <th>Applications</th>
                    <th>Interviews</th>
                    <th>Placed</th>
                    <th>Success Rate</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($recruiterPerformance as $recruiter)
                    <tr>
                        <td>
                            <div class="avatar-row">
                                <div class="avatar-sm">{{ $recruiter->initials }}</div>
                                <div>
                                    <div class="avatar-name">{{ $recruiter->name }}</div>
                                    <div class="avatar-sub">{{ $recruiter->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td>{{ $recruiter->candidates_count }}</td>
                        <td>{{ number_format($recruiter->candidates_sum_no_of_applications ?? 0) }}</td>
                        <td>{{ number_format($recruiter->candidates_sum_interviews_count ?? 0) }}</td>
                        <td>{{ $recruiter->placed_count }}</td>
                        <td>{{ $recruiter->success_rate }}%</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="page-empty mb-0">
                                <i class="bi bi-people"></i>
                                <p>No recruiter data found.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endif

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<script>
(function () {
    var trendCtx  = document.getElementById('candidateTrendChart');
    var statusCtx = document.getElementById('candidateStatusChart');
    if (!trendCtx || !statusCtx || typeof Chart === 'undefined') return;

    var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    var gridColor = isDark ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.06)';
    var tickColor = isDark ? '#94a3b8' : '#64748b';

    new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: @json($trendChartLabels),
            datasets: [{
                label: 'Candidates',
                data: @json($trendChartData),
                borderColor: '#2563eb',
                backgroundColor: 'rgba(37,99,235,0.12)',
                fill: true,
                tension: 0.35,
                pointRadius: 3,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { color: gridColor }, ticks: { color: tickColor } },
                y: { grid: { color: gridColor }, ticks: { color: tickColor, stepSize: 1 }, beginAtZero: true }
            }
        }
    });

    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: @json($statusChartLabels),
            datasets: [{
                data: @json($statusChartData),
                backgroundColor: ['#2563eb','#16a34a','#f59e0b','#8b5cf6','#0ea5e9','#ef4444','#64748b']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'right', labels: { color: tickColor, boxWidth: 12 } } }
        }
    });

    // Custom range toggle: ensure the hidden input gets the right value
    var customBtn = document.querySelector('.range-btn[onclick]');
    var customRow = document.getElementById('customRangeRow');
    var hiddenRangeInput = document.getElementById('customRangeInput');
    if (customBtn && customRow && hiddenRangeInput) {
        customBtn.addEventListener('click', function () {
            var isOpen = !customRow.classList.contains('hidden');
            if (isOpen) hiddenRangeInput.disabled = false;
        });
    }
})();
</script>

@endsection
