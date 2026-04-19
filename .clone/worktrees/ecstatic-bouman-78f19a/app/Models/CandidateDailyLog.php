<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CandidateDailyLog extends Model
{
    protected $fillable = [
        'candidate_id',
        'log_date',
        'applications',
        'assistant',
        'interviews',
        'notes',
        'logged_by',
    ];

    protected $casts = [
        'log_date' => 'date',
    ];

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function logger()
    {
        return $this->belongsTo(User::class, 'logged_by');
    }
}
