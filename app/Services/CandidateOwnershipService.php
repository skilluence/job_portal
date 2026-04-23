<?php

namespace App\Services;

use App\Models\Candidate;
use App\Models\CandidateAssignmentHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class CandidateOwnershipService
{
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
            ->orderByDesc('created_at')
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
