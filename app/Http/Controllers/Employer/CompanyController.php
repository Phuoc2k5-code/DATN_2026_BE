<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Company;
use App\Models\Job;
use App\Models\Category;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
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
        $company->company_name = $request->input('company_name');
        $company->tax_code = $request->input('tax_code');
        $company->website_url = $request->input('website_url');
        $company->industry = $request->input('industry');
        $company->size = $request->input('size');
        $company->founded_year = $request->input('founded_year');
        $company->address = $request->input('address');
        $company->description = $request->input('description');
        $company->benefits = $request->input('benefits');

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

        // SỬA ĐỔI: Sử dụng Model Job kết hợp với `with('skills')` để lấy kèm kỹ năng
        $jobs = Job::where('company_id', $company->id)
            ->with([
                'skills' => function ($query) {
                    $query->select('skills.id', 'skills.name'); // Chỉ lấy id và name kỹ năng cho nhẹ dữ liệu
                }
            ])
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

        // 2. Tìm bài đăng tuyển dụng theo ID (Phải thuộc đúng công ty này)
        $job = Job::where('id', $id)->where('company_id', $company->id)->first();

        if (!$job) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy tin tuyển dụng.'], 404);
        }

        // 3. CÁC LỚP CHẶN BẢO MẬT TRẠNG THÁI (Sửa lỗi qua mặt Admin tại đây)
        
        // Chặn 1: Đang chờ duyệt
        if ($job->status === 'pending') {
            return response()->json(['success' => false, 'message' => 'Tin đang chờ Admin duyệt, không thể thay đổi.'], 400);
        }

        // Chặn 2: Đã bị từ chối
        if ($job->status === 'rejected') {
            return response()->json(['success' => false, 'message' => 'Tin này đã bị Admin từ chối. Bạn phải chỉnh sửa lại nội dung để gửi duyệt mới có thể hiển thị!'], 400);
        }

        // Chặn 3: Đã bị khóa do vi phạm (Nếu database của bạn có trạng thái 'locked' hoặc 'blocked')
        if (in_array($job->status, ['locked', 'blocked', 'banned'])) {
            return response()->json(['success' => false, 'message' => 'Tin đăng này đã bị khóa vĩnh viễn do vi phạm chính sách hệ thống.'], 403);
        }

        // 4. Thực hiện đảo ngược trạng thái (Chỉ cho phép chạy khi tin đang 'active' hoặc 'closed')
        if (in_array($job->status, ['active', 'closed'])) {
            $job->status = ($job->status === 'active') ? 'closed' : 'active';
            $job->save();

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật trạng thái thành công!'
            ], 200);
        }

        // Lớp bảo vệ cuối cùng (Tránh các trạng thái lạ ngoài dự kiến)
        return response()->json(['success' => false, 'message' => 'Trạng thái tin không hợp lệ để thực hiện thao tác này.'], 400);
    }
    public function extendJob(Request $request, $id)
    {
        $user = $request->user();
        if (!$user)
            return response()->json(['success' => false, 'message' => 'Hết hạn phiên.'], 401);

        $company = Company::where('user_id', $user->id)->first();
        if (!$company)
            return response()->json(['success' => false, 'message' => 'Chưa cấu hình công ty.'], 404);

        $job = Job::where('id', $id)->where('company_id', $company->id)->first();
        if (!$job)
            return response()->json(['success' => false, 'message' => 'Không tìm thấy tin tuyển dụng.'], 404);

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
        if (!$user)
            return response()->json(['success' => false, 'message' => 'Hết hạn phiên.'], 401);

        $company = Company::where('user_id', $user->id)->first();
        if (!$company)
            return response()->json(['success' => false, 'message' => 'Chưa cấu hình công ty.'], 404);

        // Bổ sung luật kiểm tra mảng 'skills' gửi lên từ React
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
            'skills' => 'required|array', // Bắt buộc phải có thuộc tính skills và phải là mảng
            'skills.*' => 'integer|exists:skills,id', // Từng phần tử trong mảng phải là ID số nguyên tồn tại ở bảng skills
        ], [

            'skills.required' => 'Vui lòng lựa chọn ít nhất một kỹ năng chuyên môn yêu cầu.',
            'skills.array' => 'Dữ liệu kỹ năng không đúng định dạng mảng.',
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

        // Tiến hành đồng bộ mảng ID kỹ năng vào bảng trung gian sau khi $job đã lưu thành công
        if (!empty($validated['skills'])) {
            $job->skills()->sync($validated['skills']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Đăng tin thành công! Vui lòng chờ Quản trị viên phê duyệt.'
        ], 201);
    }
    public function updateJob(Request $request, $id)
    {
        $user = $request->user();
        if (!$user)
            return response()->json(['success' => false, 'message' => 'Hết hạn phiên.'], 401);

        $company = Company::where('user_id', $user->id)->first();
        if (!$company)
            return response()->json(['success' => false, 'message' => 'Chưa cấu hình công ty.'], 404);

        // Tìm bài đăng cần sửa
        $job = Job::where('id', $id)->where('company_id', $company->id)->first();
        if (!$job)
            return response()->json(['success' => false, 'message' => 'Không tìm thấy tin tuyển dụng.'], 404);

        // 1. CẬP NHẬT: Thêm điều kiện validate cho mảng skills gửi lên từ React
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
            'skills' => 'required|array', // Bắt buộc gửi lên dạng mảng
            'skills.*' => 'integer|exists:skills,id', // Từng ID phải tồn tại trong bảng skills
        ]);

        // Cập nhật dữ liệu cơ bản
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

        // Đổi trạng thái về chờ duyệt để Admin kiểm tra lại nội dung mới
        $job->status = 'pending';
        $job->save();

        // 2. CẬP NHẬT: Đồng bộ lại danh sách kỹ năng mới (Xóa liên kết cũ, nạp liên kết mới)
        if (isset($validated['skills'])) {
            $job->skills()->sync($validated['skills']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật thành công! Tin của bạn đã được chuyển về trạng thái Chờ duyệt.'
        ], 200);
    }
    // Hàm lấy toàn bộ danh sách kỹ năng hệ thống trả về cho Frontend
    public function getAllSkills()
    {
        try {
            // Lấy ra tất cả kỹ năng gồm id và name từ bảng tuyển dụng
            $skills = \App\Models\Skill::select('id', 'name')->orderBy('name', 'asc')->get();

            return response()->json([
                'success' => true,
                'data' => $skills
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể tải danh sách kỹ năng: ' . $e->getMessage()
            ], 500);
        }
    }
    public function getCompanyCandidates(Request $request)
    {
        $user = $request->user();
        if (!$user)
            return response()->json(['success' => false, 'message' => 'Hết hạn phiên.'], 401);

        $company = \App\Models\Company::where('user_id', $user->id)->first();
        if (!$company)
            return response()->json(['success' => false, 'message' => 'Chưa cấu hình công ty.'], 404);

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
                // Thay chữ "send" bằng chữ "queue"
                Mail::to($candidate->email)->queue(
                    new ApplicationStatusChanged($candidate, $newStatus, $companyName)
                );
            } catch (\Exception $e) {
                // Ghi log nếu có lỗi khi đẩy vào hàng đợi
                \Illuminate\Support\Facades\Log::error('Lỗi đưa email vào Queue: ' . $e->getMessage());
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
    public function getDashboardStats(Request $request)
    {
        $user = $request->user();
        if (!$user)
            return response()->json(['success' => false, 'message' => 'Hết hạn phiên.'], 401);

        $company = \App\Models\Company::where('user_id', $user->id)->first();
        if (!$company)
            return response()->json(['success' => false, 'message' => 'Chưa cấu hình công ty.'], 404);

        // 1. Đếm TỔNG số tin tuyển dụng của công ty này
        $totalJobs = \Illuminate\Support\Facades\DB::table('jobs')
            ->where('company_id', $company->id)
            ->count();

        // 2. Đếm số tin ĐANG CHẠY
        // LƯU Ý: Chữ 'active' ở dưới tùy thuộc vào cách bạn lưu trong DB. 
        // Nếu DB bạn lưu là 1 (hoạt động), 0 (ẩn) thì sửa thành ->where('status', 1) nhé.
        $activeJobs = \Illuminate\Support\Facades\DB::table('jobs')
            ->where('company_id', $company->id)
            ->where('status', 'active')
            ->count();
        $totalCVs = \Illuminate\Support\Facades\DB::table('applications')
            ->join('jobs', 'applications.job_id', '=', 'jobs.id')
            ->where('jobs.company_id', $company->id)
            ->count();
        $totalViews = \Illuminate\Support\Facades\DB::table('job_clicks')
            ->join('jobs', 'job_clicks.job_id', '=', 'jobs.id') // Gộp với bảng jobs để lọc theo công ty[cite: 1]
            ->where('jobs.company_id', $company->id)
            ->sum('job_clicks.click_count');
        $interviewCVs = \Illuminate\Support\Facades\DB::table('applications')
            ->join('jobs', 'applications.job_id', '=', 'jobs.id')
            ->where('jobs.company_id', $company->id)
            ->where('applications.status', 'Phỏng vấn')
            ->count();

        // Tính tỷ lệ %, dùng toán tử ba ngôi để tránh lỗi chia cho 0 (Division by zero) nếu chưa có ai nộp bài
        $interviewRate = $totalCVs > 0 ? round(($interviewCVs / $totalCVs) * 100, 1) : 0;

        // Nhận số tuần cần lùi về từ Request (0: Tuần này, 1: Tuần trước, 2: 2 tuần trước...)
        $weekOffset = (int) $request->input('week_offset', 0); // Lấy số tuần lùi về từ React

        // Dùng copy() để không làm biến dạng ngày gốc
        $baseDate = \Carbon\Carbon::now()->subWeeks($weekOffset);
        $startOfWeek = $baseDate->copy()->startOfWeek()->format('Y-m-d');
        $endOfWeek = $baseDate->copy()->endOfWeek()->format('Y-m-d');

        $dailyClicks = \Illuminate\Support\Facades\DB::table('job_clicks')
            ->join('jobs', 'job_clicks.job_id', '=', 'jobs.id')
            ->select(
                \Illuminate\Support\Facades\DB::raw('DATE(job_clicks.click_date) as date'),
                \Illuminate\Support\Facades\DB::raw('SUM(job_clicks.click_count) as total_clicks')
            )
            ->where('jobs.company_id', $company->id)
            ->whereBetween('job_clicks.click_date', [$startOfWeek, $endOfWeek])
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        $weeklyViewsChart = [];
        $dayLabels = ['Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7', 'CN'];

        // ÉP BUỘC SINH RA 7 NGÀY (Dù database không có dòng nào thì vẫn tạo ra cột = 0)
        for ($i = 0; $i < 7; $i++) {
            $currentDay = $baseDate->copy()->startOfWeek()->addDays($i);
            $date = $currentDay->format('Y-m-d');

            $clicks = isset($dailyClicks[$date]) ? (int) $dailyClicks[$date]->total_clicks : 0;

            $weeklyViewsChart[] = [
                'label' => $dayLabels[$i],
                'date_format' => $currentDay->format('d/m'), // Sinh ngày tháng chuẩn
                'clicks' => $clicks // Sẽ bằng 0 nếu tuần đó trống
            ];
        }
        // Lấy danh sách tất cả job_id thuộc về công ty này
        $jobIds = \Illuminate\Support\Facades\DB::table('jobs')
            ->where('company_id', $company->id)
            ->pluck('id')
            ->toArray();

        $cvChartData = [];

        // Lấy thời điểm hiện tại và mốc ngày 1 của tháng này
        $now = \Carbon\Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();

        // Định nghĩa chia lô 4 tuần quét sạch các ngày trong tháng
        $weeks = [
            1 => [
                'start' => $startOfMonth->copy(),
                'end' => $startOfMonth->copy()->addDays(6)->endOfDay() // Ngày 1 -> 7
            ],
            2 => [
                'start' => $startOfMonth->copy()->addDays(7),
                'end' => $startOfMonth->copy()->addDays(13)->endOfDay() // Ngày 8 -> 14
            ],
            3 => [
                'start' => $startOfMonth->copy()->addDays(14),
                'end' => $startOfMonth->copy()->addDays(20)->endOfDay() // Ngày 15 -> 21
            ],
            4 => [
                'start' => $startOfMonth->copy()->addDays(21),
                'end' => $startOfMonth->copy()->endOfMonth()->endOfDay() // Ngày 22 -> Cuối tháng
            ],
        ];

        foreach ($weeks as $weekNum => $dates) {
            $cvCount = 0;

            if (!empty($jobIds)) {
                $cvCount = \Illuminate\Support\Facades\DB::table('applications')
                    ->whereIn('job_id', $jobIds)
                    ->whereBetween('applied_at', [$dates['start'], $dates['end']]) // CHÚ Ý: Đã đổi sang cột applied_at
                    ->count();
            }

            // Tự động kiểm tra xem ngày hôm nay có nằm trong tuần này không để Frontend tô màu đậm
            $isCurrent = $now->between($dates['start'], $dates['end']);

            $cvChartData[] = [
                'label' => 'Tuần ' . $weekNum,
                'range' => $dates['start']->format('d/m') . ' - ' . $dates['end']->format('d/m'),
                'cvs' => $cvCount,
                'is_current' => $isCurrent
            ];
        }
        return response()->json([
            'success' => true,
            'data' => [
                'totalJobs' => $totalJobs,
                'activeJobs' => $activeJobs,
                'totalCVs' => $totalCVs,
                'totalViews' => (int) $totalViews,
                'interviewRate' => $interviewRate,
                'weeklyViewsChart' => $weeklyViewsChart,
                'cvChartData' => $cvChartData,
            ]
        ], 200);
    }

    // hàm tạo thông tin công ty kèm upload logo
    public function storeCompany(Request $request)
    {
        try {
            // 1. Viết Validate trực tiếp (Cập nhật cả logo lẫn business_license dạng File ảnh)
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|exists:users,email',
                'company_name' => 'required|string|max:255',
                'tax_code' => 'required|string|max:50|unique:companies,tax_code',
                'website_url' => 'nullable|url|max:255',
                'description' => 'nullable|string',
                'industry' => 'required|string|max:255',
                'size' => 'required|string|max:100',
                'founded_year' => 'nullable|integer|min:1900|max:' . date('Y'),
                'address' => 'required|string|max:255',
                'benefits' => 'nullable|string',
                'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Max 2MB

                // 🚀 CẬP NHẬT: Validate file ảnh Giấy phép kinh doanh (Max 4MB để đảm bảo độ nét khi admin đọc)
                'business_license' => 'required|image|mimes:jpeg,png,jpg|max:4096',
            ], [
                'email.required' => 'Không tìm thấy thông tin tài khoản vừa đăng ký.',
                'email.exists' => 'Tài khoản liên kết không tồn tại trên hệ thống.',
                'company_name.required' => 'Tên công ty không được để trống.',
                'tax_code.required' => 'Mã số thuế không được để trống.',
                'tax_code.unique' => 'Mã số thuế này đã tồn tại trên hệ thống.',
                'website_url.url' => 'Định dạng đường dẫn Website không hợp lệ.',
                'industry.required' => 'Vui lòng nhập hoặc chọn ngành nghề kinh doanh.',
                'size.required' => 'Vui lòng chọn quy mô công ty.',
                'address.required' => 'Địa chỉ công ty không được để trống.',

                'logo.image' => 'File tải lên của Logo phải là định dạng hình ảnh.',
                'logo.mimes' => 'Logo chỉ chấp nhận các định dạng: jpeg, png, jpg, gif.',
                'logo.max' => 'Dung lượng logo không được vượt quá 2MB.',

                // 🚀 BỔ SUNG: Thông báo lỗi tiếng Việt cho Giấy phép kinh doanh
                'business_license.required' => 'Vui lòng tải lên ảnh Giấy phép kinh doanh để xác thực.',
                'business_license.image' => 'File tải lên của Giấy phép kinh doanh phải là hình ảnh.',
                'business_license.mimes' => 'Giấy phép kinh doanh chỉ chấp nhận các định dạng: jpeg, png, jpg.',
                'business_license.max' => 'Dung lượng ảnh Giấy phép kinh doanh không được vượt quá 4MB.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            // 2. Tìm User dựa vào Email được truyền lên từ Frontend
            $user = User::where('email', $request->email)->first();

            // 3. Kiểm tra xem tài khoản này đã từng tạo công ty chưa
            $existingCompany = Company::where('user_id', $user->id)->first();
            if ($existingCompany) {
                return response()->json([
                    'success' => false,
                    'message' => 'Hồ sơ công ty cho tài khoản này đã được tạo trước đó.'
                ], 400);
            }

            // 4. Lấy dữ liệu hợp lệ và gán các trường hệ thống
            $validatedData = $validator->validated();

            $validatedData['user_id'] = $user->id;
            $validatedData['is_verified'] = false; // Chờ Admin duyệt bài
            $validatedData['reject_reason'] = '';

            // 5. XỬ LÝ UPLOAD LOGO VÀO THƯ MỤC PUBLIC
            if ($request->hasFile('logo')) {
                $logoFile = $request->file('logo');
                $logoName = time() . '_logo_' . $logoFile->getClientOriginalName();

                // Di chuyển file vào thư mục public/logoCompany
                $logoFile->move(public_path('logoCompany'), $logoName);
                $validatedData['logo_url'] = 'logoCompany/' . $logoName;
            } else {
                $validatedData['logo_url'] = 'logoCompany/logo-default.png';
            }

            // 6. 🚀 XỬ LÝ UPLOAD ẢNH GIẤY PHÉP KINH DOANH VÀO THƯ MỤC PUBLIC
            if ($request->hasFile('business_license')) {
                $licenseFile = $request->file('business_license');
                $licenseName = time() . '_gpkd_' . $licenseFile->getClientOriginalName();

                // Di chuyển file vào thư mục public/businessLicense
                $licenseFile->move(public_path('businessLicense'), $licenseName);

                // Ghi đè trường dữ liệu thành chuỗi đường dẫn lưu trong DB
                $validatedData['business_license'] = 'businessLicense/' . $licenseName;
            }

            // Loại bỏ các trường thừa không có cấu trúc tương ứng trong bảng companies
            unset($validatedData['email']);
            unset($validatedData['logo']);

            // 7. Tiến hành lưu dữ liệu
            $company = Company::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Đăng ký hồ sơ doanh nghiệp thành công! Vui lòng chờ Ban quản trị phê duyệt.',
                'data' => $company
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Đã xảy ra lỗi hệ thống nghiêm trọng.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}