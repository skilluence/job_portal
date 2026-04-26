<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Candidate;
use App\Models\Interview;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class StudentInfoController extends Controller
{
    private function student(): Candidate
    {
        return Candidate::with(['recruiter', 'resumes'])->findOrFail(session('student_id'));
    }

    public function index()
    {
        return view('student.info.index', ['candidate' => $this->student()]);
    }

    public function update(Request $request)
    {
        $candidate = $this->student();

        $old = $candidate->only([
            'full_name', 'first_name', 'middle_name', 'last_name',
            'email_id', 'phone_number', 'linkedin_url',
            'date_of_birth', 'gender', 'nationality',
            'domain', 'sub_domain', 'date_of_arrival_usa',
            'current_salary', 'expected_salary',
            'street_address', 'apartment_unit', 'city', 'state_province', 'zip_code', 'country',
            'visa_immigration_status', 'work_auth_status', 'visa_expiry_date',
            'open_to_relocation', 'preferred_city',
        ]);

        $data = $request->validate([
            'first_name'              => ['required', 'string', 'max:100'],
            'middle_name'             => ['nullable', 'string', 'max:100'],
            'last_name'               => ['required', 'string', 'max:100'],
            'email_id'                => ['required', 'email', 'max:255', Rule::unique('candidates', 'email_id')->ignore($candidate->id)],
            'phone_number'            => ['nullable', 'string', 'max:30'],
            'linkedin_url'            => ['nullable', 'url', 'max:255'],
            'date_of_birth'           => ['nullable', 'date'],
            'gender'                  => ['nullable', 'string', Rule::in(['male', 'female', 'other', 'prefer_not_to_say', ''])],
            'nationality'             => ['nullable', 'string', 'max:100'],
            'domain'                  => ['nullable', 'string', 'max:100'],
            'sub_domain'              => ['nullable', 'string', 'max:500'],
            'date_of_arrival_usa'     => ['nullable', 'date'],
            'current_salary'          => ['nullable', 'numeric', 'min:0'],
            'expected_salary'         => ['nullable', 'numeric', 'min:0'],
            'street_address'          => ['nullable', 'string', 'max:255'],
            'apartment_unit'          => ['nullable', 'string', 'max:50'],
            'city'                    => ['nullable', 'string', 'max:100'],
            'state_province'          => ['nullable', 'string', 'max:100'],
            'zip_code'                => ['nullable', 'string', 'max:20'],
            'country'                 => ['nullable', 'string', 'max:100'],
            'visa_immigration_status' => ['nullable', 'string', Rule::in(['us_citizen', 'green_card', 'h1b', 'h4_ead', 'opt_f1', 'stem_opt', 'cpt', 'l1', 'tn_visa', 'other', ''])],
            'work_auth_status'        => ['nullable', 'string', Rule::in(['applied_pending', 'not_applied', 'already_obtained', ''])],
            'visa_expiry_date'        => ['nullable', 'date'],
            'open_to_relocation'      => ['nullable', 'boolean'],
            'preferred_city'          => ['nullable', 'string', 'max:500'],
        ]);

        // Build full_name from all three name parts
        $data['full_name'] = trim(implode(' ', array_filter([
            $data['first_name'],
            $data['middle_name'] ?? '',
            $data['last_name'],
        ])));

        $data['email_id'] = strtolower($data['email_id']);
        $candidate->update($data);

        AuditLog::log('updated', 'student_profile', "Student profile updated: {$candidate->full_name}", $old, $data);

        return back()->with('success', 'Your details have been updated successfully.');
    }

    public function updateDocuments(Request $request)
    {
        $candidate = $this->student();

        $request->validate([
            'cv_file'               => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:5120'],
            'candidate_details_file'=> ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:5120'],
        ]);

        if (!$request->hasFile('cv_file') && !$request->hasFile('candidate_details_file')) {
            return back()->withErrors(['documents' => 'Please choose at least one document to upload.']);
        }

        $changes   = [];
        $oldValues = [];

        if ($request->hasFile('cv_file')) {
            $oldValues['cv_file_path'] = $candidate->cv_file_path;
            if ($candidate->cv_file_path) {
                Storage::disk('local')->delete($candidate->cv_file_path);
            }
            $changes['cv_file_path'] = $request->file('cv_file')->store("candidates/{$candidate->id}/cv", 'local');
        }

        if ($request->hasFile('candidate_details_file')) {
            $oldValues['candidate_details_file_path'] = $candidate->candidate_details_file_path;
            if ($candidate->candidate_details_file_path) {
                Storage::disk('local')->delete($candidate->candidate_details_file_path);
            }
            $changes['candidate_details_file_path'] = $request->file('candidate_details_file')->store("candidates/{$candidate->id}/details", 'local');
        }

        $candidate->update($changes);

        AuditLog::log('uploaded', 'student_documents', "Student uploaded documents: {$candidate->full_name}", $oldValues, $changes);

        return back()->with('success', 'Document(s) uploaded successfully.');
    }

    public function updatePassword(Request $request)
    {
        $candidate = $this->student();

        $request->validate([
            'current_password' => ['required'],
            'password'         => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (!Hash::check($request->current_password, $candidate->login_password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $candidate->update([
            'login_password'       => Hash::make($request->password),
            'login_password_plain' => $request->password,
        ]);

        AuditLog::log('updated', 'student_auth', "Student changed portal password: {$candidate->full_name}");

        return back()->with('success', 'Password updated successfully.');
    }

    public function updateInterviewSchedule(Request $request, Interview $interview)
    {
        $candidate = Candidate::findOrFail(session('student_id'));

        if ($interview->candidate_id !== $candidate->id) {
            return $request->wantsJson()
                ? response()->json(['error' => 'Not authorized'], 403)
                : abort(403, 'Not authorized.');
        }

        $data = $request->validate([
            'scheduled_date'     => ['nullable', 'date'],
            'scheduled_time'     => ['nullable', 'date_format:H:i'],
            'scheduled_timezone' => ['nullable', 'string', 'max:20'],
        ]);

        // Treat blank strings as null
        foreach ($data as $key => $val) {
            if ($val === '') $data[$key] = null;
        }

        $existingSchedule = $this->interviewUtcTimestamp($interview);
        if ($existingSchedule && $existingSchedule->lte(now('UTC'))) {
            return $request->wantsJson()
                ? response()->json(['success' => false, 'message' => 'Schedule cannot be changed after the scheduled time has passed.'], 422)
                : back()->withErrors(['scheduled_date' => 'Schedule cannot be changed after the scheduled time has passed.']);
        }

        $hasAnySchedulePart = !empty($data['scheduled_date']) || !empty($data['scheduled_time']) || !empty($data['scheduled_timezone']);
        if (!$hasAnySchedulePart) {
            return $request->wantsJson()
                ? response()->json(['success' => false, 'message' => 'Date, time, and timezone are required for interview schedule.'], 422)
                : back()->withErrors(['scheduled_date' => 'Date, time, and timezone are required for interview schedule.']);
        }

        if ($hasAnySchedulePart && (empty($data['scheduled_date']) || empty($data['scheduled_time']) || empty($data['scheduled_timezone']))) {
            return $request->wantsJson()
                ? response()->json(['success' => false, 'message' => 'Date, time, and timezone are required for interview schedule.'], 422)
                : back()->withErrors(['scheduled_date' => 'Date, time, and timezone are required for interview schedule.']);
        }

        $newSchedule = $this->interviewUtcTimestampFromValues(
            $data['scheduled_date'] ?? null,
            $data['scheduled_time'] ?? null,
            $data['scheduled_timezone'] ?? null
        );
        if ($newSchedule && $newSchedule->lte(now('UTC'))) {
            return $request->wantsJson()
                ? response()->json(['success' => false, 'message' => 'Please select a future schedule time.'], 422)
                : back()->withErrors(['scheduled_date' => 'Please select a future schedule time.']);
        }

        $old = $interview->only(['scheduled_date', 'scheduled_time', 'scheduled_timezone']);
        $interview->update($data);

        AuditLog::log(
            'updated',
            'interviews',
            "Student set interview schedule: {$candidate->full_name} - {$interview->company_name}",
            $old,
            $data
        );

        if ($request->wantsJson()) {
            $interview->refresh();
            [$displayDate, $displayTime, $displayTimezone] = $this->candidateDisplaySchedule($candidate, $interview);

            return response()->json(array_merge([
                'success'            => true,
                'scheduled_date'     => $displayDate,
                'scheduled_date_raw' => $interview->scheduled_date?->format('Y-m-d'),
                'scheduled_time'     => $interview->scheduled_time,
                'scheduled_time_fmt' => $displayTime,
                'scheduled_timezone' => $displayTimezone,
                'source_timezone'    => $interview->scheduled_timezone,
            ], $this->interviewUiState($interview)));
        }

        return back()->with('success', 'Interview schedule updated.');
    }

    public function updateInterviewStatus(Request $request, Interview $interview)
    {
        $candidate = Candidate::findOrFail(session('student_id'));

        if ($interview->candidate_id !== $candidate->id) {
            return $request->wantsJson()
                ? response()->json(['error' => 'Not authorized'], 403)
                : abort(403, 'Not authorized.');
        }

        $data = $request->validate([
            'interview_status' => ['required', Rule::in(['valid', 'invalid'])],
        ]);

        $scheduledAt = $this->interviewUtcTimestamp($interview);
        if (!$scheduledAt) {
            return $request->wantsJson()
                ? response()->json(['success' => false, 'message' => 'Set the interview schedule before updating status.'], 422)
                : back()->withErrors(['interview_status' => 'Set the interview schedule before updating status.']);
        }

        if ($scheduledAt->gt(now('UTC'))) {
            return $request->wantsJson()
                ? response()->json(['success' => false, 'message' => 'Status can be updated only after the scheduled time has passed.'], 422)
                : back()->withErrors(['interview_status' => 'Status can be updated only after the scheduled time has passed.']);
        }

        if (!empty($interview->interview_status)) {
            return $request->wantsJson()
                ? response()->json(['success' => false, 'message' => 'Interview status has already been submitted and cannot be changed.'], 422)
                : back()->withErrors(['interview_status' => 'Interview status has already been submitted and cannot be changed.']);
        }

        $old = $interview->interview_status;
        $interview->update(['interview_status' => $data['interview_status']]);

        AuditLog::log(
            'updated',
            'interviews',
            "Student updated interview status: {$candidate->full_name}",
            ['interview_status' => $old],
            ['interview_status' => $data['interview_status'], 'interview_id' => $interview->id]
        );

        if ($request->wantsJson()) {
            $interview->refresh();

            return response()->json(array_merge([
                'success' => true,
                'interview_status' => $data['interview_status'],
            ], $this->interviewUiState($interview)));
        }

        return back()->with('success', 'Interview status updated.');
    }

    private function candidateDisplaySchedule(Candidate $candidate, Interview $interview): array
    {
        if (!$interview->scheduled_date || !$interview->scheduled_time) {
            return [null, null, null];
        }

        try {
            $sourceTime = substr((string) $interview->scheduled_time, 0, 5);

            return [
                $interview->scheduled_date->format('M d, Y'),
                $sourceTime ? Carbon::createFromFormat('H:i', $sourceTime)->format('h:i A') : null,
                $interview->scheduled_timezone,
            ];
        } catch (\Throwable) {
            return [
                $interview->scheduled_date?->format('M d, Y'),
                $interview->scheduled_time ? \Carbon\Carbon::parse($interview->scheduled_time)->format('h:i A') : null,
                $interview->scheduled_timezone,
            ];
        }
    }

    private function interviewUtcTimestamp(Interview $interview): ?Carbon
    {
        return $this->interviewUtcTimestampFromValues(
            $interview->scheduled_date?->format('Y-m-d'),
            $interview->scheduled_time,
            $interview->scheduled_timezone
        );
    }

    private function interviewUiState(Interview $interview): array
    {
        $scheduledAt = $this->interviewUtcTimestamp($interview);
        $hasStatus = !empty($interview->interview_status);
        $hasPassed = $scheduledAt ? $scheduledAt->lte(now('UTC')) : false;

        return [
            'scheduled_at_ms' => $scheduledAt ? $scheduledAt->getTimestamp() * 1000 : null,
            'schedule_has_passed' => $hasPassed,
            'can_update_schedule' => !$hasPassed,
            'can_update_status' => $hasPassed && !$hasStatus,
            'status_lock_reason' => $hasStatus
                ? 'Status already submitted.'
                : ($scheduledAt ? ($hasPassed ? '' : 'Status unlocks after the scheduled time.') : 'Set the schedule before updating status.'),
        ];
    }

    private function interviewUtcTimestampFromValues($date, $time, ?string $timezone): ?Carbon
    {
        if (!$date || !$time) {
            return null;
        }

        $datePart = $date instanceof Carbon ? $date->format('Y-m-d') : (string) $date;
        $timePart = substr((string) $time, 0, 5);
        $sourceTimezone = $this->timezoneAbbreviationToIana($timezone);

        try {
            return Carbon::createFromFormat('Y-m-d H:i', $datePart . ' ' . $timePart, $sourceTimezone)->utc();
        } catch (\Throwable) {
            return null;
        }
    }

    private function resolveCandidateTimezone(Candidate $candidate): string
    {
        $state = strtoupper((string) ($candidate->state_province ?? ''));
        $stateNames = [
            'ALABAMA' => 'AL', 'ALASKA' => 'AK', 'ARIZONA' => 'AZ', 'ARKANSAS' => 'AR',
            'CALIFORNIA' => 'CA', 'COLORADO' => 'CO', 'CONNECTICUT' => 'CT', 'DELAWARE' => 'DE',
            'DISTRICT OF COLUMBIA' => 'DC', 'FLORIDA' => 'FL', 'GEORGIA' => 'GA', 'HAWAII' => 'HI',
            'IDAHO' => 'ID', 'ILLINOIS' => 'IL', 'INDIANA' => 'IN', 'IOWA' => 'IA',
            'KANSAS' => 'KS', 'KENTUCKY' => 'KY', 'LOUISIANA' => 'LA', 'MAINE' => 'ME',
            'MARYLAND' => 'MD', 'MASSACHUSETTS' => 'MA', 'MICHIGAN' => 'MI', 'MINNESOTA' => 'MN',
            'MISSISSIPPI' => 'MS', 'MISSOURI' => 'MO', 'MONTANA' => 'MT', 'NEBRASKA' => 'NE',
            'NEVADA' => 'NV', 'NEW HAMPSHIRE' => 'NH', 'NEW JERSEY' => 'NJ', 'NEW MEXICO' => 'NM',
            'NEW YORK' => 'NY', 'NORTH CAROLINA' => 'NC', 'NORTH DAKOTA' => 'ND', 'OHIO' => 'OH',
            'OKLAHOMA' => 'OK', 'OREGON' => 'OR', 'PENNSYLVANIA' => 'PA', 'RHODE ISLAND' => 'RI',
            'SOUTH CAROLINA' => 'SC', 'SOUTH DAKOTA' => 'SD', 'TENNESSEE' => 'TN', 'TEXAS' => 'TX',
            'UTAH' => 'UT', 'VERMONT' => 'VT', 'VIRGINIA' => 'VA', 'WASHINGTON' => 'WA',
            'WEST VIRGINIA' => 'WV', 'WISCONSIN' => 'WI', 'WYOMING' => 'WY',
        ];
        $state = $stateNames[$state] ?? $state;

        $stateToTimezone = [
            'CT' => 'America/New_York', 'DE' => 'America/New_York', 'DC' => 'America/New_York',
            'FL' => 'America/New_York', 'GA' => 'America/New_York', 'ME' => 'America/New_York',
            'MD' => 'America/New_York', 'MA' => 'America/New_York', 'NH' => 'America/New_York',
            'NJ' => 'America/New_York', 'NY' => 'America/New_York', 'NC' => 'America/New_York',
            'OH' => 'America/New_York', 'PA' => 'America/New_York', 'RI' => 'America/New_York',
            'SC' => 'America/New_York', 'VT' => 'America/New_York', 'VA' => 'America/New_York',
            'WV' => 'America/New_York',
            'AL' => 'America/Chicago', 'AR' => 'America/Chicago', 'IL' => 'America/Chicago',
            'IA' => 'America/Chicago', 'KS' => 'America/Chicago', 'KY' => 'America/Chicago',
            'LA' => 'America/Chicago', 'MI' => 'America/Chicago', 'MN' => 'America/Chicago',
            'MS' => 'America/Chicago', 'MO' => 'America/Chicago', 'NE' => 'America/Chicago',
            'ND' => 'America/Chicago', 'OK' => 'America/Chicago', 'TN' => 'America/Chicago',
            'TX' => 'America/Chicago', 'WI' => 'America/Chicago', 'SD' => 'America/Chicago',
            'AZ' => 'America/Phoenix', 'CO' => 'America/Denver', 'ID' => 'America/Denver',
            'NM' => 'America/Denver', 'MT' => 'America/Denver', 'UT' => 'America/Denver',
            'WY' => 'America/Denver',
            'CA' => 'America/Los_Angeles', 'NV' => 'America/Los_Angeles',
            'OR' => 'America/Los_Angeles', 'WA' => 'America/Los_Angeles',
            'AK' => 'America/Anchorage',
            'HI' => 'Pacific/Honolulu',
        ];

        if ($state && isset($stateToTimezone[$state])) {
            return $stateToTimezone[$state];
        }

        return config('app.timezone', 'UTC');
    }

    private function timezoneAbbreviationToIana(?string $abbr): string
    {
        return match (strtoupper((string) $abbr)) {
            'EST'        => '-05:00',
            'EDT'        => '-04:00',
            'CST'        => '-06:00',
            'CDT'        => '-05:00',
            'MST'        => '-07:00',
            'MDT'        => '-06:00',
            'PST'        => '-08:00',
            'PDT'        => '-07:00',
            'AKST'       => '-09:00',
            'HST'        => '-10:00',
            default      => config('app.timezone', 'UTC'),
        };
    }

    public function downloadFile(string $file)
    {
        $candidate = $this->student();
        $path = $file === 'cv' ? $candidate->cv_file_path : $candidate->candidate_details_file_path;

        if (!$path || !Storage::disk('local')->exists($path)) {
            return back()->withErrors(['file' => 'Requested file was not found.']);
        }

        AuditLog::log('downloaded', 'student_documents', "Student viewed {$file} file: {$candidate->full_name}", [], ['candidate_id' => $candidate->id, 'file_type' => $file]);

        $mimeType = Storage::disk('local')->mimeType($path) ?: 'application/octet-stream';
        $filename = basename($path);

        return response(Storage::disk('local')->get($path), 200, [
            'Content-Type'        => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
            'Cache-Control'       => 'private, no-store',
        ]);
    }
}
