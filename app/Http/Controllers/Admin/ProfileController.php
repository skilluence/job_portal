<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        return view('admin.profile.index', compact('user'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        $isAdmin = $user->isAdmin();

        $rules = [
            'name'  => 'required|string|max:255',
        ];

        if ($isAdmin) {
            $rules['email'] = 'required|email|unique:users,email,' . $user->id;
        } else {
            $rules['email'] = 'nullable|email';
        }

        $request->validate($rules);

        if (
            !$isAdmin
            && $request->filled('email')
            && strcasecmp(trim((string) $request->email), (string) $user->email) !== 0
        ) {
            return back()
                ->with('error', 'Only admin can change email address.')
                ->withInput();
        }

        $old = ['name' => $user->name, 'email' => $user->email];
        $newValues = [
            'name' => $request->name,
            'email' => $user->email,
        ];

        if ($isAdmin) {
            $newValues['email'] = strtolower((string) $request->email);
        }

        $user->update($newValues);

        AuditLog::log('updated', 'profile', "Profile updated: {$user->name}", $old, $newValues);

        return back()->with('success', 'Profile updated successfully.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password'         => 'required|min:8|confirmed',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'The current password is incorrect.']);
        }

        $user->update(['password' => Hash::make($request->password)]);

        AuditLog::log('updated', 'profile', 'Password changed', [], []);

        return back()->with('success', 'Password updated successfully.');
    }
}
