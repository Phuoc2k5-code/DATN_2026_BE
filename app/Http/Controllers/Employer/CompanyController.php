<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Company; // Đảm bảo bạn đã tạo Model Company kết nối đến bảng companies

class CompanyController extends Controller
{
    public function getOwnCompany(Request $request)
    {
        // 1. Lấy thông tin User đang đăng nhập từ Token Sanctum
        $user = $request->user(); 

        // 2. Tìm công ty trong bảng `companies` có `user_id` trùng với ID người dùng này
        $company = Company::where('user_id', $user->id)->first();

        // Nếu tài khoản này chưa tạo hồ sơ công ty
        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Tài khoản này chưa cấu hình thông tin doanh nghiệp.'
            ], 404);
        }

        // 3. Trả về đúng cấu trúc JSON mà Frontend đang cần đón (success và data)
        return response()->json([
            'success' => true,
            'data' => $company
        ], 200);
    }
    public function updateOwnCompany(Request $request)
    {
        $user = $request->user();

        // 1. Tìm hồ sơ công ty của User này, nếu chưa có thì tự tạo mới một dòng dữ liệu
        $company = Company::where('user_id', $user->id)->first();
        if (!$company) {
            $company = new Company();
            $company->user_id = $user->id;
        }

        // 2. Gán các thông tin text từ form vào các cột tương ứng trong CSDL
        $company->company_name     = $request->input('company_name');
        $company->tax_code         = $request->input('tax_code');
        $company->website_url      = $request->input('website_url');
        $company->industry          = $request->input('industry');
        $company->size              = $request->input('size');
        $company->founded_year     = $request->input('founded_year');
        $company->address           = $request->input('address');
        $company->description       = $request->input('description');
        $company->benefits          = $request->input('benefits');

        // 3. Xử lý tải file Logo (nếu người dùng chọn ảnh mới)
        if ($request->hasFile('logo_url')) {
            $file = $request->file('logo_url');
            $filename = time() . '_logo_' . $file->getClientOriginalName();
            // Lưu vào thư mục public/uploads/logos để Frontend có thể đọc được công khai
            $file->move(public_path('uploads/logos'), $filename);
            $company->logo_url = 'uploads/logos/' . $filename;
        }

        // 4. Xử lý tải file Giấy phép kinh doanh (nếu chọn file mới)
        if ($request->hasFile('business_license')) {
            $file = $request->file('business_license');
            $filename = time() . '_license_' . $file->getClientOriginalName();
            // Lưu vào thư mục public/uploads/licenses
            $file->move(public_path('uploads/licenses'), $filename);
            $company->business_license = 'uploads/licenses/' . $filename;
        }

        // 5. Thực thi lệnh lưu xuống MySQL
        $company->save();

        // 6. Trả về thông báo thành công cho React Frontend nhận lệnh
        return response()->json([
            'success' => true,
            'message' => 'Cập nhật thông tin công ty thành công!',
            'data' => $company
        ], 200);
    }
}