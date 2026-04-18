<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CandidateResume extends Model
{
    protected $fillable = [
        'candidate_id',
        'designation',
        'file_path',
        'original_filename',
    ];

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }
}
