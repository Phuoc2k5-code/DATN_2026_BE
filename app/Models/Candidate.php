<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory; // 1. Thêm dòng này nếu chưa có

class Candidate extends Model
{
    use HasFactory;
    protected $table = 'candidates';

    protected $fillable = [
        'user_id',
        'cv_template_id',
        'category_id',
        'title',
        'full_name',
        'gender',
        'birthday',
        'phone',
        'email',
        'address',
        'avatar_url',
        'summary',
        'objective',
        'links',
        'experience_years',
        'project',
        'education',
        'contact_reference',
    ];

    // Áp dụng tính năng tự động chuyển đổi chuỗi JSON của Laravel 13 siêu mượt
    protected function casts(): array
    {
        return [
            'links' => 'array', 
            'birthday' => 'date',
            'experience_years' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function cvTemplate(): BelongsTo
    {
        return $this->belongsTo(CvTemplate::class, 'cv_template_id', 'id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'candidate_skill', 'candidate_id', 'skill_id');
    }

    public function savedJobs(): BelongsToMany
    {
        return $this->belongsToMany(Job::class, 'wishlists', 'candidate_id', 'job_id')->withTimestamps();
    }
}