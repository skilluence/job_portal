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

        <form method="POST" action="{{ route('admin.candidates.bulk-ownership') }}" id="candidateBulkOwnershipForm" class="d-flex gap-8" style="flex-wrap:wrap;align-items:flex-end;">
            @csrf

            @if ($isRealAdmin ?? false)
                <div class="form-group mb-0">
                    <label class="form-label">Transfer to Recruiter</label>
                    <select name="assign_recruiter_id" id="bulkAssignRecruiter" class="form-control" style="min-width:180px;">
                        <option value="">Select recruiter</option>
                        @foreach ($recruiters as $recruiter)
                            <option value="{{ $recruiter->id }}">{{ $recruiter->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">Transfer to Team Manager</label>
                    <select name="assign_team_manager_id" id="bulkAssignManager" class="form-control" style="min-width:180px;">
                        <option value="">Select team manager</option>
                        @foreach ($managers as $manager)
                            <option value="{{ $manager->id }}">{{ $manager->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit"
                        name="bulk_action"
                        value="assign"
                        class="btn btn-primary btn-sm"
                        onclick="return validateCandidateBulkAction('assign');">
                    <i class="bi bi-arrow-left-right"></i> Assign Selected
                </button>
                <button type="submit"
                        name="bulk_action"
                        value="unassign"
                        class="btn btn-outline btn-sm"
                        onclick="return validateCandidateBulkAction('unassign');">
                    <i class="bi bi-person-dash"></i> Move to Unassigned
                </button>
            @elseif ($ownership === 'reclaim')
                <button type="submit"
                        name="bulk_action"
                        value="take_back"
                        class="btn btn-primary btn-sm"
                        onclick="return validateCandidateBulkAction('take_back');">
                    <i class="bi bi-arrow-counterclockwise"></i> Take Back Selected
                </button>
            @else
                <button type="submit"
                        name="bulk_action"
                        value="unassign"
                        class="btn btn-outline btn-sm"
                        onclick="return validateCandidateBulkAction('unassign');">
                    <i class="bi bi-person-dash"></i> Move Selected to Unassigned
                </button>
            @endif
        </form>
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
                    @endphp
                    <tr>
                        <td>
                            <input type="checkbox"
                                   class="candidate-row-checkbox"
                                   name="candidate_ids[]"
                                   value="{{ $candidate->id }}"
                                   form="candidateBulkOwnershipForm">
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
                            @else
                                <span class="badge badge-warning" style="font-size:11px;">Unassigned</span>
                                @if ($candidate->latestAssignmentHistory?->changer)
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

function getSelectedCandidateCheckboxes() {
    return Array.from(document.querySelectorAll('.candidate-row-checkbox:checked'));
}

function toggleAllCandidateRows(source) {
    document.querySelectorAll('.candidate-row-checkbox').forEach(function (checkbox) {
        checkbox.checked = source.checked;
    });
}

function validateCandidateBulkAction(action) {
    var selected = getSelectedCandidateCheckboxes();
    if (!selected.length) {
        alert('Select at least one candidate first.');
        return false;
    }

    if (action === 'assign') {
        var recruiterSelect = document.getElementById('bulkAssignRecruiter');
        var managerSelect = document.getElementById('bulkAssignManager');
        var recruiterId = recruiterSelect ? recruiterSelect.value : '';
        var managerId = managerSelect ? managerSelect.value : '';

        if ((!recruiterId && !managerId) || (recruiterId && managerId)) {
            alert('Select either one recruiter or one team manager for the transfer.');
            return false;
        }

        return confirm('Assign the selected candidates to the chosen owner?');
    }

    if (action === 'take_back') {
        return confirm('Take the selected candidates back under your ownership?');
    }

    return confirm('Move the selected candidates to unassigned?');
}

document.addEventListener('DOMContentLoaded', function () {
    var recruiterSelect = document.getElementById('bulkAssignRecruiter');
    var managerSelect = document.getElementById('bulkAssignManager');

    if (recruiterSelect && managerSelect) {
        recruiterSelect.addEventListener('change', function () {
            if (this.value) {
                managerSelect.value = '';
            }
        });

        managerSelect.addEventListener('change', function () {
            if (this.value) {
                recruiterSelect.value = '';
            }
        });
    }
});
</script>
@endpush
@endsection
