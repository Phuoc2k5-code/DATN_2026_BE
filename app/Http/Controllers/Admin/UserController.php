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
    public function getUsersExceptAdmin()
    {
        // 1. Lấy danh sách user với điều kiện role khác 'admin'
        // Dùng dấu '<>' hoặc '!=' đều được bạn nhé
        $users = User::withTrashed()->where('role', '!=', 'admin')->get();

        // 2. Trả về dữ liệu dạng JSON kèm status code 200 (OK)
        return response()->json([
            'success' => true,
            'message' => 'Lấy danh sách thành công.',
            'data'    => $users
        ], 200);
    }

    public function lockUser($id)
    {
        $user = User::findOrFail($id);

        // Hàm delete() lúc này sẽ không xóa hẳn trong DB mà chỉ nạp data vào cột deleted_at
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => "Đã khóa tài khoản của user {$user->name} thành công."
        ], 200);
    }

    /**
     * API Mở khóa tài khoản (Restore)
     */
    public function unlockUser($id)
    {
        // Vì tài khoản đã bị khóa (soft deleted), ta phải dùng 'withTrashed' mới tìm ra được
        $user = User::withTrashed()->findOrFail($id);

        // Khôi phục lại tài khoản (đưa cột deleted_at về lại null)
        $user->restore();

        return response()->json([
            'success' => true,
            'message' => "Đã mở khóa tài khoản của user {$user->name} thành công."
        ], 200);
    }

}