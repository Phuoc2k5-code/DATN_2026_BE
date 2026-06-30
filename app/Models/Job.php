<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory; // 1. Thêm dòng này nếu chưa có

class Job extends Model
{
    use HasFactory;
    protected $table = 'jobs';

    protected $fillable = [
        'company_id',
        'category_id',
        'title',
        'level',
        'salary_min',
        'salary_max',
        'is_negotiable',
        'location',
        'description',
        'requirements',
        'benefits',
        'expired_at',
        'status',
        'reject_reason'
    ];

    protected function casts(): array
    {
        return [
            'is_negotiable' => 'boolean',
            'expired_at' => 'datetime',
            'salary_min' => 'integer',
            'salary_max' => 'integer',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'job_skill', 'job_id', 'skill_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class, 'job_id', 'id');
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(JobClick::class, 'job_id', 'id');
    }

    public function wishlist(): HasMany
    {
        return $this->hasMany(Wishlist::class, 'job_id', 'id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'job_id', 'id');
    }
    
}