<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Skill;
use Illuminate\Support\Str;

class SkillController extends Controller
{
    // [GET] Lấy danh sách kỹ năng
    public static function getSkills()
{
    try {
        $skills = Skill::withCount('jobs')
            ->orderBy('name', 'asc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Lấy danh sách kỹ năng thành công!',
            'data' => $skills->items(), // Trả về mảng danh sách bản ghi hiện tại
            'pagination' => [
                'total' => $skills->total(),
                'per_page' => $skills->perPage(),
                'current_page' => $skills->currentPage(),
                'last_page' => $skills->lastPage(),
                'next_page_url' => $skills->nextPageUrl(),
                'prev_page_url' => $skills->previousPageUrl(),
            ]
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Lỗi khi lấy danh mục kỹ năng.',
            'error' => $e->getMessage()
        ], 500);
    }
}

    private function cleanString($text)
    {
        if (empty($text)) return '';
        return preg_replace('/\s+/', '', Str::lower($text));
    }

    // [POST] Tạo kỹ năng mới
    public function createSkill(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255'
        ]);

        $skillName = trim($request->name);
        $cleanInput = $this->cleanString($skillName);

        $existingSkill = Skill::whereRaw('LOWER(REPLACE(name, " ", "")) = ?', [$cleanInput])->first();

        if ($existingSkill) {
            return response()->json([
                'success' => true,
                'message' => 'Kỹ năng đã tồn tại',
                'data' => $existingSkill
            ], 200);
        }

        $slug = Str::slug($skillName, '-');
        $slugCount = Skill::where('slug', 'like', "{$slug}%")->count();
        if ($slugCount > 0) {
            $slug = $slug . '-' . ($slugCount + 1);
        }

        $newSkill = Skill::create([
            'name' => $skillName,
            'slug' => $slug
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Đã thêm kỹ năng mới thành công!',
            'data' => $newSkill
        ], 200);
    }

    // [PUT/PATCH] Cập nhật kỹ năng
    public function updateSkill(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255'
        ]);

        $skill = Skill::find($id);
        if (!$skill) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy kỹ năng!'], 404);
        }

        $skillName = trim($request->name);
        $cleanInput = $this->cleanString($skillName);

        // Check trùng tên với các bản ghi khác bản ghi hiện tại
        $existingSkill = Skill::whereRaw('LOWER(REPLACE(name, " ", "")) = ?', [$cleanInput])
            ->where('id', '!=', $id)
            ->first();

        if ($existingSkill) {
            return response()->json(['success' => false, 'message' => 'Tên kỹ năng này đã tồn tại ở bản ghi khác!'], 400);
        }

        // Cập nhật lại slug mới
        $slug = Str::slug($skillName, '-');
        $slugCount = Skill::where('slug', 'like', "{$slug}%")->where('id', '!=', $id)->count();
        if ($slugCount > 0) {
            $slug = $slug . '-' . ($slugCount + 1);
        }

        $skill->update([
            'name' => $skillName,
            'slug' => $slug
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật kỹ năng thành công!',
            'data' => $skill
        ], 200);
    }

    // [DELETE] Xóa kỹ năng
    public function destroySkill($id)
    {
        $skill = Skill::find($id);
        if (!$skill) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy kỹ năng!'], 404);
        }

        $skill->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa kỹ năng thành công!'
        ], 200);
    }
}