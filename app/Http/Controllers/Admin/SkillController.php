<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Skill;

class SkillController extends Controller
{
    public static function getSkills()
{
    try {
        // Lấy danh sách gồm id và name, sắp xếp theo tên từ A-Z
        $skills = Skill::select('id', 'name')->orderBy('name', 'asc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Lấy danh sách kỹ năng thành công!',
            'data' => $skills
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Lỗi khi lấy danh mục kỹ năng.',
            'error' => $e->getMessage()
        ], 500);
    }
}
}
