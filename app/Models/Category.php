<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;


class Category extends Model
{
    use SoftDeletes;
    protected $table = 'categories';

    protected $fillable = ['name', 'slug'];

    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class, 'category_id', 'id');
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(Candidate::class, 'category_id', 'id');
    }
}