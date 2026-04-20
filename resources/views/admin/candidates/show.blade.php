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
$timezones = ['EST','CST','MST','PST','AKST','HST','EDT','CDT','MDT','PDT'];

$totalApps       = $dailyLogs->sum('applications');
$totalAssistant  = $dailyLogs->sum('assistant_count');
$totalInterviews = $dailyLogs->sum('interview_count');
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
    display: none; width: 100%; gap: 4px; align-items: center; flex-wrap: wrap;
}
.inline-edit-wrap.active { display: flex; }
.inline-edit-input {
    flex: 1; min-width: 0;
    padding: 4px 8px; font-size: 12px;
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
                    {{ strtoupper(substr($candidate->full_name, 0, 1)) }}
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
            @include('admin.candidates._preview_field', ['field'=>'domain','label'=>'Domain','value'=>$candidate->domain,'editable'=>true])
            @include('admin.candidates._preview_field', ['field'=>'sub_domain','label'=>'Sub Domain','value'=>$candidate->sub_domain,'editable'=>true])

            {{-- ---- Address ---- --}}
            <div class="profile-section-title">Address</div>
            @include('admin.candidates._preview_field', ['field'=>'street_address','label'=>'Street','value'=>$candidate->street_address,'editable'=>true])
            @include('admin.candidates._preview_field', ['field'=>'apartment_unit','label'=>'Apt / Unit','value'=>$candidate->apartment_unit,'editable'=>true])
            @include('admin.candidates._preview_field', ['field'=>'city','label'=>'City','value'=>$candidate->city,'editable'=>true])
            @include('admin.candidates._preview_field', ['field'=>'state_province','label'=>'State','value'=>$candidate->state_province,'editable'=>true])
            @include('admin.candidates._preview_field', ['field'=>'zip_code','label'=>'ZIP','value'=>$candidate->zip_code,'editable'=>true])
            @include('admin.candidates._preview_field', ['field'=>'country','label'=>'Country','value'=>$candidate->country,'editable'=>true])

            {{-- ---- Visa ---- --}}
            <div class="profile-section-title">Visa &amp; Work Auth</div>
            @include('admin.candidates._preview_field', ['field'=>'visa_immigration_status','label'=>'Visa Status','value'=>$visaOptions[$candidate->visa_immigration_status] ?? $candidate->visa_immigration_status,'rawValue'=>$candidate->visa_immigration_status,'type'=>'select','selectOptions'=>$visaOptions,'editable'=>true])
            @include('admin.candidates._preview_field', ['field'=>'work_auth_status','label'=>'Work Auth','value'=>$workAuthOptions[$candidate->work_auth_status] ?? $candidate->work_auth_status,'rawValue'=>$candidate->work_auth_status,'type'=>'select','selectOptions'=>$workAuthOptions,'editable'=>true])
            @include('admin.candidates._preview_field', ['field'=>'open_to_relocation','label'=>'Relocation','value'=>$candidate->open_to_relocation ? 'Yes' : 'No','rawValue'=>$candidate->open_to_relocation ? '1' : '0','type'=>'select','selectOptions'=>['1'=>'Yes','0'=>'No'],'editable'=>true])
            @include('admin.candidates._preview_field', ['field'=>'preferred_city','label'=>'Preferred City','value'=>$candidate->preferred_city,'editable'=>true])
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
            @include('admin.candidates._preview_field', ['field'=>'marketing_phone','label'=>'Mkt Phone','value'=>$candidate->marketing_phone,'editable'=>true])
            @include('admin.candidates._preview_field', ['field'=>'marketing_email','label'=>'Mkt Email','value'=>$candidate->marketing_email,'editable'=>true])
            @include('admin.candidates._preview_field', ['field'=>'marketing_linkedin_id','label'=>'LinkedIn ID','value'=>$candidate->marketing_linkedin_id,'editable'=>true])

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
            <button class="page-tab active" onclick="switchTab('logs', this)" role="tab">
                <i class="bi bi-journal-text"></i> Daily Logs
                <span class="badge badge-neutral" style="margin-left:4px;font-size:10px;">{{ $dailyLogs->count() }}</span>
            </button>
            <button class="page-tab" onclick="switchTab('interviews', this)" role="tab">
                <i class="bi bi-briefcase"></i> Interviews
                <span class="badge badge-neutral" style="margin-left:4px;font-size:10px;">{{ $interviews->count() }}</span>
            </button>
            <button class="page-tab" onclick="switchTab('documents', this)" role="tab">
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
                    <div class="summary-stat-val" style="color:var(--purple-text);">{{ $totalAssistant }}</div>
                    <div class="summary-stat-lbl">Total Assistant</div>
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
                        <div class="card-subtitle">Double-click a row to edit inline</div>
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
                                <th style="width:80px;">Assistant</th>
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
                                    <input type="number" min="0" class="log-edit-input edit-val" style="display:none;" value="{{ $log->assistant_count }}" data-field="assistant_count">
                                </td>
                                <td class="td-interviews">
                                    <span class="view-val">{{ $log->interview_count }}</span>
                                    <input type="number" min="0" class="log-edit-input edit-val" style="display:none;" value="{{ $log->interview_count }}" data-field="interview_count">
                                </td>
                                <td class="td-remark" style="max-width:200px;">
                                    <span class="view-val" style="white-space:pre-wrap;font-size:12px;">{{ $log->remark ?: '—' }}</span>
                                    <input type="text" class="log-edit-remark edit-val" style="display:none;" value="{{ $log->remark }}" data-field="remark">
                                </td>
                                <td class="text-sm text-muted">{{ $log->creator?->name ?? '—' }}</td>
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
                                <th>Mail Date</th>
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
                            <tr>
                                <td class="text-muted text-sm">{{ $i + 1 }}</td>
                                <td class="text-sm" style="max-width:120px;">{{ $interview->role }}</td>
                                <td class="text-sm font-600">{{ $interview->company_name }}</td>
                                <td class="text-sm" style="max-width:140px;">
                                    <a href="{{ $interview->company_domain }}" target="_blank" rel="noopener"
                                       style="color:var(--blue-text);word-break:break-all;">{{ $interview->company_domain }}</a>
                                </td>
                                <td class="text-sm text-muted" style="white-space:nowrap;">
                                    {{ $interview->mail_date?->format('M d, Y') ?? '—' }}
                                    @if ($interview->mail_time)<br><span style="font-size:11px;">{{ $interview->mail_time }}</span>@endif
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
                                        @if ($interview->scheduled_time)<br><span style="font-size:11px;">{{ $interview->scheduled_time }} {{ $interview->scheduled_timezone }}</span>@endif
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="text-sm text-muted">{{ $interview->creator?->name ?? '—' }}</td>
                                @if ($isAdmin)
                                <td>
                                    <div class="d-flex gap-4">
                                        <button class="btn btn-outline btn-sm"
                                            title="Edit"
                                            onclick="openInterviewModal({{ json_encode([
                                                'id'                 => $interview->id,
                                                'role'               => $interview->role,
                                                'company_name'       => $interview->company_name,
                                                'company_domain'     => $interview->company_domain,
                                                'mail_date'          => $interview->mail_date?->format('Y-m-d'),
                                                'mail_time'          => $interview->mail_time,
                                                'interview_type'     => $interview->interview_type,
                                                'remark'             => $interview->remark,
                                                'scheduled_date'     => $interview->scheduled_date?->format('Y-m-d'),
                                                'scheduled_time'     => $interview->scheduled_time,
                                                'scheduled_timezone' => $interview->scheduled_timezone,
                                                'update_url'         => route('admin.candidates.interviews.update', [$candidate, $interview]),
                                            ]) }})">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form method="POST" action="{{ route('admin.candidates.interviews.destroy', [$candidate, $interview]) }}"
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
                                <td colspan="{{ $isAdmin ? 11 : 10 }}">
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
        <form method="POST" action="{{ route('admin.candidates.daily-logs.store', $candidate) }}">
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
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div class="form-group mb-0">
                        <label class="form-label">Applications <span style="color:var(--red-text)">*</span></label>
                        <input type="number" name="applications" class="form-control"
                            value="0" min="0" max="9999" required>
                    </div>
                    <div class="form-group mb-0">
                        <label class="form-label">Assistant <span style="color:var(--red-text)">*</span></label>
                        <input type="number" name="assistant_count" class="form-control"
                            value="0" min="0" max="9999" required>
                    </div>
                </div>
                <div class="form-group" style="margin-top:12px;">
                    <label class="form-label">
                        Interview Count
                        <span style="font-weight:400;color:var(--text-muted);font-size:11px;">(auto-calculated)</span>
                    </label>
                    <div class="form-control" style="background:var(--main-bg);color:var(--text-muted);cursor:not-allowed;display:flex;align-items:center;gap:6px;">
                        <i class="bi bi-lock-fill" style="font-size:11px;"></i>
                        <span>Auto-counted from today's interviews</span>
                    </div>
                </div>
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
                        <label class="form-label">Role <span style="color:var(--red-text);">*</span></label>
                        <input type="text" name="role" id="iRole" class="form-control" placeholder="e.g. Software Engineer" maxlength="200" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Company Name <span style="color:var(--red-text);">*</span></label>
                        <input type="text" name="company_name" id="iCompanyName" class="form-control" placeholder="e.g. Acme Corp" maxlength="200" required>
                    </div>
                    <div class="form-group" style="grid-column:span 2;">
                        <label class="form-label">Company Domain / URL <span style="color:var(--red-text);">*</span></label>
                        <input type="text" name="company_domain" id="iCompanyDomain" class="form-control" placeholder="https://company.com or www.company.com" maxlength="500" required>
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
                    <div class="form-group">
                        <label class="form-label">Mail Date</label>
                        <input type="date" name="mail_date" id="iMailDate" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Mail Time</label>
                        <input type="time" name="mail_time" id="iMailTime" class="form-control">
                    </div>
                    @if ($isAdmin)
                    <div class="form-group">
                        <label class="form-label">Scheduled Date <span class="text-muted text-sm">(admin only)</span></label>
                        <input type="date" name="scheduled_date" id="iSchedDate" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Scheduled Time <span class="text-muted text-sm">(admin only)</span></label>
                        <input type="time" name="scheduled_time" id="iSchedTime" class="form-control">
                    </div>
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
                    @endif
                    <div class="form-group" style="grid-column:span 2;">
                        <label class="form-label">Remark</label>
                        <textarea name="remark" id="iRemark" class="form-control" rows="3" placeholder="Additional notes..."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeInterviewModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" id="interviewSubmitBtn">Add Interview</button>
            </div>
        </form>
    </div>
</div>

<script>
// ── Inline field patch ──────────────────────────────────────────────
const PATCH_URL = '{{ route('admin.candidates.patch-field', $candidate) }}';
const CSRF      = document.querySelector('meta[name="csrf-token"]').content;

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
            if (displayEl) displayEl.textContent = value || '—';
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
    // Focus input
    const inp = document.querySelector('#field-edit-' + field + ' .inline-edit-input');
    if (inp) inp.focus();
}

function cancelFieldEdit(field) {
    const show = document.getElementById('field-show-' + field);
    const edit = document.getElementById('field-edit-' + field);
    if (show) show.style.display = '';
    if (edit) { edit.style.display = 'none'; edit.classList.remove('active'); }
}

function saveFieldEdit(field) {
    const inp = document.querySelector('#field-edit-' + field + ' .inline-edit-input');
    if (!inp) return;
    saveInlineField(field, inp.value);
}

// ── Tab switching ──────────────────────────────────────────────────
function switchTab(name, btn) {
    document.querySelectorAll('.page-tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.page-tab').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + name).classList.add('active');
    btn.classList.add('active');
}

// ── Daily Log Modal ────────────────────────────────────────────────
function openLogModal() {
    document.getElementById('logModal').classList.add('open');
}
function closeLogModal() {
    document.getElementById('logModal').classList.remove('open');
}

// ── Daily Log inline edit ──────────────────────────────────────────
const IS_ADMIN = {{ $isAdmin ? 'true' : 'false' }};

function startLogEdit(row) {
    // Show edit inputs, hide view values
    row.querySelectorAll('.view-val').forEach(el => el.style.display = 'none');
    row.querySelectorAll('.edit-val').forEach(el => {
        if (!IS_ADMIN && el.tagName === 'INPUT' && ['applications','assistant_count','interview_count'].includes(el.dataset.field)) {
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
    const logId     = row.dataset.logId;
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
            // Update view values
            row.querySelectorAll('.edit-val[data-field]').forEach(inp => {
                const field = inp.dataset.field;
                const viewEl = inp.previousElementSibling;
                if (viewEl && viewEl.classList.contains('view-val')) {
                    viewEl.textContent = inp.value || '—';
                }
            });
            cancelLogEdit(row);
        } else {
            showToast(data.message || 'Update failed.', 'error');
        }
    })
    .catch(() => showToast('Network error.', 'error'));
}

// ── Interview Modal ────────────────────────────────────────────────
const ADD_INTERVIEW_URL = '{{ route('admin.candidates.interviews.store', $candidate) }}';

function openInterviewModal(data) {
    const form   = document.getElementById('interviewForm');
    const title  = document.getElementById('interviewModalTitle');
    const btn    = document.getElementById('interviewSubmitBtn');
    const spoof  = document.getElementById('interviewMethodSpoof');

    // Clear form
    form.reset();

    // Remove any existing method spoof input
    const existingMethod = form.querySelector('input[name="_method"]');
    if (existingMethod) existingMethod.remove();

    if (data) {
        // Edit mode
        title.innerHTML = '<i class="bi bi-pencil"></i> Edit Interview';
        btn.textContent = 'Update Interview';
        form.action     = data.update_url;

        // Add PUT spoof
        const methodInput = document.createElement('input');
        methodInput.type  = 'hidden';
        methodInput.name  = '_method';
        methodInput.value = 'PUT';
        form.insertBefore(methodInput, form.firstChild);

        // Fill fields
        document.getElementById('iRole').value          = data.role || '';
        document.getElementById('iCompanyName').value   = data.company_name || '';
        document.getElementById('iCompanyDomain').value = data.company_domain || '';
        document.getElementById('iType').value          = data.interview_type || '';
        document.getElementById('iMailDate').value      = data.mail_date || '';
        document.getElementById('iMailTime').value      = data.mail_time || '';
        document.getElementById('iSchedDate').value     = data.scheduled_date || '';
        document.getElementById('iSchedTime').value     = data.scheduled_time || '';
        document.getElementById('iTimezone').value      = data.scheduled_timezone || '';
        document.getElementById('iRemark').value        = data.remark || '';
    } else {
        // Add mode
        title.innerHTML = '<i class="bi bi-briefcase"></i> Add Interview';
        btn.textContent = 'Add Interview';
        form.action     = ADD_INTERVIEW_URL;
    }

    document.getElementById('interviewModal').classList.add('open');
}

function closeInterviewModal() {
    document.getElementById('interviewModal').classList.remove('open');
}

// Close modals on overlay click
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function(e) {
        if (e.target === this) {
            this.classList.remove('open');
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
