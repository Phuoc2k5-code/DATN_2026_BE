<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    // [GET] Lấy danh sách ngành nghề
    public static function getCategories()
{
    try {
        // 1. withCount('jobs') đếm số bài đăng thuộc ngành nghề này
        // 2. Tối ưu select cột cần thiết
        // 3. Phân trang 15 bản ghi/trang (Laravel tự động loại trừ các bản ghi đã xóa mềm)
        $categories = Category::withCount('jobs')
            ->orderBy('name', 'asc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Lấy danh sách ngành nghề thành công!',
            'data' => $categories->items(), // Trả về mảng danh sách data trang hiện tại
            'pagination' => [
                'total' => $categories->total(),
                'per_page' => $categories->perPage(),
                'current_page' => $categories->currentPage(),
                'last_page' => $categories->lastPage(),
                'next_page_url' => $categories->nextPageUrl(),
                'prev_page_url' => $categories->previousPageUrl(),
            ]
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Lỗi khi lấy danh mục ngành nghề.',
            'error' => $e->getMessage()
        ], 500);
    }
}

    private function cleanString($text)
    {
        if (empty($text)) return '';
        return preg_replace('/\s+/', '', Str::lower($text));
    }

    // [POST] Tạo danh mục mới
    public function createCategory(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255'
        ]);

        $categoryName = trim($request->name);
        $cleanInput = $this->cleanString($categoryName);

        $existingCategory = Category::whereRaw('LOWER(REPLACE(name, " ", "")) = ?', [$cleanInput])->first();

        if ($existingCategory) {
            return response()->json([
                'success' => true,
                'message' => 'Danh mục đã tồn tại',
                'data' => $existingCategory
            ], 200);
        }

        $slug = Str::slug($categoryName, '-');
        $slugCount = Category::where('slug', 'like', "{$slug}%")->count();
        if ($slugCount > 0) {
            $slug = $slug . '-' . ($slugCount + 1);
        }

        $newCategory = Category::create([
            'name' => $categoryName,
            'slug' => $slug
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Đã thêm danh mục mới thành công!',
            'data' => $newCategory
        ], 200);
    }

    // [PUT/PATCH] Cập nhật danh mục
    public function updateCategory(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255'
        ]);

        $category = Category::find($id);
        if (!$category) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy danh mục!'], 404);
        }

        $categoryName = trim($request->name);
        $cleanInput = $this->cleanString($categoryName);

        // Check trùng với các thằng KHÁC thằng đang sửa
        $existingCategory = Category::whereRaw('LOWER(REPLACE(name, " ", "")) = ?', [$cleanInput])
            ->where('id', '!=', $id)
            ->first();

        if ($existingCategory) {
            return response()->json(['success' => false, 'message' => 'Tên danh mục này đã tồn tại ở một bản ghi khác!'], 400);
        }

        // Tạo lại slug mới dựa theo tên mới
        $slug = Str::slug($categoryName, '-');
        $slugCount = Category::where('slug', 'like', "{$slug}%")->where('id', '!=', $id)->count();
        if ($slugCount > 0) {
            $slug = $slug . '-' . ($slugCount + 1);
        }

        $category->update([
            'name' => $categoryName,
            'slug' => $slug
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật danh mục thành công!',
            'data' => $category
        ], 200);
    }

    // [DELETE] Xóa danh mục
    public function destroyCategory($id)
    {
        $category = Category::find($id);
        if (!$category) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy danh mục!'], 404);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa danh mục thành công!'
        ], 200);
    }
}