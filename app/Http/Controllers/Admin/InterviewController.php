<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Candidate;
use App\Models\Interview;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InterviewController extends Controller
{
    private const TYPES = ['phone_call', 'virtual', 'on_site'];
    private const TIMEZONES = ['EST', 'CST', 'MST', 'PST', 'AKST', 'HST', 'EDT', 'CDT', 'MDT', 'PDT'];

    public function store(Request $request, Candidate $candidate)
    {
        $user      = $request->user();
        $isAdmin   = $user->isAdmin();
        $isManager = $user->isManager();

        // Access control
        if ($user->isRecruiter() && $candidate->recruiter_id !== $user->id) {
            abort(403, 'Not authorized.');
        }
        if ($isManager
            && $candidate->team_manager_id !== $user->id
            && !in_array($candidate->recruiter_id, $user->teamMemberIds(), true)) {
            abort(403, 'Not authorized.');
        }

        $data = $request->validate([
            'role'               => ['required', 'string', 'max:200'],
            'company_name'       => ['required', 'string', 'max:200'],
            'company_domain'     => ['required', 'string', 'max:500'],
            'mail_date'          => ['nullable', 'date'],
            'mail_time'          => ['nullable', 'date_format:H:i'],
            'interview_type'     => ['required', Rule::in(self::TYPES)],
            'remark'             => ['nullable', 'string'],
            'scheduled_date'     => ['nullable', 'date'],
            'scheduled_time'     => ['nullable', 'date_format:H:i'],
            'scheduled_timezone' => ['nullable', Rule::in(self::TIMEZONES)],
        ]);

        // Normalise company_domain — prepend https:// if no scheme given
        if (!empty($data['company_domain']) && !preg_match('#^https?://#i', $data['company_domain'])) {
            $data['company_domain'] = 'https://' . $data['company_domain'];
        }

        $data['candidate_id']     = $candidate->id;
        $data['recruiter_id']     = $candidate->recruiter_id; // always the candidate's assigned recruiter
        $data['created_by']       = $user->id;
        $data['interview_status'] = null; // blank by default; updated by admin or candidate

        // Non-admin (recruiter/manager) cannot set scheduled fields — only admin/candidate may
        if (!$isAdmin) {
            $data['scheduled_date']     = null;
            $data['scheduled_time']     = null;
            $data['scheduled_timezone'] = null;
        }

        Interview::create($data);

        AuditLog::log(
            'created',
            'interviews',
            "Added interview for {$candidate->full_name}",
            [],
            ['candidate_id' => $candidate->id, 'company' => $data['company_name']]
        );

        return back()->with('success', 'Interview added.');
    }

    public function update(Request $request, Candidate $candidate, Interview $interview)
    {
        $user    = $request->user();
        $isAdmin = $user->isAdmin();

        // Only admin can edit after creation
        if (!$isAdmin) {
            return back()->withErrors(['auth' => 'Only admin can edit interviews.']);
        }

        $data = $request->validate([
            'role'               => ['required', 'string', 'max:200'],
            'company_name'       => ['required', 'string', 'max:200'],
            'company_domain'     => ['required', 'string', 'max:500'],
            'mail_date'          => ['nullable', 'date'],
            'mail_time'          => ['nullable', 'date_format:H:i'],
            'interview_type'     => ['required', Rule::in(self::TYPES)],
            'remark'             => ['nullable', 'string'],
            'scheduled_date'     => ['nullable', 'date'],
            'scheduled_time'     => ['nullable', 'date_format:H:i'],
            'scheduled_timezone' => ['nullable', Rule::in(self::TIMEZONES)],
        ]);

        // Normalise company_domain — prepend https:// if no scheme given
        if (!empty($data['company_domain']) && !preg_match('#^https?://#i', $data['company_domain'])) {
            $data['company_domain'] = 'https://' . $data['company_domain'];
        }

        // Handle nullable date/time fields explicitly
        foreach (['mail_date', 'mail_time', 'scheduled_date', 'scheduled_time', 'scheduled_timezone'] as $f) {
            if (!isset($data[$f]) || $data[$f] === '') {
                $data[$f] = null;
            }
        }

        $interview->update($data);

        AuditLog::log(
            'updated',
            'interviews',
            "Updated interview for {$candidate->full_name}",
            [],
            ['interview_id' => $interview->id, 'company' => $data['company_name']]
        );

        return back()->with('success', 'Interview updated.');
    }

    public function destroy(Request $request, Candidate $candidate, Interview $interview)
    {
        $user = $request->user();
        if (!$user->isAdmin()) {
            return back()->withErrors(['auth' => 'Only admin can delete interviews.']);
        }

        $interviewId = $interview->id;
        $interview->delete();

        AuditLog::log(
            'deleted',
            'interviews',
            "Deleted interview for {$candidate->full_name}",
            ['interview_id' => $interviewId],
            []
        );

        return back()->with('success', 'Interview deleted.');
    }
}
