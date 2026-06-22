<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Company;
use App\Models\Job;
use App\Models\Category;
use Illuminate\Support\Facades\DB; 
use Illuminate\Support\Facades\Mail;
use App\Mail\ApplicationStatusChanged;

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
       public function getCompanyCandidates(Request $request)
    {
        $user = $request->user();
        if (!$user) return response()->json(['success' => false, 'message' => 'Hết hạn phiên.'], 401);

        $company = \App\Models\Company::where('user_id', $user->id)->first();
        if (!$company) return response()->json(['success' => false, 'message' => 'Chưa cấu hình công ty.'], 404);

        // 1. Khởi tạo Query (Chú ý dấu chấm phẩy ở cuối dòng orderBy)
        $query = \Illuminate\Support\Facades\DB::table('jobs')
            ->join('applications', 'jobs.id', '=', 'applications.job_id')
            ->join('cv_files', 'applications.cv_file_id', '=', 'cv_files.id')
            ->join('candidates', 'cv_files.user_id', '=', 'candidates.user_id')
            ->where('jobs.company_id', $company->id)
            ->select(
                'applications.id',
                'candidates.full_name as name',
                'applications.status',
                'applications.applied_at as timeApplied',
                'jobs.title as jobTitle',
                'candidates.experience_years as exp',
                'candidates.education',
                'candidates.email',
                'cv_files.file_path',
                'applications.matching_score'
            )
            ->orderBy('applications.applied_at', 'desc'); 

        // 2. Chèn bộ lọc kỹ năng
        if ($request->has('skill') && $request->skill !== 'Tất cả') {
            $skillId = $request->skill; 

            $query->whereExists(function ($q) use ($skillId) {
                $q->select(\Illuminate\Support\Facades\DB::raw(1))
                    ->from('candidate_skill')
                    ->whereColumn('candidate_skill.candidate_id', 'candidates.id')
                    ->where('candidate_skill.skill_id', $skillId);
            });
        }

        // 3. Thực thi lấy dữ liệu
       $candidates = $query->get();

        // 4. KIỂM TRA: Nếu không có ứng viên nào, trả về mảng rỗng để không bị lỗi map
        if ($candidates->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => []
            ], 200);
        }

        // 5. Format dữ liệu
        $formattedCandidates = $candidates->map(function ($item) {
            $statusMap = [
                'pending' => 'Chờ duyệt',
                'viewed' => 'Đã xem',
                'interviewing' => 'Phỏng vấn',
                'accepted' => 'Nhận việc',
                'rejected' => 'Từ chối'
            ];

            return [
                'id' => $item->id,
                'name' => $item->name,
                'status' => $statusMap[$item->status] ?? $item->status,
                'timeApplied' => isset($item->timeApplied) ? date('d/m/Y H:i', strtotime($item->timeApplied)) : 'N/A',
                'jobTitle' => $item->jobTitle,
                'exp' => $item->exp,
                'education' => $item->education ?? 'Chưa cập nhật',
                'email' => $item->email,
                'file_path' => $item->file_path,
                'matchScore' => $item->matching_score,
                'skills' => [] 
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $formattedCandidates
        ], 200);
    }
        public function updateApplicationStatus(Request $request, $id)
    {
        // Xác thực tài khoản nhà tuyển dụng đang đăng nhập
        $user = $request->user();
        $company = DB::table('companies')->where('user_id', $user->id)->first();

        if (!$company) {
            return response()->json([
                'success' => false, 
                'message' => 'Tài khoản không có quyền thực hiện hành động này.'
            ], 403);
        }

        // Tên công ty động dùng làm tên người gửi thư
        $companyName = $company->company_name ?? 'Công ty của tôi'; 

        //  Tìm kiếm đơn ứng tuyển cần cập nhật trạng thái
        $application = DB::table('applications')->where('id', $id)->first();
        if (!$application) {
            return response()->json([
                'success' => false, 
                'message' => 'Đơn ứng tuyển không tồn tại.'
            ], 404);
        }

        //Tiến hành cập nhật trạng thái mới vào Database
        $newStatus = $request->status; // Trạng thái truyền từ ReactJS lên
        DB::table('applications')
            ->where('id', $id)
            ->update([
                'status' => $newStatus,
                'updated_at' => now()
            ]);

        // Lấy thông tin ứng viên liên kết với đơn ứng tuyển để lấy Email nhận thư
        // Kết nối qua bảng trung gian cv_files để tìm thông tin chính xác nhất của Candidate
        $candidate = DB::table('cv_files')
            ->join('candidates', 'cv_files.user_id', '=', 'candidates.user_id')
            ->where('cv_files.id', $application->cv_file_id)
            ->select('candidates.full_name', 'candidates.email')
            ->first();

        //  Kiểm tra nếu có email thì kích hoạt tiến trình gửi thư thật
        if ($candidate && $candidate->email) {
            try {
                Mail::to($candidate->email)->send(
                    new ApplicationStatusChanged($candidate, $newStatus, $companyName)
                );
            } catch (\Exception $e) {
                // Ghi nhận nhật ký lỗi nếu SMTP thất bại nhưng vẫn giữ trạng thái cập nhật database thành công
                \Illuminate\Support\Facades\Log::error('Lỗi gửi email SMTP: ' . $e->getMessage());
            }
        }

        return response()->json([
            'success' => true, 
            'message' => 'Cập nhật trạng thái và gửi email thông báo thành công!'
        ]);
    }
        public function getCandidates(Request $request) 
{
    //  Khởi tạo query từ bảng candidates của bạn
    $query = DB::table('candidates');

    //Lọc theo Kỹ năng (Sử dụng bảng trung gian candidate_skill)
    if ($request->has('skill') && $request->input('skill') !== 'Tất cả') {
        $skillId = $request->input('skill');

        $query->whereExists(function ($q) use ($skillId) {
            $q->select(DB::raw(1))
              ->from('candidate_skill')
              // Khớp id của bảng candidates với candidate_id của bảng trung gian
              ->whereColumn('candidate_skill.candidate_id', 'candidates.id') 
              ->where('candidate_skill.skill_id', $skillId);
        });
    }

    //  Lọc theo Kinh nghiệm (Dựa vào cột experience_years trong DB của bạn)
    if ($request->has('experience') && $request->input('experience') !== 'Tất cả') {
        $exp = $request->input('experience');
        if ($exp === 'fresher') {
            $query->where('candidates.experience_years', '<', 2);
        } elseif ($exp === 'junior') {
            $query->whereBetween('candidates.experience_years', [2, 4]);
        } elseif ($exp === 'senior') {
            $query->where('candidates.experience_years', '>', 4);
        }
    }

    //  Lọc theo Học vấn (Dựa vào cột education trong DB của bạn)
    if ($request->has('edu') && $request->input('edu') !== 'Tất cả') {
        $query->where('candidates.education', 'LIKE', '%' . $request->input('edu') . '%');
    }

    return response()->json($query->get());
}
}