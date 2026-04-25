<?php

namespace App\Services;

use App\Models\Candidate;
use App\Models\CandidateAssignmentHistory;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class CandidateOwnershipService
{
    public const TEMPORARY_UNASSIGN_ACTION = 'temporary_unassigned';
    public const TEMPORARY_TIMEZONE = 'Asia/Kolkata';

    public function applyOwnedScope(Builder $query, User $user, ?string $managerScope = null): Builder
    {
        if ($user->isRecruiter()) {
            return $query->where('recruiter_id', $user->id);
        }

        if ($user->isManager()) {
            $teamMemberIds = $user->teamMemberIds();

            if ($managerScope === 'mine') {
                return $query->where('team_manager_id', $user->id)
                    ->whereNull('recruiter_id');
            }

            if ($managerScope === 'team') {
                return $query->whereIn('recruiter_id', $teamMemberIds);
            }

            return $query->where(function (Builder $innerQuery) use ($user, $teamMemberIds) {
                $innerQuery->where(function (Builder $directQuery) use ($user) {
                    $directQuery->where('team_manager_id', $user->id)
                        ->whereNull('recruiter_id');
                })->orWhereIn('recruiter_id', $teamMemberIds);
            });
        }

        return $query;
    }

    public function ownedCandidateIdsQuery(User $user, ?string $managerScope = null): Builder
    {
        return $this->applyOwnedScope(Candidate::query()->select('id'), $user, $managerScope);
    }

    public function directManagedCandidateCount(User $user): int
    {
        return Candidate::query()
            ->where('team_manager_id', $user->id)
            ->whereNull('recruiter_id')
            ->count();
    }

    public function teamRecruiterCandidateCount(User $user): int
    {
        $teamMemberIds = $user->teamMemberIds();

        if ($teamMemberIds === []) {
            return 0;
        }

        return Candidate::query()
            ->whereIn('recruiter_id', $teamMemberIds)
            ->count();
    }

    public function totalOwnedCandidateCount(User $user): int
    {
        return $this->applyOwnedScope(Candidate::query(), $user)->count();
    }

    public function reclaimableCandidateIds(User $user): array
    {
        return CandidateAssignmentHistory::query()
            ->where('changed_by', $user->id)
            ->where('action', 'unassigned')
            ->distinct()
            ->pluck('candidate_id')
            ->all();
    }

    public function changeOwnership(
        Candidate $candidate,
        ?int $toRecruiterId,
        ?int $toTeamManagerId,
        ?User $actor,
        string $action,
        ?string $note = null
    ): bool {
        if ($toRecruiterId && $toTeamManagerId) {
            throw new InvalidArgumentException('A candidate can only be assigned to a recruiter or a team manager, not both.');
        }

        $fromRecruiterId = $candidate->recruiter_id;
        $fromTeamManagerId = $candidate->team_manager_id;

        if ((int) ($fromRecruiterId ?? 0) === (int) ($toRecruiterId ?? 0)
            && (int) ($fromTeamManagerId ?? 0) === (int) ($toTeamManagerId ?? 0)) {
            return false;
        }

        $candidate->forceFill([
            'recruiter_id' => $toRecruiterId,
            'team_manager_id' => $toTeamManagerId,
        ])->save();

        CandidateAssignmentHistory::create([
            'candidate_id' => $candidate->id,
            'from_recruiter_id' => $fromRecruiterId,
            'from_team_manager_id' => $fromTeamManagerId,
            'to_recruiter_id' => $toRecruiterId,
            'to_team_manager_id' => $toTeamManagerId,
            'changed_by' => $actor?->id,
            'action' => $action,
            'note' => $note,
        ]);

        return true;
    }

    public function scheduleTemporaryUnassign(
        Candidate $candidate,
        CarbonInterface $startsAt,
        CarbonInterface $endsAt,
        ?User $actor,
        ?string $note = null
    ): bool {
        $startsAt = Carbon::parse($startsAt)->setTimezone(self::TEMPORARY_TIMEZONE);
        $endsAt = Carbon::parse($endsAt)->setTimezone(self::TEMPORARY_TIMEZONE);

        if ($endsAt->lessThanOrEqualTo($startsAt)) {
            throw new InvalidArgumentException('Temporary unassign end time must be after the start time.');
        }

        if (!$candidate->recruiter_id && !$candidate->team_manager_id) {
            return false;
        }

        CandidateAssignmentHistory::create([
            'candidate_id' => $candidate->id,
            'from_recruiter_id' => $candidate->recruiter_id,
            'from_team_manager_id' => $candidate->team_manager_id,
            'to_recruiter_id' => null,
            'to_team_manager_id' => null,
            'changed_by' => $actor?->id,
            'action' => self::TEMPORARY_UNASSIGN_ACTION,
            'note' => $note,
            'temporary_starts_at' => $startsAt,
            'temporary_ends_at' => $endsAt,
            'restore_recruiter_id' => $candidate->recruiter_id,
            'restore_team_manager_id' => $candidate->team_manager_id,
        ]);

        return true;
    }

    public function syncTemporaryUnassignments(?CarbonInterface $moment = null): array
    {
        $moment = $moment
            ? Carbon::parse($moment)->setTimezone(self::TEMPORARY_TIMEZONE)
            : now(self::TEMPORARY_TIMEZONE);

        $activated = 0;
        $restored = 0;

        CandidateAssignmentHistory::query()
            ->with('candidate')
            ->where('action', self::TEMPORARY_UNASSIGN_ACTION)
            ->whereNull('temporary_activated_at')
            ->where('temporary_starts_at', '<=', $moment)
            ->where('temporary_ends_at', '>', $moment)
            ->get()
            ->each(function (CandidateAssignmentHistory $history) use ($moment, &$activated) {
                if ($this->activateTemporaryUnassign($history, $moment)) {
                    $activated++;
                }
            });

        CandidateAssignmentHistory::query()
            ->with('candidate')
            ->where('action', self::TEMPORARY_UNASSIGN_ACTION)
            ->whereNotNull('temporary_activated_at')
            ->whereNull('temporary_restored_at')
            ->where('temporary_ends_at', '<=', $moment)
            ->get()
            ->each(function (CandidateAssignmentHistory $history) use ($moment, &$restored) {
                if ($this->restoreTemporaryUnassign($history, $moment)) {
                    $restored++;
                }
            });

        return ['activated' => $activated, 'restored' => $restored];
    }

    private function activateTemporaryUnassign(CandidateAssignmentHistory $history, CarbonInterface $moment): bool
    {
        $candidate = $history->candidate;
        if (!$candidate || $history->temporary_activated_at) {
            return false;
        }

        $candidate->forceFill([
            'recruiter_id' => null,
            'team_manager_id' => null,
        ])->save();

        $history->forceFill([
            'temporary_activated_at' => Carbon::parse($moment)->setTimezone(self::TEMPORARY_TIMEZONE),
        ])->save();

        return true;
    }

    private function restoreTemporaryUnassign(CandidateAssignmentHistory $history, CarbonInterface $moment): bool
    {
        $candidate = $history->candidate;
        if (!$candidate || $history->temporary_restored_at) {
            return false;
        }

        $candidate->forceFill([
            'recruiter_id' => $history->restore_recruiter_id,
            'team_manager_id' => $history->restore_team_manager_id,
        ])->save();

        $history->forceFill([
            'temporary_restored_at' => Carbon::parse($moment)->setTimezone(self::TEMPORARY_TIMEZONE),
        ])->save();

        return true;
    }

    public function bulkChangeOwnership(
        Collection $candidates,
        ?int $toRecruiterId,
        ?int $toTeamManagerId,
        ?User $actor,
        string $action,
        ?string $note = null
    ): int {
        $changedCount = 0;

        foreach ($candidates as $candidate) {
            if ($this->changeOwnership($candidate, $toRecruiterId, $toTeamManagerId, $actor, $action, $note)) {
                $changedCount++;
            }
        }

        return $changedCount;
    }
}
