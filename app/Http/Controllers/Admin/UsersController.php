<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Candidate;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UsersController extends Controller
{
    public function index(Request $request)
    {
        $authUser  = $request->user();
        $isAdmin   = $authUser->isAdmin();
        $isManager = $authUser->isManager();
        $search    = trim((string) $request->get('search'));
        $role      = $request->get('role');

        $query = User::withCount('candidates')->with('teamManager');

        if ($isManager) {
            // Manager sees only their own team members
            $query->where('team_manager_id', $authUser->id);
        } else {
            $query->when($role, fn ($q) => $q->where('role', $role));
        }

        $query->when($search, function ($q) use ($search) {
            $like = '%' . str_replace('%', '\%', $search) . '%';
            $q->where(fn ($sub) => $sub->where('name', 'like', $like)->orWhere('email', 'like', $like));
        })->latest();

        $users = $query->paginate(15)->withQueryString();

        $managers    = User::managers()->active()->orderBy('name')->get(['id', 'name']);
        $adminExists = User::where('role', 'admin')->exists();

        return view('admin.users.index', [
            'users'       => $users,
            'search'      => $search,
            'role'        => $role,
            'managers'    => $managers,
            'adminExists' => $adminExists,
            'currentUser' => $authUser,
            'isAdmin'     => $isAdmin,
            'isManager'   => $isManager,
        ]);
    }

    public function store(Request $request)
    {
        $authUser  = $request->user();
        $isAdmin   = $authUser->isAdmin();
        $isManager = $authUser->isManager();

        if (!$isAdmin && !$isManager) {
            abort(403);
        }

        // Admins can create any role; managers can only create recruiters
        $allowedRoles = $isAdmin ? ['admin', 'manager', 'recruiter'] : ['recruiter'];

        // Block second admin
        if ($isAdmin && $request->input('role') === 'admin' && User::where('role', 'admin')->exists()) {
            return back()->withErrors(['role' => 'Only one admin account is allowed. The admin already exists.'])->withInput();
        }

        $data = $request->validate([
            'name'            => ['required', 'string', 'max:255'],
            'email'           => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'        => ['required', 'string', 'min:8', 'confirmed'],
            'role'            => ['required', Rule::in($allowedRoles)],
            'status'          => ['required', Rule::in(['active', 'inactive'])],
            'team_manager_id' => [
                Rule::requiredIf(fn () => $request->input('role') === 'recruiter'),
                'nullable',
                'exists:users,id',
            ],
        ]);

        // Managers auto-assign new recruiters to themselves
        if ($isManager) {
            $data['team_manager_id'] = $authUser->id;
        }

        // Non-recruiter roles don't have a team manager
        if (($data['role'] ?? '') !== 'recruiter') {
            $data['team_manager_id'] = null;
        }

        $data['password'] = Hash::make($data['password']);
        $data['email']    = strtolower($data['email']);

        $user = User::create($data);

        AuditLog::log('created', 'users', "Created user: {$user->name}", [], $this->sanitizeAuditValues($data));

        return redirect()->route('admin.users')
            ->with('success', "User \"{$user->name}\" created successfully.");
    }

    public function update(Request $request, User $user)
    {
        $authUser  = $request->user();
        $isAdmin   = $authUser->isAdmin();
        $isManager = $authUser->isManager();

        // Permission checks
        if ($authUser->isRecruiter() && $authUser->id !== $user->id) {
            abort(403, 'You can only edit your own profile.');
        }
        if ($isManager && $authUser->id !== $user->id && $user->team_manager_id !== $authUser->id) {
            abort(403, 'You can only edit members of your team.');
        }

        if ($authUser->isRecruiter()) {
            $data = $request->validate([
                'name'     => ['required', 'string', 'max:255'],
                'email'    => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
                'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            ]);
        } elseif ($isManager) {
            $data = $request->validate([
                'name'     => ['required', 'string', 'max:255'],
                'email'    => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
                'status'   => ['required', Rule::in(['active', 'inactive'])],
                'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            ]);
        } else {
            // Admin: full edit
            $data = $request->validate([
                'name'            => ['required', 'string', 'max:255'],
                'email'           => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
                'role'            => ['required', Rule::in(['admin', 'manager', 'recruiter'])],
                'status'          => ['required', Rule::in(['active', 'inactive'])],
                'password'        => ['nullable', 'string', 'min:8', 'confirmed'],
                'team_manager_id' => [
                    Rule::requiredIf(fn () => $request->input('role') === 'recruiter'),
                    'nullable',
                    'exists:users,id',
                ],
            ]);

            if (($data['role'] ?? '') !== 'recruiter') {
                $data['team_manager_id'] = null;
            }
        }

        $old           = $user->toArray();
        $data['email'] = strtolower($data['email']);

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        AuditLog::log('updated', 'users', "Updated user: {$user->name}", $this->sanitizeAuditValues($old), $this->sanitizeAuditValues($data));

        return redirect()->route('admin.users')
            ->with('success', "User \"{$user->name}\" updated successfully.");
    }

    public function destroy(Request $request, User $user)
    {
        $authUser  = $request->user();
        $isManager = $authUser->isManager();

        if ($user->role === 'admin') {
            return back()->withErrors(['delete' => 'Admin accounts cannot be deleted.']);
        }

        if ($authUser->id === $user->id) {
            return back()->withErrors(['delete' => 'You cannot delete your own account.']);
        }

        if ($isManager && $user->team_manager_id !== $authUser->id) {
            abort(403, 'You can only delete members of your team.');
        }

        // Reassign candidates to admin
        $adminId = User::where('role', 'admin')->orderBy('id')->value('id');
        if ($adminId) {
            Candidate::withTrashed()->where('recruiter_id', $user->id)->update(['recruiter_id' => $adminId]);
        }

        // Unlink team members if deleting a manager
        if ($user->role === 'manager') {
            User::where('team_manager_id', $user->id)->update(['team_manager_id' => null]);
        }

        $name  = $user->name;
        $email = $user->email;

        AuditLog::log(
            'deleted',
            'users',
            "Deleted user: {$name}",
            ['user_id' => $user->id, 'email' => $email, 'role' => $user->role],
            ['deleted_by' => $authUser->email, 'candidates_reassigned_to_admin' => $adminId]
        );

        $user->delete();

        return redirect()->route('admin.users')
            ->with('success', "User \"{$name}\" deleted successfully.");
    }

    private function sanitizeAuditValues(array $values): array
    {
        unset($values['password'], $values['remember_token']);
        return $values;
    }
}
