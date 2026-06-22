<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Application extends Model
{
    protected $table = 'applications';

    protected $fillable = [
        'job_id',
        'user_id',
        'cv_file_id',
        'description',
        'matching_score',
        'status',
        'status_details',
        'applied_at',
    ];

    protected function casts(): array
    {
        return [
            'applied_at' => 'datetime',
            'matching_score' => 'float',
            'status_details' => 'array'
        ];
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class, 'job_id', 'id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function cvFile(): BelongsTo
    {
        return $this->belongsTo(CvFile::class, 'cv_file_id', 'id');
    }

}