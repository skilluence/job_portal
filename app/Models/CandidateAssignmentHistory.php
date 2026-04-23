<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CandidateAssignmentHistory extends Model
{
    protected $fillable = [
        'candidate_id',
        'from_recruiter_id',
        'from_team_manager_id',
        'to_recruiter_id',
        'to_team_manager_id',
        'changed_by',
        'action',
        'note',
    ];

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function changer()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public function fromRecruiter()
    {
        return $this->belongsTo(User::class, 'from_recruiter_id');
    }

    public function fromTeamManager()
    {
        return $this->belongsTo(User::class, 'from_team_manager_id');
    }

    public function toRecruiter()
    {
        return $this->belongsTo(User::class, 'to_recruiter_id');
    }

    public function toTeamManager()
    {
        return $this->belongsTo(User::class, 'to_team_manager_id');
    }
}
