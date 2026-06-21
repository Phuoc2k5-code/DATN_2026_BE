<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Company;
use App\Models\Job;
use App\Models\Category;

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
      public function getOwnCompanyJobs(Request $request)
    {
    $user = $request->user(); 
    if (!$user) {
        return response()->json(['success' => false, 'message' => 'Phiên đăng nhập đã hết hạn.'], 401);
    }

    $company = Company::where('user_id', $user->id)->first();
    if (!$company) {
        return response()->json(['success' => false, 'message' => 'Chưa cấu hình thông tin doanh nghiệp.'], 404);
    }

    $jobs = Job::where('company_id', $company->id)
        ->leftJoin('categories', 'jobs.category_id', '=', 'categories.id')
        
        // Cú pháp chọn lấy mọi cột của jobs và lấy tên category
        ->select('jobs.*', 'categories.name as category_name')
        
        // 1. Format lại ngày hết hạn
        ->selectRaw('DATE_FORMAT(jobs.expired_at, "%d/%m/%Y") as deadline')
        
        // 2. Tự động phiên dịch Trạng thái từ Database (Tiếng Anh -> Tiếng Việt)
        ->selectRaw('
            CASE 
                WHEN jobs.status = "active" THEN "Vận hành"
                WHEN jobs.status = "closed" THEN "Tạm đóng"
                WHEN jobs.status = "pending" THEN "Chờ duyệt"
                ELSE jobs.status 
            END as status
        ')
        
        // 3. Đếm tổng lượt xem
        ->selectRaw('(SELECT COALESCE(SUM(click_count), 0) FROM job_clicks WHERE job_clicks.job_id = jobs.id) as views')
        
        // 4. Đếm tổng số lượng CV nộp vào
        ->selectRaw('(SELECT COUNT(*) FROM applications WHERE applications.job_id = jobs.id) as applicants')
        
        ->orderBy('jobs.created_at', 'desc')
        ->get();

    return response()->json([
        'success' => true,
        'data' => $jobs
    ], 200);
    }
        public function toggleJobStatus(Request $request, $id)
    {
        // 1. Xác thực tài khoản nhà tuyển dụng
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Phiên đăng nhập đã hết hạn.'], 401);
        }

        $company = Company::where('user_id', $user->id)->first();
        if (!$company) {
            return response()->json(['success' => false, 'message' => 'Chưa cấu hình công ty.'], 404);
        }

        // 2. Tìm bài đăng tuyển dụng theo ID (Bảo mật: Phải thuộc đúng công ty này)
        $job = Job::where('id', $id)->where('company_id', $company->id)->first();

        if (!$job) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy tin tuyển dụng.'], 404);
        }

        // 3. Chặn thao tác nếu tin đang ở trạng thái Chờ duyệt (pending)
        if ($job->status === 'pending') {
            return response()->json(['success' => false, 'message' => 'Tin đang chờ Admin duyệt, không thể thay đổi.'], 400);
        }

        // 4. Thực hiện đảo ngược trạng thái (active <-> closed)
        $job->status = ($job->status === 'active') ? 'closed' : 'active';
        $job->save();

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật trạng thái thành công!'
        ], 200);
    }
        public function extendJob(Request $request, $id)
    {
        $user = $request->user();
        if (!$user) return response()->json(['success' => false, 'message' => 'Hết hạn phiên.'], 401);

        $company = Company::where('user_id', $user->id)->first();
        if (!$company) return response()->json(['success' => false, 'message' => 'Chưa cấu hình công ty.'], 404);

        $job = Job::where('id', $id)->where('company_id', $company->id)->first();
        if (!$job) return response()->json(['success' => false, 'message' => 'Không tìm thấy tin tuyển dụng.'], 404);

        // Kiểm tra dữ liệu ngày tháng gửi lên
        $request->validate([
            'new_deadline' => 'required|date|after:today',
        ], [
            'new_deadline.required' => 'Vui lòng chọn ngày gia hạn.',
            'new_deadline.after' => 'Ngày gia hạn phải tính từ ngày mai trở đi.'
        ]);

        // Cập nhật ngày hết hạn mới (Mặc định cho hết hạn vào 23:59:59 của ngày đó)
        $job->expired_at = $request->new_deadline . ' 23:59:59';
        
        if ($job->status === 'closed') {
            $job->status = 'active';
        }
        
        $job->save();

        return response()->json([
            'success' => true, 
            'message' => 'Gia hạn thành công! Tin đã được cập nhật.'
        ], 200);
    }
        public function storeJob(Request $request)
    {
        // Xác thực người dùng và công ty
        $user = $request->user();
        if (!$user) return response()->json(['success' => false, 'message' => 'Hết hạn phiên.'], 401);

        $company = Company::where('user_id', $user->id)->first();
        if (!$company) return response()->json(['success' => false, 'message' => 'Chưa cấu hình công ty.'], 404);

        // Kiểm tra dữ liệu Form gửi lên
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category_id' => 'required|integer',
            'level' => 'required|string',
            'salary_min' => 'nullable|numeric',
            'salary_max' => 'nullable|numeric',
            'is_negotiable' => 'boolean',
            'location' => 'required|string',
            'description' => 'required|string',
            'requirements' => 'required|string',
            'benefits' => 'nullable|string',
            'expired_at' => 'required|date|after:today',
        ]);

        // Tạo bản ghi mới trong bảng jobs
        $job = new Job();
        $job->company_id = $company->id;
        $job->category_id = $validated['category_id'];
        $job->title = $validated['title'];
        $job->level = $validated['level'];
        $job->salary_min = $validated['salary_min'];
        $job->salary_max = $validated['salary_max'];
        $job->is_negotiable = $validated['is_negotiable'] ?? 0;
        $job->location = $validated['location'];
        $job->description = $validated['description'];
        $job->requirements = $validated['requirements'];
        $job->benefits = $validated['benefits'] ?? null;
        $job->expired_at = $validated['expired_at'] . ' 23:59:59';
        $job->status = 'pending'; // Tin mới đăng tự động đưa vào trạng thái Chờ duyệt
        $job->save();

        return response()->json([
            'success' => true,
            'message' => 'Đăng tin thành công! Vui lòng chờ Quản trị viên phê duyệt.'
        ], 201);
    }
        public function updateJob(Request $request, $id)
    {
        $user = $request->user();
        if (!$user) return response()->json(['success' => false, 'message' => 'Hết hạn phiên.'], 401);

        $company = Company::where('user_id', $user->id)->first();
        if (!$company) return response()->json(['success' => false, 'message' => 'Chưa cấu hình công ty.'], 404);

        // Tìm bài đăng cần sửa
        $job = Job::where('id', $id)->where('company_id', $company->id)->first();
        if (!$job) return response()->json(['success' => false, 'message' => 'Không tìm thấy tin tuyển dụng.'], 404);

        // Kiểm tra dữ liệu (Không cần validate expired_at vì mình đã có chức năng Gia hạn riêng)
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category_id' => 'required|integer',
            'level' => 'required|string',
            'salary_min' => 'nullable|numeric',
            'salary_max' => 'nullable|numeric',
            'is_negotiable' => 'boolean',
            'location' => 'required|string',
            'description' => 'required|string',
            'requirements' => 'required|string',
            'benefits' => 'nullable|string',
        ]);

        // Cập nhật dữ liệu
        $job->category_id = $validated['category_id'];
        $job->title = $validated['title'];
        $job->level = $validated['level'];
        $job->salary_min = $validated['salary_min'];
        $job->salary_max = $validated['salary_max'];
        $job->is_negotiable = $validated['is_negotiable'] ?? 0;
        $job->location = $validated['location'];
        $job->description = $validated['description'];
        $job->requirements = $validated['requirements'];
        $job->benefits = $validated['benefits'] ?? null;
        
        // ĐIỂM QUAN TRỌNG: Đổi trạng thái về chờ duyệt để Admin kiểm tra lại nội dung mới
        $job->status = 'pending'; 

        $job->save();

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật thành công! Tin của bạn đã được chuyển về trạng thái Chờ duyệt.'
        ], 200);
    }
}