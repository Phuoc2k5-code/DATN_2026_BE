<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;

class CategoryController extends Controller
{
    public static function getCategories()
{
    try {
        // Lấy danh sách gồm id và name, sắp xếp theo tên từ A-Z
        $categories = Category::select('id', 'name')->orderBy('name', 'asc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Lấy danh sách ngành nghề thành công!',
            'data' => $categories
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Lỗi khi lấy danh mục ngành nghề.',
            'error' => $e->getMessage()
        ], 500);
    }
}
}
