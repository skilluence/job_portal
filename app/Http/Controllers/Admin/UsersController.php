<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UsersController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->get('search'));
        $role = $request->get('role');

        $users = User::with('teamManager')
            ->withCount('candidates')
            ->when($search, function ($q) use ($search) {
                $like = '%' . str_replace('%', '\%', $search) . '%';

                $q->where(function ($query) use ($like) {
                    $query->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like);
                });
            })
            ->when($role, fn ($q) => $q->where('role', $role))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $managers = User::whereIn('role', ['admin', 'manager'])
            ->active()
            ->orderBy('name')
            ->get();

        $defaultAdmin = User::where('role', 'admin')->orderBy('id')->first();

        return view('admin.users.index', compact('users', 'managers', 'search', 'role', 'defaultAdmin'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::in(['admin', 'recruiter', 'manager'])],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'team_manager_id' => ['nullable', Rule::exists('users', 'id')->where(fn ($q) => $q->whereIn('role', ['admin', 'manager']))],
        ]);

        $data['password'] = Hash::make($data['password']);
        $data['email'] = strtolower($data['email']);
        $data['team_manager_id'] = $this->resolveTeamManagerId($data['role'], $data['team_manager_id'] ?? null);

        $user = User::create($data);

        AuditLog::log(
            'created',
            'users',
            "Created user: {$user->name}",
            [],
            $this->sanitizeAuditValues($data)
        );

        return redirect()->route('admin.users')
            ->with('success', "User \"{$user->name}\" created successfully.");
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::in(['admin', 'recruiter', 'manager'])],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'team_manager_id' => ['nullable', Rule::exists('users', 'id')->where(fn ($q) => $q->whereIn('role', ['admin', 'manager']))],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $old = $user->toArray();
        $data['email'] = strtolower($data['email']);
        $data['team_manager_id'] = $this->resolveTeamManagerId($data['role'], $data['team_manager_id'] ?? null);

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        AuditLog::log(
            'updated',
            'users',
            "Updated user: {$user->name}",
            $this->sanitizeAuditValues($old),
            $this->sanitizeAuditValues($data)
        );

        return redirect()->route('admin.users')
            ->with('success', "User \"{$user->name}\" updated successfully.");
    }

    public function destroy(Request $request, User $user)
    {
        AuditLog::log(
            'delete_blocked',
            'users',
            "Deletion blocked for user: {$user->name}",
            ['user_id' => $user->id],
            ['attempted_by' => $request->user()?->email]
        );

        return back()->withErrors([
            'delete' => 'User accounts cannot be deleted once created.',
        ]);
    }

    private function resolveTeamManagerId(string $role, ?int $teamManagerId): ?int
    {
        if ($role !== 'recruiter') {
            return $teamManagerId;
        }

        if ($teamManagerId) {
            return $teamManagerId;
        }

        return User::where('role', 'admin')->orderBy('id')->value('id');
    }

    private function sanitizeAuditValues(array $values): array
    {
        unset($values['password'], $values['remember_token']);

        return $values;
    }
}
