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
    <div class="stat-card">
        <div class="stat-icon green"><i class="bi bi-person-check-fill"></i></div>
        <div>
            <div class="stat-value">{{ $stats['active_recruiters'] }}</div>
            <div class="stat-label">Active Recruiters</div>
        </div>
    </div>
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
        <div class="stat-icon blue"><i class="bi bi-people"></i></div>
        <div>
            <div class="stat-value">{{ $stats['total_candidates'] }}</div>
            <div class="stat-label">Total Candidates</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="bi bi-person-lines-fill"></i></div>
        <div>
            <div class="stat-value">{{ $stats['total_recruiters'] }}</div>
            <div class="stat-label">Total Recruiters</div>
        </div>
    </div>
</div>

<div class="content-grid mb-24">
    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title">Key Performance Indicators</div>
                <div class="card-subtitle">Conversion and daily productivity metrics</div>
            </div>
        </div>
        <div class="d-flex align-center justify-between mb-12">
            <span class="text-sm font-600">Conversion Rate</span>
            <span class="badge badge-primary">{{ $conversionRate }}%</span>
        </div>
        <div class="progress-wrap mb-16">
            <div class="progress-bar" style="width:{{ min($conversionRate, 100) }}%"></div>
        </div>

        <div class="d-flex align-center justify-between mb-10">
            <span class="text-sm">Active Candidates</span>
            <strong>{{ $stats['active_candidates'] }}</strong>
        </div>
        <div class="d-flex align-center justify-between mb-10">
            <span class="text-sm">Today's Applications</span>
            <strong>{{ $stats['today_applications'] }}</strong>
        </div>
        <div class="d-flex align-center justify-between">
            <span class="text-sm">Today's Interviews</span>
            <strong>{{ $stats['today_interviews'] }}</strong>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title">Top Recruiters</div>
                <div class="card-subtitle">Sorted by success rate</div>
            </div>
        </div>
        @forelse ($topPerformers as $index => $performer)
            <div class="d-flex align-center gap-12 {{ $index < $topPerformers->count() - 1 ? 'mb-12' : '' }}">
                <span class="text-sm text-muted font-600" style="width:18px;text-align:right;flex-shrink:0;">{{ $index + 1 }}</span>
                <div class="avatar-sm">{{ $performer->initials }}</div>
                <div style="flex:1;min-width:0;">
                    <div class="avatar-name">{{ $performer->name }}</div>
                    <div class="avatar-sub">{{ $performer->candidates_count }} candidates</div>
                </div>
                <span class="badge badge-success">{{ $performer->success_rate }}%</span>
            </div>
        @empty
            <div class="page-empty mb-0">
                <i class="bi bi-person-badge"></i>
                <p>No recruiter performance data available.</p>
            </div>
        @endforelse
    </div>
</div>

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

<div class="content-grid mb-24">
    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title">Last 7 Days Candidates</div>
                <div class="card-subtitle">New candidate records per day</div>
            </div>
        </div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>New Candidates</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($last7DaysRows as $row)
                        <tr>
                            <td>{{ $row['date'] }}</td>
                            <td>{{ $row['count'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title">Charts</div>
                <div class="card-subtitle">Candidate trend and status distribution</div>
            </div>
        </div>
        <div style="height:220px;margin-bottom:18px;">
            <canvas id="candidateTrendChart"></canvas>
        </div>
        <div style="height:220px;">
            <canvas id="candidateStatusChart"></canvas>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<script>
    (function() {
        var trendCtx = document.getElementById('candidateTrendChart');
        var statusCtx = document.getElementById('candidateStatusChart');
        if (!trendCtx || !statusCtx || typeof Chart === 'undefined') return;

        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: @json($trendChartLabels),
                datasets: [{
                    label: 'Candidates',
                    data: @json($trendChartData),
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37,99,235,0.15)',
                    fill: true,
                    tension: 0.35
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: @json($statusChartLabels),
                datasets: [{
                    data: @json($statusChartData),
                    backgroundColor: ['#2563eb', '#16a34a', '#f59e0b', '#8b5cf6', '#0ea5e9', '#ef4444', '#64748b']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    })();
</script>

@endsection
