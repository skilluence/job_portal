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
        'temporary_starts_at',
        'temporary_ends_at',
        'temporary_activated_at',
        'temporary_restored_at',
        'restore_recruiter_id',
        'restore_team_manager_id',
    ];

    protected $casts = [
        'temporary_starts_at' => 'datetime',
        'temporary_ends_at' => 'datetime',
        'temporary_activated_at' => 'datetime',
        'temporary_restored_at' => 'datetime',
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

    public function restoreRecruiter()
    {
        return $this->belongsTo(User::class, 'restore_recruiter_id');
    }

    public function restoreTeamManager()
    {
        return $this->belongsTo(User::class, 'restore_team_manager_id');
    }
}
