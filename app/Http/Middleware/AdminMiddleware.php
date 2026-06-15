<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Kiểm tra xem user đã đăng nhập chưa
        // 2. Kiểm tra xem role có phải là admin không (thay 'admin' bằng logic của bạn nếu cần)
        if (auth()->check() && auth()->user()->role === 'admin') {
            
            // Nếu đúng là admin, cho phép "đi tiếp" vào Controller
            return $next($request);
            
        }

        // Nếu KHÔNG PHẢI admin, chặn lại và trả về lỗi 403 (Forbidden) kèm thông báo
        return response()->json([
            'success' => false,
            'message' => 'Bạn không có quyền truy cập vào chức năng này!'
        ], 403);
    }
}