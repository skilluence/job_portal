@extends('layouts.student')
@section('title', 'Dashboard')
@section('content')

<div class="sp-hero">
    <div class="sp-hero-top">
        <div class="sp-hero-avatar">{{ strtoupper(substr($candidate->full_name, 0, 1)) }}</div>
        <div class="sp-hero-info">
            <div class="sp-hero-greeting">Welcome back</div>
            <div class="sp-hero-name">{{ $candidate->full_name }}</div>
            <div class="sp-hero-meta">
                <span><i class="bi bi-envelope"></i> {{ $candidate->email_id }}</span>
                @if ($candidate->recruiter)
                    <span><i class="bi bi-person-badge"></i> Recruiter: {{ $candidate->recruiter->name }}</span>
                @endif
            </div>
        </div>
        <div class="sp-hero-status">
            <span class="sp-status-badge {{ $candidate->status }}">
                <i class="bi bi-circle-fill" style="font-size:7px;"></i>
                {{ ucfirst($candidate->status) }}
            </span>
        </div>
    </div>

    <div class="sp-hero-stats">
        <div class="sp-hstat">
            <div class="sp-hstat-icon"><i class="bi bi-file-earmark-text-fill"></i></div>
            <div>
                <div class="sp-hstat-val">{{ $candidate->no_of_applications }}</div>
                <div class="sp-hstat-lbl">Applications</div>
            </div>
        </div>
        <div class="sp-hstat">
            <div class="sp-hstat-icon" style="background:rgba(16,185,129,0.15);color:#10b981;"><i class="bi bi-calendar-check-fill"></i></div>
            <div>
                <div class="sp-hstat-val">{{ $candidate->interviews_count }}</div>
                <div class="sp-hstat-lbl">Interviews</div>
            </div>
        </div>
        <div class="sp-hstat">
            <div class="sp-hstat-icon" style="background:rgba(245,158,11,0.15);color:#f59e0b;"><i class="bi bi-person-workspace"></i></div>
            <div>
                <div class="sp-hstat-val">{{ $candidate->sales_agent ?: '-' }}</div>
                <div class="sp-hstat-lbl">Sales Agent</div>
            </div>
        </div>
        <div class="sp-hstat">
            <div class="sp-hstat-icon" style="background:rgba(168,85,247,0.15);color:#a855f7;"><i class="bi bi-check2-circle"></i></div>
            <div>
                <div class="sp-hstat-val">{{ $profileCompletion }}%</div>
                <div class="sp-hstat-lbl">Profile Completion</div>
            </div>
        </div>
    </div>
</div>

<div class="sp-grid-2">
    <div class="sp-card">
        <div class="sp-card-head">
            <div>
                <div class="sp-card-title"><i class="bi bi-person-circle"></i> My Details</div>
                <div class="sp-card-sub">Editable profile information</div>
            </div>
            <a href="{{ route('student.info') }}" class="sp-edit-btn"><i class="bi bi-pencil-fill"></i> Edit</a>
        </div>

        <div class="sp-info-list">
            <div class="sp-info-item">
                <div class="sp-ii-icon"><i class="bi bi-person"></i></div>
                <div>
                    <div class="sp-ii-label">Full Name</div>
                    <div class="sp-ii-val">{{ $candidate->full_name }}</div>
                </div>
            </div>
            <div class="sp-info-item">
                <div class="sp-ii-icon" style="background:#fef3c7;color:#d97706;"><i class="bi bi-envelope"></i></div>
                <div>
                    <div class="sp-ii-label">Email ID</div>
                    <div class="sp-ii-val">{{ $candidate->email_id ?: '-' }}</div>
                </div>
            </div>
            <div class="sp-info-item">
                <div class="sp-ii-icon" style="background:#f0fdf4;color:#16a34a;"><i class="bi bi-linkedin"></i></div>
                <div>
                    <div class="sp-ii-label">LinkedIn ID</div>
                    <div class="sp-ii-val">{{ $candidate->linkedin_id ?: '-' }}</div>
                </div>
            </div>
            <div class="sp-info-item">
                <div class="sp-ii-icon" style="background:#fdf2f8;color:#db2777;"><i class="bi bi-geo-alt"></i></div>
                <div>
                    <div class="sp-ii-label">Address</div>
                    <div class="sp-ii-val">{{ $candidate->address ?: '-' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="sp-card">
        <div class="sp-card-head">
            <div>
                <div class="sp-card-title"><i class="bi bi-folder2-open"></i> Documents and Activity</div>
                <div class="sp-card-sub">Your files and latest portal actions</div>
            </div>
        </div>

        <div class="d-flex gap-8 mb-16" style="flex-wrap:wrap;">
            @if ($candidate->cv_file_path)
                <a class="sp-edit-btn" href="{{ route('student.files', 'cv') }}"><i class="bi bi-file-earmark-arrow-down"></i> Download CV</a>
            @endif
            @if ($candidate->candidate_details_file_path)
                <a class="sp-edit-btn" href="{{ route('student.files', 'details') }}"><i class="bi bi-file-earmark-arrow-down"></i> Candidate Details</a>
            @endif
            @if (!$candidate->cv_file_path && !$candidate->candidate_details_file_path)
                <span class="text-sm text-muted">No documents uploaded yet.</span>
            @endif
            <a class="sp-edit-btn" href="{{ route('student.info') }}"><i class="bi bi-cloud-arrow-up"></i> Upload Documents</a>
        </div>

        <div style="margin-top:16px;padding-top:12px;border-top:1px solid #e2e8f0;">
            <div class="sp-card-sub" style="margin-bottom:10px;">Recent Activity</div>
            @forelse ($recentStudentLogs as $log)
                <div class="text-sm mb-8" style="color:#475569;">
                    <strong>{{ ucfirst(str_replace('_', ' ', $log->action)) }}</strong>:
                    {{ $log->description }}
                    <div class="text-muted" style="font-size:11px;">{{ $log->created_at->format('M d, Y h:i A') }}</div>
                </div>
            @empty
                <div class="text-sm text-muted">No recent student activity found.</div>
            @endforelse
        </div>
    </div>
</div>

@endsection
