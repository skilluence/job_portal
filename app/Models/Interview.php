<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Interview extends Model
{
    protected $fillable = [
        'candidate_id',
        'recruiter_id',
        'role',
        'company_name',
        'company_domain',
        'mail_date',
        'mail_time',
        'interview_type',
        'interview_status',
        'remark',
        'scheduled_date',
        'scheduled_time',
        'scheduled_timezone',
        'created_by',
    ];

    protected $casts = [
        'mail_date'      => 'date',
        'scheduled_date' => 'date',
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
