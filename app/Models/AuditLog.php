<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'actor_type',
        'actor_name',
        'action',
        'module',
        'description',
        'ip_address',
        'user_agent',
        'old_values',
        'new_values',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function log(
        string $action,
        string $module,
        string $description,
        array $old = [],
        array $new = [],
        ?string $actorType = null,
        ?string $actorName = null
    ): void {
        $userId = null;

        if (!$actorType || !$actorName) {
            if (Auth::check()) {
                $actorType = 'staff';
                $actorName = Auth::user()->name;
                $userId = Auth::id();
            } elseif (session()->has('student_id')) {
                $candidate = Candidate::find(session('student_id'));
                $actorType = 'student';
                $actorName = $candidate?->full_name ?: 'Student';
            } else {
                $actorType = $actorType ?: 'system';
                $actorName = $actorName ?: 'System';
            }
        }

        static::create([
            'user_id' => $userId,
            'actor_type' => $actorType,
            'actor_name' => $actorName,
            'action' => $action,
            'module' => $module,
            'description' => $description,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'old_values' => $old ?: null,
            'new_values' => $new ?: null,
        ]);
    }
}
