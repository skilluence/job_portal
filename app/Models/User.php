<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
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
        ];
    }

    public function candidates()
    {
        return $this->hasMany(Candidate::class, 'recruiter_id');
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

    public function getInitialsAttribute(): string
    {
        $words = preg_split('/\s+/', trim($this->name)) ?: ['U'];
        $first = strtoupper(substr($words[0], 0, 1));
        $second = isset($words[1]) ? strtoupper(substr($words[1], 0, 1)) : '';

        return $first . $second;
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
