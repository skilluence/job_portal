@extends('layouts.admin')
@section('title', 'Import / Export')
@section('module-title', 'Import / Export')
@section('module-description', 'Bulk-import records via CSV or export your data with custom filters.')
@section('content')

<style>
.ie-tabs { display:flex; border-bottom:2px solid var(--border-color); margin-bottom:24px; gap:0; }
.ie-tab  { padding:10px 22px; font-size:14px; font-weight:600; color:var(--text-secondary); background:none; border:none; border-bottom:2px solid transparent; cursor:pointer; margin-bottom:-2px; display:flex; align-items:center; gap:7px; transition:color var(--transition),border-color var(--transition); }
.ie-tab:hover  { color:var(--blue-text); }
.ie-tab.active { color:var(--blue-text); border-bottom-color:var(--blue); }
.ie-subtabs  { display:flex; gap:8px; margin-bottom:22px; flex-wrap:wrap; }
.ie-subtab   { padding:7px 16px; font-size:13px; font-weight:500; color:var(--text-secondary); background:var(--main-bg); border:1px solid var(--border-color); border-radius:6px; cursor:pointer; display:flex; align-items:center; gap:6px; transition:all var(--transition); }
.ie-subtab:hover   { border-color:var(--blue); color:var(--blue-text); }
.ie-subtab.active  { background:var(--blue); color:#fff; border-color:var(--blue); }
.ie-tab-panel      { display:none; }
.ie-tab-panel.active { display:block; }
.ie-export-form    { background:var(--main-bg); border:1px solid var(--border-color); border-radius:var(--radius-sm); padding:18px 20px; margin-bottom:16px; }
.ie-export-form-title { font-size:13px; font-weight:700; color:var(--text-primary); margin-bottom:14px; display:flex; align-items:center; gap:6px; }
.ie-export-all-link { display:flex; align-items:center; gap:12px; padding:13px 16px; border:1.5px dashed var(--border-color); border-radius:var(--radius-sm); text-decoration:none; color:var(--text-secondary); font-size:13px; transition:all var(--transition); }
.ie-export-all-link:hover { border-color:var(--blue); color:var(--blue-text); background:var(--blue-light); }
.ie-col-list { display:grid; grid-template-columns:1fr 1fr; gap:3px 10px; }
.ie-col-tag  { font-size:10.5px; padding:2px 0; color:var(--text-secondary); }
.ie-col-tag.required { color:var(--blue-text); font-weight:600; }
</style>

{{-- Flash Toast --}}
@if (session('success'))
<div class="toast-container" id="flashToast">
    <div class="toast toast-success">
        <i class="bi bi-check-circle-fill"></i>
        <span>{{ session('success') }}</span>
    </div>
</div>
@endif

{{-- Import Errors --}}
@if (session('import_errors') && count(session('import_errors')) > 0)
<div class="alert alert-error mb-16">
    <i class="bi bi-exclamation-circle-fill" style="flex-shrink:0;font-size:16px;"></i>
    <div>
        <strong>{{ count(session('import_errors')) }} row(s) had issues:</strong>
        <ul style="margin-top:6px;">
            @foreach (session('import_errors') as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
</div>
@endif

{{-- Validation Errors --}}
@if ($errors->any())
<div class="alert alert-error mb-16">
    <i class="bi bi-exclamation-circle-fill" style="flex-shrink:0;font-size:16px;"></i>
    <div>
        @foreach ($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
</div>
@endif

{{-- ════ Main Card with Top-Level Tabs ════ --}}
<div class="card" style="margin-bottom:24px;">

    {{-- Top Tabs: Import / Export --}}
    <div class="ie-tabs">
        <button class="ie-tab" id="tab-import" onclick="switchIETab('import')">
            <i class="bi bi-cloud-upload"></i> Import
        </button>
        <button class="ie-tab" id="tab-export" onclick="switchIETab('export')">
            <i class="bi bi-cloud-download"></i> Export
        </button>
    </div>

    {{-- ══════════════ IMPORT PANEL ══════════════ --}}
    <div class="ie-tab-panel" id="panel-import">

        <div class="ie-subtabs">
            <button class="ie-subtab" id="subtab-import-candidates" onclick="switchIESubTab('import','candidates')">
                <i class="bi bi-people-fill"></i> Candidates
            </button>
            <button class="ie-subtab" id="subtab-import-recruiters" onclick="switchIESubTab('import','recruiters')">
                <i class="bi bi-person-badge-fill"></i> Recruiters &amp; Managers
            </button>
        </div>

        {{-- Import → Candidates --}}
        <div class="ie-tab-panel" id="subpanel-import-candidates">
            <div class="ie-row">

                {{-- Steps --}}
                <div>
                    <div class="ie-section-header">
                        <div class="ie-section-icon ie-icon-up"><i class="bi bi-upload"></i></div>
                        <div>
                            <div class="ie-section-name">Import Candidates</div>
                            <div class="ie-section-desc">Bulk upload candidates from a CSV spreadsheet</div>
                        </div>
                    </div>

                    <div class="ie-step">
                        <div class="ie-step-num">1</div>
                        <div class="ie-step-body">
                            <div class="ie-step-title">Download Template</div>
                            <div class="ie-step-desc">Get the CSV template with all required columns and a sample data row.</div>
                            <a href="{{ route('admin.import-export.candidate-template') }}" class="btn btn-outline btn-sm" style="margin-top:10px;">
                                <i class="bi bi-file-earmark-arrow-down"></i> Download Template
                            </a>
                        </div>
                    </div>

                    <div class="ie-step">
                        <div class="ie-step-num">2</div>
                        <div class="ie-step-body">
                            <div class="ie-step-title">Fill Your Data</div>
                            <div class="ie-step-desc">Open the template in Excel or Google Sheets. Fill in all required fields and save as <strong>.csv</strong>.</div>
                        </div>
                    </div>

                    <div class="ie-step" style="border-bottom:none;padding-bottom:0;margin-bottom:0;">
                        <div class="ie-step-num">3</div>
                        <div class="ie-step-body">
                            <div class="ie-step-title">Upload &amp; Import</div>
                            <div class="ie-step-desc">Select your filled CSV file to import candidates into the system.</div>
                            <form method="POST" action="{{ route('admin.import-export.import-candidates') }}"
                                  enctype="multipart/form-data" style="margin-top:12px;">
                                @csrf
                                <div class="ie-file-row">
                                    <label class="btn btn-primary btn-sm" style="cursor:pointer;position:relative;overflow:hidden;">
                                        <i class="bi bi-cloud-upload"></i> Select File
                                        <input type="file" name="file" accept=".csv,.txt"
                                               style="position:absolute;inset:0;opacity:0;cursor:pointer;"
                                               onchange="document.getElementById('cand-fn').textContent=this.files[0]?.name||''; document.getElementById('cand-sub').style.display='inline-flex';">
                                    </label>
                                    <span id="cand-fn" class="text-sm text-muted"></span>
                                </div>
                                <button type="submit" id="cand-sub" class="btn btn-primary btn-sm" style="margin-top:10px;display:none;">
                                    <i class="bi bi-upload"></i> Start Import
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- Tips + Column List --}}
                <div>
                    <div class="ie-tips">
                        <div class="ie-tips-title"><i class="bi bi-info-circle-fill"></i> Import Tips — Candidates</div>
                        <ul>
                            <li>Column names must match the template exactly</li>
                            <li>Required: <strong>first_name, last_name, email_id, portal_login_password, recruiter</strong></li>
                            <li>Status values: <strong>active, enrolled, interview, offer, placed, onhold, inactive</strong></li>
                            <li>Recruiter name must match an existing recruiter in the system</li>
                            <li>Visa status: <strong>us_citizen, green_card, h1b, h4_ead, opt_f1, stem_opt, cpt, l1, tn_visa, other</strong></li>
                            <li>Up to <strong>1,000 candidates</strong> per import; duplicate emails are skipped</li>
                        </ul>
                    </div>

                    <div style="margin-top:14px;padding:14px;background:var(--main-bg);border-radius:var(--radius-sm);border:1px solid var(--border-color);">
                        <div style="font-size:12px;font-weight:700;color:var(--text-primary);margin-bottom:10px;display:flex;align-items:center;gap:5px;">
                            <i class="bi bi-table" style="color:var(--blue);"></i> Template Columns
                        </div>
                        <div class="ie-col-list">
                            @foreach ([
                                ['first_name', true], ['last_name', true],
                                ['email_id', true], ['phone_number', false],
                                ['domain', false], ['sub_domain', false],
                                ['city', false], ['state_province', false],
                                ['zip_code', false], ['visa_immigration_status', false],
                                ['work_auth_status', false], ['no_of_applications', false],
                                ['status', false], ['recruiter', true],
                                ['portal_login_password', true], ['', false],
                            ] as [$col, $req])
                            @if($col)
                            <div class="ie-col-tag {{ $req ? 'required' : '' }}">
                                <code style="font-size:10px;background:rgba(0,0,0,0.04);padding:1px 4px;border-radius:3px;">{{ $col }}</code>
                                @if ($req)<span style="color:var(--blue-text);font-size:9px;margin-left:2px;">*</span>@endif
                            </div>
                            @endif
                            @endforeach
                        </div>
                        <div style="font-size:10px;color:var(--text-muted);margin-top:8px;"><span style="color:var(--blue-text);">*</span> = required field</div>
                    </div>
                </div>

            </div>
        </div>

        {{-- Import → Recruiters --}}
        <div class="ie-tab-panel" id="subpanel-import-recruiters">
            <div class="ie-row">

                {{-- Steps --}}
                <div>
                    <div class="ie-section-header">
                        <div class="ie-section-icon ie-icon-up"><i class="bi bi-upload"></i></div>
                        <div>
                            <div class="ie-section-name">Import Recruiters &amp; Managers</div>
                            <div class="ie-section-desc">Bulk upload recruiters and team managers from a CSV spreadsheet</div>
                        </div>
                    </div>

                    <div class="ie-step">
                        <div class="ie-step-num">1</div>
                        <div class="ie-step-body">
                            <div class="ie-step-title">Download Template</div>
                            <div class="ie-step-desc">Get the CSV template with all required columns and sample data rows.</div>
                            <a href="{{ route('admin.import-export.recruiter-template') }}" class="btn btn-outline btn-sm" style="margin-top:10px;">
                                <i class="bi bi-file-earmark-arrow-down"></i> Download Template
                            </a>
                        </div>
                    </div>

                    <div class="ie-step">
                        <div class="ie-step-num">2</div>
                        <div class="ie-step-body">
                            <div class="ie-step-title">Fill Your Data</div>
                            <div class="ie-step-desc">Open the template in Excel or Google Sheets. Fill in all required fields and save as <strong>.csv</strong>.</div>
                        </div>
                    </div>

                    <div class="ie-step" style="border-bottom:none;padding-bottom:0;margin-bottom:0;">
                        <div class="ie-step-num">3</div>
                        <div class="ie-step-body">
                            <div class="ie-step-title">Upload &amp; Import</div>
                            <div class="ie-step-desc">Select your filled CSV file to import recruiters and team managers.</div>
                            <form method="POST" action="{{ route('admin.import-export.import-recruiters') }}"
                                  enctype="multipart/form-data" style="margin-top:12px;">
                                @csrf
                                <div class="ie-file-row">
                                    <label class="btn btn-primary btn-sm" style="cursor:pointer;position:relative;overflow:hidden;">
                                        <i class="bi bi-cloud-upload"></i> Select File
                                        <input type="file" name="file" accept=".csv,.txt"
                                               style="position:absolute;inset:0;opacity:0;cursor:pointer;"
                                               onchange="document.getElementById('rec-fn').textContent=this.files[0]?.name||''; document.getElementById('rec-sub').style.display='inline-flex';">
                                    </label>
                                    <span id="rec-fn" class="text-sm text-muted"></span>
                                </div>
                                <button type="submit" id="rec-sub" class="btn btn-primary btn-sm" style="margin-top:10px;display:none;">
                                    <i class="bi bi-upload"></i> Start Import
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- Tips + Column List --}}
                <div>
                    <div class="ie-tips">
                        <div class="ie-tips-title"><i class="bi bi-info-circle-fill"></i> Import Tips — Recruiters</div>
                        <ul>
                            <li>Required: <strong>name, email, password, role</strong></li>
                            <li>Role must be: <strong>admin</strong>, <strong>recruiter</strong>, or <strong>manager</strong></li>
                            <li><strong>team_manager_email</strong> links a recruiter to their manager</li>
                            <li>Status: <strong>active</strong> or <strong>inactive</strong> — defaults to active if blank</li>
                            <li>Passwords are hashed securely on import</li>
                            <li>Duplicate emails are skipped with a row error notice</li>
                        </ul>
                    </div>

                    <div style="margin-top:14px;padding:14px;background:var(--main-bg);border-radius:var(--radius-sm);border:1px solid var(--border-color);">
                        <div style="font-size:12px;font-weight:700;color:var(--text-primary);margin-bottom:10px;display:flex;align-items:center;gap:5px;">
                            <i class="bi bi-table" style="color:var(--blue);"></i> Template Columns
                        </div>
                        <div style="display:flex;flex-direction:column;gap:5px;">
                            @foreach ([
                                ['name', true], ['email', true],
                                ['password', true], ['role', true],
                                ['status', false], ['team_manager_email', false],
                            ] as [$col, $req])
                            <div class="ie-col-tag {{ $req ? 'required' : '' }}">
                                <code style="font-size:10px;background:rgba(0,0,0,0.04);padding:1px 4px;border-radius:3px;">{{ $col }}</code>
                                @if ($req)<span style="color:var(--blue-text);font-size:9px;margin-left:2px;">*</span>@endif
                            </div>
                            @endforeach
                        </div>
                        <div style="font-size:10px;color:var(--text-muted);margin-top:8px;"><span style="color:var(--blue-text);">*</span> = required field</div>
                    </div>
                </div>

            </div>
        </div>

    </div>{{-- /panel-import --}}

    {{-- ══════════════ EXPORT PANEL ══════════════ --}}
    <div class="ie-tab-panel" id="panel-export">

        <div class="ie-subtabs">
            <button class="ie-subtab" id="subtab-export-candidates" onclick="switchIESubTab('export','candidates')">
                <i class="bi bi-people-fill"></i> Candidates
            </button>
            <button class="ie-subtab" id="subtab-export-recruiters" onclick="switchIESubTab('export','recruiters')">
                <i class="bi bi-person-badge-fill"></i> Recruiters &amp; Managers
            </button>
        </div>

        {{-- Export → Candidates --}}
        <div class="ie-tab-panel" id="subpanel-export-candidates">
            <div class="ie-row">

                <div>
                    <form method="GET" action="{{ route('admin.import-export.export-candidates') }}">
                        <div class="ie-export-form">
                            <div class="ie-export-form-title">
                                <i class="bi bi-funnel-fill" style="color:var(--blue);"></i> Filter Export
                            </div>
                            <div class="form-grid" style="margin-bottom:14px;">
                                <div class="form-group mb-0">
                                    <label class="form-label">Date From <span class="text-muted" style="font-weight:400;">(created)</span></label>
                                    <input type="date" name="date_from" class="form-control">
                                </div>
                                <div class="form-group mb-0">
                                    <label class="form-label">Date To <span class="text-muted" style="font-weight:400;">(created)</span></label>
                                    <input type="date" name="date_to" class="form-control">
                                </div>
                                <div class="form-group mb-0">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-control">
                                        <option value="">All Statuses</option>
                                        <option value="active">Active</option>
                                        <option value="enrolled">Enrolled</option>
                                        <option value="interview">Interview</option>
                                        <option value="offer">Offer</option>
                                        <option value="placed">Placed</option>
                                        <option value="onhold">On Hold</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                                <div class="form-group mb-0">
                                    <label class="form-label">Recruiter</label>
                                    <select name="recruiter_id" class="form-control">
                                        <option value="">All Recruiters</option>
                                        @foreach ($recruiters as $rec)
                                            <option value="{{ $rec->id }}">{{ $rec->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="bi bi-download"></i> Export Filtered Data
                            </button>
                        </div>
                    </form>

                    <a href="{{ route('admin.import-export.export-candidates') }}" class="ie-export-all-link">
                        <i class="bi bi-file-earmark-spreadsheet" style="font-size:22px;color:var(--blue);flex-shrink:0;"></i>
                        <div style="flex:1;min-width:0;">
                            <div style="font-weight:600;font-size:13px;color:var(--text-primary);">Export All Candidates</div>
                            <div style="font-size:11px;color:var(--text-muted);margin-top:1px;">Download the complete candidate list without any filters</div>
                        </div>
                        <i class="bi bi-chevron-right" style="color:var(--text-muted);flex-shrink:0;"></i>
                    </a>
                </div>

                <div class="ie-tips" style="align-self:start;">
                    <div class="ie-tips-title"><i class="bi bi-info-circle-fill"></i> Export Info — Candidates</div>
                    <ul>
                        <li>All candidate fields are included in the export</li>
                        <li>Use date filters to export records created within a specific range</li>
                        <li>Use status filter to export one specific stage of candidates</li>
                        <li>Use recruiter filter to export one recruiter's candidate list</li>
                        <li>Combine multiple filters for a precise export</li>
                        <li>File is compatible with Excel &amp; Google Sheets</li>
                    </ul>
                </div>

            </div>
        </div>

        {{-- Export → Recruiters --}}
        <div class="ie-tab-panel" id="subpanel-export-recruiters">
            <div class="ie-row">

                <div>
                    <form method="GET" action="{{ route('admin.import-export.export-recruiters') }}">
                        <div class="ie-export-form">
                            <div class="ie-export-form-title">
                                <i class="bi bi-funnel-fill" style="color:var(--blue);"></i> Filter Export
                            </div>
                            <div class="form-grid" style="margin-bottom:14px;">
                                <div class="form-group mb-0">
                                    <label class="form-label">Date From <span class="text-muted" style="font-weight:400;">(created)</span></label>
                                    <input type="date" name="date_from" class="form-control">
                                </div>
                                <div class="form-group mb-0">
                                    <label class="form-label">Date To <span class="text-muted" style="font-weight:400;">(created)</span></label>
                                    <input type="date" name="date_to" class="form-control">
                                </div>
                                <div class="form-group mb-0">
                                    <label class="form-label">Role</label>
                                    <select name="role" class="form-control">
                                        <option value="">All Roles</option>
                                        <option value="admin">Admin</option>
                                        <option value="recruiter">Recruiter</option>
                                        <option value="manager">Team Manager</option>
                                    </select>
                                </div>
                                <div class="form-group mb-0">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-control">
                                        <option value="">All Statuses</option>
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="bi bi-download"></i> Export Filtered Data
                            </button>
                        </div>
                    </form>

                    <a href="{{ route('admin.import-export.export-recruiters') }}" class="ie-export-all-link">
                        <i class="bi bi-file-earmark-spreadsheet" style="font-size:22px;color:var(--blue);flex-shrink:0;"></i>
                        <div style="flex:1;min-width:0;">
                            <div style="font-weight:600;font-size:13px;color:var(--text-primary);">Export All Recruiters &amp; Managers</div>
                            <div style="font-size:11px;color:var(--text-muted);margin-top:1px;">Download the complete recruiter list without any filters</div>
                        </div>
                        <i class="bi bi-chevron-right" style="color:var(--text-muted);flex-shrink:0;"></i>
                    </a>
                </div>

                <div class="ie-tips" style="align-self:start;">
                    <div class="ie-tips-title"><i class="bi bi-info-circle-fill"></i> Export Info — Recruiters</div>
                    <ul>
                        <li>All recruiter fields are included in the export</li>
                        <li>Use date filters to export records created within a specific range</li>
                        <li>Filter by role to export only recruiters, managers, or admins</li>
                        <li>Filter by status to export only active or inactive accounts</li>
                        <li>File is compatible with Excel &amp; Google Sheets</li>
                    </ul>
                </div>

            </div>
        </div>

    </div>{{-- /panel-export --}}

</div>{{-- /card --}}

{{-- ════ Import / Export History ════ --}}
<div class="card">
    <div class="card-header">
        <div>
            <div class="card-title">Import / Export History</div>
            <div class="card-subtitle">{{ $history->count() }} recent action(s)</div>
        </div>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Date &amp; Time</th>
                    <th>Action</th>
                    <th>Module</th>
                    <th>Description</th>
                    <th>Performed By</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($history as $log)
                <tr>
                    <td class="text-sm text-muted" style="white-space:nowrap;">
                        {{ $log->created_at->format('M d, Y') }}<br>
                        <span style="font-size:10px;">{{ $log->created_at->format('H:i:s') }}</span>
                    </td>
                    <td>
                        <span class="badge {{ $log->action === 'imported' ? 'badge-success' : 'badge-primary' }}">
                            <i class="bi bi-{{ $log->action === 'imported' ? 'cloud-upload' : 'cloud-download' }}" style="font-size:10px;margin-right:3px;"></i>
                            {{ ucfirst($log->action) }}
                        </span>
                    </td>
                    <td class="text-sm">{{ ucfirst($log->module) }}</td>
                    <td class="text-sm" style="max-width:320px;">{{ $log->description }}</td>
                    <td class="text-sm text-muted">{{ $log->actor_name }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5">
                        <div class="page-empty mb-0">
                            <i class="bi bi-clock-history"></i>
                            <p>No import/export history found yet.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
function switchIETab(tab) {
    document.querySelectorAll('.ie-tab').forEach(t => t.classList.remove('active'));
    document.getElementById('tab-' + tab).classList.add('active');
    document.querySelectorAll('#panel-import, #panel-export').forEach(p => p.classList.remove('active'));
    document.getElementById('panel-' + tab).classList.add('active');
    try { sessionStorage.setItem('ie_tab', tab); } catch(e){}
}

function switchIESubTab(parent, sub) {
    const panel = document.getElementById('panel-' + parent);
    panel.querySelectorAll('.ie-subtab').forEach(t => t.classList.remove('active'));
    document.getElementById('subtab-' + parent + '-' + sub).classList.add('active');
    panel.querySelectorAll('[id^="subpanel-' + parent + '-"]').forEach(p => p.classList.remove('active'));
    document.getElementById('subpanel-' + parent + '-' + sub).classList.add('active');
    try { sessionStorage.setItem('ie_subtab_' + parent, sub); } catch(e){}
}

document.addEventListener('DOMContentLoaded', function () {
    @if (session('import_errors') || ($errors->any()) || (session('success') && request()->isMethod('get')))
        var initTab = 'import';
    @else
        var initTab = (function(){ try { return sessionStorage.getItem('ie_tab') || 'import'; } catch(e){ return 'import'; } })();
    @endif

    var initImportSub = (function(){ try { return sessionStorage.getItem('ie_subtab_import') || 'candidates'; } catch(e){ return 'candidates'; } })();
    var initExportSub = (function(){ try { return sessionStorage.getItem('ie_subtab_export') || 'candidates'; } catch(e){ return 'candidates'; } })();

    switchIETab(initTab);
    switchIESubTab('import', initImportSub);
    switchIESubTab('export', initExportSub);
});
</script>

@endsection
