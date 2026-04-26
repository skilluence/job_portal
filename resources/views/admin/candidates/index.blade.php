@extends('layouts.admin')
@section('title', 'Candidates')
@section('module-title', 'Candidates')
@section('module-description', 'Create, assign, and manage all candidates with recruiter ownership and document uploads.')
@section('content')

@php
$usStates = ['AL'=>'Alabama','AK'=>'Alaska','AZ'=>'Arizona','AR'=>'Arkansas','CA'=>'California',
    'CO'=>'Colorado','CT'=>'Connecticut','DE'=>'Delaware','FL'=>'Florida','GA'=>'Georgia',
    'HI'=>'Hawaii','ID'=>'Idaho','IL'=>'Illinois','IN'=>'Indiana','IA'=>'Iowa','KS'=>'Kansas',
    'KY'=>'Kentucky','LA'=>'Louisiana','ME'=>'Maine','MD'=>'Maryland','MA'=>'Massachusetts',
    'MI'=>'Michigan','MN'=>'Minnesota','MS'=>'Mississippi','MO'=>'Missouri','MT'=>'Montana',
    'NE'=>'Nebraska','NV'=>'Nevada','NH'=>'New Hampshire','NJ'=>'New Jersey','NM'=>'New Mexico',
    'NY'=>'New York','NC'=>'North Carolina','ND'=>'North Dakota','OH'=>'Ohio','OK'=>'Oklahoma',
    'OR'=>'Oregon','PA'=>'Pennsylvania','RI'=>'Rhode Island','SC'=>'South Carolina',
    'SD'=>'South Dakota','TN'=>'Tennessee','TX'=>'Texas','UT'=>'Utah','VT'=>'Vermont',
    'VA'=>'Virginia','WA'=>'Washington','WV'=>'West Virginia','WI'=>'Wisconsin','WY'=>'Wyoming',
    'DC'=>'District of Columbia'];

$visaOptions = [
    'us_citizen'  => 'U.S. Citizen',
    'green_card'  => 'Green Card',
    'h1b'         => 'H-1B',
    'h4_ead'      => 'H-4 EAD',
    'opt_f1'      => 'Initial OPT F-1',
    'stem_opt'    => 'STEM OPT',
    'cpt'         => 'CPT',
    'l1'          => 'L-1',
    'tn_visa'     => 'TN Visa',
    'other'       => 'Other',
];

$workAuthOptions = [
    'applied_pending'  => 'Applied — pending approval',
    'not_applied'      => 'Not Applied yet',
    'already_obtained' => 'Already obtained / active',
];
@endphp

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

<style>
.candidate-offcanvas-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.45);
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.2s ease;
    z-index: 1100;
}
.candidate-offcanvas-backdrop.open { opacity: 1; pointer-events: auto; }
.candidate-offcanvas {
    position: fixed;
    top: 0;
    right: 0;
    width: min(460px, 100vw);
    height: 100vh;
    background: var(--card-bg);
    border-left: 1px solid var(--border-color);
    box-shadow: -20px 0 45px rgba(15, 23, 42, 0.18);
    transform: translateX(100%);
    transition: transform 0.24s ease;
    z-index: 1110;
    display: flex;
}
.candidate-offcanvas.open { transform: translateX(0); }
.candidate-offcanvas form { display:flex; flex-direction:column; width:100%; min-height:0; }
.candidate-offcanvas-header,
.candidate-offcanvas-footer {
    padding: 18px 22px;
    border-bottom: 1px solid var(--border-color);
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:12px;
}
.candidate-offcanvas-footer { border-top: 1px solid var(--border-color); border-bottom:0; }
.candidate-offcanvas-title { font-size:18px; font-weight:700; color:var(--text-primary); }
.candidate-offcanvas-subtitle { font-size:13px; color:var(--text-muted); margin-top:2px; }
.candidate-offcanvas-body { padding:18px 22px; overflow:auto; display:flex; flex-direction:column; gap:14px; }
.candidate-action-card {
    border: 1px solid var(--border-color);
    border-radius: 8px;
    padding: 14px;
    background: var(--card-bg);
}
.candidate-action-radio { display:flex; gap:10px; align-items:flex-start; cursor:pointer; margin:0; }
.candidate-action-radio input { margin-top:3px; }
.candidate-action-radio strong { display:block; color:var(--text-primary); font-size:14px; }
.candidate-action-radio small { display:block; color:var(--text-muted); font-size:12px; line-height:1.4; margin-top:2px; }
.candidate-action-fields { margin-top:14px; }
.candidate-segmented {
    display:grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap:6px;
    margin-bottom:12px;
}
.candidate-segmented label { margin:0; }
.candidate-segmented input { display:none; }
.candidate-segmented span {
    display:flex;
    justify-content:center;
    align-items:center;
    min-height:38px;
    border:1px solid var(--border-color);
    border-radius:7px;
    font-size:13px;
    font-weight:600;
    color:var(--text-secondary);
    cursor:pointer;
}
.candidate-segmented input:checked + span {
    border-color:var(--blue);
    background:var(--blue-light);
    color:var(--blue-text);
}
.candidate-range-list { display:flex; flex-direction:column; gap:10px; margin-bottom:10px; }
.candidate-range-row {
    border:1px solid var(--border-color);
    border-radius:8px;
    padding:12px;
    background:var(--main-bg);
}
.candidate-range-row-head {
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:10px;
    margin-bottom:10px;
}
.candidate-range-row-title { font-size:13px; font-weight:700; color:var(--text-primary); }
.candidate-range-grid {
    display:grid;
    grid-template-columns: 1fr 1fr;
    gap:10px;
}
.candidate-range-grid .form-group:first-child { grid-column:1 / -1; }
@media (max-width: 520px) {
    .candidate-range-grid { grid-template-columns:1fr; }
    .candidate-range-grid .form-group:first-child { grid-column:auto; }
}
</style>

@if ($isManager ?? false)
<div class="d-flex gap-12 mb-16" style="flex-wrap:wrap;align-items:stretch;">
    <a href="{{ route('admin.candidates') }}"
       class="manager-scope-card {{ !$scope ? 'scope-active' : '' }}">
        <div class="scope-num">{{ $managerAllCount }}</div>
        <div class="scope-lbl">All Candidates</div>
        <div class="scope-sub">Everyone you manage</div>
    </a>
    <a href="{{ route('admin.candidates', ['scope' => 'mine']) }}"
       class="manager-scope-card {{ $scope === 'mine' ? 'scope-active' : '' }}">
        <div class="scope-num" style="color:var(--blue);">{{ $managerMyCount }}</div>
        <div class="scope-lbl">My Candidates</div>
        <div class="scope-sub">Directly assigned to you</div>
    </a>
    <a href="{{ route('admin.candidates', ['scope' => 'team']) }}"
       class="manager-scope-card {{ $scope === 'team' ? 'scope-active' : '' }}">
        <div class="scope-num" style="color:var(--green);">{{ $managerTeamCount }}</div>
        <div class="scope-lbl">Recruiters' Candidates</div>
        <div class="scope-sub">Through your team recruiters</div>
    </a>
    @if ($isRealAdmin ?? false)
        <div style="flex:1;display:flex;align-items:center;justify-content:flex-end;min-width:160px;">
            <button class="btn btn-primary" onclick="openCandidateModal('add')">
                <i class="bi bi-plus-lg"></i> Add Candidate
            </button>
        </div>
    @endif
</div>
@endif

<div class="card" style="margin-bottom:16px;">
    <div class="d-flex gap-12" style="justify-content:space-between;align-items:flex-end;flex-wrap:wrap;">
        <div class="d-flex gap-8" style="flex-wrap:wrap;align-items:center;">
            <a href="{{ route('admin.candidates', array_filter(['scope' => $scope])) }}"
               class="btn {{ !$ownership ? 'btn-primary' : 'btn-outline' }} btn-sm">
                <i class="bi bi-people-fill"></i>
                @if ($isRealAdmin ?? false)
                    All Candidates
                @elseif ($isManager ?? false)
                    Current Ownership
                @else
                    My Candidates
                @endif
            </a>

            @if ($isRealAdmin ?? false)
                <a href="{{ route('admin.candidates', ['ownership' => 'unassigned']) }}"
                   class="btn {{ $ownership === 'unassigned' ? 'btn-primary' : 'btn-outline' }} btn-sm">
                    <i class="bi bi-person-dash-fill"></i> Unassigned
                    <span class="badge badge-warning" style="margin-left:6px;">{{ $unassignedCount ?? 0 }}</span>
                </a>
            @elseif (($reclaimableCount ?? 0) > 0 || $ownership === 'reclaim')
                <a href="{{ route('admin.candidates', ['ownership' => 'reclaim']) }}"
                   class="btn {{ $ownership === 'reclaim' ? 'btn-primary' : 'btn-outline' }} btn-sm">
                    <i class="bi bi-arrow-counterclockwise"></i> Can Take Back
                    <span class="badge badge-info" style="margin-left:6px;">{{ $reclaimableCount ?? 0 }}</span>
                </a>
            @endif
        </div>

        <button type="button" class="btn btn-primary btn-sm" onclick="openCandidateBulkPanel()">
            <i class="bi bi-sliders"></i> Candidate Actions
        </button>
    </div>
</div>

<div class="card">
    <div class="card-header" style="flex-wrap:wrap;gap:12px;">
        <div>
            <div class="card-title">
                @if (($isRealAdmin ?? false) && $ownership === 'unassigned') Unassigned Candidates
                @elseif ($ownership === 'reclaim') Candidates You Can Take Back
                @elseif (($isManager ?? false) && $scope === 'mine') My Candidates
                @elseif (($isManager ?? false) && $scope === 'team') Recruiters' Candidates
                @else All Candidates
                @endif
            </div>
            <div class="card-subtitle">{{ $candidates->total() }} total records</div>
        </div>
        <div class="d-flex gap-8" style="flex-wrap:wrap;align-items:center;">
            <form method="GET" action="{{ route('admin.candidates') }}" class="d-flex gap-8" style="flex-wrap:wrap;">
                @if ($scope)
                    <input type="hidden" name="scope" value="{{ $scope }}">
                @endif
                @if ($ownership)
                    <input type="hidden" name="ownership" value="{{ $ownership }}">
                @endif
                <input type="text" name="search" class="form-control" placeholder="Search name, email, domain, city..."
                    value="{{ $search }}" style="width:260px;">
                <select name="status" class="form-control" style="width:150px;" onchange="this.form.submit()">
                    <option value="">All Status</option>
                    @foreach ($statusOptions as $s)
                        <option value="{{ $s }}" @selected($status === $s)>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-outline"><i class="bi bi-search"></i></button>
                @if ($search || $status)
                    <a href="{{ route('admin.candidates', array_filter(['scope' => $scope, 'ownership' => $ownership])) }}"
                       class="btn btn-outline" title="Clear filters"><i class="bi bi-x-lg"></i></a>
                @endif
            </form>
            @if ($isRealAdmin ?? false)
                <button class="btn btn-primary" onclick="openCandidateModal('add')">
                    <i class="bi bi-plus-lg"></i> Add Candidate
                </button>
            @endif
        </div>
    </div>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th style="width:34px;">
                        <input type="checkbox" id="candidateSelectAll" onclick="toggleAllCandidateRows(this)">
                    </th>
                    <th style="width:36px;">#</th>
                    <th>Candidate</th>
                    <th>Domain</th>
                    <th>Phone</th>
                    <th>Visa Status</th>
                    <th>Status</th>
                    <th style="width:50px;">Apps</th>
                    <th>Owner</th>
                    <th>Created At</th>
                    <th style="width:90px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($candidates as $i => $candidate)
                    @php
                        $visaLabel = $visaOptions[$candidate->visa_immigration_status] ?? ucfirst(str_replace('_', ' ', $candidate->visa_immigration_status ?? ''));
                        $domainText = implode(' / ', array_filter([$candidate->domain, $candidate->sub_domain]));
                        $ownsCandidateNow = ($isRealAdmin ?? false)
                            || ($currentUser->isRecruiter() && (int) $candidate->recruiter_id === (int) $currentUser->id)
                            || (($isManager ?? false) && (
                                ((int) $candidate->team_manager_id === (int) $currentUser->id && empty($candidate->recruiter_id))
                                || ((int) ($candidate->recruiter?->team_manager_id ?? 0) === (int) $currentUser->id)
                            ));
                        $canTakeBack = $ownership === 'reclaim' && !$ownsCandidateNow;
                        $temporaryUnassign = $candidate->activeTemporaryUnassign;
                        $restoreOwner = $temporaryUnassign?->restoreRecruiter ?: $temporaryUnassign?->restoreTeamManager;
                        $restoreOwnerRole = $temporaryUnassign?->restoreRecruiter ? 'Recruiter' : ($temporaryUnassign?->restoreTeamManager ? 'Team Manager' : null);
                        $restoreAt = $temporaryUnassign?->temporary_ends_at
                            ? $temporaryUnassign->temporary_ends_at->timezone(\App\Services\CandidateOwnershipService::TEMPORARY_TIMEZONE)
                            : null;
                    @endphp
                    <tr>
                        <td>
                            <input type="checkbox"
                                   class="candidate-row-checkbox"
                                   value="{{ $candidate->id }}">
                        </td>
                        <td class="text-muted text-sm">{{ $candidates->firstItem() + $i }}</td>
                        <td>
                            <a href="{{ route('admin.candidates.show', $candidate) }}" class="avatar-row" style="text-decoration:none;color:inherit;">
                                <div class="avatar-sm">{{ $candidate->initials }}</div>
                                <div>
                                    <div class="avatar-name" style="color:var(--blue);">{{ $candidate->full_name }}</div>
                                    <div class="avatar-sub">{{ $candidate->email_id }}</div>
                                </div>
                            </a>
                        </td>
                        <td class="text-sm">{{ $domainText ?: '-' }}</td>
                        <td class="text-muted text-sm">{{ $candidate->phone_number ?: '-' }}</td>
                        <td class="text-sm">
                            @if ($candidate->visa_immigration_status)
                                <span class="badge badge-neutral" style="font-size:11px;">{{ $visaLabel }}</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="td-status-cell"
                            data-inline-status
                            data-current-status="{{ $candidate->status }}"
                            data-status-url="{{ route('admin.candidates.status', $candidate) }}"
                            title="Double-click to change status">
                            <span class="badge {{ $candidate->status_badge }}">{{ ucfirst($candidate->status) }}</span>
                            <i class="bi bi-pencil-fill td-status-hint"></i>
                        </td>
                        <td>{{ $candidate->no_of_applications }}</td>
                        <td class="text-muted text-sm">
                            @if ($candidate->current_owner_name)
                                {{ $candidate->current_owner_name }}
                                @if ($candidate->current_owner_role_label)
                                    <span style="display:block;font-size:11px;color:var(--text-muted);">{{ $candidate->current_owner_role_label }}</span>
                                @endif
                                @if ($temporaryUnassign)
                                    <span class="badge badge-info" style="font-size:10px;margin-top:4px;">Temporary cover</span>
                                    <span style="display:block;font-size:11px;color:var(--text-muted);margin-top:3px;">
                                        Returns to {{ $restoreOwner?->name ?? 'previous owner' }}@if ($restoreAt) on {{ $restoreAt->format('M d, Y h:i A') }}@endif
                                    </span>
                                @endif
                            @else
                                <span class="badge badge-warning" style="font-size:11px;">
                                    {{ $temporaryUnassign ? 'Temporarily Unassigned' : 'Unassigned' }}
                                </span>
                                @if ($temporaryUnassign)
                                    <span style="display:block;font-size:11px;color:var(--text-muted);margin-top:3px;">
                                        Original: {{ $restoreOwner?->name ?? 'previous owner' }}@if ($restoreOwnerRole) ({{ $restoreOwnerRole }})@endif
                                    </span>
                                    <span style="display:block;font-size:11px;color:var(--text-muted);">
                                        @if ($restoreAt)
                                            Restores {{ $restoreAt->format('M d, Y h:i A') }}
                                        @else
                                            Restore window pending
                                        @endif
                                    </span>
                                    @if ($temporaryUnassign->changer)
                                        <span style="display:block;font-size:11px;color:var(--text-muted);">
                                            Unassigned by {{ $temporaryUnassign->changer->name }}
                                        </span>
                                    @endif
                                @elseif ($candidate->latestAssignmentHistory?->changer)
                                    <span style="display:block;font-size:11px;color:var(--text-muted);">
                                        Last moved by {{ $candidate->latestAssignmentHistory->changer->name }}
                                    </span>
                                @endif
                            @endif
                        </td>
                        <td class="text-sm">
                            <div>{{ $candidate->created_at?->format('M d, Y') ?? '-' }}</div>
                            @if ($candidate->created_at)
                                <div class="text-muted" style="font-size:11px;">{{ $candidate->created_at->format('h:i A') }}</div>
                            @endif
                        </td>
                        <td>
                            @php
                                $p = [
                                    'id'               => $candidate->id,
                                    'first_name'       => $candidate->first_name,
                                    'middle_name'      => $candidate->middle_name,
                                    'last_name'        => $candidate->last_name,
                                    'date_of_birth'    => $candidate->date_of_birth?->format('Y-m-d'),
                                    'gender'           => $candidate->gender,
                                    'nationality'      => $candidate->nationality,
                                    'email_id'         => $candidate->email_id,
                                    'phone_number'     => $candidate->phone_number,
                                    'domain'           => $candidate->domain,
                                    'sub_domain'       => $candidate->sub_domain,
                                    'date_of_arrival_usa' => $candidate->date_of_arrival_usa?->format('Y-m-d'),
                                    'current_salary'   => $candidate->current_salary,
                                    'expected_salary'  => $candidate->expected_salary,
                                    'street_address'   => $candidate->street_address,
                                    'apartment_unit'   => $candidate->apartment_unit,
                                    'city'             => $candidate->city,
                                    'state_province'   => $candidate->state_province,
                                    'zip_code'         => $candidate->zip_code,
                                    'country'          => $candidate->country ?? 'United States',
                                    'visa_immigration_status' => $candidate->visa_immigration_status,
                                    'work_auth_status' => $candidate->work_auth_status,
                                    'open_to_relocation' => $candidate->open_to_relocation,
                                    'preferred_city'   => $candidate->preferred_city,
                                    'visa_expiry_date' => $candidate->visa_expiry_date?->format('Y-m-d'),
                                    'marketing_phone'  => $candidate->marketing_phone,
                                    'marketing_email'  => $candidate->marketing_email,
                                    'marketing_email_password' => $candidate->marketing_email_password,
                                    'marketing_linkedin_id' => $candidate->marketing_linkedin_id,
                                    'marketing_linkedin_password' => $candidate->marketing_linkedin_password,
                                    'masters_university' => $candidate->masters_university,
                                    'masters_program'  => $candidate->masters_program,
                                    'masters_start'    => $candidate->masters_start?->format('Y-m-d'),
                                    'masters_end'      => $candidate->masters_end?->format('Y-m-d'),
                                    'masters_country'  => $candidate->masters_country,
                                    'bachelors_university' => $candidate->bachelors_university,
                                    'bachelors_program'=> $candidate->bachelors_program,
                                    'bachelors_start'  => $candidate->bachelors_start?->format('Y-m-d'),
                                    'bachelors_end'    => $candidate->bachelors_end?->format('Y-m-d'),
                                    'bachelors_country'=> $candidate->bachelors_country,
                                    'github_url'       => $candidate->github_url,
                                    'linkedin_url'     => $candidate->linkedin_url,
                                    'portfolio_url'    => $candidate->portfolio_url,
                                    'recruiter_notes'  => $candidate->recruiter_notes,
                                    'no_of_applications' => $candidate->no_of_applications,
                                    'status'           => $candidate->status,
                                    'recruiter_id'     => $candidate->recruiter_id,
                                    'team_manager_id'  => $candidate->team_manager_id,
                                    'cv_file_url'      => $candidate->cv_file_path ? route('admin.candidates.files', [$candidate, 'cv']) : null,
                                    'speedy_file_url'  => $candidate->speedy_apply_json_path ? route('admin.candidates.files', [$candidate, 'speedy']) : null,
                                    'reveal_password_url' => route('admin.candidates.reveal-password', $candidate),
                                    'resumes'          => $candidate->resumes->map(fn($r) => [
                                        'designation'       => $r->designation,
                                        'original_filename' => $r->original_filename,
                                        'url'               => route('admin.candidates.resumes.download', [$candidate, $r]),
                                        'uploaded_at'       => $r->created_at->format('M d, Y'),
                                    ])->values(),
                                ];
                            @endphp
                            <div class="tbl-actions">
                                @if ($canTakeBack)
                                    <form method="POST" action="{{ route('admin.candidates.bulk-ownership') }}" style="display:inline;margin:0;">
                                        @csrf
                                        <input type="hidden" name="candidate_ids[]" value="{{ $candidate->id }}">
                                        <input type="hidden" name="bulk_action" value="take_back">
                                        <button class="btn btn-primary btn-sm" title="Take back candidate"
                                                onclick="return confirm('Take this candidate back under your ownership?');">
                                            <i class="bi bi-arrow-counterclockwise"></i>
                                        </button>
                                    </form>
                                @endif
                                @if ($ownsCandidateNow)
                                    <a href="{{ route('admin.candidates.show', $candidate) }}"
                                       class="btn btn-outline btn-sm" title="View candidate preview">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <button class="btn btn-outline btn-sm"
                                        data-candidate="{{ base64_encode(json_encode($p, JSON_UNESCAPED_SLASHES)) }}"
                                        onclick="editCandidateFromButton(this)" title="Edit candidate">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form method="POST" action="{{ route('admin.candidates.destroy', $candidate) }}" style="display:inline;margin:0;"
                                        onsubmit="return confirm('Delete candidate {{ addslashes($candidate->full_name) }}? This will block student login.');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-danger btn-sm" title="Delete candidate"><i class="bi bi-trash"></i></button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11">
                            <div class="page-empty mb-0">
                                <i class="bi bi-people"></i>
                                <p>No candidates found{{ ($search || $status) ? ' matching your filters' : '' }}.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($candidates->hasPages())
        <div class="pagination-wrap">
            <span class="pagination-info">
                Showing {{ $candidates->firstItem() }}-{{ $candidates->lastItem() }} of {{ $candidates->total() }}
            </span>
            {{ $candidates->links('pagination.custom') }}
        </div>
    @endif
</div>

<div class="candidate-offcanvas-backdrop" id="candidateBulkBackdrop" onclick="closeCandidateBulkPanel()"></div>
<aside class="candidate-offcanvas" id="candidateBulkPanel" aria-hidden="true">
    <form method="POST" action="{{ route('admin.candidates.bulk-ownership') }}" id="candidateBulkOwnershipForm">
        @csrf
        <div class="candidate-offcanvas-header">
            <div>
                <div class="candidate-offcanvas-title">Candidate Actions</div>
                <div class="candidate-offcanvas-subtitle"><span id="candidateBulkSelectedCount">0</span> selected</div>
            </div>
            <button type="button" class="modal-close" onclick="closeCandidateBulkPanel()">&times;</button>
        </div>

        <div class="candidate-offcanvas-body">
            <div id="candidateBulkIds"></div>

            @if ($ownership === 'reclaim' && !($isRealAdmin ?? false))
                <div class="candidate-action-card">
                    <label class="candidate-action-radio">
                        <input type="radio" name="bulk_action" value="take_back" checked>
                        <span>
                            <strong>Take Back</strong>
                            <small>Move selected candidates back under your ownership.</small>
                        </span>
                    </label>
                </div>
            @else
                @if ($isRealAdmin ?? false)
                    <div class="candidate-action-card">
                        <label class="candidate-action-radio">
                            <input type="radio" name="bulk_action" value="assign" checked onchange="syncCandidateBulkActionPanel()">
                            <span>
                                <strong>Transfer Ownership</strong>
                                <small>Assign selected candidates to one recruiter or team manager. During an active temporary unassign window, this acts as coverage until the original owner is restored.</small>
                            </span>
                        </label>
                        <div class="candidate-action-fields" id="candidateAssignFields">
                            <label class="form-label">Transfer To</label>
                            <select name="assign_owner" id="bulkAssignOwner" class="form-control">
                                <option value="">Select recruiter or team manager</option>
                                @if ($recruiters->count())
                                    <optgroup label="Recruiters">
                                        @foreach ($recruiters as $recruiter)
                                            <option value="recruiter:{{ $recruiter->id }}">{{ $recruiter->name }} (Recruiter)</option>
                                        @endforeach
                                    </optgroup>
                                @endif
                                @if ($managers->count())
                                    <optgroup label="Team Managers">
                                        @foreach ($managers as $manager)
                                            <option value="manager:{{ $manager->id }}">{{ $manager->name }} (Team Manager)</option>
                                        @endforeach
                                    </optgroup>
                                @endif
                            </select>
                        </div>
                    </div>
                @endif

                <div class="candidate-action-card">
                    <label class="candidate-action-radio">
                        <input type="radio" name="bulk_action" value="unassign" @if (!($isRealAdmin ?? false)) checked @endif onchange="syncCandidateBulkActionPanel()">
                        <span>
                            <strong>Unassign Temporarily</strong>
                            <small>Remove ownership for full days or a specific time range, then restore automatically.</small>
                        </span>
                    </label>

                    <div class="candidate-action-fields" id="candidateUnassignFields">
                        <div class="candidate-segmented">
                            <label>
                                <input type="radio" name="unassign_type" value="full_day" checked onchange="syncUnassignTypePanel()">
                                <span>Full Day</span>
                            </label>
                            <label>
                                <input type="radio" name="unassign_type" value="time_range" onchange="syncUnassignTypePanel()">
                                <span>Time Range</span>
                            </label>
                            @if ($isRealAdmin ?? false)
                                <label>
                                    <input type="radio" name="unassign_type" value="permanent" onchange="syncUnassignTypePanel()">
                                    <span>Permanent</span>
                                </label>
                            @endif
                        </div>

                        <div id="unassignFullDayFields">
                            <label class="form-label">Unassign Date(s)</label>
                            <input type="text" id="bulkUnassignDatesDisplay" class="form-control" placeholder="Select one or more dates">
                            <input type="hidden" name="unassign_dates" id="bulkUnassignDates">
                        </div>

                        <div id="unassignTimeRangeFields" style="display:none;">
                            <div class="candidate-range-list" id="bulkUnassignRangeList"></div>
                            <button type="button" class="btn btn-outline btn-sm" onclick="addUnassignRangeRow()">
                                <i class="bi bi-plus-lg"></i> Add More
                            </button>
                            <div class="text-muted text-sm" style="margin-top:8px;">
                                Add multiple rows for different dates or multiple ranges on the same date.
                            </div>
                        </div>

                        @if ($isRealAdmin ?? false)
                            <div class="alert alert-error mb-0" id="unassignPermanentNotice" style="display:none;padding:10px 12px;">
                                <i class="bi bi-exclamation-circle-fill"></i>
                                <div>Permanent unassign removes ownership until an admin transfers the candidate again.</div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        <div class="candidate-offcanvas-footer">
            <button type="button" class="btn btn-outline" onclick="closeCandidateBulkPanel()">Cancel</button>
            <button type="submit" class="btn btn-primary" onclick="return validateCandidateBulkAction();">
                <i class="bi bi-check-lg"></i> Apply Action
            </button>
        </div>
    </form>
</aside>

{{-- ============================================================ --}}
{{-- ADD CANDIDATE MODAL --}}
{{-- ============================================================ --}}
@if ($isRealAdmin ?? false)
<div class="modal-overlay" id="addCandidateModal">
    <div class="modal modal-xl">
        <div class="modal-header">
            <div class="modal-title"><i class="bi bi-person-plus" style="margin-right:6px;"></i> Add New Candidate</div>
            <button class="modal-close" onclick="closeModal('addCandidateModal')">&times;</button>
        </div>
        <form method="POST" action="{{ route('admin.candidates.store') }}" enctype="multipart/form-data" novalidate>
            @csrf
            @include('admin.candidates._form', ['mode' => 'add', 'usStates' => $usStates, 'visaOptions' => $visaOptions, 'workAuthOptions' => $workAuthOptions, 'recruiters' => $recruiters, 'managers' => $managers, 'statusOptions' => $statusOptions, 'isAdmin' => $isAdmin, 'isRealAdmin' => $isRealAdmin, 'prefix' => 'add'])
        </form>
    </div>
</div>
@endif

{{-- ============================================================ --}}
{{-- EDIT CANDIDATE MODAL --}}
{{-- ============================================================ --}}
<div class="modal-overlay" id="editCandidateModal">
    <div class="modal modal-xl">
        <div class="modal-header">
            <div class="modal-title"><i class="bi bi-pencil-square" style="margin-right:6px;"></i> Edit Candidate</div>
            <button class="modal-close" onclick="closeModal('editCandidateModal')">&times;</button>
        </div>
        <form method="POST" id="editCandidateForm" action="" data-base="{{ url('admin/candidates') }}"
            enctype="multipart/form-data" novalidate>
            @csrf
            @method('PUT')
            @include('admin.candidates._form', ['mode' => 'edit', 'usStates' => $usStates, 'visaOptions' => $visaOptions, 'workAuthOptions' => $workAuthOptions, 'recruiters' => $recruiters, 'managers' => $managers, 'statusOptions' => $statusOptions, 'isAdmin' => $isAdmin, 'isRealAdmin' => $isRealAdmin, 'prefix' => 'edit'])
        </form>
    </div>
</div>

@push('scripts')
<script>
window.__isManager = {{ $isManager ? 'true' : 'false' }};
window.__isRealAdmin = {{ ($isRealAdmin ?? false) ? 'true' : 'false' }};
var bulkUnassignRangeIndex = 0;

function getSelectedCandidateCheckboxes() {
    return Array.from(document.querySelectorAll('.candidate-row-checkbox:checked'));
}

function toggleAllCandidateRows(source) {
    document.querySelectorAll('.candidate-row-checkbox').forEach(function (checkbox) {
        checkbox.checked = source.checked;
    });
}

function selectedBulkAction() {
    var checked = document.querySelector('input[name="bulk_action"]:checked');
    return checked ? checked.value : '';
}

function selectedUnassignType() {
    var checked = document.querySelector('input[name="unassign_type"]:checked');
    return checked ? checked.value : 'full_day';
}

function syncBulkCandidateIds() {
    var container = document.getElementById('candidateBulkIds');
    var count = document.getElementById('candidateBulkSelectedCount');
    var selected = getSelectedCandidateCheckboxes();
    if (!container) return selected;

    container.innerHTML = '';
    selected.forEach(function (checkbox) {
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'candidate_ids[]';
        input.value = checkbox.value;
        container.appendChild(input);
    });

    if (count) count.textContent = selected.length;
    return selected;
}

function openCandidateBulkPanel() {
    var selected = syncBulkCandidateIds();
    if (!selected.length) {
        alert('Select at least one candidate first.');
        return;
    }

    document.getElementById('candidateBulkBackdrop').classList.add('open');
    document.getElementById('candidateBulkPanel').classList.add('open');
    document.getElementById('candidateBulkPanel').setAttribute('aria-hidden', 'false');
    syncCandidateBulkActionPanel();
}

function closeCandidateBulkPanel() {
    document.getElementById('candidateBulkBackdrop').classList.remove('open');
    document.getElementById('candidateBulkPanel').classList.remove('open');
    document.getElementById('candidateBulkPanel').setAttribute('aria-hidden', 'true');
}

function syncCandidateBulkActionPanel() {
    var action = selectedBulkAction();
    var assignFields = document.getElementById('candidateAssignFields');
    var unassignFields = document.getElementById('candidateUnassignFields');
    if (assignFields) {
        if (window.smoothToggleElement) window.smoothToggleElement(assignFields, action === 'assign');
        else assignFields.style.display = action === 'assign' ? '' : 'none';
    }
    if (unassignFields) {
        if (window.smoothToggleElement) window.smoothToggleElement(unassignFields, action === 'unassign');
        else unassignFields.style.display = action === 'unassign' ? '' : 'none';
    }
    syncUnassignTypePanel();
}

function syncUnassignTypePanel() {
    var type = selectedUnassignType();
    var fullDay = document.getElementById('unassignFullDayFields');
    var timeRange = document.getElementById('unassignTimeRangeFields');
    var permanent = document.getElementById('unassignPermanentNotice');
    if (fullDay) {
        if (window.smoothToggleElement) window.smoothToggleElement(fullDay, type === 'full_day');
        else fullDay.style.display = type === 'full_day' ? '' : 'none';
    }
    if (timeRange) {
        if (window.smoothToggleElement) window.smoothToggleElement(timeRange, type === 'time_range');
        else timeRange.style.display = type === 'time_range' ? '' : 'none';
    }
    if (permanent) {
        if (window.smoothToggleElement) window.smoothToggleElement(permanent, type === 'permanent');
        else permanent.style.display = type === 'permanent' ? '' : 'none';
    }

    if (type === 'time_range' && document.querySelectorAll('.candidate-range-row').length === 0) {
        addUnassignRangeRow();
    }
}

function todayYmd() {
    var now = new Date();
    var month = String(now.getMonth() + 1).padStart(2, '0');
    var day = String(now.getDate()).padStart(2, '0');
    return now.getFullYear() + '-' + month + '-' + day;
}

function isPastSelectedTime(dateValue, timeValue) {
    if (!dateValue || !timeValue) return false;
    var selected = new Date(dateValue + 'T' + timeValue);
    return selected.getTime() < Date.now();
}

function initFlatpickrForRangeRow(row) {
    if (typeof flatpickr === 'undefined' || !row) return;

    row.querySelectorAll('[data-range-date]').forEach(function (input) {
        if (input._flatpickr) return;
        flatpickr(input, {
            dateFormat: 'Y-m-d',
            minDate: 'today',
            allowInput: false,
        });
    });

    row.querySelectorAll('[data-range-time]').forEach(function (input) {
        if (input._flatpickr) return;
        flatpickr(input, {
            enableTime: true,
            noCalendar: true,
            dateFormat: 'H:i',
            altInput: true,
            altFormat: 'h:i K',
            time_24hr: false,
            minuteIncrement: 5,
            allowInput: false,
        });
    });
}

function refreshUnassignRangeTitles() {
    document.querySelectorAll('.candidate-range-row').forEach(function (row, index) {
        var title = row.querySelector('.candidate-range-row-title');
        var removeBtn = row.querySelector('[data-remove-range]');
        if (title) title.textContent = 'Time Range ' + (index + 1);
        if (removeBtn) removeBtn.style.display = index === 0 ? 'none' : '';
    });
}

function addUnassignRangeRow() {
    var list = document.getElementById('bulkUnassignRangeList');
    if (!list) return;

    var index = bulkUnassignRangeIndex++;
    var row = document.createElement('div');
    row.className = 'candidate-range-row';
    row.innerHTML = [
        '<div class="candidate-range-row-head">',
            '<div class="candidate-range-row-title">Time Range</div>',
            '<button type="button" class="btn btn-outline btn-sm" data-remove-range onclick="removeUnassignRangeRow(this)" title="Remove range">',
                '<i class="bi bi-x-lg"></i>',
            '</button>',
        '</div>',
        '<div class="candidate-range-grid">',
            '<div class="form-group mb-0">',
                '<label class="form-label">Date</label>',
                '<input type="text" name="unassign_ranges[' + index + '][date]" class="form-control" data-range-date placeholder="Select date">',
            '</div>',
            '<div class="form-group mb-0">',
                '<label class="form-label">Start Time</label>',
                '<input type="text" name="unassign_ranges[' + index + '][start_time]" class="form-control" data-range-time placeholder="Select start time">',
            '</div>',
            '<div class="form-group mb-0">',
                '<label class="form-label">End Time</label>',
                '<input type="text" name="unassign_ranges[' + index + '][end_time]" class="form-control" data-range-time placeholder="Select end time">',
            '</div>',
        '</div>',
    ].join('');

    list.appendChild(row);
    initFlatpickrForRangeRow(row);
    refreshUnassignRangeTitles();
}

function removeUnassignRangeRow(button) {
    var row = button.closest('.candidate-range-row');
    if (!row) return;
    row.remove();
    if (document.querySelectorAll('.candidate-range-row').length === 0) {
        addUnassignRangeRow();
    }
    refreshUnassignRangeTitles();
}

function getUnassignRangeRows() {
    return Array.from(document.querySelectorAll('.candidate-range-row')).map(function (row) {
        return {
            row: row,
            date: row.querySelector('[data-range-date]'),
            start: row.querySelector('[name$="[start_time]"]'),
            end: row.querySelector('[name$="[end_time]"]'),
        };
    });
}

function validateCandidateBulkAction() {
    syncBulkCandidateIds();
    var selected = getSelectedCandidateCheckboxes();
    if (!selected.length) {
        alert('Select at least one candidate first.');
        return false;
    }

    var action = selectedBulkAction();

    if (action === 'assign') {
        var ownerSelect = document.getElementById('bulkAssignOwner');

        if (!ownerSelect || !ownerSelect.value) {
            alert('Select one recruiter or team manager for the transfer.');
            return false;
        }

        return confirm('Assign the selected candidates to the chosen owner?');
    }

    if (action === 'take_back') {
        return confirm('Take the selected candidates back under your ownership?');
    }

    if (action === 'unassign') {
        var type = selectedUnassignType();

        if (type === 'permanent') {
            if (!window.__isRealAdmin) {
                alert('Only admin can permanently unassign candidates.');
                return false;
            }

            return confirm('Permanently move the selected candidates to unassigned?');
        }

        if (type === 'full_day') {
            var dates = document.getElementById('bulkUnassignDates');
            if (!dates || !dates.value) {
                alert('Select at least one unassign date.');
                return false;
            }

            return confirm('Temporarily unassign selected candidates for the selected full day(s)?');
        }

        var rows = getUnassignRangeRows();
        if (!rows.length) {
            alert('Add at least one time range.');
            return false;
        }

        for (var i = 0; i < rows.length; i++) {
            var range = rows[i];
            if (!range.date.value || !range.start.value || !range.end.value) {
                alert('Select date, start time, and end time for Time Range ' + (i + 1) + '.');
                return false;
            }
            if (range.end.value <= range.start.value) {
                alert('End time must be after start time for Time Range ' + (i + 1) + '.');
                return false;
            }
            if (isPastSelectedTime(range.date.value, range.start.value)) {
                alert('Past time cannot be selected for Time Range ' + (i + 1) + '.');
                return false;
            }
        }

        return confirm('Temporarily unassign selected candidates for the configured time range(s)?');
    }

    alert('Select an action first.');
    return false;
}

document.addEventListener('DOMContentLoaded', function () {
    if (typeof flatpickr !== 'undefined') {
        var display = document.getElementById('bulkUnassignDatesDisplay');
        var hidden = document.getElementById('bulkUnassignDates');
        if (display && hidden) {
            flatpickr(display, {
                mode: 'multiple',
                dateFormat: 'Y-m-d',
                minDate: 'today',
                onChange: function (selectedDates, dateStr) {
                    hidden.value = dateStr.replace(/, /g, ',');
                },
            });
        }
    }

    document.querySelectorAll('input[name="bulk_action"]').forEach(function (input) {
        input.addEventListener('change', syncCandidateBulkActionPanel);
    });

    syncCandidateBulkActionPanel();
});
</script>
@endpush
@endsection
