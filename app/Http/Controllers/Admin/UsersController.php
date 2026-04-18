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
        $search = trim((string) $request->get('search'));
        $role   = $request->get('role');

        $users = User::withCount('candidates')
            ->when($search, function ($q) use ($search) {
                $like = '%' . str_replace('%', '\%', $search) . '%';
                $q->where(function ($query) use ($like) {
                    $query->where('name', 'like', $like)->orWhere('email', 'like', $like);
                });
            })
            ->when($role, fn ($q) => $q->where('role', $role))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $defaultAdmin = User::where('role', 'admin')->orderBy('id')->first();

        return view('admin.users.index', [
            'users'        => $users,
            'search'       => $search,
            'role'         => $role,
            'defaultAdmin' => $defaultAdmin,
            'currentUser'  => $request->user(),
            'isAdmin'      => $request->user()->isAdmin(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role'     => ['required', Rule::in(['admin', 'recruiter'])],
            'status'   => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $data['password'] = Hash::make($data['password']);
        $data['email']    = strtolower($data['email']);

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
        $authUser    = $request->user();
        $isAdmin     = $authUser->isAdmin();
        $isRecruiter = $authUser->isRecruiter();

        // Recruiters can only edit their own profile
        if ($isRecruiter && $authUser->id !== $user->id) {
            abort(403, 'You can only edit your own profile.');
        }

        if ($isRecruiter) {
            // Recruiter self-edit: name, email, password only
            $data = $request->validate([
                'name'     => ['required', 'string', 'max:255'],
                'email'    => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
                'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            ]);
        } else {
            // Admin: full edit
            $data = $request->validate([
                'name'   => ['required', 'string', 'max:255'],
                'email'  => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
                'role'   => ['required', Rule::in(['admin', 'recruiter'])],
                'status' => ['required', Rule::in(['active', 'inactive'])],
                'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            ]);
        }

        $old           = $user->toArray();
        $data['email'] = strtolower($data['email']);

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
        if ($user->role === 'admin') {
            return back()->withErrors(['delete' => 'Admin accounts cannot be deleted.']);
        }

        if ($request->user()?->id === $user->id) {
            return back()->withErrors(['delete' => 'You cannot delete your own account.']);
        }

        $adminId = User::where('role', 'admin')->orderBy('id')->value('id');
        if ($adminId) {
            Candidate::withTrashed()->where('recruiter_id', $user->id)->update(['recruiter_id' => $adminId]);
        }

        $name  = $user->name;
        $email = $user->email;

        AuditLog::log(
            'deleted',
            'users',
            "Deleted user: {$name}",
            ['user_id' => $user->id, 'email' => $email, 'role' => $user->role],
            ['deleted_by' => $request->user()?->email, 'candidates_reassigned_to_admin' => $adminId]
        );

        $user->delete();

        return redirect()->route('admin.users')
            ->with('success', "User \"{$name}\" deleted. Their candidates have been reassigned to admin.");
    }

    private function sanitizeAuditValues(array $values): array
    {
        unset($values['password'], $values['remember_token']);
        return $values;
    }
}
