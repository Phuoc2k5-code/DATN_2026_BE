<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Application extends Model
{
    protected $table = 'applications';

    protected $fillable = [
        'job_id',
        'candidate_id',
        'cv_type',
        'cv_file_id',
        'description',
        'matching_score',
        'status',
        'applied_at',
    ];

    protected function casts(): array
    {
        return [
            'applied_at' => 'datetime',
            'matching_score' => 'float',
        ];
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class, 'job_id', 'id');
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class, 'candidate_id', 'id');
    }

    public function cvFile(): BelongsTo
    {
        return $this->belongsTo(CvFile::class, 'cv_file_id', 'id');
    }
}