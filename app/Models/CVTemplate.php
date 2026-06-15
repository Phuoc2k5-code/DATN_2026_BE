<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
class CvTemplate extends Model
{
    use SoftDeletes;
    protected $table = 'cv_templates';

    protected $fillable = [
        'name',
        'description',
        'file_path',
    ];

    public function candidates(): HasMany
    {
        return $this->hasMany(Candidate::class, 'cv_template_id', 'id');
    }
}