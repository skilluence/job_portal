<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\LeaveRequest;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'inactive_reason',
        'leave_override_until',
        'team_manager_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'leave_override_until' => 'datetime',
        ];
    }

    public function candidates()
    {
        return $this->hasMany(Candidate::class, 'recruiter_id');
    }

    public function managedCandidates()
    {
        return $this->hasMany(Candidate::class, 'team_manager_id');
    }

    public function directManagedCandidates()
    {
        return $this->hasMany(Candidate::class, 'team_manager_id')->whereNull('recruiter_id');
    }

    public function teamCandidates()
    {
        return $this->hasManyThrough(
            Candidate::class,
            User::class,
            'team_manager_id',
            'recruiter_id',
            'id',
            'id'
        );
    }

    public function teamManager()
    {
        return $this->belongsTo(User::class, 'team_manager_id');
    }

    public function teamMembers()
    {
        return $this->hasMany(User::class, 'team_manager_id');
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class)->latest('leave_date')->latest();
    }

    public function scopeRecruiters($query)
    {
        return $query->where('role', 'recruiter');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isRecruiter(): bool
    {
        return $this->role === 'recruiter';
    }

    public function isManager(): bool
    {
        return $this->role === 'manager';
    }

    public function teamMemberIds(): array
    {
        return $this->teamMembers()->pluck('id')->toArray();
    }

    public function scopeManagers($query)
    {
        return $query->where('role', 'manager');
    }

    public static function buildInitials(?string $name, string $fallback = 'U'): string
    {
        $parts = array_values(array_filter(preg_split('/\s+/', trim((string) $name)) ?: []));

        if ($parts === []) {
            return strtoupper(substr($fallback, 0, 2));
        }

        $first = strtoupper(substr($parts[0], 0, 1));
        $last = count($parts) > 1
            ? strtoupper(substr($parts[count($parts) - 1], 0, 1))
            : '';

        return $first . $last;
    }

    public function getInitialsAttribute(): string
    {
        return self::buildInitials($this->name);
    }

    public function getRoleBadgeColorAttribute(): string
    {
        return match ($this->role) {
            'admin' => 'badge-primary',
            'recruiter' => 'badge-success',
            'manager' => 'badge-info',
            default => 'badge-neutral',
        };
    }

    public function getRoleLabelAttribute(): string
    {
        return match ($this->role) {
            'manager' => 'Team Manager',
            'recruiter' => 'Recruiter',
            'admin' => 'Admin',
            default => ucfirst($this->role),
        };
    }
}
