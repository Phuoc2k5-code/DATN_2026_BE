<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes; // 🚀 1. Thêm dòng này

class CvFile extends Model
{
    use SoftDeletes; // 🚀 2. Kích hoạt tính năng xóa mềm

    protected $dates = ['deleted_at']; // Định nghĩa cột thời gian xóa
    protected $table = 'cv_files';
    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'file_name',
        'file_path',
        'file_size',
        'type',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'uploaded_at' => 'datetime',
            'file_size' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}