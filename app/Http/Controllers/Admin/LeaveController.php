<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Services\LeaveStatusService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LeaveController extends Controller
{
    public function index(Request $request)
    {
        $authUser = $request->user();
        $isAdmin = $authUser->isAdmin();
        $staffSearch = trim((string) $request->get('staff'));
        $editingId = (int) $request->get('edit');

        $leaves = LeaveRequest::query()
            ->with(['user:id,name,role', 'creator:id,name', 'approver:id,name', 'rejector:id,name'])
            ->when(!$isAdmin, fn ($q) => $q->where('user_id', $authUser->id))
            ->when($isAdmin && $staffSearch !== '', function ($q) use ($staffSearch) {
                $like = '%' . str_replace('%', '\%', $staffSearch) . '%';
                $q->whereHas('user', fn ($uq) => $uq->where('name', 'like', $like));
            })
            ->orderByDesc('leave_date')
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $editableLeave = null;
        if ($isAdmin && $editingId > 0) {
            $editableLeave = LeaveRequest::with('user:id,name,role')->findOrFail($editingId);
        }

        $staffMembers = $isAdmin
            ? User::query()
                ->whereIn('role', ['recruiter', 'manager'])
                ->orderBy('name')
                ->get(['id', 'name', 'role'])
            : collect();

        return view('admin.leaves.index', [
            'leaves' => $leaves,
            'editableLeave' => $editableLeave,
            'staffMembers' => $staffMembers,
            'staffSearch' => $staffSearch,
            'isAdmin' => $isAdmin,
            'currentUser' => $authUser,
            'dashboardToday' => now(LeaveStatusService::DASHBOARD_TIMEZONE),
        ]);
    }

    public function store(Request $request, LeaveStatusService $leaveStatusService)
    {
        $authUser = $request->user();
        $isAdmin = $authUser->isAdmin();

        $data = $request->validate($this->validationRules($isAdmin));
        $targetUser = $this->resolveTargetUser($authUser, $data);

        $this->validateFutureLeaveDate($data['leave_date']);
        $this->ensureNoDuplicateLeave($targetUser->id, $data['leave_date']);

        $payload = $this->buildPayload($request, $data, $targetUser, $authUser, null);
        $leave = LeaveRequest::create($payload);

        AuditLog::log(
            'created',
            'leaves',
            "Created leave for {$targetUser->name} on {$leave->leave_date->format('Y-m-d')}",
            [],
            ['leave_request_id' => $leave->id, 'status' => $leave->status]
        );

        if ($leave->status === LeaveRequest::STATUS_APPROVED) {
            $leaveStatusService->syncUserStatus($targetUser);
        }

        return redirect()->route('admin.leaves')
            ->with('success', $isAdmin ? 'Leave added and approved successfully.' : 'Leave request submitted successfully.');
    }

    public function update(Request $request, LeaveRequest $leave, LeaveStatusService $leaveStatusService)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $data = $request->validate($this->validationRules(true));
        $targetUser = $this->resolveTargetUser($request->user(), $data);
        $oldUser = $leave->user;

        $this->validateFutureLeaveDate($data['leave_date']);
        $this->ensureNoDuplicateLeave($targetUser->id, $data['leave_date'], $leave->id);

        $oldValues = $leave->only(['user_id', 'leave_date', 'leave_type', 'half_day_session', 'reason', 'status', 'document_path']);
        $payload = $this->buildPayload($request, $data, $targetUser, $request->user(), $leave);

        $leave->update($payload);

        AuditLog::log(
            'updated',
            'leaves',
            "Updated leave #{$leave->id} for {$targetUser->name}",
            $oldValues,
            $leave->only(['user_id', 'leave_date', 'leave_type', 'half_day_session', 'reason', 'status', 'document_path'])
        );

        if ($oldUser) {
            $leaveStatusService->syncUserStatus($oldUser);
        }
        $leaveStatusService->syncUserStatus($targetUser);

        return redirect()->route('admin.leaves')
            ->with('success', 'Leave updated successfully.');
    }

    public function approve(Request $request, LeaveRequest $leave, LeaveStatusService $leaveStatusService)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $oldStatus = $leave->status;
        $leave->update([
            'status' => LeaveRequest::STATUS_APPROVED,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'rejected_by' => null,
            'rejected_at' => null,
        ]);

        AuditLog::log(
            'updated',
            'leaves',
            "Approved leave #{$leave->id} for {$leave->user?->name}",
            ['status' => $oldStatus],
            ['status' => LeaveRequest::STATUS_APPROVED]
        );

        if ($leave->user) {
            $leaveStatusService->syncUserStatus($leave->user);
        }

        return back()->with('success', 'Leave approved successfully.');
    }

    public function reject(Request $request, LeaveRequest $leave, LeaveStatusService $leaveStatusService)
    {
        abort_unless($request->user()->isAdmin(), 403);

        if (!$leave->isRejectable(now(LeaveStatusService::DASHBOARD_TIMEZONE))) {
            throw ValidationException::withMessages([
                'leave' => 'Past or current-date leaves cannot be rejected.',
            ]);
        }

        $oldStatus = $leave->status;
        $leave->update([
            'status' => LeaveRequest::STATUS_REJECTED,
            'approved_by' => null,
            'approved_at' => null,
            'rejected_by' => $request->user()->id,
            'rejected_at' => now(),
        ]);

        AuditLog::log(
            'updated',
            'leaves',
            "Rejected leave #{$leave->id} for {$leave->user?->name}",
            ['status' => $oldStatus],
            ['status' => LeaveRequest::STATUS_REJECTED]
        );

        if ($leave->user) {
            $leaveStatusService->syncUserStatus($leave->user);
        }

        return back()->with('success', 'Leave rejected successfully.');
    }

    public function download(Request $request, LeaveRequest $leave)
    {
        $authUser = $request->user();
        if (!$authUser->isAdmin() && $leave->user_id !== $authUser->id) {
            abort(403);
        }

        if (!$leave->document_path || !Storage::disk('local')->exists($leave->document_path)) {
            throw ValidationException::withMessages([
                'leave' => 'Leave document was not found.',
            ]);
        }

        AuditLog::log(
            'downloaded',
            'leaves',
            "Downloaded leave document for {$leave->user?->name}",
            [],
            ['leave_request_id' => $leave->id]
        );

        return Storage::disk('local')->download($leave->document_path, $leave->document_name ?: basename($leave->document_path));
    }

    private function validationRules(bool $isAdmin): array
    {
        $rules = [
            'leave_date' => ['required', 'date'],
            'leave_type' => ['required', Rule::in([LeaveRequest::TYPE_FULL_DAY, LeaveRequest::TYPE_HALF_DAY])],
            'half_day_session' => [
                Rule::requiredIf(fn () => request('leave_type') === LeaveRequest::TYPE_HALF_DAY),
                'nullable',
                Rule::in([LeaveRequest::SESSION_FIRST_HALF, LeaveRequest::SESSION_SECOND_HALF]),
            ],
            'reason' => ['required', 'string', 'max:255'],
            'document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:5120'],
        ];

        if ($isAdmin) {
            $rules['user_id'] = [
                'required',
                Rule::exists('users', 'id')->where(fn ($q) => $q->whereIn('role', ['recruiter', 'manager'])),
            ];
        }

        return $rules;
    }

    private function resolveTargetUser(User $authUser, array $data): User
    {
        if ($authUser->isAdmin()) {
            return User::query()
                ->whereIn('role', ['recruiter', 'manager'])
                ->findOrFail((int) $data['user_id']);
        }

        return $authUser;
    }

    private function validateFutureLeaveDate(string $leaveDate): void
    {
        $selected = Carbon::parse($leaveDate, LeaveStatusService::DASHBOARD_TIMEZONE)->startOfDay();
        $today = now(LeaveStatusService::DASHBOARD_TIMEZONE)->startOfDay();

        if ($selected->lessThanOrEqualTo($today)) {
            throw ValidationException::withMessages([
                'leave_date' => 'Please select a future leave date.',
            ]);
        }
    }

    private function ensureNoDuplicateLeave(int $userId, string $leaveDate, ?int $ignoreId = null): void
    {
        $exists = LeaveRequest::forUserDate($userId, $leaveDate, $ignoreId)->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'leave_date' => 'A leave entry already exists for this staff member on the selected date.',
            ]);
        }
    }

    private function buildPayload(
        Request $request,
        array $data,
        User $targetUser,
        User $authUser,
        ?LeaveRequest $leave
    ): array {
        $payload = [
            'user_id' => $targetUser->id,
            'leave_date' => $data['leave_date'],
            'leave_type' => $data['leave_type'],
            'half_day_session' => $data['leave_type'] === LeaveRequest::TYPE_HALF_DAY ? $data['half_day_session'] : null,
            'reason' => trim((string) $data['reason']),
        ];

        if (!$leave) {
            $payload['created_by'] = $authUser->id;
            if ($authUser->isAdmin()) {
                $payload['status'] = LeaveRequest::STATUS_APPROVED;
                $payload['approved_by'] = $authUser->id;
                $payload['approved_at'] = now();
            } else {
                $payload['status'] = LeaveRequest::STATUS_PENDING;
            }
        } else {
            $payload['status'] = $leave->status;
            $payload['approved_by'] = $leave->approved_by;
            $payload['approved_at'] = $leave->approved_at;
            $payload['rejected_by'] = $leave->rejected_by;
            $payload['rejected_at'] = $leave->rejected_at;
        }

        if ($request->hasFile('document')) {
            if ($leave?->document_path) {
                Storage::disk('local')->delete($leave->document_path);
            }

            $storedFile = $request->file('document');
            $payload['document_path'] = $storedFile->store("leave-documents/{$targetUser->id}", 'local');
            $payload['document_name'] = $storedFile->getClientOriginalName();
        } elseif (!$leave) {
            $payload['document_path'] = null;
            $payload['document_name'] = null;
        }

        return $payload;
    }
}
