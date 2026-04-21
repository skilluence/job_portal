<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Candidate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        // Core identity
        'full_name',
        'first_name',
        'middle_name',
        'last_name',
        'date_of_birth',
        'gender',
        'nationality',
        'email_id',
        'phone_number',
        'domain',
        'sub_domain',
        'ssn',
        'date_of_arrival_usa',
        'current_salary',
        'expected_salary',

        // Address
        'street_address',
        'apartment_unit',
        'city',
        'state_province',
        'zip_code',
        'country',

        // Visa
        'visa_immigration_status',
        'work_auth_status',
        'open_to_relocation',
        'preferred_city',
        'visa_expiry_date',

        // Marketing
        'marketing_phone',
        'marketing_email',
        'marketing_email_password',
        'marketing_linkedin_id',
        'marketing_linkedin_password',

        // Education — Masters
        'masters_university',
        'masters_program',
        'masters_start',
        'masters_end',
        'masters_country',

        // Education — Bachelors
        'bachelors_university',
        'bachelors_program',
        'bachelors_start',
        'bachelors_end',
        'bachelors_country',

        // Professional
        'github_url',
        'linkedin_url',
        'portfolio_url',
        'speedy_apply_json_path',
        'recruiter_notes',

        // Portal / Admin
        'no_of_applications',
        'interviews_count',
        'status',
        'interview_status',
        'recruiter_id',
        'team_manager_id',
        'created_by',
        'login_password',
        'login_password_plain',
        'cv_file_path',
        'candidate_details_file_path',
        'agreement_file_path',
    ];

    protected $hidden = [
        'login_password',
        'login_password_plain',
        'ssn',
        'marketing_email_password',
        'marketing_linkedin_password',
    ];

    protected $casts = [
        'date_of_birth'       => 'date',
        'date_of_arrival_usa' => 'date',
        'visa_expiry_date'    => 'date',
        'masters_start'       => 'date',
        'masters_end'         => 'date',
        'bachelors_start'     => 'date',
        'bachelors_end'       => 'date',
        'open_to_relocation'  => 'boolean',
        'current_salary'      => 'decimal:2',
        'expected_salary'     => 'decimal:2',
        'login_password_plain'         => 'encrypted',
        'ssn'                          => 'encrypted',
        'marketing_email_password'     => 'encrypted',
        'marketing_linkedin_password'  => 'encrypted',
    ];

    public function recruiter()
    {
        return $this->belongsTo(User::class, 'recruiter_id');
    }

    public function teamManager()
    {
        return $this->belongsTo(User::class, 'team_manager_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function resumes()
    {
        return $this->hasMany(CandidateResume::class)->latest();
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'active'   => 'badge-success',
            'enrolled' => 'badge-info',
            'interview'=> 'badge-warning',
            'offer'    => 'badge-primary',
            'placed'   => 'badge-success',
            'onhold'   => 'badge-warning',
            'inactive' => 'badge-danger',
            default    => 'badge-neutral',
        };
    }

    public function scopeSearch($query, string $term)
    {
        $like = '%' . str_replace('%', '\%', $term) . '%';

        return $query->where(function ($q) use ($like) {
            $q->where('full_name', 'like', $like)
                ->orWhere('email_id', 'like', $like)
                ->orWhere('phone_number', 'like', $like)
                ->orWhere('domain', 'like', $like)
                ->orWhere('city', 'like', $like)
                ->orWhere('visa_immigration_status', 'like', $like);
        });
    }
}
