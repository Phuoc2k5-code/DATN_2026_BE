<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;


class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function company(): HasOne
    {
        return $this->hasOne(Company::class, 'user_id', 'id');
    }

    // Mối quan hệ 1-1 với Ứng viên
    public function candidate(): HasOne
    {
        return $this->hasOne(Candidate::class, 'user_id', 'id');
    }

     public function cvFiles(): HasMany
    {
        return $this->hasMany(CvFile::class, 'user_id', 'id');
    }

     public function applications(): HasMany
    {
        return $this->hasMany(Application::class, 'user_id', 'id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'user_id', 'id');
    }
}
