<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;


class Skill extends Model
{
    use SoftDeletes;
    protected $table = 'skills';

    protected $fillable = ['name', 'slug'];

    public function jobs(): BelongsToMany
    {
        return $this->belongsToMany(Job::class, 'job_skill', 'skill_id', 'job_id');
    }

    public function candidates(): BelongsToMany
    {
        return $this->belongsToMany(Candidate::class, 'candidate_skill', 'skill_id', 'candidate_id');
    }
}