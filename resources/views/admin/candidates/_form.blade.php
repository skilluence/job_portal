{{-- Candidate add/edit shared form partial --}}
{{-- Variables: $mode (add|edit), $prefix, $usStates, $visaOptions, $workAuthOptions, $recruiters, $statusOptions, $isAdmin --}}
@php $isEdit = $mode === 'edit'; @endphp

<div class="modal-tabs" id="{{ $prefix }}_tabs">
    <button type="button" class="modal-tab-btn active" data-tab="{{ $prefix }}_tab_personal" onclick="switchModalTab('{{ $prefix }}_tabs','{{ $prefix }}_tab_personal')">
        <i class="bi bi-person-fill"></i> Personal
    </button>
    <button type="button" class="modal-tab-btn" data-tab="{{ $prefix }}_tab_address" onclick="switchModalTab('{{ $prefix }}_tabs','{{ $prefix }}_tab_address')">
        <i class="bi bi-geo-alt-fill"></i> Address & Visa
    </button>
    <button type="button" class="modal-tab-btn" data-tab="{{ $prefix }}_tab_marketing" onclick="switchModalTab('{{ $prefix }}_tabs','{{ $prefix }}_tab_marketing')">
        <i class="bi bi-megaphone-fill"></i> Marketing
    </button>
    <button type="button" class="modal-tab-btn" data-tab="{{ $prefix }}_tab_education" onclick="switchModalTab('{{ $prefix }}_tabs','{{ $prefix }}_tab_education')">
        <i class="bi bi-mortarboard-fill"></i> Education
    </button>
    <button type="button" class="modal-tab-btn" data-tab="{{ $prefix }}_tab_professional" onclick="switchModalTab('{{ $prefix }}_tabs','{{ $prefix }}_tab_professional')">
        <i class="bi bi-briefcase-fill"></i> Professional
    </button>
    <button type="button" class="modal-tab-btn" data-tab="{{ $prefix }}_tab_portal" onclick="switchModalTab('{{ $prefix }}_tabs','{{ $prefix }}_tab_portal')">
        <i class="bi bi-shield-lock-fill"></i> Portal
    </button>
</div>

<div class="modal-body" style="padding-top:0;">

{{-- ── TAB 1: Personal Information ──────────────────────────── --}}
<div class="modal-tab-panel active" id="{{ $prefix }}_tab_personal">
    <div class="form-section-title">Personal Information</div>
    <div class="form-grid form-grid-3">
        <div class="form-group">
            <label class="form-label">First Name <span style="color:var(--red-text)">*</span></label>
            <input type="text" name="first_name" id="{{ $prefix }}_first_name" class="form-control" required
                value="{{ old('first_name') }}" placeholder="First name">
        </div>
        <div class="form-group">
            <label class="form-label">Middle Name</label>
            <input type="text" name="middle_name" id="{{ $prefix }}_middle_name" class="form-control"
                value="{{ old('middle_name') }}" placeholder="Optional">
        </div>
        <div class="form-group">
            <label class="form-label">Last Name <span style="color:var(--red-text)">*</span></label>
            <input type="text" name="last_name" id="{{ $prefix }}_last_name" class="form-control" required
                value="{{ old('last_name') }}" placeholder="Last name">
        </div>
        <div class="form-group">
            <label class="form-label">Date of Birth</label>
            <input type="date" name="date_of_birth" id="{{ $prefix }}_date_of_birth" class="form-control" value="{{ old('date_of_birth') }}">
        </div>
        <div class="form-group">
            <label class="form-label">Gender</label>
            <select name="gender" id="{{ $prefix }}_gender" class="form-control">
                <option value="">Select Gender</option>
                <option value="male">Male</option>
                <option value="female">Female</option>
                <option value="other">Other</option>
                <option value="prefer_not_to_say">Prefer not to say</option>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Nationality</label>
            <input type="text" name="nationality" id="{{ $prefix }}_nationality" class="form-control"
                value="{{ old('nationality') }}" placeholder="e.g. Indian">
        </div>
        <div class="form-group">
            <label class="form-label">Personal Email <span style="color:var(--red-text)">*</span></label>
            <input type="email" name="email_id" id="{{ $prefix }}_email_id" class="form-control" required
                value="{{ old('email_id') }}" placeholder="candidate@email.com">
        </div>
        <div class="form-group">
            <label class="form-label">Phone Number</label>
            <input type="text" name="phone_number" id="{{ $prefix }}_phone_number" class="form-control"
                value="{{ old('phone_number') }}" placeholder="+1 (555) 000-0000">
        </div>
        <div class="form-group">
            <label class="form-label">Domain / Technology</label>
            <input type="text" name="domain" id="{{ $prefix }}_domain" class="form-control"
                value="{{ old('domain') }}" placeholder="e.g. Java, Data Science">
        </div>
        <div class="form-group">
            <label class="form-label">Sub-Domain</label>
            <input type="text" name="sub_domain" id="{{ $prefix }}_sub_domain" class="form-control"
                value="{{ old('sub_domain') }}" placeholder="e.g. Spring Boot, ML">
        </div>
        <div class="form-group">
            <label class="form-label">SSN <span class="text-muted text-sm">(encrypted)</span></label>
            <input type="text" name="ssn" id="{{ $prefix }}_ssn" class="form-control"
                value="" placeholder="{{ $isEdit ? 'Leave blank to keep current' : 'e.g. XXX-XX-XXXX' }}" autocomplete="off">
        </div>
        <div class="form-group">
            <label class="form-label">Date of Arrival in USA</label>
            <input type="date" name="date_of_arrival_usa" id="{{ $prefix }}_date_of_arrival_usa" class="form-control" value="{{ old('date_of_arrival_usa') }}">
        </div>
        <div class="form-group">
            <label class="form-label">Current Salary ($)</label>
            <input type="number" name="current_salary" id="{{ $prefix }}_current_salary" class="form-control"
                value="{{ old('current_salary') }}" min="0" step="0.01" placeholder="0.00">
        </div>
        <div class="form-group">
            <label class="form-label">Expected Salary ($)</label>
            <input type="number" name="expected_salary" id="{{ $prefix }}_expected_salary" class="form-control"
                value="{{ old('expected_salary') }}" min="0" step="0.01" placeholder="0.00">
        </div>
    </div>
</div>

{{-- ── TAB 2: Address & Visa ─────────────────────────────────── --}}
<div class="modal-tab-panel" id="{{ $prefix }}_tab_address">
    <div class="form-section-title">Address</div>
    <div class="form-grid form-grid-3">
        <div class="form-group" style="grid-column:span 2;">
            <label class="form-label">Street Address</label>
            <input type="text" name="street_address" id="{{ $prefix }}_street_address" class="form-control"
                value="{{ old('street_address') }}" placeholder="123 Main St">
        </div>
        <div class="form-group">
            <label class="form-label">Apt / Unit</label>
            <input type="text" name="apartment_unit" id="{{ $prefix }}_apartment_unit" class="form-control"
                value="{{ old('apartment_unit') }}" placeholder="Apt 4B">
        </div>
        <div class="form-group">
            <label class="form-label">City</label>
            <input type="text" name="city" id="{{ $prefix }}_city" class="form-control"
                value="{{ old('city') }}" placeholder="City">
        </div>
        <div class="form-group">
            <label class="form-label">State</label>
            <select name="state_province" id="{{ $prefix }}_state_province" class="form-control">
                <option value="">Select State</option>
                @foreach ($usStates as $code => $name)
                    <option value="{{ $code }}">{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">ZIP Code</label>
            <input type="text" name="zip_code" id="{{ $prefix }}_zip_code" class="form-control"
                value="{{ old('zip_code') }}" placeholder="10001" maxlength="10">
        </div>
        <div class="form-group">
            <label class="form-label">Country</label>
            <input type="text" name="country" id="{{ $prefix }}_country" class="form-control"
                value="{{ old('country', 'United States') }}" readonly>
        </div>
    </div>

    <div class="form-section-title" style="margin-top:16px;">Visa & Work Authorization</div>
    <div class="form-grid form-grid-3">
        <div class="form-group">
            <label class="form-label">Visa / Immigration Status</label>
            <select name="visa_immigration_status" id="{{ $prefix }}_visa_immigration_status" class="form-control">
                <option value="">Select Status</option>
                @foreach ($visaOptions as $val => $label)
                    <option value="{{ $val }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Work Auth Status</label>
            <select name="work_auth_status" id="{{ $prefix }}_work_auth_status" class="form-control">
                <option value="">Select Status</option>
                @foreach ($workAuthOptions as $val => $label)
                    <option value="{{ $val }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Visa Expiry Date</label>
            <input type="date" name="visa_expiry_date" id="{{ $prefix }}_visa_expiry_date" class="form-control" value="{{ old('visa_expiry_date') }}">
        </div>
        <div class="form-group">
            <label class="form-label">Open to Relocation</label>
            <div class="d-flex gap-16" style="margin-top:8px;">
                <label class="d-flex align-center gap-6" style="cursor:pointer;">
                    <input type="radio" name="open_to_relocation" id="{{ $prefix }}_relocation_yes" value="1"> Yes
                </label>
                <label class="d-flex align-center gap-6" style="cursor:pointer;">
                    <input type="radio" name="open_to_relocation" id="{{ $prefix }}_relocation_no" value="0"> No
                </label>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Preferred City <span class="text-muted text-sm">(if not relocating)</span></label>
            <input type="text" name="preferred_city" id="{{ $prefix }}_preferred_city" class="form-control"
                value="{{ old('preferred_city') }}" placeholder="e.g. New York">
        </div>
    </div>
</div>

{{-- ── TAB 3: Marketing Contact ──────────────────────────────── --}}
<div class="modal-tab-panel" id="{{ $prefix }}_tab_marketing">
    <div class="form-section-title">Marketing Contact Details</div>
    <div class="form-grid">
        <div class="form-group">
            <label class="form-label">Marketing Phone</label>
            <input type="text" name="marketing_phone" id="{{ $prefix }}_marketing_phone" class="form-control"
                value="{{ old('marketing_phone') }}" placeholder="+1 (555) 000-0000">
        </div>
        <div class="form-group">
            <label class="form-label">Marketing Email</label>
            <input type="email" name="marketing_email" id="{{ $prefix }}_marketing_email" class="form-control"
                value="{{ old('marketing_email') }}" placeholder="marketing@email.com">
        </div>
        <div class="form-group">
            <label class="form-label">Email Password <span class="text-muted text-sm">(encrypted)</span></label>
            <div class="input-with-icon">
                <input type="password" name="marketing_email_password" id="{{ $prefix }}_marketing_email_password" class="form-control"
                    placeholder="{{ $isEdit ? 'Leave blank to keep current' : 'Email password' }}" autocomplete="new-password">
                <button type="button" class="input-eye-btn password-toggle"><i class="bi bi-eye"></i></button>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">LinkedIn ID (Marketing)</label>
            <input type="text" name="marketing_linkedin_id" id="{{ $prefix }}_marketing_linkedin_id" class="form-control"
                value="{{ old('marketing_linkedin_id') }}" placeholder="LinkedIn username or URL">
        </div>
        <div class="form-group">
            <label class="form-label">LinkedIn Password <span class="text-muted text-sm">(encrypted)</span></label>
            <div class="input-with-icon">
                <input type="password" name="marketing_linkedin_password" id="{{ $prefix }}_marketing_linkedin_password" class="form-control"
                    placeholder="{{ $isEdit ? 'Leave blank to keep current' : 'LinkedIn password' }}" autocomplete="new-password">
                <button type="button" class="input-eye-btn password-toggle"><i class="bi bi-eye"></i></button>
            </div>
        </div>
    </div>
</div>

{{-- ── TAB 4: Education ──────────────────────────────────────── --}}
<div class="modal-tab-panel" id="{{ $prefix }}_tab_education">
    <div class="form-section-title">Master's Education</div>
    <div class="form-grid form-grid-3">
        <div class="form-group" style="grid-column:span 2;">
            <label class="form-label">University Name</label>
            <input type="text" name="masters_university" id="{{ $prefix }}_masters_university" class="form-control"
                value="{{ old('masters_university') }}" placeholder="e.g. University of Texas at Dallas">
        </div>
        <div class="form-group">
            <label class="form-label">Program / Course</label>
            <input type="text" name="masters_program" id="{{ $prefix }}_masters_program" class="form-control"
                value="{{ old('masters_program') }}" placeholder="e.g. MS Computer Science">
        </div>
        <div class="form-group">
            <label class="form-label">Start Date</label>
            <input type="date" name="masters_start" id="{{ $prefix }}_masters_start" class="form-control" value="{{ old('masters_start') }}">
        </div>
        <div class="form-group">
            <label class="form-label">End Date</label>
            <input type="date" name="masters_end" id="{{ $prefix }}_masters_end" class="form-control" value="{{ old('masters_end') }}">
        </div>
        <div class="form-group">
            <label class="form-label">Country</label>
            <input type="text" name="masters_country" id="{{ $prefix }}_masters_country" class="form-control"
                value="{{ old('masters_country') }}" placeholder="e.g. United States">
        </div>
    </div>

    <div class="form-section-title" style="margin-top:16px;">Bachelor's Education</div>
    <div class="form-grid form-grid-3">
        <div class="form-group" style="grid-column:span 2;">
            <label class="form-label">University Name</label>
            <input type="text" name="bachelors_university" id="{{ $prefix }}_bachelors_university" class="form-control"
                value="{{ old('bachelors_university') }}" placeholder="e.g. Delhi University">
        </div>
        <div class="form-group">
            <label class="form-label">Program / Course</label>
            <input type="text" name="bachelors_program" id="{{ $prefix }}_bachelors_program" class="form-control"
                value="{{ old('bachelors_program') }}" placeholder="e.g. B.Tech Computer Science">
        </div>
        <div class="form-group">
            <label class="form-label">Start Date</label>
            <input type="date" name="bachelors_start" id="{{ $prefix }}_bachelors_start" class="form-control" value="{{ old('bachelors_start') }}">
        </div>
        <div class="form-group">
            <label class="form-label">End Date</label>
            <input type="date" name="bachelors_end" id="{{ $prefix }}_bachelors_end" class="form-control" value="{{ old('bachelors_end') }}">
        </div>
        <div class="form-group">
            <label class="form-label">Country</label>
            <input type="text" name="bachelors_country" id="{{ $prefix }}_bachelors_country" class="form-control"
                value="{{ old('bachelors_country') }}" placeholder="e.g. India">
        </div>
    </div>
</div>

{{-- ── TAB 5: Professional Profile & Resume ─────────────────── --}}
<div class="modal-tab-panel" id="{{ $prefix }}_tab_professional">
    <div class="form-section-title">Professional Profile & Resume</div>
    <div class="form-grid">
        <div class="form-group">
            <label class="form-label">GitHub URL</label>
            <input type="url" name="github_url" id="{{ $prefix }}_github_url" class="form-control"
                value="{{ old('github_url') }}" placeholder="https://github.com/username">
        </div>
        <div class="form-group">
            <label class="form-label">LinkedIn URL</label>
            <input type="url" name="linkedin_url" id="{{ $prefix }}_linkedin_url" class="form-control"
                value="{{ old('linkedin_url') }}" placeholder="https://linkedin.com/in/username">
        </div>
    </div>

    <div class="form-grid">
        <div class="form-group">
            <label class="form-label">Upload Resume (CV)</label>
            @if ($isEdit)
                <div class="doc-replace-hint" id="{{ $prefix }}_cv_hint"></div>
            @endif
            <input type="file" name="cv_file" id="{{ $prefix }}_cv_file" class="form-control" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
        </div>
        <div class="form-group">
            <label class="form-label">Candidate Details File</label>
            @if ($isEdit)
                <div class="doc-replace-hint" id="{{ $prefix }}_details_hint"></div>
            @endif
            <input type="file" name="candidate_details_file" id="{{ $prefix }}_candidate_details_file" class="form-control" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
        </div>
        <div class="form-group">
            <label class="form-label">Speedy Apply JSON <span class="text-muted text-sm">(optional)</span></label>
            @if ($isEdit)
                <div class="doc-replace-hint" id="{{ $prefix }}_speedy_hint"></div>
            @endif
            <input type="file" name="speedy_apply_json" id="{{ $prefix }}_speedy_apply_json" class="form-control" accept=".json,.txt">
        </div>
    </div>

    <div class="form-group" style="margin-top:4px;">
        <label class="form-label">Notes for Recruiter</label>
        <textarea name="recruiter_notes" id="{{ $prefix }}_recruiter_notes" class="form-control" rows="4"
            placeholder="Internal notes, special instructions...">{{ old('recruiter_notes') }}</textarea>
    </div>
</div>

{{-- ── TAB 6: Portal Access ──────────────────────────────────── --}}
<div class="modal-tab-panel" id="{{ $prefix }}_tab_portal">
    <div class="form-section-title">Portal Access &amp; Status</div>

    <div class="form-grid">
        <div class="form-group">
            <label class="form-label">Status <span style="color:var(--red-text)">*</span></label>
            <select name="status" id="{{ $prefix }}_status" class="form-control" required>
                @foreach ($statusOptions as $s)
                    <option value="{{ $s }}" @selected(old('status', 'active') === $s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">No. of Applications <span style="color:var(--red-text)">*</span></label>
            <input type="number" name="no_of_applications" id="{{ $prefix }}_no_of_applications" class="form-control"
                value="{{ old('no_of_applications', 0) }}" min="0" required>
        </div>
    </div>

    @if ($isAdmin)
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Search Recruiter</label>
                <input type="text" id="{{ $prefix }}_recruiter_search" class="form-control"
                    placeholder="Type recruiter name..."
                    oninput="filterSelectOptions('{{ $prefix }}_recruiter_search','{{ $prefix }}_recruiter_id')">
            </div>
            <div class="form-group">
                <label class="form-label">Assign to Recruiter <span style="color:var(--red-text)">*</span></label>
                <select name="recruiter_id" id="{{ $prefix }}_recruiter_id" class="form-control" required>
                    <option value="">Select recruiter</option>
                    @foreach ($recruiters as $rec)
                        <option value="{{ $rec->id }}" @selected(old('recruiter_id') == $rec->id)>{{ $rec->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    @else
        <div class="alert mb-16" style="background:var(--blue-light);color:var(--blue-text);border:1px solid rgba(37,99,235,0.2);">
            <i class="bi bi-info-circle-fill"></i>
            <span>Candidates created from your login are automatically assigned to you.</span>
        </div>
    @endif

    <div style="padding-top:12px;border-top:1px solid var(--border-color);margin-top:8px;">
        <p class="text-sm mb-12" style="font-weight:600;color:var(--text-secondary);display:flex;align-items:center;gap:6px;">
            <i class="bi bi-shield-lock-fill" style="color:var(--blue);"></i> Student Portal Login Password
        </p>

        @if ($isEdit)
            {{-- Reveal existing password --}}
            <div class="form-group">
                <label class="form-label">Current Login Password</label>
                <div class="input-with-icon password-verify-wrap" id="candidate_password_verify_wrap">
                    <input type="password" id="edit_current_login_password" class="form-control" readonly
                        value="********" data-unlocked="0">
                    <button type="button" class="input-eye-btn" id="edit_reveal_password_btn"
                        onclick="toggleCandidateCurrentPassword()"
                        title="View current password after verification">
                        <i class="bi bi-eye"></i>
                    </button>
                    <div class="password-verify-tooltip" id="candidate_password_verify_tooltip">
                        <div class="password-verify-title">Verify your account password</div>
                        <input type="password" id="candidate_verify_login_password" class="form-control"
                            placeholder="Enter your login password"
                            onkeydown="handleCandidateRevealPasswordKeydown(event)">
                        <div class="password-verify-actions">
                            <button type="button" class="btn btn-outline btn-sm" onclick="cancelCandidatePasswordReveal()">Cancel</button>
                            <button type="button" class="btn btn-primary btn-sm" id="candidate_verify_submit" onclick="confirmCandidatePasswordReveal()">Verify</button>
                        </div>
                        <div class="password-verify-error" id="candidate_verify_error"></div>
                    </div>
                </div>
                <div class="portal-pass-hint">
                    <i class="bi bi-info-circle"></i>
                    Enter your own login password to reveal the candidate's current portal password.
                </div>
            </div>
            <div class="form-group mb-0">
                <label class="form-label">New Login Password <span class="text-muted" style="font-weight:400;">(leave blank to keep current)</span></label>
                <div class="input-with-icon">
                    <input type="password" name="login_password" id="edit_login_password" class="form-control"
                        placeholder="Enter new password only if you want to change it">
                    <button type="button" class="input-eye-btn password-toggle"><i class="bi bi-eye"></i></button>
                </div>
            </div>
        @else
            <div class="form-group mb-0">
                <label class="form-label">Portal Login Password <span style="color:var(--red-text)">*</span></label>
                <div class="input-with-icon">
                    <input type="password" name="login_password" class="form-control"
                        placeholder="Set student portal password (min 8 chars)" required minlength="8">
                    <button type="button" class="input-eye-btn password-toggle"><i class="bi bi-eye"></i></button>
                </div>
            </div>
        @endif
    </div>
</div>

</div>{{-- end modal-body --}}

<div class="modal-footer">
    <button type="button" class="btn btn-outline" onclick="closeModal('{{ $prefix === 'add' ? 'addCandidateModal' : 'editCandidateModal' }}')">Cancel</button>
    <button type="submit" class="btn btn-primary">
        <i class="bi bi-check-lg"></i> {{ $isEdit ? 'Save Changes' : 'Add Candidate' }}
    </button>
</div>
