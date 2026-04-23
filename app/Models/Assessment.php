<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Assessment extends Model
{
    protected $fillable = [
        'candidate_id',
        'recruiter_id',
        'assessment_date',
        'assessment_time',
        'role',
        'company_name',
        'domain',
        'company_website_url',
        'mail_date',
        'mail_time',
        'assessment_type',
        'remark',
        'created_by',
    ];

    protected $casts = [
        'assessment_date' => 'date',
        'mail_date' => 'date',
    ];

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function recruiter()
    {
        return $this->belongsTo(User::class, 'recruiter_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
