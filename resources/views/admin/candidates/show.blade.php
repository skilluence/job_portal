@extends('layouts.admin')
@section('title', $candidate->full_name . ' — Preview')
@section('module-title', 'Candidate Preview')
@section('module-description', 'View and manage all candidate details, daily logs, interviews, and documents.')
@section('content')

@php
$visaOptions = [
    'us_citizen' => 'U.S. Citizen',
    'green_card' => 'Green Card',
    'h1b'        => 'H-1B',
    'h4_ead'     => 'H-4 EAD',
    'opt_f1'     => 'Initial OPT F-1',
    'stem_opt'   => 'STEM OPT',
    'cpt'        => 'CPT',
    'l1'         => 'L-1',
    'tn_visa'    => 'TN Visa',
    'other'      => 'Other',
];
$workAuthOptions = [
    'applied_pending'  => 'Applied — pending',
    'not_applied'      => 'Not Applied',
    'already_obtained' => 'Already obtained',
];
$statusOptions = ['active','enrolled','interview','offer','placed','onhold','inactive'];
$interviewTypes = ['phone_call' => 'Phone Call', 'virtual' => 'Virtual', 'on_site' => 'On Site'];
$assessmentTypes = [
    'technical' => 'Technical',
    'screening' => 'Screening',
    'ai_interview' => 'AI Interview',
    'questions' => 'Questions',
    'virtual_video_interview' => 'Virtual / Video Interview',
];
$timezones = ['EST','CST','MST','PST','AKST','HST','EDT','CDT','MDT','PDT'];

$totalApps       = $dailyLogs->sum('applications');
$totalAssessment = $assessments->count();
$totalInterviews = $interviews->count();
@endphp

<style>
.preview-layout {
    display: flex;
    gap: 20px;
    align-items: flex-start;
}
.preview-left {
    flex: 0 0 340px;
    position: sticky;
    top: 84px;
    max-height: calc(100vh - 104px);
    overflow-y: auto;
}
.preview-left::-webkit-scrollbar { width: 4px; }
.preview-left::-webkit-scrollbar-thumb { background: var(--border-color); border-radius: 2px; }
.preview-right {
    flex: 1;
    min-width: 0;
}
.candidate-avatar-xl {
    width: 68px; height: 68px; border-radius: 50%;
    background: linear-gradient(135deg, var(--blue), #3b82f6);
    color: #fff; font-size: 26px; font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    text-transform: uppercase; flex-shrink: 0;
    box-shadow: 0 4px 14px rgba(37,99,235,0.28);
}
.profile-section-title {
    font-size: 10.5px; font-weight: 700; color: var(--text-muted);
    text-transform: uppercase; letter-spacing: 0.07em;
    margin: 16px 0 10px; padding-bottom: 6px;
    border-bottom: 1px solid var(--border-color);
}
.detail-row {
    display: flex; align-items: flex-start;
    padding: 5px 0; gap: 8px; position: relative;
    min-height: 28px;
}
.detail-row:hover .edit-pencil { opacity: 1; }
.detail-label {
    flex: 0 0 120px; font-size: 12px; color: var(--text-muted);
    font-weight: 500; padding-top: 2px; flex-shrink: 0;
}
.detail-value {
    flex: 1; font-size: 12.5px; color: var(--text-primary);
    font-weight: 400; min-width: 0; word-break: break-word;
}
.edit-pencil {
    opacity: 0; cursor: pointer; color: var(--text-muted);
    font-size: 11px; padding: 2px 4px; border-radius: 4px;
    transition: opacity 0.15s, color 0.15s, background 0.15s;
    flex-shrink: 0; margin-top: 2px;
}
.edit-pencil:hover { color: var(--blue); background: var(--blue-light); }
.inline-edit-wrap {
    display: none; width: 100%; gap: 8px; align-items: stretch; flex-wrap: wrap;
}
.inline-edit-wrap.active { display: flex; }
.preview-inline-editor {
    margin: 2px 0 10px 128px;
    width: calc(100% - 128px);
    align-items: flex-start;
}
.preview-inline-editor.active { display: flex; }
.preview-inline-editor .inline-edit-input,
.preview-inline-editor .select2-container,
.preview-inline-editor .iti { flex: 1 1 100%; width: 100% !important; min-width: 0; }
.preview-inline-actions { display:flex; gap:6px; width:100%; justify-content:flex-end; }
.preview-badge-wrap { width:100%; min-height:38px; }
.inline-edit-input {
    flex: 1; min-width: 0;
    padding: 8px 10px; font-size: 12.5px;
    border: 1px solid var(--input-border); border-radius: var(--radius-sm);
    background: var(--input-bg); color: var(--text-primary);
    outline: none;
}
.inline-edit-input:focus { border-color: var(--blue); box-shadow: 0 0 0 2px rgba(37,99,235,0.1); }
.inline-edit-btn {
    padding: 3px 8px; font-size: 11px; border-radius: var(--radius-sm);
    border: none; cursor: pointer; font-family: inherit; font-weight: 500;
}
.inline-edit-save   { background: var(--blue); color: #fff; }
.inline-edit-cancel { background: var(--main-bg); color: var(--text-secondary); border: 1px solid var(--border-color); }

/* Page tabs */
.page-tabs {
    display: flex; gap: 2px;
    background: var(--main-bg); border-radius: var(--radius-sm);
    padding: 3px; margin-bottom: 16px;
    border: 1px solid var(--border-color);
}
.page-tab {
    flex: 1; padding: 7px 12px; text-align: center;
    font-size: 12.5px; font-weight: 500; color: var(--text-secondary);
    border-radius: 5px; border: none; background: transparent;
    cursor: pointer; transition: all 0.2s; white-space: nowrap;
}
.page-tab.active {
    background: var(--card-bg); color: var(--text-primary);
    box-shadow: 0 1px 4px rgba(0,0,0,0.08);
}
.page-tab-panel { display: none; }
.page-tab-panel.active { display: block; }

/* Summary stat row */
.summary-stats {
    display: flex; gap: 12px; margin-bottom: 16px; flex-wrap: wrap;
}
.summary-stat {
    background: var(--card-bg); border: 1px solid var(--border-color);
    border-radius: var(--radius-sm); padding: 10px 18px;
    text-align: center; flex: 1; min-width: 80px;
}
.summary-stat-val { font-size: 22px; font-weight: 700; color: var(--text-primary); }
.summary-stat-lbl { font-size: 11px; color: var(--text-muted); margin-top: 2px; }

/* Interview type badges */
.type-phone  { background: var(--blue-light);   color: var(--blue-text); }
.type-virtual{ background: var(--purple-light); color: var(--purple-text); }
.type-onsite { background: var(--green-light);  color: var(--green-text); }

/* Inline edit log row */
.log-edit-input {
    width: 70px; padding: 3px 6px; font-size: 12px;
    border: 1px solid var(--input-border); border-radius: 5px;
    background: var(--input-bg); color: var(--text-primary);
}
.log-edit-remark {
    width: 160px; padding: 3px 6px; font-size: 12px;
    border: 1px solid var(--input-border); border-radius: 5px;
    background: var(--input-bg); color: var(--text-primary);
}
.doc-item {
    display: flex; align-items: center; gap: 12px;
    padding: 12px 0; border-bottom: 1px solid var(--border-color);
}
.doc-item:last-child { border-bottom: none; }
.doc-icon {
    width: 38px; height: 38px; border-radius: 9px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px;
}
.doc-info { flex: 1; min-width: 0; }
.doc-name { font-size: 13px; font-weight: 500; color: var(--text-primary); }
.doc-meta { font-size: 11px; color: var(--text-muted); margin-top: 2px; }

@media (max-width: 1000px) {
    .preview-layout { flex-direction: column; }
    .preview-left { position: static; max-height: none; flex: none; width: 100%; }
    .preview-inline-editor { margin-left:0; width:100%; }
}
</style>

{{-- Flash messages --}}
@if (session('success'))
<div class="toast-container" id="flashToast">
    <div class="toast toast-success"><i class="bi bi-check-circle-fill"></i><span>{{ session('success') }}</span></div>
</div>
@endif
@if ($errors->any())
<div class="alert alert-error mb-16">
    <i class="bi bi-exclamation-circle-fill" style="flex-shrink:0;font-size:16px;"></i>
    <div>@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
</div>
@endif

<div class="preview-layout">

    {{-- ===== LEFT PANEL ===== --}}
    <div class="preview-left">
        <div style="margin-bottom:12px;">
            <a href="{{ route('admin.candidates') }}" class="btn btn-outline btn-sm">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>

        <div class="card" style="padding:20px;">
            {{-- Profile header --}}
            <div style="display:flex;align-items:center;gap:14px;margin-bottom:16px;padding-bottom:16px;border-bottom:1px solid var(--border-color);">
                <div class="candidate-avatar-xl">
                    {{ $candidate->initials }}
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="font-size:16px;font-weight:700;color:var(--text-primary);line-height:1.2;" id="displayFullName">
                        {{ $candidate->full_name }}
                    </div>
                    <div style="font-size:12px;color:var(--text-secondary);margin-top:3px;">
                        {{ $candidate->email_id }}
                    </div>
                    @if ($candidate->phone_number)
                    <div style="font-size:12px;color:var(--text-muted);margin-top:2px;">
                        <i class="bi bi-telephone" style="font-size:11px;"></i> {{ $candidate->phone_number }}
                    </div>
                    @endif
                </div>
            </div>

            {{-- Status badge --}}
            <div class="detail-row">
                <span class="detail-label">Status</span>
                <div class="detail-value" id="statusDisplay">
                    @php
                    $statusBadgeMap = [
                        'active'    => 'badge-success',
                        'enrolled'  => 'badge-info',
                        'interview' => 'badge-warning',
                        'offer'     => 'badge-primary',
                        'placed'    => 'badge-success',
                        'onhold'    => 'badge-warning',
                        'inactive'  => 'badge-danger',
                    ];
                    @endphp
                    <span class="badge {{ $statusBadgeMap[$candidate->status] ?? 'badge-neutral' }}" id="statusBadge">{{ ucfirst($candidate->status) }}</span>
                </div>
                @if ($isAdmin || $isManager)
                <i class="bi bi-pencil-fill edit-pencil" onclick="startStatusEdit()" title="Edit status"></i>
                @endif
            </div>
            <div class="inline-edit-wrap" id="statusEditWrap">
                <select class="inline-edit-input" id="statusEditInput">
                    @foreach ($statusOptions as $s)
                        <option value="{{ $s }}" @selected($candidate->status === $s)>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
                <button class="inline-edit-btn inline-edit-save" onclick="saveInlineField('status', document.getElementById('statusEditInput').value)">Save</button>
                <button class="inline-edit-btn inline-edit-cancel" onclick="cancelInlineEdit('statusDisplay','statusEditWrap')">Cancel</button>
            </div>

            {{-- Recruiter / Manager info --}}
            <div class="detail-row">
                <span class="detail-label">Recruiter</span>
                <span class="detail-value">{{ $candidate->recruiter?->name ?? '—' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Team Manager</span>
                <span class="detail-value">{{ $candidate->teamManager?->name ?? '—' }}</span>
            </div>

            {{-- ---- Personal ---- --}}
            <div class="profile-section-title">Personal</div>

            @php
            $genderLabels = ['male'=>'Male','female'=>'Female','other'=>'Other','prefer_not_to_say'=>'Prefer not to say'];
            @endphp

            @include('admin.candidates._preview_field', ['field'=>'first_name','label'=>'First Name','value'=>$candidate->first_name,'editable'=>true])
            @include('admin.candidates._preview_field', ['field'=>'middle_name','label'=>'Middle Name','value'=>$candidate->middle_name,'editable'=>true])
            @include('admin.candidates._preview_field', ['field'=>'last_name','label'=>'Last Name','value'=>$candidate->last_name,'editable'=>true])
            @include('admin.candidates._preview_field', ['field'=>'date_of_birth','label'=>'Date of Birth','value'=>$candidate->date_of_birth?->format('M d, Y'),'rawValue'=>$candidate->date_of_birth?->format('Y-m-d'),'type'=>'date','editable'=>true])
            @include('admin.candidates._preview_field', ['field'=>'gender','label'=>'Gender','value'=>$genderLabels[$candidate->gender] ?? $candidate->gender,'rawValue'=>$candidate->gender,'type'=>'select','selectOptions'=>['male'=>'Male','female'=>'Female','other'=>'Other','prefer_not_to_say'=>'Prefer not to say'],'editable'=>true])
            @include('admin.candidates._preview_field', ['field'=>'nationality','label'=>'Nationality','value'=>$candidate->nationality,'editable'=>true])
            @include('admin.candidates._preview_field', ['field'=>'phone_number','label'=>'Phone','value'=>$candidate->phone_number,'rawValue'=>$candidate->phone_number,'type'=>'phone','editable'=>true])
            @include('admin.candidates._preview_field', ['field'=>'domain','label'=>'Domain','value'=>$candidate->domain,'editable'=>true])
            @include('admin.candidates._preview_field', ['field'=>'sub_domain','label'=>'Sub Domain','value'=>$candidate->sub_domain,'rawValue'=>$candidate->sub_domain,'type'=>'badge','editable'=>true])

            {{-- ---- Address ---- --}}
            <div class="profile-section-title">Address</div>
            @include('admin.candidates._preview_field', ['field'=>'street_address','label'=>'Street','value'=>$candidate->street_address,'editable'=>true])
            @include('admin.candidates._preview_field', ['field'=>'apartment_unit','label'=>'Apt / Unit','value'=>$candidate->apartment_unit,'editable'=>true])
            @include('admin.candidates._preview_field', ['field'=>'city','label'=>'City','value'=>$candidate->city,'rawValue'=>$candidate->city,'type'=>'geo_city','editable'=>true])
            @include('admin.candidates._preview_field', ['field'=>'state_province','label'=>'State','value'=>$candidate->state_province,'rawValue'=>$candidate->state_province,'type'=>'geo_state','editable'=>true])
            @include('admin.candidates._preview_field', ['field'=>'zip_code','label'=>'ZIP','value'=>$candidate->zip_code,'editable'=>true])
            @include('admin.candidates._preview_field', ['field'=>'country','label'=>'Country','value'=>$candidate->country,'rawValue'=>$candidate->country,'type'=>'geo_country','editable'=>true])

            {{-- ---- Visa ---- --}}
            <div class="profile-section-title">Visa &amp; Work Auth</div>
            @include('admin.candidates._preview_field', ['field'=>'visa_immigration_status','label'=>'Visa Status','value'=>$visaOptions[$candidate->visa_immigration_status] ?? $candidate->visa_immigration_status,'rawValue'=>$candidate->visa_immigration_status,'type'=>'select','selectOptions'=>$visaOptions,'editable'=>true])
            @include('admin.candidates._preview_field', ['field'=>'work_auth_status','label'=>'Work Auth','value'=>$workAuthOptions[$candidate->work_auth_status] ?? $candidate->work_auth_status,'rawValue'=>$candidate->work_auth_status,'type'=>'select','selectOptions'=>$workAuthOptions,'editable'=>true])
            @include('admin.candidates._preview_field', ['field'=>'open_to_relocation','label'=>'Relocation','value'=>$candidate->open_to_relocation ? 'Yes' : 'No','rawValue'=>$candidate->open_to_relocation ? '1' : '0','type'=>'select','selectOptions'=>['1'=>'Yes','0'=>'No'],'editable'=>true])
            @php
                $prefCities = collect(explode(',', $candidate->preferred_city ?? ''))
                    ->map('trim')->filter()->values();
                $prefCityDisplay = $prefCities->isNotEmpty()
                    ? $prefCities->implode(' · ')
                    : null;
            @endphp
            @include('admin.candidates._preview_field', ['field'=>'preferred_city','label'=>'Preferred City','value'=>$prefCityDisplay,'rawValue'=>$candidate->preferred_city,'type'=>'badge','editable'=>true])
            @include('admin.candidates._preview_field', ['field'=>'visa_expiry_date','label'=>'Visa Expiry','value'=>$candidate->visa_expiry_date?->format('M d, Y'),'rawValue'=>$candidate->visa_expiry_date?->format('Y-m-d'),'type'=>'date','editable'=>true])

            {{-- ---- Salary ---- --}}
            <div class="profile-section-title">Salary</div>
            @include('admin.candidates._preview_field', ['field'=>'current_salary','label'=>'Current','value'=>$candidate->current_salary ? '$'.number_format($candidate->current_salary,0) : null,'rawValue'=>$candidate->current_salary,'editable'=>true])
            @include('admin.candidates._preview_field', ['field'=>'expected_salary','label'=>'Expected','value'=>$candidate->expected_salary ? '$'.number_format($candidate->expected_salary,0) : null,'rawValue'=>$candidate->expected_salary,'editable'=>true])

            {{-- ---- Professional ---- --}}
            <div class="profile-section-title">Professional</div>
            @include('admin.candidates._preview_field', ['field'=>'github_url','label'=>'GitHub','value'=>$candidate->github_url,'editable'=>true,'isLink'=>true])
            @include('admin.candidates._preview_field', ['field'=>'linkedin_url','label'=>'LinkedIn','value'=>$candidate->linkedin_url,'editable'=>true,'isLink'=>true])

            {{-- ---- Marketing ---- --}}
            <div class="profile-section-title">Marketing</div>
            @include('admin.candidates._preview_field', ['field'=>'marketing_phone','label'=>'Mkt Phone','value'=>$candidate->marketing_phone,'rawValue'=>$candidate->marketing_phone,'type'=>'phone','editable'=>true])
            @include('admin.candidates._preview_field', ['field'=>'marketing_email','label'=>'Mkt Email','value'=>$candidate->marketing_email,'editable'=>true])
            @include('admin.candidates._preview_field', ['field'=>'marketing_linkedin_id','label'=>'LinkedIn ID','value'=>$candidate->marketing_linkedin_id,'editable'=>true])
            @include('admin.candidates._preview_field', ['field'=>'marketing_email_password','label'=>'Email Password','value'=>$candidate->marketing_email_password])
            @include('admin.candidates._preview_field', ['field'=>'marketing_linkedin_password','label'=>'LinkedIn Password','value'=>$candidate->marketing_linkedin_password])

            {{-- ---- Education ---- --}}
            <div class="profile-section-title">Education — Masters</div>
            @include('admin.candidates._preview_field', ['field'=>'masters_university','label'=>'University','value'=>$candidate->masters_university,'editable'=>true])
            @include('admin.candidates._preview_field', ['field'=>'masters_program','label'=>'Program','value'=>$candidate->masters_program,'editable'=>true])
            @include('admin.candidates._preview_field', ['field'=>'masters_start','label'=>'Start','value'=>$candidate->masters_start?->format('M Y'),'rawValue'=>$candidate->masters_start?->format('Y-m-d'),'type'=>'date','editable'=>true])
            @include('admin.candidates._preview_field', ['field'=>'masters_end','label'=>'End','value'=>$candidate->masters_end?->format('M Y'),'rawValue'=>$candidate->masters_end?->format('Y-m-d'),'type'=>'date','editable'=>true])
            @include('admin.candidates._preview_field', ['field'=>'masters_country','label'=>'Country','value'=>$candidate->masters_country,'editable'=>true])

            <div class="profile-section-title">Education — Bachelors</div>
            @include('admin.candidates._preview_field', ['field'=>'bachelors_university','label'=>'University','value'=>$candidate->bachelors_university,'editable'=>true])
            @include('admin.candidates._preview_field', ['field'=>'bachelors_program','label'=>'Program','value'=>$candidate->bachelors_program,'editable'=>true])
            @include('admin.candidates._preview_field', ['field'=>'bachelors_start','label'=>'Start','value'=>$candidate->bachelors_start?->format('M Y'),'rawValue'=>$candidate->bachelors_start?->format('Y-m-d'),'type'=>'date','editable'=>true])
            @include('admin.candidates._preview_field', ['field'=>'bachelors_end','label'=>'End','value'=>$candidate->bachelors_end?->format('M Y'),'rawValue'=>$candidate->bachelors_end?->format('Y-m-d'),'type'=>'date','editable'=>true])
            @include('admin.candidates._preview_field', ['field'=>'bachelors_country','label'=>'Country','value'=>$candidate->bachelors_country,'editable'=>true])

            {{-- ---- Notes ---- --}}
            @if ($isAdmin || $isManager)
            <div class="profile-section-title">Admin</div>
            @include('admin.candidates._preview_field', ['field'=>'no_of_applications','label'=>'# Apps Allowed','value'=>$candidate->no_of_applications,'editable'=>$isAdmin,'rawValue'=>$candidate->no_of_applications])
            @endif

            {{-- Recruiter notes --}}
            <div class="profile-section-title">Notes</div>
            <div style="margin-top:4px;">
                <div style="font-size:12px;color:var(--text-secondary);white-space:pre-wrap;line-height:1.5;" id="notesDisplay">{{ $candidate->recruiter_notes ?: '—' }}</div>
                <button class="btn btn-outline btn-sm" style="margin-top:6px;" onclick="startNotesEdit()" id="notesEditBtn"><i class="bi bi-pencil"></i> Edit Notes</button>
                <div id="notesEditWrap" style="display:none;margin-top:6px;">
                    <textarea class="form-control" id="notesEditInput" rows="4" style="font-size:12px;">{{ $candidate->recruiter_notes }}</textarea>
                    <div style="display:flex;gap:6px;margin-top:6px;">
                        <button class="btn btn-primary btn-sm" onclick="saveInlineField('recruiter_notes', document.getElementById('notesEditInput').value)">Save</button>
                        <button class="btn btn-outline btn-sm" onclick="cancelNotesEdit()">Cancel</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== RIGHT PANEL ===== --}}
    <div class="preview-right">
        <div class="page-tabs" role="tablist">
            <button class="page-tab active" data-tab="logs" onclick="switchTab('logs', this)" role="tab">
                <i class="bi bi-journal-text"></i> Daily Logs
                <span class="badge badge-neutral" style="margin-left:4px;font-size:10px;">{{ $dailyLogs->count() }}</span>
            </button>
            <button class="page-tab" data-tab="interviews" onclick="switchTab('interviews', this)" role="tab">
                <i class="bi bi-briefcase"></i> Interviews
                <span class="badge badge-neutral" style="margin-left:4px;font-size:10px;">{{ $interviews->count() }}</span>
            </button>
            <button class="page-tab" data-tab="assessments" onclick="switchTab('assessments', this)" role="tab">
                <i class="bi bi-clipboard-check"></i> Assessment
                <span class="badge badge-neutral" style="margin-left:4px;font-size:10px;">{{ $assessments->count() }}</span>
            </button>
            <button class="page-tab" data-tab="documents" onclick="switchTab('documents', this)" role="tab">
                <i class="bi bi-folder2-open"></i> Documents
            </button>
        </div>

        {{-- ===== DAILY LOGS TAB ===== --}}
        <div class="page-tab-panel active" id="tab-logs">
            {{-- Summary --}}
            <div class="summary-stats">
                <div class="summary-stat">
                    <div class="summary-stat-val" style="color:var(--blue-text);">{{ $totalApps }}</div>
                    <div class="summary-stat-lbl">Total Applications</div>
                </div>
                <div class="summary-stat">
                    <div class="summary-stat-val" style="color:var(--purple-text);">{{ $totalAssessment }}</div>
                    <div class="summary-stat-lbl">Total Assessment</div>
                </div>
                <div class="summary-stat">
                    <div class="summary-stat-val" style="color:var(--green-text);">{{ $totalInterviews }}</div>
                    <div class="summary-stat-lbl">Total Interviews</div>
                </div>
                <div class="summary-stat">
                    <div class="summary-stat-val">{{ $dailyLogs->count() }}</div>
                    <div class="summary-stat-lbl">Log Days</div>
                </div>
            </div>

            <div class="card" style="padding:0;">
                <div class="card-header" style="padding:14px 16px;">
                    <div>
                        <div class="card-title">Daily Logs</div>
                        <div class="card-subtitle">Applications are editable here. Assessment and interview totals are auto-synced from their tabs.</div>
                    </div>
                    <button class="btn btn-primary btn-sm" onclick="openLogModal()">
                        <i class="bi bi-plus-lg"></i> Add Log
                    </button>
                </div>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th style="width:80px;">Apps</th>
                                <th style="width:80px;">Assessment</th>
                                <th style="width:80px;">Interviews</th>
                                <th>Remark</th>
                                <th>Added By</th>
                                <th style="width:80px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($dailyLogs as $log)
                            <tr data-log-id="{{ $log->id }}"
                                data-update-url="{{ route('admin.candidates.daily-logs.update', [$candidate, $log]) }}"
                                ondblclick="startLogEdit(this)"
                                style="cursor:pointer;"
                                title="Double-click to edit">
                                <td class="text-sm" style="white-space:nowrap;">
                                    {{ $log->log_date->format('M d, Y') }}
                                </td>
                                <td class="td-apps">
                                    <span class="view-val">{{ $log->applications }}</span>
                                    <input type="number" min="0" class="log-edit-input edit-val" style="display:none;" value="{{ $log->applications }}" data-field="applications">
                                </td>
                                <td class="td-assistant">
                                    <span class="view-val">{{ $log->assistant_count }}</span>
                                </td>
                                <td class="td-interviews">
                                    <span class="view-val">{{ $log->interview_count }}</span>
                                </td>
                                <td class="td-remark" style="max-width:200px;">
                                    <span class="view-val" style="white-space:pre-wrap;font-size:12px;">{{ $log->remark ?: '—' }}</span>
                                    <input type="text" class="log-edit-remark edit-val" style="display:none;" value="{{ $log->remark }}" data-field="remark">
                                </td>
                                <td class="text-sm text-muted">{{ $log->recruiter?->name ?? $log->creator?->name ?? '—' }}</td>
                                <td class="td-log-actions">
                                    <div class="view-val d-flex gap-6">
                                        {{-- no-op, dblclick handles edit --}}
                                    </div>
                                    <div class="edit-val d-flex gap-4" style="display:none!important;">
                                        <button class="btn btn-primary btn-sm" onclick="saveLogEdit(this.closest('tr'))">Save</button>
                                        <button class="btn btn-outline btn-sm" onclick="cancelLogEdit(this.closest('tr'))">Cancel</button>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7">
                                    <div class="page-empty" style="padding:32px 24px;">
                                        <i class="bi bi-journal-text"></i>
                                        <p>No daily logs yet. Click "Add Log" to get started.</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ===== INTERVIEWS TAB ===== --}}
        <div class="page-tab-panel" id="tab-interviews">
            <div class="card" style="padding:0;">
                <div class="card-header" style="padding:14px 16px;">
                    <div>
                        <div class="card-title">Interviews</div>
                        <div class="card-subtitle">{{ $interviews->count() }} total interviews recorded</div>
                    </div>
                    <button class="btn btn-primary btn-sm" onclick="openInterviewModal()">
                        <i class="bi bi-plus-lg"></i> Add Interview
                    </button>
                </div>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th style="width:32px;">#</th>
                                <th>Role</th>
                                <th>Company</th>
                                <th>Domain</th>
                                <th>Application At</th>
                                <th>Mail At</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Remark</th>
                                <th>Scheduled At</th>
                                <th>Added By</th>
                                @if ($isAdmin)
                                <th style="width:70px;">Actions</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($interviews as $i => $interview)
                            @php
                                $applicationTime = $interview->application_time
                                    ? \Illuminate\Support\Carbon::parse($interview->application_time)->format('h:i A')
                                    : null;
                                $mailTime = $interview->mail_time
                                    ? \Illuminate\Support\Carbon::parse($interview->mail_time)->format('h:i A')
                                    : null;
                                $scheduledTime = $interview->scheduled_time
                                    ? \Illuminate\Support\Carbon::parse($interview->scheduled_time)->format('h:i A')
                                    : null;
                            @endphp
                            <tr>
                                <td class="text-muted text-sm">{{ $i + 1 }}</td>
                                <td class="text-sm" style="max-width:120px;">{{ $interview->role }}</td>
                                <td class="text-sm font-600">{{ $interview->company_name }}</td>
                                <td class="text-sm" style="max-width:140px;">
                                    <a href="{{ $interview->company_domain }}" target="_blank" rel="noopener"
                                       style="color:var(--blue-text);word-break:break-all;">{{ $interview->company_domain }}</a>
                                </td>
                                <td class="text-sm text-muted" style="white-space:nowrap;">
                                    {{ $interview->application_date?->format('M d, Y') ?? '—' }}
                                    @if ($applicationTime)<br><span style="font-size:11px;">{{ $applicationTime }}</span>@endif
                                </td>
                                <td class="text-sm text-muted" style="white-space:nowrap;">
                                    {{ $interview->mail_date?->format('M d, Y') ?? '—' }}
                                    @if ($mailTime)<br><span style="font-size:11px;">{{ $mailTime }}</span>@endif
                                </td>
                                <td>
                                    @php
                                    $typeClass = match($interview->interview_type) {
                                        'phone_call' => 'type-phone',
                                        'virtual'    => 'type-virtual',
                                        'on_site'    => 'type-onsite',
                                        default      => '',
                                    };
                                    @endphp
                                    <span class="badge {{ $typeClass }}">{{ $interviewTypes[$interview->interview_type] ?? $interview->interview_type }}</span>
                                </td>
                                <td>
                                    @if ($interview->interview_status)
                                        <span class="badge {{ $interview->interview_status === 'valid' ? 'badge-success' : 'badge-danger' }}">
                                            {{ ucfirst($interview->interview_status) }}
                                        </span>
                                    @else
                                        <span style="color:var(--text-muted);font-size:12px;">—</span>
                                    @endif
                                </td>
                                <td style="max-width:160px;font-size:12px;color:var(--text-secondary);">{{ $interview->remark ?: '—' }}</td>
                                <td class="text-sm text-muted" style="white-space:nowrap;">
                                    @if ($interview->scheduled_date)
                                        {{ $interview->scheduled_date->format('M d, Y') }}
                                        @if ($scheduledTime)<br><span style="font-size:11px;">{{ $scheduledTime }} {{ $interview->scheduled_timezone }}</span>@endif
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="text-sm text-muted">{{ $interview->recruiter?->name ?? $interview->creator?->name ?? '—' }}</td>
                                @if ($isAdmin)
                                <td>
                                    <div class="d-flex gap-4">
                                        <button class="btn btn-outline btn-sm"
                                            title="Edit"
                                            onclick="openInterviewModal({{ json_encode([
                                                'id'                 => $interview->id,
                                                'application_date'   => $interview->application_date?->format('Y-m-d'),
                                                'application_time'   => $interview->application_time ? substr((string) $interview->application_time, 0, 5) : null,
                                                'role'               => $interview->role,
                                                'company_name'       => $interview->company_name,
                                                'company_domain'     => $interview->company_domain,
                                                'mail_date'          => $interview->mail_date?->format('Y-m-d'),
                                                'mail_time'          => $interview->mail_time ? substr((string) $interview->mail_time, 0, 5) : null,
                                                'interview_type'     => $interview->interview_type,
                                                'remark'             => $interview->remark,
                                                'scheduled_date'     => $interview->scheduled_date?->format('Y-m-d'),
                                                'scheduled_time'     => $interview->scheduled_time ? substr((string) $interview->scheduled_time, 0, 5) : null,
                                                'scheduled_timezone' => $interview->scheduled_timezone,
                                                'interview_status'   => $interview->interview_status,
                                                'update_url'         => route('admin.candidates.interviews.update', [$candidate, $interview]),
                                            ]) }})">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form method="POST" action="{{ route('admin.candidates.interviews.destroy', [$candidate, $interview]) }}"
                                              class="interview-delete-form"
                                              onsubmit="return confirm('Delete this interview?')" style="display:inline;">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                                @endif
                            </tr>
                            @empty
                            <tr>
                                <td colspan="{{ $isAdmin ? 12 : 11 }}">
                                    <div class="page-empty" style="padding:32px 24px;">
                                        <i class="bi bi-briefcase"></i>
                                        <p>No interviews recorded yet. Click "Add Interview" to get started.</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ===== ASSESSMENTS TAB ===== --}}
        <div class="page-tab-panel" id="tab-assessments">
            <div class="card" style="padding:0;">
                <div class="card-header" style="padding:14px 16px;">
                    <div>
                        <div class="card-title">Assessment</div>
                        <div class="card-subtitle">{{ $assessments->count() }} total assessments recorded</div>
                    </div>
                    <button class="btn btn-primary btn-sm" onclick="openAssessmentModal()">
                        <i class="bi bi-plus-lg"></i> Add Assessment
                    </button>
                </div>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th style="width:32px;">#</th>
                                <th>Assessment At</th>
                                <th>Mail At</th>
                                <th>Company</th>
                                <th>Domain</th>
                                <th>Role</th>
                                <th>Type</th>
                                <th>Remark</th>
                                <th>Added By</th>
                                <th>Created At</th>
                                @if ($isAdmin)
                                <th style="width:70px;">Actions</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($assessments as $i => $assessment)
                            @php
                                $assessmentTime = $assessment->assessment_time
                                    ? \Illuminate\Support\Carbon::parse($assessment->assessment_time)->format('h:i A')
                                    : null;
                                $assessmentMailTime = $assessment->mail_time
                                    ? \Illuminate\Support\Carbon::parse($assessment->mail_time)->format('h:i A')
                                    : null;
                            @endphp
                            <tr>
                                <td class="text-muted text-sm">{{ $i + 1 }}</td>
                                <td class="text-sm text-muted" style="white-space:nowrap;">
                                    {{ $assessment->assessment_date?->format('M d, Y') ?? 'â€”' }}
                                    @if ($assessmentTime)<br><span style="font-size:11px;">{{ $assessmentTime }}</span>@endif
                                </td>
                                <td class="text-sm text-muted" style="white-space:nowrap;">
                                    {{ $assessment->mail_date?->format('M d, Y') ?? 'â€”' }}
                                    @if ($assessmentMailTime)<br><span style="font-size:11px;">{{ $assessmentMailTime }}</span>@endif
                                </td>
                                <td class="text-sm font-600" style="max-width:180px;">
                                    <div>{{ $assessment->company_name }}</div>
                                    @if ($assessment->company_website_url)
                                        <a href="{{ $assessment->company_website_url }}" target="_blank" rel="noopener" style="font-size:11px;color:var(--blue-text);word-break:break-all;">
                                            {{ $assessment->company_website_url }}
                                        </a>
                                    @endif
                                </td>
                                <td class="text-sm" style="max-width:140px;">{{ $assessment->domain }}</td>
                                <td class="text-sm" style="max-width:140px;">{{ $assessment->role }}</td>
                                <td>
                                    <span class="badge badge-info">{{ $assessmentTypes[$assessment->assessment_type] ?? $assessment->assessment_type }}</span>
                                </td>
                                <td style="max-width:180px;font-size:12px;color:var(--text-secondary);">{{ $assessment->remark ?: 'â€”' }}</td>
                                <td class="text-sm text-muted">{{ $assessment->recruiter?->name ?? $assessment->creator?->name ?? '—' }}</td>
                                <td class="text-sm text-muted" style="white-space:nowrap;">{{ $assessment->created_at?->format('M d, Y h:i A') ?? 'â€”' }}</td>
                                @if ($isAdmin)
                                <td>
                                    <div class="d-flex gap-4">
                                        <button class="btn btn-outline btn-sm"
                                            title="Edit"
                                            onclick="openAssessmentModal({{ json_encode([
                                                'id' => $assessment->id,
                                                'assessment_date' => $assessment->assessment_date?->format('Y-m-d'),
                                                'assessment_time' => $assessment->assessment_time ? substr((string) $assessment->assessment_time, 0, 5) : null,
                                                'company_name' => $assessment->company_name,
                                                'domain' => $assessment->domain,
                                                'company_website_url' => $assessment->company_website_url,
                                                'role' => $assessment->role,
                                                'assessment_type' => $assessment->assessment_type,
                                                'mail_date' => $assessment->mail_date?->format('Y-m-d'),
                                                'mail_time' => $assessment->mail_time ? substr((string) $assessment->mail_time, 0, 5) : null,
                                                'remark' => $assessment->remark,
                                                'update_url' => route('admin.candidates.assessments.update', [$candidate, $assessment]),
                                            ]) }})">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form method="POST" action="{{ route('admin.candidates.assessments.destroy', [$candidate, $assessment]) }}"
                                              class="assessment-delete-form"
                                              onsubmit="return confirm('Delete this assessment?')" style="display:inline;">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                                @endif
                            </tr>
                            @empty
                            <tr>
                                <td colspan="{{ $isAdmin ? 11 : 10 }}">
                                    <div class="page-empty" style="padding:32px 24px;">
                                        <i class="bi bi-clipboard-check"></i>
                                        <p>No assessments recorded yet. Click "Add Assessment" to get started.</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ===== DOCUMENTS TAB ===== --}}
        <div class="page-tab-panel" id="tab-documents">
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Documents</div>
                    <div class="card-subtitle">All uploaded files for this candidate</div>
                </div>

                @php
                $hasAnyDoc = $candidate->cv_file_path || $candidate->candidate_details_file_path || $candidate->speedy_apply_json_path || $candidate->agreement_file_path || $candidate->resumes->isNotEmpty();
                @endphp

                @if (!$hasAnyDoc)
                    <div class="page-empty">
                        <i class="bi bi-folder2-open"></i>
                        <p>No documents uploaded yet.</p>
                    </div>
                @else
                    @if ($candidate->cv_file_path)
                    <div class="doc-item">
                        <div class="doc-icon" style="background:var(--red-light);color:var(--red-text);">
                            <i class="bi bi-file-earmark-person"></i>
                        </div>
                        <div class="doc-info">
                            <div class="doc-name">CV / Resume</div>
                            <div class="doc-meta">{{ basename($candidate->cv_file_path) }}</div>
                        </div>
                        <div class="d-flex gap-6">
                            <a href="{{ route('admin.candidates.files', [$candidate, 'cv']) }}" target="_blank" rel="noopener" class="btn btn-outline btn-sm">
                                <i class="bi bi-eye"></i> View
                            </a>
                            <a href="{{ route('admin.candidates.files', [$candidate, 'cv']) }}" download class="btn btn-outline btn-sm">
                                <i class="bi bi-download"></i>
                            </a>
                        </div>
                    </div>
                    @endif

                    @if ($candidate->candidate_details_file_path)
                    <div class="doc-item">
                        <div class="doc-icon" style="background:var(--blue-light);color:var(--blue-text);">
                            <i class="bi bi-file-earmark-text"></i>
                        </div>
                        <div class="doc-info">
                            <div class="doc-name">Candidate Details</div>
                            <div class="doc-meta">{{ basename($candidate->candidate_details_file_path) }}</div>
                        </div>
                        <div class="d-flex gap-6">
                            <a href="{{ route('admin.candidates.files', [$candidate, 'details']) }}" target="_blank" rel="noopener" class="btn btn-outline btn-sm">
                                <i class="bi bi-eye"></i> View
                            </a>
                        </div>
                    </div>
                    @endif

                    @if ($candidate->speedy_apply_json_path)
                    <div class="doc-item">
                        <div class="doc-icon" style="background:var(--yellow-light);color:var(--yellow-text);">
                            <i class="bi bi-filetype-json"></i>
                        </div>
                        <div class="doc-info">
                            <div class="doc-name">Speedy Apply JSON</div>
                            <div class="doc-meta">{{ basename($candidate->speedy_apply_json_path) }}</div>
                        </div>
                        <div class="d-flex gap-6">
                            <a href="{{ route('admin.candidates.files', [$candidate, 'speedy']) }}" target="_blank" rel="noopener" class="btn btn-outline btn-sm">
                                <i class="bi bi-eye"></i> View
                            </a>
                            <a href="{{ route('admin.candidates.files', [$candidate, 'speedy']) }}" download class="btn btn-outline btn-sm">
                                <i class="bi bi-download"></i>
                            </a>
                        </div>
                    </div>
                    @endif

                    @if ($candidate->agreement_file_path)
                    <div class="doc-item">
                        <div class="doc-icon" style="background:#f0fdf4;color:#16a34a;">
                            <i class="bi bi-file-earmark-check-fill"></i>
                        </div>
                        <div class="doc-info">
                            <div class="doc-name">Agreement</div>
                            <div class="doc-meta">{{ basename($candidate->agreement_file_path) }}</div>
                        </div>
                        <div class="d-flex gap-6">
                            <a href="{{ route('admin.candidates.files', [$candidate, 'agreement']) }}" target="_blank" rel="noopener" class="btn btn-outline btn-sm">
                                <i class="bi bi-eye"></i> View
                            </a>
                            <a href="{{ route('admin.candidates.files', [$candidate, 'agreement']) }}" download class="btn btn-outline btn-sm">
                                <i class="bi bi-download"></i>
                            </a>
                        </div>
                    </div>
                    @endif

                    @foreach ($candidate->resumes as $resume)
                    <div class="doc-item">
                        <div class="doc-icon" style="background:var(--green-light);color:var(--green-text);">
                            <i class="bi bi-file-earmark-richtext"></i>
                        </div>
                        <div class="doc-info">
                            <div class="doc-name">{{ $resume->designation }}</div>
                            <div class="doc-meta">{{ $resume->original_filename }} &bull; Uploaded {{ $resume->created_at->format('M d, Y') }}</div>
                        </div>
                        <div class="d-flex gap-6">
                            <a href="{{ route('admin.candidates.resumes.download', [$candidate, $resume]) }}" target="_blank" rel="noopener" class="btn btn-outline btn-sm">
                                <i class="bi bi-eye"></i> View
                            </a>
                            <a href="{{ route('admin.candidates.resumes.download', [$candidate, $resume]) }}" download class="btn btn-outline btn-sm">
                                <i class="bi bi-download"></i>
                            </a>
                        </div>
                    </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ===== ADD DAILY LOG MODAL ===== --}}
<div class="modal-overlay" id="logModal">
    <div class="modal modal-sm">
        <form id="dailyLogForm" method="POST" action="{{ route('admin.candidates.daily-logs.store', $candidate) }}">
            @csrf
            <div class="modal-header">
                <div class="modal-title"><i class="bi bi-plus-circle"></i> Add Daily Log</div>
                <button type="button" class="modal-close" onclick="closeLogModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Date</label>
                    <input type="date" name="log_date" class="form-control"
                        value="{{ now()->format('Y-m-d') }}" readonly
                        style="background:var(--main-bg);cursor:default;">
                    <div style="font-size:11px;color:var(--text-muted);margin-top:3px;">
                        <i class="bi bi-lock-fill" style="font-size:10px;"></i> One log per candidate per day.
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Applications <span style="color:var(--red-text)">*</span></label>
                    <input type="number" name="applications" id="logApplications" class="form-control"
                        value="" min="0" max="9999" placeholder="Enter applications count" required>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <!-- <div class="form-group mb-0">
                        <label class="form-label">Applications <span style="color:var(--red-text)">*</span></label>
                        <input type="number" name="applications" class="form-control"
                            value="0" min="0" max="9999" required>
                    </div> -->
                    <!-- <div class="form-group mb-0">
                        <label class="form-label">
                            Assessment Total
                            <span style="font-weight:400;color:var(--text-muted);font-size:11px;">(auto-calculated)</span>
                        </label>
                        <div class="form-control" style="background:var(--main-bg);color:var(--text-muted);cursor:not-allowed;display:flex;align-items:center;gap:6px;">
                            <i class="bi bi-lock-fill" style="font-size:11px;"></i>
                            <span>Auto-counted from the assessment tab</span>
                        </div>
                    </div> -->
                </div>
                <!-- <div class="form-group" style="margin-top:12px;">
                    <label class="form-label">
                        Interview Total
                        <span style="font-weight:400;color:var(--text-muted);font-size:11px;">(auto-calculated)</span>
                    </label>
                    <div class="form-control" style="background:var(--main-bg);color:var(--text-muted);cursor:not-allowed;display:flex;align-items:center;gap:6px;">
                        <i class="bi bi-lock-fill" style="font-size:11px;"></i>
                        <span>Auto-counted from the interview tab</span>
                    </div>
                </div> -->
                <div class="form-group mb-0">
                    <label class="form-label">Remark <span style="color:var(--text-muted);font-weight:400;font-size:11px;">(optional)</span></label>
                    <textarea name="remark" class="form-control" rows="2"
                        placeholder="Notes about today's activity..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeLogModal()">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Save Log</button>
            </div>
        </form>
    </div>
</div>

{{-- ===== INTERVIEW MODAL (shared add/edit) ===== --}}
<div class="modal-overlay" id="interviewModal">
    <div class="modal modal-lg">
        <form id="interviewForm" method="POST">
            @csrf
            <span id="interviewMethodSpoof" style="display:none;"></span>
            <div class="modal-header">
                <div class="modal-title" id="interviewModalTitle"><i class="bi bi-briefcase"></i> Add Interview</div>
                <button type="button" class="modal-close" onclick="closeInterviewModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Application Date &amp; Time <span style="color:var(--red-text);">*</span></label>
                        <input type="text" id="iApplicationAtDisplay" class="form-control" placeholder="Select application date and time">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Mail Date &amp; Time</label>
                        <input type="text" id="iMailAtDisplay" class="form-control" placeholder="Select mail date and time">
                    </div>
                    <input type="hidden" name="application_date" id="iApplicationDate">
                    <input type="hidden" name="application_time" id="iApplicationTime">
                    <input type="hidden" name="mail_date" id="iMailDate">
                    <input type="hidden" name="mail_time" id="iMailTime">
                    <div class="form-group">
                        <label class="form-label">Company Name <span style="color:var(--red-text);">*</span></label>
                        <input type="text" name="company_name" id="iCompanyName" class="form-control" placeholder="e.g. Acme Corp" maxlength="200" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Company Domain / URL <span style="color:var(--red-text);">*</span></label>
                        <input type="text" name="company_domain" id="iCompanyDomain" class="form-control" placeholder="https://company.com or www.company.com" maxlength="500" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Role <span style="color:var(--red-text);">*</span></label>
                        <input type="text" name="role" id="iRole" class="form-control" placeholder="e.g. Software Engineer" maxlength="200" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Interview Type <span style="color:var(--red-text);">*</span></label>
                        <select name="interview_type" id="iType" class="form-control" required>
                            <option value="">Select type...</option>
                            <option value="phone_call">Phone Call</option>
                            <option value="virtual">Virtual</option>
                            <option value="on_site">On Site</option>
                        </select>
                    </div>
                    <div class="form-group" style="grid-column:span 2;">
                        <label class="form-label">Remark</label>
                        <textarea name="remark" id="iRemark" class="form-control" rows="3" placeholder="Additional notes..."></textarea>
                    </div>
                    @if ($isAdmin)
                    <div class="form-group">
                        <label class="form-label">Interview Status <span class="text-muted text-sm">(admin only)</span></label>
                        <select name="interview_status" id="iInterviewStatus" class="form-control">
                            <option value="">Not marked</option>
                            <option value="valid">Valid</option>
                            <option value="invalid">Invalid</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Interview Schedule Date &amp; Time <span class="text-muted text-sm">(admin only)</span></label>
                        <input type="text" id="iScheduledAtDisplay" class="form-control" placeholder="Select schedule date and time">
                    </div>
                    <input type="hidden" name="scheduled_date" id="iSchedDate">
                    <input type="hidden" name="scheduled_time" id="iSchedTime">
                    <div class="form-group">
                        <label class="form-label">Timezone <span class="text-muted text-sm">(admin only)</span></label>
                        <select name="scheduled_timezone" id="iTimezone" class="form-control">
                            <option value="">Select timezone...</option>
                            @foreach ($timezones as $tz)
                                <option value="{{ $tz }}">{{ $tz }}</option>
                            @endforeach
                        </select>
                    </div>
                    @else
                    {{-- Hidden fields so JS populate works without errors --}}
                    <input type="hidden" name="scheduled_date"     id="iSchedDate">
                    <input type="hidden" name="scheduled_time"     id="iSchedTime">
                    <input type="hidden" name="scheduled_timezone" id="iTimezone">
                    <input type="hidden" name="interview_status"   id="iInterviewStatus">
                    @endif
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeInterviewModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" id="interviewSubmitBtn">Add Interview</button>
            </div>
        </form>
    </div>
</div>

{{-- ===== ASSESSMENT MODAL (shared add/edit) ===== --}}
<div class="modal-overlay" id="assessmentModal">
    <div class="modal modal-lg">
        <form id="assessmentForm" method="POST">
            @csrf
            <div class="modal-header">
                <div class="modal-title" id="assessmentModalTitle"><i class="bi bi-clipboard-check"></i> Add Assessment</div>
                <button type="button" class="modal-close" onclick="closeAssessmentModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Assessment Date &amp; Time <span style="color:var(--red-text);">*</span></label>
                        <input type="text" id="aAssessmentAtDisplay" class="form-control" placeholder="Select assessment date and time">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Company Name <span style="color:var(--red-text);">*</span></label>
                        <input type="text" name="company_name" id="aCompanyName" class="form-control" placeholder="e.g. Acme Corp" maxlength="200" required>
                    </div>
                    <input type="hidden" name="assessment_date" id="aAssessmentDate">
                    <input type="hidden" name="assessment_time" id="aAssessmentTime">
                    <div class="form-group">
                        <label class="form-label">Domain <span style="color:var(--red-text);">*</span></label>
                        <input type="text" name="domain" id="aDomain" class="form-control" placeholder="e.g. Java, DevOps, Healthcare" maxlength="200" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Company Website URL <span style="color:var(--red-text);">*</span></label>
                        <input type="text" name="company_website_url" id="aCompanyWebsiteUrl" class="form-control" placeholder="https://company.com" maxlength="500" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Role <span style="color:var(--red-text);">*</span></label>
                        <input type="text" name="role" id="aRole" class="form-control" placeholder="e.g. Software Engineer" maxlength="200" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Type <span style="color:var(--red-text);">*</span></label>
                        <select name="assessment_type" id="aType" class="form-control" required>
                            <option value="">Select type...</option>
                            @foreach ($assessmentTypes as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Mail Date &amp; Time</label>
                        <input type="text" id="aMailAtDisplay" class="form-control" placeholder="Select mail date and time">
                    </div>
                    <input type="hidden" name="mail_date" id="aMailDate">
                    <input type="hidden" name="mail_time" id="aMailTime">
                    <div class="form-group" style="grid-column:span 2;">
                        <label class="form-label">Remark</label>
                        <textarea name="remark" id="aRemark" class="form-control" rows="3" placeholder="Additional notes..."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeAssessmentModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" id="assessmentSubmitBtn">Add Assessment</button>
            </div>
        </form>
    </div>
</div>

<script>
// ── Inline field patch ──────────────────────────────────────────────
const PATCH_URL = '{{ route('admin.candidates.patch-field', $candidate) }}';
const CSRF      = document.querySelector('meta[name="csrf-token"]').content;
const PREVIEW_GEO_VALUES = {
    country: @json($candidate->country),
    state_province: @json($candidate->state_province),
    city: @json($candidate->city),
};
const PREVIEW_BADGE_FIELDS = ['sub_domain', 'preferred_city'];
const PREVIEW_PHONE_FIELDS = ['phone_number', 'marketing_phone'];

function previewDisplayValue(field, value) {
    if (PREVIEW_BADGE_FIELDS.includes(field)) {
        return (value || '').split(',').map(v => v.trim()).filter(Boolean).join(' · ') || '—';
    }

    const select = document.querySelector('#field-edit-' + field + ' select');
    if (select && select.selectedOptions.length) {
        return select.selectedOptions[0].textContent || '—';
    }

    return value || '—';
}

function saveInlineField(field, value) {
    fetch(PATCH_URL, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF,
            'Accept': 'application/json',
        },
        body: JSON.stringify({ field, value }),
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast('Field updated successfully.', 'success');
            // Update displayed value
            const displayEl = document.getElementById('field-display-' + field);
            if (displayEl) displayEl.textContent = previewDisplayValue(field, value);
            const editWrap = document.getElementById('field-edit-' + field);
            const dispWrap = document.getElementById('field-show-' + field);
            if (editWrap) editWrap.style.display = 'none';
            if (dispWrap) dispWrap.style.display = '';
            // Update full name if name fields changed
            if (['first_name','last_name','middle_name'].includes(field) && data.full_name) {
                document.getElementById('displayFullName').textContent = data.full_name;
            }
            // Update status badge
            if (field === 'status') {
                const badgeMap = {
                    active:'badge-success', enrolled:'badge-info', interview:'badge-warning',
                    offer:'badge-primary', placed:'badge-success', onhold:'badge-warning', inactive:'badge-danger'
                };
                const badge = document.getElementById('statusBadge');
                if (badge) {
                    badge.className = 'badge ' + (badgeMap[value] || 'badge-neutral');
                    badge.textContent = value.charAt(0).toUpperCase() + value.slice(1);
                }
                cancelInlineEdit('statusDisplay','statusEditWrap');
            }
            // Update notes
            if (field === 'recruiter_notes') {
                document.getElementById('notesDisplay').textContent = value || '—';
                cancelNotesEdit();
            }
            if (Object.prototype.hasOwnProperty.call(PREVIEW_GEO_VALUES, field)) {
                PREVIEW_GEO_VALUES[field] = value || '';
            }
        } else {
            showToast(data.message || 'Could not save field.', 'error');
        }
    })
    .catch(() => showToast('Network error. Please try again.', 'error'));
}

function cancelInlineEdit(displayId, editId) {
    const d = document.getElementById(displayId);
    const e = document.getElementById(editId);
    if (d) d.style.display = '';
    if (e) { e.classList.remove('active'); e.style.display = 'none'; }
}

function startStatusEdit() {
    document.getElementById('statusDisplay').style.display = 'none';
    const w = document.getElementById('statusEditWrap');
    w.classList.add('active');
    w.style.display = '';
}

function startNotesEdit() {
    document.getElementById('notesEditBtn').style.display = 'none';
    document.getElementById('notesEditWrap').style.display = '';
}
function cancelNotesEdit() {
    document.getElementById('notesEditBtn').style.display = '';
    document.getElementById('notesEditWrap').style.display = 'none';
}

// Generic inline edit triggers (used by _preview_field partial)
function startFieldEdit(field) {
    const show = document.getElementById('field-show-' + field);
    const edit = document.getElementById('field-edit-' + field);
    if (show) show.style.display = 'none';
    if (edit) { edit.style.display = 'flex'; edit.classList.add('active'); }
    initPreviewFieldEditor(field);
    // Focus input
    const inp = document.querySelector('#field-edit-' + field + ' .inline-edit-input, #field-edit-' + field + ' .subdomain-text-input');
    if (inp) inp.focus();
}

function cancelFieldEdit(field) {
    const show = document.getElementById('field-show-' + field);
    const edit = document.getElementById('field-edit-' + field);
    if (show) show.style.display = '';
    if (edit) { edit.style.display = 'none'; edit.classList.remove('active'); }
}

function saveFieldEdit(field) {
    flushPreviewBadgeInput(field);
    const inp = document.getElementById('input-' + field) || document.getElementById('preview_' + field);
    if (!inp) return;
    saveInlineField(field, normalizedPreviewFieldValue(field, inp));
}

function initPreviewFieldEditor(field) {
    if (PREVIEW_BADGE_FIELDS.includes(field)) {
        renderPreviewBadges(field);
        return;
    }

    if (PREVIEW_PHONE_FIELDS.includes(field)) {
        if (window._initPhoneInputs) window._initPhoneInputs(document.getElementById('field-edit-' + field));
        return;
    }

    if (['country', 'state_province', 'city'].includes(field)) {
        if (window._initCandidateSelects) window._initCandidateSelects(document.getElementById('field-edit-' + field));
        if (window._setCandidateGeo) {
            window._setCandidateGeo('preview', PREVIEW_GEO_VALUES.country || '', PREVIEW_GEO_VALUES.state_province || '', PREVIEW_GEO_VALUES.city || '');
        }
    }
}

function normalizedPreviewFieldValue(field, input) {
    if (PREVIEW_PHONE_FIELDS.includes(field)) {
        const raw = (input.value || '').replace(/\D/g, '');
        if (!raw) return '';
        const instance = window.intlTelInputGlobals?.getInstance(input);
        const dialCode = instance?.getSelectedCountryData()?.dialCode || '';
        return dialCode && raw.indexOf(dialCode) !== 0 ? '+' + dialCode + raw : (input.value || '');
    }

    return input.value;
}

function previewBadgeValues(field) {
    const hidden = document.getElementById('input-' + field);
    return hidden && hidden.value
        ? hidden.value.split(',').map(v => v.trim()).filter(Boolean)
        : [];
}

function renderPreviewBadges(field) {
    const container = document.getElementById('badge-wrap-' + field);
    const input = document.getElementById('badge-input-' + field);
    if (!container || !input) return;

    container.querySelectorAll('.subdomain-badge').forEach(el => el.remove());
    previewBadgeValues(field).forEach(function (value, index) {
        const badge = document.createElement('span');
        badge.className = 'subdomain-badge';
        badge.innerHTML = escapePreviewHtml(value) + '<button type="button" class="subdomain-badge-x" onclick="removePreviewBadge(\'' + field + '\',' + index + ')">&times;</button>';
        container.insertBefore(badge, input);
    });
}

function handlePreviewBadgeKey(event, field) {
    if (event.key !== 'Enter' && event.key !== ',') return;
    event.preventDefault();

    const input = document.getElementById('badge-input-' + field);
    const hidden = document.getElementById('input-' + field);
    if (!input || !hidden) return;

    const value = input.value.trim().replace(/,/g, '');
    if (!value) return;

    const values = previewBadgeValues(field);
    if (!values.includes(value)) {
        values.push(value);
        hidden.value = values.join(',');
    }

    input.value = '';
    renderPreviewBadges(field);
}

function flushPreviewBadgeInput(field) {
    if (!PREVIEW_BADGE_FIELDS.includes(field)) return;
    handlePreviewBadgeKey({ key: 'Enter', preventDefault: function () {} }, field);
}

function removePreviewBadge(field, index) {
    const hidden = document.getElementById('input-' + field);
    if (!hidden) return;
    const values = previewBadgeValues(field);
    values.splice(index, 1);
    hidden.value = values.join(',');
    renderPreviewBadges(field);
}

function escapePreviewHtml(value) {
    return String(value).replace(/[&<>"']/g, function (char) {
        return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[char];
    });
}

// ── Tab switching ──────────────────────────────────────────────────
const TAB_STORAGE_KEY = 'candidate_preview_active_tab_{{ $candidate->id }}';

function persistActiveTab(name) {
    try {
        sessionStorage.setItem(TAB_STORAGE_KEY, name);
    } catch (e) {}
}

function activateTab(name) {
    const panel = document.getElementById('tab-' + name);
    const button = document.querySelector('.page-tab[data-tab="' + name + '"]');
    if (!panel || !button) return;

    document.querySelectorAll('.page-tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.page-tab').forEach(b => b.classList.remove('active'));
    panel.classList.add('active');
    button.classList.add('active');
    persistActiveTab(name);
}

function switchTab(name, btn) {
    activateTab(name);
}

// ── Daily Log Modal ────────────────────────────────────────────────
function openLogModal() {
    const form = document.getElementById('dailyLogForm');
    if (form) form.reset();
    resetModalValidation(form);
    document.getElementById('logModal').classList.add('open');
}
function closeLogModal() {
    document.getElementById('logModal').classList.remove('open');
}

function registerCandidatePreviewValidationMethods() {
    if (typeof $ === 'undefined' || typeof $.validator === 'undefined') return;

    if (!$.validator.methods.noSpacesOnly) {
        $.validator.addMethod('noSpacesOnly', function (value, element) {
            return this.optional(element) || value.trim().length > 0;
        }, 'This field cannot be blank spaces only.');
    }

    if (!$.validator.methods.portalUrlAllowed) {
        $.validator.addMethod('portalUrlAllowed', function (value, element) {
            if (this.optional(element)) return true;
            const rawValue = value.trim();
            const normalizedValue = /^(https?:\/\/|www\.)/i.test(rawValue)
                ? rawValue.replace(/^www\./i, 'https://www.')
                : 'https://' + rawValue;

            try {
                const parsedUrl = new URL(normalizedValue);
                return ['http:', 'https:'].includes(parsedUrl.protocol)
                    && parsedUrl.hostname.includes('.')
                    && !/\s/.test(rawValue);
            } catch (e) {
                return false;
            }
        }, 'Please enter a valid URL or domain.');
    }
}

function resetModalValidation(form) {
    if (!form || typeof $ === 'undefined' || typeof $.validator === 'undefined') return;

    const $form = $(form);
    if ($form.data('validator')) {
        $form.validate().resetForm();
    }
    $form.find('.is-invalid').removeClass('is-invalid');
    $form.find('label.sp-field-error').remove();
}

function setupCandidatePreviewFormValidation() {
    if (typeof $ === 'undefined' || typeof $.validator === 'undefined') return;
    registerCandidatePreviewValidationMethods();

    const baseOptions = {
        errorClass: 'sp-field-error',
        groups: {
            interviewApplicationAt: 'application_date application_time',
            assessmentAt: 'assessment_date assessment_time',
        },
        ignore: function (i, el) {
            const $el = $(el);
            if ($el.prop('disabled')) return true;
            if ($el.attr('type') !== 'hidden') return false;
            return !['application_date', 'application_time', 'assessment_date', 'assessment_time'].includes($el.attr('name') || '');
        },
        onfocusout: function (element) {
            this.element(element);
        },
        errorPlacement: function (error, element) {
            const name = element.attr('name') || '';
            if (['application_date', 'application_time'].includes(name)) {
                error.insertAfter($('#iApplicationAtDisplay').next('input').length
                    ? $('#iApplicationAtDisplay').next('input')
                    : $('#iApplicationAtDisplay'));
                return;
            }
            if (['assessment_date', 'assessment_time'].includes(name)) {
                error.insertAfter($('#aAssessmentAtDisplay').next('input').length
                    ? $('#aAssessmentAtDisplay').next('input')
                    : $('#aAssessmentAtDisplay'));
                return;
            }
            error.insertAfter(element);
        },
        highlight: function (element) {
            const name = element.name || '';
            if (['application_date', 'application_time'].includes(name)) {
                ($('#iApplicationAtDisplay').next('input').length
                    ? $('#iApplicationAtDisplay').next('input')
                    : $('#iApplicationAtDisplay')).addClass('is-invalid');
                return;
            }
            if (['assessment_date', 'assessment_time'].includes(name)) {
                ($('#aAssessmentAtDisplay').next('input').length
                    ? $('#aAssessmentAtDisplay').next('input')
                    : $('#aAssessmentAtDisplay')).addClass('is-invalid');
                return;
            }
            $(element).addClass('is-invalid');
        },
        unhighlight: function (element) {
            const name = element.name || '';
            if (['application_date', 'application_time'].includes(name)) {
                ($('#iApplicationAtDisplay').next('input').length
                    ? $('#iApplicationAtDisplay').next('input')
                    : $('#iApplicationAtDisplay')).removeClass('is-invalid');
                return;
            }
            if (['assessment_date', 'assessment_time'].includes(name)) {
                ($('#aAssessmentAtDisplay').next('input').length
                    ? $('#aAssessmentAtDisplay').next('input')
                    : $('#aAssessmentAtDisplay')).removeClass('is-invalid');
                return;
            }
            $(element).removeClass('is-invalid');
        },
        invalidHandler: function (event, validator) {
            if (!validator.errorList || !validator.errorList.length) return;
            showToast('Please fix the highlighted errors before submitting.', 'error');
            setTimeout(function () {
                const firstEl = validator.errorList[0].element;
                const name = firstEl.name || '';
                if (['application_date', 'application_time'].includes(name)) {
                    document.getElementById('iApplicationAtDisplay')._flatpickr?.open();
                    return;
                }
                if (['assessment_date', 'assessment_time'].includes(name)) {
                    document.getElementById('aAssessmentAtDisplay')._flatpickr?.open();
                    return;
                }
                firstEl.focus();
            }, 80);
        },
    };

    [
        {
            selector: '#dailyLogForm',
            tabName: 'logs',
            buttonText: '<i class="bi bi-hourglass-split"></i> Saving...',
            rules: {
                applications: { required: true, digits: true, min: 0, max: 9999 },
                remark: { maxlength: 2000 },
            },
            messages: {
                applications: {
                    required: 'Applications count is required.',
                    digits: 'Applications must be a whole number.',
                    min: 'Applications cannot be negative.',
                    max: 'Applications cannot be more than 9999.',
                },
                remark: { maxlength: 'Remark cannot be more than 2000 characters.' },
            },
        },
        {
            selector: '#interviewForm',
            tabName: 'interviews',
            buttonText: '<i class="bi bi-hourglass-split"></i> Saving...',
            rules: {
                application_date: { required: true },
                application_time: { required: true },
                company_name: { required: true, noSpacesOnly: true, maxlength: 200 },
                company_domain: { required: true, noSpacesOnly: true, portalUrlAllowed: true, maxlength: 500 },
                role: { required: true, noSpacesOnly: true, maxlength: 200 },
                interview_type: { required: true },
                interview_status: { pattern: /^(valid|invalid)?$/ },
                remark: { maxlength: 2000 },
                scheduled_timezone: {
                    required: function () {
                        const date = document.getElementById('iSchedDate');
                        const time = document.getElementById('iSchedTime');
                        return Boolean((date && date.value) || (time && time.value));
                    },
                },
            },
            messages: {
                application_date: { required: 'Application date and time is required.' },
                application_time: { required: 'Application date and time is required.' },
                company_name: {
                    required: 'Company name is required.',
                    noSpacesOnly: 'Company name cannot be blank spaces only.',
                    maxlength: 'Company name cannot be more than 200 characters.',
                },
                company_domain: {
                    required: 'Company domain or URL is required.',
                    noSpacesOnly: 'Company domain cannot be blank spaces only.',
                    portalUrlAllowed: 'Please enter a valid domain or URL.',
                    maxlength: 'Company domain cannot be more than 500 characters.',
                },
                role: {
                    required: 'Role is required.',
                    noSpacesOnly: 'Role cannot be blank spaces only.',
                    maxlength: 'Role cannot be more than 200 characters.',
                },
                interview_type: { required: 'Interview type is required.' },
                interview_status: { pattern: 'Interview status must be Valid or Invalid.' },
                remark: { maxlength: 'Remark cannot be more than 2000 characters.' },
                scheduled_timezone: { required: 'Timezone is required when schedule date/time is selected.' },
            },
        },
        {
            selector: '#assessmentForm',
            tabName: 'assessments',
            buttonText: '<i class="bi bi-hourglass-split"></i> Saving...',
            rules: {
                assessment_date: { required: true },
                assessment_time: { required: true },
                company_name: { required: true, noSpacesOnly: true, maxlength: 200 },
                domain: { required: true, noSpacesOnly: true, maxlength: 200 },
                company_website_url: { required: true, noSpacesOnly: true, portalUrlAllowed: true, maxlength: 500 },
                role: { required: true, noSpacesOnly: true, maxlength: 200 },
                assessment_type: { required: true },
                remark: { maxlength: 2000 },
            },
            messages: {
                assessment_date: { required: 'Assessment date and time is required.' },
                assessment_time: { required: 'Assessment date and time is required.' },
                company_name: {
                    required: 'Company name is required.',
                    noSpacesOnly: 'Company name cannot be blank spaces only.',
                    maxlength: 'Company name cannot be more than 200 characters.',
                },
                domain: {
                    required: 'Domain is required.',
                    noSpacesOnly: 'Domain cannot be blank spaces only.',
                    maxlength: 'Domain cannot be more than 200 characters.',
                },
                company_website_url: {
                    required: 'Company website URL is required.',
                    noSpacesOnly: 'Company website URL cannot be blank spaces only.',
                    portalUrlAllowed: 'Please enter a valid company website URL.',
                    maxlength: 'Company website URL cannot be more than 500 characters.',
                },
                role: {
                    required: 'Role is required.',
                    noSpacesOnly: 'Role cannot be blank spaces only.',
                    maxlength: 'Role cannot be more than 200 characters.',
                },
                assessment_type: { required: 'Assessment type is required.' },
                remark: { maxlength: 'Remark cannot be more than 2000 characters.' },
            },
        },
    ].forEach(function (config) {
        const $form = $(config.selector);
        if (!$form.length) return;
        if ($form.data('validator')) $form.data('validator').destroy();

        $form.validate($.extend(true, {}, baseOptions, {
            rules: config.rules,
            messages: config.messages,
            submitHandler: function (form) {
                persistActiveTab(config.tabName);
                const $btn = $(form).find('.modal-footer .btn-primary');
                $btn.prop('disabled', true).html(config.buttonText);
                form.submit();
            },
        }));
    });
}

// ── Daily Log inline edit ──────────────────────────────────────────
const IS_ADMIN = {{ $isAdmin ? 'true' : 'false' }};

function startLogEdit(row) {
    // Show edit inputs, hide view values
    row.querySelectorAll('.view-val').forEach(el => el.style.display = 'none');
    row.querySelectorAll('.td-assistant .view-val, .td-interviews .view-val').forEach(el => el.style.display = '');
    row.querySelectorAll('.edit-val').forEach(el => {
        if (!IS_ADMIN && el.tagName === 'INPUT' && ['applications'].includes(el.dataset.field)) {
            // Non-admin: skip numeric fields
            el.style.display = 'none';
            el.previousElementSibling && (el.previousElementSibling.style.display = '');
        } else {
            el.style.display = '';
        }
    });
    // Show action buttons
    const actionDiv = row.querySelector('.td-log-actions .edit-val');
    if (actionDiv) actionDiv.style.setProperty('display', 'flex', 'important');
    row.ondblclick = null;
}

function cancelLogEdit(row) {
    row.querySelectorAll('.view-val').forEach(el => el.style.display = '');
    row.querySelectorAll('.edit-val').forEach(el => el.style.display = 'none');
    const actionDiv = row.querySelector('.td-log-actions .edit-val');
    if (actionDiv) actionDiv.style.setProperty('display', 'none', 'important');
    row.ondblclick = function() { startLogEdit(this); };
}

function saveLogEdit(row) {
    const updateUrl = row.dataset.updateUrl;
    const payload   = {};

    row.querySelectorAll('.edit-val[data-field]').forEach(inp => {
        if (inp.style.display !== 'none') {
            payload[inp.dataset.field] = inp.value;
        }
    });

    fetch(updateUrl, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF,
            'Accept': 'application/json',
        },
        body: JSON.stringify(payload),
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast('Log updated.', 'success');
            const logData = data.log || {};
            const assessmentCell = row.querySelector('.td-assistant .view-val');
            const interviewsCell = row.querySelector('.td-interviews .view-val');
            // Update view values
            row.querySelectorAll('.edit-val[data-field]').forEach(inp => {
                const field = inp.dataset.field;
                const viewEl = inp.previousElementSibling;
                if (viewEl && viewEl.classList.contains('view-val')) {
                    viewEl.textContent = inp.value || '—';
                }
            });
            if (assessmentCell) assessmentCell.textContent = String(logData.assistant_count ?? assessmentCell.textContent);
            if (interviewsCell) interviewsCell.textContent = String(logData.interview_count ?? interviewsCell.textContent);
            cancelLogEdit(row);
        } else {
            showToast(data.message || 'Update failed.', 'error');
        }
    })
    .catch(() => showToast('Network error.', 'error'));
}

// ── Interview Modal ────────────────────────────────────────────────
const ADD_INTERVIEW_URL = '{{ route('admin.candidates.interviews.store', $candidate) }}';
const interviewDateTimePickers = {};

function padDateTimePart(value) {
    return String(value).padStart(2, '0');
}

function normalizePickerTime(timeValue) {
    return timeValue ? String(timeValue).slice(0, 5) : '00:00';
}

function syncInterviewDateTimeHidden(displayId, dateId, timeId, selectedDates) {
    const dateInput = document.getElementById(dateId);
    const timeInput = document.getElementById(timeId);
    if (!dateInput || !timeInput) return;

    if (!selectedDates.length) {
        dateInput.value = '';
        timeInput.value = '';
        return;
    }

    const date = selectedDates[0];
    dateInput.value = [
        date.getFullYear(),
        padDateTimePart(date.getMonth() + 1),
        padDateTimePart(date.getDate()),
    ].join('-');
    timeInput.value = [
        padDateTimePart(date.getHours()),
        padDateTimePart(date.getMinutes()),
    ].join(':');
}

function setInterviewDateTimeValue(displayId, dateId, timeId, dateValue, timeValue) {
    const displayInput = document.getElementById(displayId);
    const dateInput = document.getElementById(dateId);
    const timeInput = document.getElementById(timeId);
    const picker = interviewDateTimePickers[displayId];

    if (!displayInput || !dateInput || !timeInput) return;

    if (!dateValue) {
        dateInput.value = '';
        timeInput.value = '';
        if (picker) {
            picker.clear();
        } else {
            displayInput.value = '';
        }
        return;
    }

    const normalizedTime = normalizePickerTime(timeValue);
    dateInput.value = dateValue;
    timeInput.value = normalizedTime;

    if (picker) {
        picker.setDate(dateValue + ' ' + normalizedTime, true, 'Y-m-d H:i');
    } else {
        displayInput.value = dateValue + ' ' + normalizedTime;
    }
}

function initInterviewDateTimePicker(displayId, dateId, timeId) {
    const displayInput = document.getElementById(displayId);
    if (!displayInput || typeof flatpickr === 'undefined' || interviewDateTimePickers[displayId]) {
        return;
    }

    interviewDateTimePickers[displayId] = flatpickr(displayInput, {
        enableTime: true,
        dateFormat: 'Y-m-d H:i',
        altInput: true,
        altFormat: 'M d, Y h:i K',
        time_24hr: false,
        allowInput: false,
        minuteIncrement: 5,
        onChange: function (selectedDates) {
            syncInterviewDateTimeHidden(displayId, dateId, timeId, selectedDates);
        },
        onClose: function (selectedDates) {
            syncInterviewDateTimeHidden(displayId, dateId, timeId, selectedDates);
        },
    });
}

function openInterviewModal(data) {
    const form   = document.getElementById('interviewForm');
    const title  = document.getElementById('interviewModalTitle');
    const btn    = document.getElementById('interviewSubmitBtn');

    form.reset();
    resetModalValidation(form);
    setInterviewDateTimeValue('iApplicationAtDisplay', 'iApplicationDate', 'iApplicationTime', '', '');
    setInterviewDateTimeValue('iMailAtDisplay', 'iMailDate', 'iMailTime', '', '');
    setInterviewDateTimeValue('iScheduledAtDisplay', 'iSchedDate', 'iSchedTime', '', '');
    const statusInput = document.getElementById('iInterviewStatus');
    if (statusInput) statusInput.value = '';

    const existingMethod = form.querySelector('input[name="_method"]');
    if (existingMethod) existingMethod.remove();

    if (data) {
        title.innerHTML = '<i class="bi bi-pencil"></i> Edit Interview';
        btn.textContent = 'Update Interview';
        form.action     = data.update_url;

        const methodInput = document.createElement('input');
        methodInput.type  = 'hidden';
        methodInput.name  = '_method';
        methodInput.value = 'PUT';
        form.insertBefore(methodInput, form.firstChild);

        setInterviewDateTimeValue('iApplicationAtDisplay', 'iApplicationDate', 'iApplicationTime', data.application_date || '', data.application_time || '');
        setInterviewDateTimeValue('iMailAtDisplay', 'iMailDate', 'iMailTime', data.mail_date || '', data.mail_time || '');
        setInterviewDateTimeValue('iScheduledAtDisplay', 'iSchedDate', 'iSchedTime', data.scheduled_date || '', data.scheduled_time || '');
        document.getElementById('iRole').value          = data.role || '';
        document.getElementById('iCompanyName').value   = data.company_name || '';
        document.getElementById('iCompanyDomain').value = data.company_domain || '';
        document.getElementById('iType').value          = data.interview_type || '';
        document.getElementById('iTimezone').value      = data.scheduled_timezone || '';
        if (statusInput) statusInput.value              = data.interview_status || '';
        document.getElementById('iRemark').value        = data.remark || '';
    } else {
        title.innerHTML = '<i class="bi bi-briefcase"></i> Add Interview';
        btn.textContent = 'Add Interview';
        form.action     = ADD_INTERVIEW_URL;
    }

    if (window.openModal) {
        window.openModal('interviewModal');
    } else {
        document.getElementById('interviewModal').classList.add('open');
    }
}

function closeInterviewModal() {
    if (window.closeModal) {
        window.closeModal('interviewModal');
    } else {
        document.getElementById('interviewModal').classList.remove('open');
    }
}

const ADD_ASSESSMENT_URL = '{{ route('admin.candidates.assessments.store', $candidate) }}';
const assessmentDateTimePickers = {};

function syncAssessmentDateTimeHidden(displayId, dateId, timeId, selectedDates) {
    const dateInput = document.getElementById(dateId);
    const timeInput = document.getElementById(timeId);
    if (!dateInput || !timeInput) return;

    if (!selectedDates.length) {
        dateInput.value = '';
        timeInput.value = '';
        return;
    }

    const date = selectedDates[0];
    dateInput.value = [
        date.getFullYear(),
        padDateTimePart(date.getMonth() + 1),
        padDateTimePart(date.getDate()),
    ].join('-');
    timeInput.value = [
        padDateTimePart(date.getHours()),
        padDateTimePart(date.getMinutes()),
    ].join(':');
}

function setAssessmentDateTimeValue(displayId, dateId, timeId, dateValue, timeValue) {
    const displayInput = document.getElementById(displayId);
    const dateInput = document.getElementById(dateId);
    const timeInput = document.getElementById(timeId);
    const picker = assessmentDateTimePickers[displayId];

    if (!displayInput || !dateInput || !timeInput) return;

    if (!dateValue) {
        dateInput.value = '';
        timeInput.value = '';
        if (picker) {
            picker.clear();
        } else {
            displayInput.value = '';
        }
        return;
    }

    const normalizedTime = normalizePickerTime(timeValue);
    dateInput.value = dateValue;
    timeInput.value = normalizedTime;

    if (picker) {
        picker.setDate(dateValue + ' ' + normalizedTime, true, 'Y-m-d H:i');
    } else {
        displayInput.value = dateValue + ' ' + normalizedTime;
    }
}

function initAssessmentDateTimePicker(displayId, dateId, timeId) {
    const displayInput = document.getElementById(displayId);
    if (!displayInput || typeof flatpickr === 'undefined' || assessmentDateTimePickers[displayId]) {
        return;
    }

    assessmentDateTimePickers[displayId] = flatpickr(displayInput, {
        enableTime: true,
        dateFormat: 'Y-m-d H:i',
        altInput: true,
        altFormat: 'M d, Y h:i K',
        time_24hr: false,
        allowInput: false,
        minuteIncrement: 5,
        onChange: function (selectedDates) {
            syncAssessmentDateTimeHidden(displayId, dateId, timeId, selectedDates);
        },
        onClose: function (selectedDates) {
            syncAssessmentDateTimeHidden(displayId, dateId, timeId, selectedDates);
        },
    });
}

function openAssessmentModal(data) {
    const form = document.getElementById('assessmentForm');
    const title = document.getElementById('assessmentModalTitle');
    const btn = document.getElementById('assessmentSubmitBtn');

    form.reset();
    resetModalValidation(form);
    setAssessmentDateTimeValue('aAssessmentAtDisplay', 'aAssessmentDate', 'aAssessmentTime', '', '');
    setAssessmentDateTimeValue('aMailAtDisplay', 'aMailDate', 'aMailTime', '', '');

    const existingMethod = form.querySelector('input[name="_method"]');
    if (existingMethod) existingMethod.remove();

    if (data) {
        title.innerHTML = '<i class="bi bi-pencil"></i> Edit Assessment';
        btn.textContent = 'Update Assessment';
        form.action = data.update_url;

        const methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = '_method';
        methodInput.value = 'PUT';
        form.insertBefore(methodInput, form.firstChild);

        setAssessmentDateTimeValue('aAssessmentAtDisplay', 'aAssessmentDate', 'aAssessmentTime', data.assessment_date || '', data.assessment_time || '');
        setAssessmentDateTimeValue('aMailAtDisplay', 'aMailDate', 'aMailTime', data.mail_date || '', data.mail_time || '');
        document.getElementById('aCompanyName').value = data.company_name || '';
        document.getElementById('aDomain').value = data.domain || '';
        document.getElementById('aCompanyWebsiteUrl').value = data.company_website_url || '';
        document.getElementById('aRole').value = data.role || '';
        document.getElementById('aType').value = data.assessment_type || '';
        document.getElementById('aRemark').value = data.remark || '';
    } else {
        title.innerHTML = '<i class="bi bi-clipboard-check"></i> Add Assessment';
        btn.textContent = 'Add Assessment';
        form.action = ADD_ASSESSMENT_URL;
    }

    if (window.openModal) {
        window.openModal('assessmentModal');
    } else {
        document.getElementById('assessmentModal').classList.add('open');
    }
}

function closeAssessmentModal() {
    if (window.closeModal) {
        window.closeModal('assessmentModal');
    } else {
        document.getElementById('assessmentModal').classList.remove('open');
    }
}

// Close modals on overlay click
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function(e) {
        if (e.target === this) {
            if (this.id === 'interviewModal') {
                closeInterviewModal();
            } else if (this.id === 'assessmentModal') {
                closeAssessmentModal();
            } else if (window.closeModal) {
                window.closeModal(this.id);
            } else {
                this.classList.remove('open');
            }
        }
    });
});

// ── Toast helper ──────────────────────────────────────────────────
function showToast(msg, type = 'success') {
    const container = document.createElement('div');
    container.className = 'toast-container';
    container.innerHTML = `<div class="toast toast-${type}"><i class="bi bi-${type === 'success' ? 'check-circle-fill' : 'exclamation-circle-fill'}"></i><span>${msg}</span></div>`;
    document.body.appendChild(container);
    setTimeout(() => {
        container.querySelector('.toast').style.opacity = '0';
        setTimeout(() => container.remove(), 400);
    }, 3000);
}

// Auto-dismiss flash toasts
window.addEventListener('DOMContentLoaded', () => {
    // Restore active tab after page refresh/redirect.
    const urlParams = new URLSearchParams(window.location.search);
    const requestedTab = urlParams.get('tab');
    const savedTab = (() => {
        try {
            return sessionStorage.getItem(TAB_STORAGE_KEY);
        } catch (e) {
            return null;
        }
    })();
    const tabToActivate = ['logs', 'interviews', 'assessments', 'documents'].includes(requestedTab)
        ? requestedTab
        : (['logs', 'interviews', 'assessments', 'documents'].includes(savedTab) ? savedTab : 'logs');
    activateTab(tabToActivate);

    initInterviewDateTimePicker('iApplicationAtDisplay', 'iApplicationDate', 'iApplicationTime');
    initInterviewDateTimePicker('iMailAtDisplay', 'iMailDate', 'iMailTime');
    initInterviewDateTimePicker('iScheduledAtDisplay', 'iSchedDate', 'iSchedTime');
    initAssessmentDateTimePicker('aAssessmentAtDisplay', 'aAssessmentDate', 'aAssessmentTime');
    initAssessmentDateTimePicker('aMailAtDisplay', 'aMailDate', 'aMailTime');
    setupCandidatePreviewFormValidation();

    const logForm = document.querySelector('#logModal form');
    if (logForm) {
        logForm.addEventListener('submit', () => persistActiveTab('logs'));
    }
    const interviewForm = document.getElementById('interviewForm');
    if (interviewForm) {
        interviewForm.addEventListener('submit', () => persistActiveTab('interviews'));
    }
    const assessmentForm = document.getElementById('assessmentForm');
    if (assessmentForm) {
        assessmentForm.addEventListener('submit', () => persistActiveTab('assessments'));
    }
    document.querySelectorAll('.interview-delete-form').forEach(form => {
        form.addEventListener('submit', () => persistActiveTab('interviews'));
    });
    document.querySelectorAll('.assessment-delete-form').forEach(form => {
        form.addEventListener('submit', () => persistActiveTab('assessments'));
    });

    const flash = document.getElementById('flashToast');
    if (flash) {
        setTimeout(() => {
            flash.querySelector('.toast').style.opacity = '0';
            setTimeout(() => flash.remove(), 400);
        }, 4000);
    }
});
</script>

@endsection
