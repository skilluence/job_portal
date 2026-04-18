@extends('layouts.student')
@section('title', 'Dashboard')
@section('content')

{{-- Hero / Welcome Banner --}}
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

{{-- Stats Row --}}
<div class="stp-stats-row">
    <div class="stp-stat-card">
        <div class="stp-stat-icon" style="background:rgba(37,99,235,0.1);color:#2563eb;">
            <i class="bi bi-file-earmark-text-fill"></i>
        </div>
        <div class="stp-stat-body">
            <div class="stp-stat-val">{{ $candidate->no_of_applications }}</div>
            <div class="stp-stat-lbl">Total Applications</div>
        </div>
    </div>
    <div class="stp-stat-card">
        <div class="stp-stat-icon" style="background:rgba(16,185,129,0.1);color:#10b981;">
            <i class="bi bi-calendar-check-fill"></i>
        </div>
        <div class="stp-stat-body">
            <div class="stp-stat-val">{{ $candidate->interviews_count }}</div>
            <div class="stp-stat-lbl">Interviews</div>
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

{{-- Two-column content --}}
<div class="stp-grid-2">

    {{-- Profile Details card --}}
    <div class="stp-card">
        <div class="stp-card-head">
            <div class="stp-card-title"><i class="bi bi-person-circle"></i> My Details</div>
            <a href="{{ route('student.info') }}" class="stp-card-action">
                <i class="bi bi-pencil-fill"></i> Edit Profile
            </a>
        </div>
        <div class="stp-info-list">
            <div class="stp-info-row">
                <div class="stp-info-icon" style="background:#eff6ff;color:#2563eb;"><i class="bi bi-person"></i></div>
                <div>
                    <div class="stp-info-label">Full Name</div>
                    <div class="stp-info-val">{{ $candidate->full_name }}</div>
                </div>
            </div>
            <div class="stp-info-row">
                <div class="stp-info-icon" style="background:#fefce8;color:#ca8a04;"><i class="bi bi-envelope"></i></div>
                <div>
                    <div class="stp-info-label">Email</div>
                    <div class="stp-info-val">{{ $candidate->email_id }}</div>
                </div>
            </div>
            @if ($candidate->phone_number)
                <div class="stp-info-row">
                    <div class="stp-info-icon" style="background:#f0fdf4;color:#16a34a;"><i class="bi bi-telephone"></i></div>
                    <div>
                        <div class="stp-info-label">Phone</div>
                        <div class="stp-info-val">{{ $candidate->phone_number }}</div>
                    </div>
                </div>
            @endif
            @if ($candidate->linkedin_url)
                <div class="stp-info-row">
                    <div class="stp-info-icon" style="background:#eff6ff;color:#0077b5;"><i class="bi bi-linkedin"></i></div>
                    <div>
                        <div class="stp-info-label">LinkedIn</div>
                        <div class="stp-info-val">
                            <a href="{{ $candidate->linkedin_url }}" target="_blank" rel="noopener" style="color:var(--blue);">View Profile</a>
                        </div>
                    </div>
                </div>
            @endif
            @if ($candidate->city || $candidate->state_province)
                <div class="stp-info-row">
                    <div class="stp-info-icon" style="background:#fdf4ff;color:#a21caf;"><i class="bi bi-geo-alt"></i></div>
                    <div>
                        <div class="stp-info-label">Location</div>
                        <div class="stp-info-val">
                            {{ implode(', ', array_filter([$candidate->city, $candidate->state_province])) }}
                        </div>
                    </div>
                </div>
            @endif
            @if ($candidate->visa_immigration_status)
                <div class="stp-info-row">
                    <div class="stp-info-icon" style="background:#fff7ed;color:#ea580c;"><i class="bi bi-shield-check"></i></div>
                    <div>
                        <div class="stp-info-label">Visa Status</div>
                        <div class="stp-info-val">{{ ucfirst(str_replace('_', ' ', $candidate->visa_immigration_status)) }}</div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Documents & Activity card --}}
    <div class="stp-card">
        <div class="stp-card-head">
            <div class="stp-card-title"><i class="bi bi-folder2-open"></i> Documents &amp; Activity</div>
            <a href="{{ route('student.info') }}" class="stp-card-action">
                <i class="bi bi-cloud-arrow-up"></i> Upload
            </a>
        </div>

        <div class="stp-doc-list">
            @if ($candidate->cv_file_path)
                <a href="{{ route('student.files', 'cv') }}" target="_blank" class="stp-doc-item">
                    <div class="stp-doc-icon"><i class="bi bi-file-earmark-pdf-fill"></i></div>
                    <div class="stp-doc-body">
                        <div class="stp-doc-name">Curriculum Vitae (CV)</div>
                        <div class="stp-doc-hint">Click to open</div>
                    </div>
                    <i class="bi bi-box-arrow-up-right stp-doc-arrow"></i>
                </a>
            @endif
            @if ($candidate->candidate_details_file_path)
                <a href="{{ route('student.files', 'details') }}" target="_blank" class="stp-doc-item">
                    <div class="stp-doc-icon" style="background:#f0fdf4;color:#16a34a;"><i class="bi bi-file-earmark-text-fill"></i></div>
                    <div class="stp-doc-body">
                        <div class="stp-doc-name">Candidate Details</div>
                        <div class="stp-doc-hint">Click to open</div>
                    </div>
                    <i class="bi bi-box-arrow-up-right stp-doc-arrow"></i>
                </a>
            @endif
            @if (!$candidate->cv_file_path && !$candidate->candidate_details_file_path)
                <div class="stp-empty-docs">
                    <i class="bi bi-file-earmark-x" style="font-size:28px;color:#cbd5e1;margin-bottom:6px;"></i>
                    <div>No documents uploaded yet.</div>
                    <a href="{{ route('student.info') }}" class="stp-card-action" style="margin-top:8px;">Upload Documents</a>
                </div>
            @endif
        </div>

        <div class="stp-divider"></div>

        <div class="stp-activity-title"><i class="bi bi-clock-history"></i> Recent Activity</div>
        <div class="stp-activity-list">
            @forelse ($recentStudentLogs as $log)
                <div class="stp-activity-item">
                    <div class="stp-activity-dot"></div>
                    <div>
                        <div class="stp-activity-action">{{ ucfirst(str_replace('_', ' ', $log->action)) }}: {{ $log->description }}</div>
                        <div class="stp-activity-time">{{ $log->created_at->format('M d, Y h:i A') }}</div>
                    </div>
                </div>
            @empty
                <div class="stp-activity-empty">No recent activity found.</div>
            @endforelse
        </div>
    </div>
</div>

@endsection
