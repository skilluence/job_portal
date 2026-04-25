<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\Candidate;
use App\Models\DailyLog;
use App\Models\Interview;
use Illuminate\Support\Carbon;

class DailyLogSummaryService
{
    public function syncForCandidateDate(Candidate $candidate, ?string $date, ?int $actorId = null): ?DailyLog
    {
        if (!$date) {
            return null;
        }

        $logDate = Carbon::parse($date)->toDateString();

        $assessmentCount = Assessment::query()
            ->where('candidate_id', $candidate->id)
            ->where(function ($query) use ($logDate) {
                $query->whereDate('assessment_date', $logDate)
                    ->orWhere(function ($fallbackQuery) use ($logDate) {
                        $fallbackQuery->whereNull('assessment_date')
                            ->whereDate('created_at', $logDate);
                    });
            })
            ->count();

        $interviewCount = Interview::query()
            ->where('candidate_id', $candidate->id)
            ->where(function ($query) use ($logDate) {
                $query->whereDate('application_date', $logDate)
                    ->orWhere(function ($fallbackQuery) use ($logDate) {
                        $fallbackQuery->whereNull('application_date')
                            ->whereDate('created_at', $logDate);
                    });
            })
            ->count();

        $log = DailyLog::query()->firstOrNew([
            'candidate_id' => $candidate->id,
            'log_date' => $logDate,
        ]);

        if (!$log->exists) {
            $log->recruiter_id = $candidate->recruiter_id;
            $log->applications = 0;
            $log->remark = null;
            $log->created_by = $actorId;
        } elseif (!$log->recruiter_id) {
            $log->recruiter_id = $candidate->recruiter_id;
        }

        $log->assistant_count = $assessmentCount;
        $log->interview_count = $interviewCount;

        $hasContent = (int) $log->applications > 0
            || $assessmentCount > 0
            || $interviewCount > 0
            || filled($log->remark);

        if (!$hasContent) {
            if ($log->exists) {
                $log->delete();
            }

            return null;
        }

        if (!$log->created_by) {
            $log->created_by = $actorId;
        }

        $log->save();

        return $log;
    }

    public function syncInterview(Interview $interview, ?string $previousDate = null, ?int $actorId = null): void
    {
        $dates = array_filter([
            $previousDate,
            $this->interviewLogDate($interview),
        ]);

        foreach (array_unique($dates) as $date) {
            $this->syncForCandidateDate($interview->candidate, $date, $actorId);
        }
    }

    public function syncAssessment(Assessment $assessment, ?string $previousDate = null, ?int $actorId = null): void
    {
        $dates = array_filter([
            $previousDate,
            $this->assessmentLogDate($assessment),
        ]);

        foreach (array_unique($dates) as $date) {
            $this->syncForCandidateDate($assessment->candidate, $date, $actorId);
        }
    }

    public function interviewLogDate(Interview $interview): ?string
    {
        if ($interview->application_date) {
            return $interview->application_date->toDateString();
        }

        return $interview->created_at?->toDateString();
    }

    public function assessmentLogDate(Assessment $assessment): ?string
    {
        if ($assessment->assessment_date) {
            return $assessment->assessment_date->toDateString();
        }

        return $assessment->created_at?->toDateString();
    }
}
