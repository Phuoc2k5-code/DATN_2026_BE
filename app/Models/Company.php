<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory; // 1. Thêm dòng này nếu chưa có

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
        'is_verified',
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
}