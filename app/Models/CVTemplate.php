<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CvTemplate extends Model
{
    protected $table = 'cv_templates';

    protected $fillable = [
        'name',
        'thumbnail_url',
        'file_path',
    ];

    public function candidates(): HasMany
    {
        return $this->hasMany(Candidate::class, 'cv_template_id', 'id');
    }
}