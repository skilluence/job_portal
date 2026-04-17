<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Candidate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'full_name',
        'enrollment_date',
        'sales_agent',
        'no_of_applications',
        'status',
        'recruiter_id',
        'linkedin_id',
        'linkedin_password',
        'email_id',
        'email_password',
        'linkedin_updated',
        'address',
        'profile',
        'notes',
        'interviews_count',
        'created_by',
        'login_password',
        'login_password_plain',
        'cv_file_path',
        'candidate_details_file_path',
    ];

    protected $hidden = ['login_password', 'login_password_plain'];

    protected $casts = [
        'enrollment_date' => 'date',
        'linkedin_updated' => 'date',
        'login_password_plain' => 'encrypted',
    ];

    public function recruiter()
    {
        return $this->belongsTo(User::class, 'recruiter_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'active' => 'badge-success',
            'enrolled' => 'badge-info',
            'interview' => 'badge-warning',
            'offer' => 'badge-primary',
            'placed' => 'badge-success',
            'onhold' => 'badge-warning',
            'inactive' => 'badge-danger',
            default => 'badge-neutral',
        };
    }

    public function getProfileShortAttribute(): string
    {
        return $this->profile ? Str::limit($this->profile, 40) : '-';
    }

    public function scopeSearch($query, string $term)
    {
        $like = '%' . str_replace('%', '\%', $term) . '%';

        return $query->where(function ($q) use ($like) {
            $q->where('full_name', 'like', $like)
                ->orWhere('email_id', 'like', $like)
                ->orWhere('profile', 'like', $like)
                ->orWhere('sales_agent', 'like', $like);
        });
    }
}
