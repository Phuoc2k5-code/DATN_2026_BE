<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobClick extends Model
{
    protected $table = 'job_clicks';

    protected $fillable = [
        'job_id',
        'click_date',
        'click_count',
    ];

    protected function casts(): array
    {
        return [
            'click_date' => 'date',
            'click_count' => 'integer',
        ];
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class, 'job_id', 'id');
    }
}