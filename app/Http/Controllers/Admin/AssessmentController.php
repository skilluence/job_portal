<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AuditLog;
use App\Models\Candidate;
use App\Services\DailyLogSummaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class AssessmentController extends Controller
{
    public const TYPES = [
        'technical',
        'screening',
        'ai_interview',
        'questions',
        'virtual_video_interview',
    ];

    public function store(Request $request, Candidate $candidate, DailyLogSummaryService $dailyLogSummaryService)
    {
        $user = $request->user();
        $this->authorizeCandidateAccess($user, $candidate);

        $data = $request->validate($this->rules());

        if (!empty($data['company_website_url']) && !preg_match('#^https?://#i', $data['company_website_url'])) {
            $data['company_website_url'] = 'https://' . $data['company_website_url'];
        }

        $data = $this->normalizeDateTimes($data);
        $data['candidate_id'] = $candidate->id;
        $data['recruiter_id'] = $candidate->recruiter_id;
        $data['created_by'] = $user->id;

        $assessment = Assessment::create($data);

        $dailyLogSummaryService->syncAssessment($assessment, null, $user->id);

        AuditLog::log(
            'created',
            'assessments',
            "Added assessment for {$candidate->full_name}",
            [],
            ['assessment_id' => $assessment->id, 'company' => $assessment->company_name]
        );

        return redirect()->route('admin.candidates.show', [$candidate, 'tab' => 'assessments'])
            ->with('success', 'Assessment added.');
    }

    public function update(Request $request, Candidate $candidate, Assessment $assessment, DailyLogSummaryService $dailyLogSummaryService)
    {
        $user = $request->user();
        abort_unless((int) $assessment->candidate_id === (int) $candidate->id, 404);

        if (!$user->isAdmin()) {
            return back()->withErrors(['auth' => 'Only admin can edit assessments.']);
        }

        $previousDate = $dailyLogSummaryService->assessmentLogDate($assessment);
        $data = $request->validate($this->rules());

        if (!empty($data['company_website_url']) && !preg_match('#^https?://#i', $data['company_website_url'])) {
            $data['company_website_url'] = 'https://' . $data['company_website_url'];
        }

        $data = $this->normalizeDateTimes($data);

        $assessment->update($data);
        $assessment->refresh();

        $dailyLogSummaryService->syncAssessment($assessment, $previousDate, $user->id);

        AuditLog::log(
            'updated',
            'assessments',
            "Updated assessment for {$candidate->full_name}",
            [],
            ['assessment_id' => $assessment->id, 'company' => $assessment->company_name]
        );

        return redirect()->route('admin.candidates.show', [$candidate, 'tab' => 'assessments'])
            ->with('success', 'Assessment updated.');
    }

    public function destroy(Request $request, Candidate $candidate, Assessment $assessment, DailyLogSummaryService $dailyLogSummaryService)
    {
        $user = $request->user();
        abort_unless((int) $assessment->candidate_id === (int) $candidate->id, 404);

        if (!$user->isAdmin()) {
            return back()->withErrors(['auth' => 'Only admin can delete assessments.']);
        }

        $previousDate = $dailyLogSummaryService->assessmentLogDate($assessment);
        $assessmentId = $assessment->id;
        $assessment->delete();

        if ($previousDate) {
            $dailyLogSummaryService->syncForCandidateDate($candidate, $previousDate, $user->id);
        }

        AuditLog::log(
            'deleted',
            'assessments',
            "Deleted assessment for {$candidate->full_name}",
            ['assessment_id' => $assessmentId],
            []
        );

        return redirect()->route('admin.candidates.show', [$candidate, 'tab' => 'assessments'])
            ->with('success', 'Assessment deleted.');
    }

    private function rules(): array
    {
        return [
            'assessment_date' => ['nullable', 'date'],
            'assessment_time' => ['nullable', 'date_format:H:i'],
            'company_name' => ['required', 'string', 'max:200'],
            'domain' => ['required', 'string', 'max:200'],
            'company_website_url' => ['nullable', 'string', 'max:500'],
            'role' => ['required', 'string', 'max:200'],
            'assessment_type' => ['required', Rule::in(self::TYPES)],
            'mail_date' => ['nullable', 'date'],
            'mail_time' => ['nullable', 'date_format:H:i'],
            'remark' => ['nullable', 'string'],
        ];
    }

    private function normalizeDateTimes(array $data): array
    {
        foreach (['assessment_date', 'assessment_time', 'mail_date', 'mail_time'] as $field) {
            if (!array_key_exists($field, $data) || $data[$field] === '') {
                $data[$field] = null;
            }
        }

        foreach (['assessment_time', 'mail_time'] as $timeField) {
            if (!empty($data[$timeField])) {
                $data[$timeField] = Carbon::createFromFormat('H:i', substr((string) $data[$timeField], 0, 5))
                    ->format('H:i');
            }
        }

        return $data;
    }

    private function authorizeCandidateAccess($user, Candidate $candidate): void
    {
        if ($user->isRecruiter() && $candidate->recruiter_id !== $user->id) {
            abort(403, 'Not authorized.');
        }

        if (
            $user->isManager()
            && $candidate->team_manager_id !== $user->id
            && !in_array($candidate->recruiter_id, $user->teamMemberIds(), true)
        ) {
            abort(403, 'Not authorized.');
        }
    }
}
