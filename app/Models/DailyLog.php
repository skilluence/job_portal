<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyLog extends Model
{
    protected $fillable = [
        'candidate_id',
        'recruiter_id',
        'log_date',
        'applications',
        'assistant_count',
        'interview_count',
        'remark',
        'created_by',
    ];

    protected $casts = [
        'log_date' => 'date',
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
