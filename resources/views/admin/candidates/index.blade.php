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
    <div style="flex:1;display:flex;align-items:center;justify-content:flex-end;min-width:160px;">
        <button class="btn btn-primary" onclick="openCandidateModal('add')">
            <i class="bi bi-plus-lg"></i> Add Candidate
        </button>
    </div>
</div>
@endif

<div class="card">
    <div class="card-header" style="flex-wrap:wrap;gap:12px;">
        <div>
            <div class="card-title">
                @if (($isManager ?? false) && $scope === 'mine') My Candidates
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
                    <a href="{{ route('admin.candidates', $scope ? ['scope' => $scope] : []) }}"
                       class="btn btn-outline" title="Clear filters"><i class="bi bi-x-lg"></i></a>
                @endif
            </form>
            @if (!($isManager ?? false))
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
                    <th style="width:36px;">#</th>
                    <th>Candidate</th>
                    <th>Domain</th>
                    <th>Phone</th>
                    <th>Visa Status</th>
                    <th>Status</th>
                    <th style="width:50px;">Apps</th>
                    <th>Recruiter</th>
                    <th>Documents</th>
                    <th style="width:90px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($candidates as $i => $candidate)
                    @php
                        $visaLabel = $visaOptions[$candidate->visa_immigration_status] ?? ucfirst(str_replace('_', ' ', $candidate->visa_immigration_status ?? ''));
                        $domainText = implode(' / ', array_filter([$candidate->domain, $candidate->sub_domain]));
                    @endphp
                    <tr>
                        <td class="text-muted text-sm">{{ $candidates->firstItem() + $i }}</td>
                        <td>
                            <a href="{{ route('admin.candidates.show', $candidate) }}" class="avatar-row" style="text-decoration:none;color:inherit;">
                                <div class="avatar-sm">{{ strtoupper(substr($candidate->full_name, 0, 1)) }}</div>
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
                        <td class="text-muted text-sm">{{ $candidate->recruiter?->name ?? '-' }}</td>
                        <td>
                            <div class="d-flex gap-4" style="flex-wrap:wrap;">
                                @if ($candidate->cv_file_path)
                                    <a class="btn btn-outline btn-sm" href="{{ route('admin.candidates.files', [$candidate, 'cv']) }}" target="_blank" rel="noopener" title="View CV">CV</a>
                                @endif
                                @if ($candidate->candidate_details_file_path)
                                    <a class="btn btn-outline btn-sm" href="{{ route('admin.candidates.files', [$candidate, 'details']) }}" target="_blank" rel="noopener" title="View Details">Details</a>
                                @endif
                                @if ($candidate->speedy_apply_json_path)
                                    <a class="btn btn-outline btn-sm" href="{{ route('admin.candidates.files', [$candidate, 'speedy']) }}" target="_blank" rel="noopener" title="Speedy Apply">JSON</a>
                                @endif
                                @if (!$candidate->cv_file_path && !$candidate->candidate_details_file_path && !$candidate->speedy_apply_json_path)
                                    <span class="text-muted text-sm">-</span>
                                @endif
                            </div>
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
                                    'marketing_linkedin_id' => $candidate->marketing_linkedin_id,
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
                            <div style="display:flex;gap:6px;align-items:center;flex-wrap:nowrap;">
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
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10">
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
<div class="modal-overlay" id="addCandidateModal">
    <div class="modal modal-xl">
        <div class="modal-header">
            <div class="modal-title"><i class="bi bi-person-plus" style="margin-right:6px;"></i> Add New Candidate</div>
            <button class="modal-close" onclick="closeModal('addCandidateModal')">&times;</button>
        </div>
        <form method="POST" action="{{ route('admin.candidates.store') }}" enctype="multipart/form-data">
            @csrf
            @include('admin.candidates._form', ['mode' => 'add', 'usStates' => $usStates, 'visaOptions' => $visaOptions, 'workAuthOptions' => $workAuthOptions, 'recruiters' => $recruiters, 'managers' => $managers, 'statusOptions' => $statusOptions, 'isAdmin' => $isAdmin, 'isRealAdmin' => $isRealAdmin, 'prefix' => 'add'])
        </form>
    </div>
</div>

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
            enctype="multipart/form-data">
            @csrf
            @method('PUT')
            @include('admin.candidates._form', ['mode' => 'edit', 'usStates' => $usStates, 'visaOptions' => $visaOptions, 'workAuthOptions' => $workAuthOptions, 'recruiters' => $recruiters, 'managers' => $managers, 'statusOptions' => $statusOptions, 'isAdmin' => $isAdmin, 'isRealAdmin' => $isRealAdmin, 'prefix' => 'edit'])
        </form>
    </div>
</div>

@push('scripts')
<script>window.__isManager = {{ $isManager ? 'true' : 'false' }};</script>
@endpush
@endsection
