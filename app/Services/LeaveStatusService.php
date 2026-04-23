<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\LeaveRequest;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class LeaveStatusService
{
    public const DASHBOARD_TIMEZONE = 'Asia/Kolkata';
    public const HALF_DAY_SPLIT_HOUR = 13;

    public function syncUserStatus(User $user, ?CarbonInterface $moment = null): void
    {
        if (!$user->isRecruiter() && !$user->isManager()) {
            if ($user->leave_override_until && $user->leave_override_until->isPast()) {
                $user->forceFill(['leave_override_until' => null])->saveQuietly();
            }

            return;
        }

        $moment = $moment
            ? Carbon::parse($moment)->setTimezone(self::DASHBOARD_TIMEZONE)
            : now(self::DASHBOARD_TIMEZONE);

        $user->refresh();

        $activeLeave = $this->currentActiveApprovedLeave($user, $moment);
        $overrideUntil = $user->leave_override_until
            ? Carbon::parse($user->leave_override_until)->setTimezone(self::DASHBOARD_TIMEZONE)
            : null;

        if ($activeLeave) {
            if ($overrideUntil && $overrideUntil->greaterThan($moment)) {
                return;
            }

            if ($user->status === 'active') {
                $user->forceFill([
                    'status' => 'inactive',
                    'inactive_reason' => 'leave',
                    'leave_override_until' => null,
                ])->saveQuietly();

                AuditLog::log(
                    'updated',
                    'leaves',
                    "Auto-inactivated {$user->name} for approved leave",
                    [],
                    ['user_id' => $user->id, 'leave_request_id' => $activeLeave->id],
                    'system',
                    'System'
                );
            }

            return;
        }

        $updates = [];

        if ($user->status === 'inactive' && $user->inactive_reason === 'leave') {
            $updates['status'] = 'active';
            $updates['inactive_reason'] = null;
            $updates['leave_override_until'] = null;
        } elseif ($overrideUntil && $overrideUntil->lessThanOrEqualTo($moment)) {
            $updates['leave_override_until'] = null;
        }

        if (!empty($updates)) {
            $user->forceFill($updates)->saveQuietly();

            if (($updates['status'] ?? null) === 'active') {
                AuditLog::log(
                    'updated',
                    'leaves',
                    "Auto-reactivated {$user->name} after leave ended",
                    [],
                    ['user_id' => $user->id],
                    'system',
                    'System'
                );
            }
        }
    }

    public function currentActiveApprovedLeave(User $user, ?CarbonInterface $moment = null): ?LeaveRequest
    {
        $moment = $moment
            ? Carbon::parse($moment)->setTimezone(self::DASHBOARD_TIMEZONE)
            : now(self::DASHBOARD_TIMEZONE);

        $leaves = LeaveRequest::query()
            ->where('user_id', $user->id)
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->whereDate('leave_date', $moment->toDateString())
            ->orderByDesc('id')
            ->get();

        foreach ($leaves as $leave) {
            if ($this->isLeaveActiveAt($leave, $moment)) {
                return $leave;
            }
        }

        return null;
    }

    public function isLeaveActiveAt(LeaveRequest $leave, ?CarbonInterface $moment = null): bool
    {
        if ($leave->status !== LeaveRequest::STATUS_APPROVED || !$leave->leave_date) {
            return false;
        }

        $moment = $moment
            ? Carbon::parse($moment)->setTimezone(self::DASHBOARD_TIMEZONE)
            : now(self::DASHBOARD_TIMEZONE);

        if ($leave->leave_date->toDateString() !== $moment->toDateString()) {
            return false;
        }

        if ($leave->leave_type === LeaveRequest::TYPE_FULL_DAY) {
            return true;
        }

        $hour = (int) $moment->format('H');

        return match ($leave->half_day_session) {
            LeaveRequest::SESSION_FIRST_HALF => $hour < self::HALF_DAY_SPLIT_HOUR,
            LeaveRequest::SESSION_SECOND_HALF => $hour >= self::HALF_DAY_SPLIT_HOUR,
            default => false,
        };
    }

    public function leaveWindowEndsAt(LeaveRequest $leave): Carbon
    {
        $date = $leave->leave_date->copy()->setTimezone(self::DASHBOARD_TIMEZONE);

        if ($leave->leave_type === LeaveRequest::TYPE_FULL_DAY) {
            return $date->copy()->endOfDay();
        }

        if ($leave->half_day_session === LeaveRequest::SESSION_FIRST_HALF) {
            return $date->copy()->setTime(self::HALF_DAY_SPLIT_HOUR, 0, 0);
        }

        return $date->copy()->endOfDay();
    }

    public function setAdminOverrideForActiveLeave(User $user, ?CarbonInterface $moment = null): void
    {
        $moment = $moment
            ? Carbon::parse($moment)->setTimezone(self::DASHBOARD_TIMEZONE)
            : now(self::DASHBOARD_TIMEZONE);

        $activeLeave = $this->currentActiveApprovedLeave($user, $moment);

        if (!$activeLeave) {
            $user->forceFill([
                'leave_override_until' => null,
                'inactive_reason' => null,
            ])->saveQuietly();

            return;
        }

        $user->forceFill([
            'leave_override_until' => $this->leaveWindowEndsAt($activeLeave),
            'inactive_reason' => null,
        ])->saveQuietly();
    }
}
