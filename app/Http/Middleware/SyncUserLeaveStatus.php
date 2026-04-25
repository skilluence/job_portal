<?php

namespace App\Http\Middleware;

use App\Services\CandidateOwnershipService;
use App\Services\LeaveStatusService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SyncUserLeaveStatus
{
    public function __construct(
        private readonly LeaveStatusService $leaveStatusService,
        private readonly CandidateOwnershipService $candidateOwnershipService
    )
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            $this->leaveStatusService->syncUserStatus($user);
            $this->candidateOwnershipService->syncTemporaryUnassignments();
        }

        return $next($request);
    }
}
