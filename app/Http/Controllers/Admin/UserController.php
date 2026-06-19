<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Lấy danh sách user có role khác admin
     */
    public function getUsersExceptAdmin(Request $request){
        $query = User::withTrashed()->where('role', '!=', 'admin');

        // 2. Tìm kiếm toàn bộ hệ thống theo tên hoặc email (nếu có truyền lên)
        if ($request->has('search') && !empty($request->input('search'))) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%");
            });
        }

        // 3. Lọc theo Vai trò (nếu có truyền lên và khác 'all')
        if ($request->has('role') && $request->input('role') !== 'all') {
            $query->where('role', $request->input('role'));
        }

        // 4. Phân trang kết quả sau khi đã lọc (Mỗi trang 10 user)
        $users = $query->latest()->paginate(10);

        // 5. Trả về kèm thông tin phân trang chuẩn
        return response()->json([
            'success' => true,
            'message' => 'Lấy danh sách thành công.',
            'data' => $users->items(),
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'total' => $users->total(),
                'per_page' => $users->perPage(),
            ]
        ], 200);
    }
    // hàm Khóa tài khoản
    public function lockUser($id)
    {
        $user = User::findOrFail($id);

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => "Đã khóa tài khoản của user {$user->name} thành công."
        ], 200);
    }
    // Hàm Mở khóa tài khoản (Restore)
    public function unlockUser($id)
    {
        $user = User::withTrashed()->findOrFail($id);

        $user->restore();

        return response()->json([
            'success' => true,
            'message' => "Đã mở khóa tài khoản của user {$user->name} thành công."
        ], 200);
    }

}