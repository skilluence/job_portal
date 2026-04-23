<?php

namespace App\Services;

use App\Models\Candidate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

class StudentRememberService
{
    private const COOKIE_NAME = 'student_portal_remember';

    public function login(Request $request, Candidate $candidate, bool $remember = false): void
    {
        $request->session()->regenerate();
        $request->session()->regenerate();
        $request->session()->put('student_id', $candidate->id);

        if ($remember) {
            $plainToken = Str::random(60);
            $candidate->forceFill([
                'remember_token' => hash('sha256', $plainToken),
            ])->save();

            Cookie::queue(Cookie::forever(self::COOKIE_NAME, $candidate->id . '|' . $plainToken));

            return;
        }

        if ($candidate->remember_token) {
            $candidate->forceFill(['remember_token' => null])->save();
        }

        Cookie::queue(Cookie::forget(self::COOKIE_NAME));
    }

    public function logout(Request $request): void
    {
        $candidateId = $request->session()->get('student_id');

        if ($candidateId) {
            Candidate::query()->whereKey($candidateId)->update(['remember_token' => null]);
        }

        Cookie::queue(Cookie::forget(self::COOKIE_NAME));
    }

    public function restoreFromRememberCookie(Request $request): ?Candidate
    {
        if ($request->session()->has('student_id')) {
            return Candidate::withTrashed()->find($request->session()->get('student_id'));
        }

        $cookieValue = $request->cookie(self::COOKIE_NAME);
        if (!is_string($cookieValue) || !str_contains($cookieValue, '|')) {
            return null;
        }

        [$candidateId, $plainToken] = explode('|', $cookieValue, 2);
        if (!ctype_digit((string) $candidateId) || $plainToken === '') {
            return null;
        }

        $candidate = Candidate::withTrashed()->find((int) $candidateId);
        if (!$candidate || !$candidate->remember_token) {
            $this->forgetRememberCookie();

            return null;
        }

        if (!hash_equals($candidate->remember_token, hash('sha256', $plainToken))) {
            $this->forgetRememberCookie();

            return null;
        }

        if ($candidate->trashed() || $candidate->status === 'inactive') {
            $candidate->forceFill(['remember_token' => null])->save();
            $this->forgetRememberCookie();

            return null;
        }

        $request->session()->put('student_id', $candidate->id);

        return $candidate;
    }

    public function forgetRememberCookie(): void
    {
        Cookie::queue(Cookie::forget(self::COOKIE_NAME));
    }
}
