@extends('layouts.admin')
@section('title', 'Import / Export')
@section('module-title', 'Import / Export')
@section('module-description', 'Download your data as CSV or bulk-upload records from a spreadsheet.')
@section('content')

{{-- ── Flash Toast ─────────────────────────────────────────────────────────── --}}
@if (session('success'))
<div class="toast-container" id="flashToast">
    <div class="toast toast-success">
        <i class="bi bi-check-circle-fill"></i>
        <span>{{ session('success') }}</span>
    </div>
</div>
@endif

{{-- ── Import Errors ────────────────────────────────────────────────────────── --}}
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

{{-- ── Validation Errors ────────────────────────────────────────────────────── --}}
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


{{-- ════════════════════════════════ CANDIDATES ════════════════════════════════ --}}
<div class="ie-section-title">Candidates</div>

<div class="ie-row mb-24">

    {{-- Export Candidates --}}
    <div class="card">
        <div class="ie-section-header">
            <div class="ie-section-icon ie-icon-down"><i class="bi bi-download"></i></div>
            <div>
                <div class="ie-section-name">Export Candidates</div>
                <div class="ie-section-desc">Download all candidates in CSV format</div>
            </div>
        </div>
        <a href="{{ route('admin.import-export.export-candidates') }}" class="ie-export-link">
            <div class="ie-export-link-inner">
                <div class="ie-export-link-left">
                    <i class="bi bi-file-earmark-spreadsheet" style="font-size:18px;color:var(--blue);"></i>
                    <div>
                        <div class="ie-link-title">Export as CSV</div>
                        <div class="ie-link-sub">Compatible with Excel &amp; Google Sheets</div>
                    </div>
                </div>
                <i class="bi bi-chevron-right" style="color:var(--text-muted);"></i>
            </div>
        </a>
    </div>

    {{-- Import Candidates --}}
    <div class="card">
        <div class="ie-section-header">
            <div class="ie-section-icon ie-icon-up"><i class="bi bi-upload"></i></div>
            <div>
                <div class="ie-section-name">Import Candidates</div>
                <div class="ie-section-desc">Bulk upload candidates from CSV file</div>
            </div>
        </div>

        {{-- Step 1 --}}
        <div class="ie-step">
            <div class="ie-step-num">1</div>
            <div class="ie-step-body">
                <div class="ie-step-title">Download Template</div>
                <div class="ie-step-desc">Get the CSV template with sample data and correct column format</div>
                <a href="{{ route('admin.import-export.candidate-template') }}" class="btn btn-outline btn-sm" style="margin-top:10px;">
                    <i class="bi bi-file-earmark-arrow-down"></i> Download Template
                </a>
            </div>
        </div>

        {{-- Step 2 --}}
        <div class="ie-step">
            <div class="ie-step-num">2</div>
            <div class="ie-step-body">
                <div class="ie-step-title">Fill Your Data</div>
                <div class="ie-step-desc">Open the template in Excel or Google Sheets and fill in your candidate data</div>
            </div>
        </div>

        {{-- Step 3 --}}
        <div class="ie-step">
            <div class="ie-step-num">3</div>
            <div class="ie-step-body">
                <div class="ie-step-title">Upload File</div>
                <div class="ie-step-desc">Upload your filled CSV file to import candidates</div>
                <form method="POST" action="{{ route('admin.import-export.import-candidates') }}"
                      enctype="multipart/form-data" style="margin-top:10px;">
                    @csrf
                    <div class="ie-file-row">
                        <label class="btn btn-primary btn-sm" style="cursor:pointer;position:relative;overflow:hidden;">
                            <i class="bi bi-cloud-upload"></i> Select File to Import
                            <input type="file" name="file" accept=".csv,.txt"
                                   style="position:absolute;inset:0;opacity:0;cursor:pointer;"
                                   onchange="this.closest('form').querySelector('.ie-filename').textContent = this.files[0]?.name || ''; this.closest('form').querySelector('.ie-submit-btn').style.display='inline-flex';">
                        </label>
                        <span class="ie-filename text-sm text-muted"></span>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm ie-submit-btn" style="margin-top:8px;display:none;">
                        <i class="bi bi-upload"></i> Start Import
                    </button>
                </form>
            </div>
        </div>

        {{-- Tips --}}
        <div class="ie-tips">
            <div class="ie-tips-title"><i class="bi bi-info-circle-fill"></i> Import Tips:</div>
            <ul>
                <li>Make sure column names match the template exactly</li>
                <li>Required fields: <strong>full_name, email_id, portal_login_password</strong></li>
                <li>Status must be: active, enrolled, interview, offer, placed, onhold, or inactive</li>
                <li>You can import up to 1,000 candidates at once</li>
                <li>Errors will be reported after import with row numbers</li>
            </ul>
        </div>
    </div>

</div>


{{-- ════════════════════════════════ RECRUITERS ════════════════════════════════ --}}
<div class="ie-section-title">Recruiters &amp; Team Managers</div>

<div class="ie-row">

    {{-- Export Recruiters --}}
    <div class="card">
        <div class="ie-section-header">
            <div class="ie-section-icon ie-icon-down"><i class="bi bi-download"></i></div>
            <div>
                <div class="ie-section-name">Export Recruiters</div>
                <div class="ie-section-desc">Download all recruiters and team managers</div>
            </div>
        </div>
        <a href="{{ route('admin.import-export.export-recruiters') }}" class="ie-export-link">
            <div class="ie-export-link-inner">
                <div class="ie-export-link-left">
                    <i class="bi bi-file-earmark-spreadsheet" style="font-size:18px;color:var(--blue);"></i>
                    <div>
                        <div class="ie-link-title">Export as CSV</div>
                        <div class="ie-link-sub">Recruiters &amp; Team Managers</div>
                    </div>
                </div>
                <i class="bi bi-chevron-right" style="color:var(--text-muted);"></i>
            </div>
        </a>
    </div>

    {{-- Import Recruiters --}}
    <div class="card">
        <div class="ie-section-header">
            <div class="ie-section-icon ie-icon-up"><i class="bi bi-upload"></i></div>
            <div>
                <div class="ie-section-name">Import Recruiters</div>
                <div class="ie-section-desc">Bulk upload recruiters and team managers from CSV file</div>
            </div>
        </div>

        <div class="d-flex gap-8" style="margin-bottom:20px;flex-wrap:wrap;">
            <a href="{{ route('admin.import-export.recruiter-template') }}" class="btn btn-outline btn-sm">
                <i class="bi bi-file-earmark-arrow-down"></i> Download Template
            </a>
            <form method="POST" action="{{ route('admin.import-export.import-recruiters') }}"
                  enctype="multipart/form-data" class="d-flex gap-8 align-center" style="flex-wrap:wrap;">
                @csrf
                <label class="btn btn-primary btn-sm" style="cursor:pointer;position:relative;overflow:hidden;">
                    <i class="bi bi-cloud-upload"></i> Choose File to Import
                    <input type="file" name="file" accept=".csv,.txt"
                           style="position:absolute;inset:0;opacity:0;cursor:pointer;"
                           onchange="this.closest('form').querySelector('.ie-filename-r').textContent = this.files[0]?.name || ''; this.closest('form').querySelector('.ie-submit-r').style.display='inline-flex';">
                </label>
                <span class="ie-filename-r text-sm text-muted"></span>
                <button type="submit" class="btn btn-primary btn-sm ie-submit-r" style="display:none;">
                    <i class="bi bi-upload"></i> Import
                </button>
            </form>
        </div>

        {{-- Tips --}}
        <div class="ie-tips">
            <div class="ie-tips-title"><i class="bi bi-info-circle-fill"></i> Import Tips:</div>
            <ul>
                <li>Required fields: <strong>name, email, password, role</strong></li>
                <li>Role must be <strong>admin</strong>, <strong>recruiter</strong>, or <strong>manager</strong></li>
                <li><strong>team_manager_email</strong> is optional — links recruiter to a manager</li>
                <li>Passwords will be hashed securely on import</li>
                <li>Duplicate emails will be skipped with an error notice</li>
            </ul>
        </div>
    </div>

</div>

@endsection
