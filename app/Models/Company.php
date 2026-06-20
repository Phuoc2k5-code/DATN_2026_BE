<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory; 
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Company extends Model
{
    use HasFactory;
    protected $table = 'companies';

    protected $fillable = [
        'user_id',
        'company_name',
        'tax_code',
        'business_license',
        'website_url',
        'description',
        'industry', 
        'size', 
        'founded_year', 
        'address', 
        'benefits',
        'is_verified',
        'reject_reason'
    ];

    protected function casts(): array
    {
        return [
            'is_verified' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class, 'company_id', 'id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'company_id', 'id');
    }

    public function applications(): HasManyThrough
{
    return $this->hasManyThrough(Application::class, Job::class, 'company_id', 'job_id');
}
}