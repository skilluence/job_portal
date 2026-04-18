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
                <i class="bi bi-person-badge-fill"></i> Recruiters
            </button>
        </div>

        {{-- Import → Candidates --}}
        <div class="ie-tab-panel" id="subpanel-import-candidates">
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

            <div class="ie-step" style="border-bottom:none;padding-bottom:0;margin-bottom:0;">
                <div class="ie-step-num">2</div>
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

        {{-- Import → Recruiters --}}
        <div class="ie-tab-panel" id="subpanel-import-recruiters">
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

            <div class="ie-step" style="border-bottom:none;padding-bottom:0;margin-bottom:0;">
                <div class="ie-step-num">2</div>
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

    </div>{{-- /panel-import --}}

    {{-- ══════════════ EXPORT PANEL ══════════════ --}}
    <div class="ie-tab-panel" id="panel-export">

        <div class="ie-subtabs">
            <button class="ie-subtab" id="subtab-export-candidates" onclick="switchIESubTab('export','candidates')">
                <i class="bi bi-people-fill"></i> Candidates
            </button>
            <button class="ie-subtab" id="subtab-export-recruiters" onclick="switchIESubTab('export','recruiters')">
                <i class="bi bi-person-badge-fill"></i> Recruiters
            </button>
        </div>

        {{-- Export → Candidates --}}
        <div class="ie-tab-panel" id="subpanel-export-candidates">
            <form method="GET" action="{{ route('admin.import-export.export-candidates') }}">
                <div class="ie-export-form">
                    <div class="ie-export-form-title">
                        <i class="bi bi-funnel-fill" style="color:var(--blue);"></i> Filter Export
                    </div>
                    <div style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
                        <div class="form-group mb-0" style="flex:1;min-width:140px;">
                            <label class="form-label">Date From <span class="text-muted" style="font-weight:400;">(created)</span></label>
                            <input type="date" name="date_from" class="form-control">
                        </div>
                        <div class="form-group mb-0" style="flex:1;min-width:140px;">
                            <label class="form-label">Date To <span class="text-muted" style="font-weight:400;">(created)</span></label>
                            <input type="date" name="date_to" class="form-control">
                        </div>
                        <div class="form-group mb-0" style="flex:1;min-width:140px;">
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
                        <div class="form-group mb-0" style="flex:1;min-width:140px;">
                            <label class="form-label">Recruiter</label>
                            <select name="recruiter_id" class="form-control">
                                <option value="">All Recruiters</option>
                                @foreach ($recruiters as $rec)
                                    <option value="{{ $rec->id }}">{{ $rec->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group mb-0" style="flex-shrink:0;">
                            <label class="form-label" style="visibility:hidden;">x</label>
                            <button type="submit" class="btn btn-primary" title="Export Filtered Data"
                                style="width:38px;height:38px;padding:0;display:flex;align-items:center;justify-content:center;font-size:16px;">
                                <i class="bi bi-download"></i>
                            </button>
                        </div>
                    </div>
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

        {{-- Export → Recruiters --}}
        <div class="ie-tab-panel" id="subpanel-export-recruiters">
            <form method="GET" action="{{ route('admin.import-export.export-recruiters') }}">
                <div class="ie-export-form">
                    <div class="ie-export-form-title">
                        <i class="bi bi-funnel-fill" style="color:var(--blue);"></i> Filter Export
                    </div>
                    <div style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
                        <div class="form-group mb-0" style="flex:1;min-width:140px;">
                            <label class="form-label">Date From <span class="text-muted" style="font-weight:400;">(created)</span></label>
                            <input type="date" name="date_from" class="form-control">
                        </div>
                        <div class="form-group mb-0" style="flex:1;min-width:140px;">
                            <label class="form-label">Date To <span class="text-muted" style="font-weight:400;">(created)</span></label>
                            <input type="date" name="date_to" class="form-control">
                        </div>
                        <div class="form-group mb-0" style="flex:1;min-width:140px;">
                            <label class="form-label">Role</label>
                            <select name="role" class="form-control">
                                <option value="">All Roles</option>
                                <option value="admin">Admin</option>
                                <option value="recruiter">Recruiter</option>
                            </select>
                        </div>
                        <div class="form-group mb-0" style="flex:1;min-width:140px;">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-control">
                                <option value="">All Statuses</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="form-group mb-0" style="flex-shrink:0;">
                            <label class="form-label" style="visibility:hidden;">x</label>
                            <button type="submit" class="btn btn-primary" title="Export Filtered Data"
                                style="width:38px;height:38px;padding:0;display:flex;align-items:center;justify-content:center;font-size:16px;">
                                <i class="bi bi-download"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </form>

            <a href="{{ route('admin.import-export.export-recruiters') }}" class="ie-export-all-link">
                <i class="bi bi-file-earmark-spreadsheet" style="font-size:22px;color:var(--blue);flex-shrink:0;"></i>
                <div style="flex:1;min-width:0;">
                    <div style="font-weight:600;font-size:13px;color:var(--text-primary);">Export All Recruiters</div>
                    <div style="font-size:11px;color:var(--text-muted);margin-top:1px;">Download the complete recruiter list without any filters</div>
                </div>
                <i class="bi bi-chevron-right" style="color:var(--text-muted);flex-shrink:0;"></i>
            </a>
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
