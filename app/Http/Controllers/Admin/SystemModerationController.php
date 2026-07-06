<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Job;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use App\Mail\JobApprovedMail;
use App\Mail\JobRejectedMail;
use App\Mail\CompanyApprovedMail;
use App\Mail\CompanyRejectedMail;

class SystemModerationController extends Controller
{
    // 1. Lấy danh sách tin đăng chờ duyệt + bộ lọc tìm kiếm
    public function getPendingJobs(Request $request)
    {
        $search = $request->query('search');

        $jobs = Job::where('status', 'pending')
            ->when($search, function ($query, $search) {
                return $query->where('title', 'like', "%{$search}%")
                             ->orWhereHas('company', function ($q) use ($search) {
                                 $q->where('company_name', 'like', "%{$search}%");
                             });
            })
            ->with('company:id,company_name') // Lấy kèm thông tin công ty rút gọn
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $jobs
        ], 200);
    }

    // 2. Lấy danh sách doanh nghiệp chờ xác minh + bộ lọc tìm kiếm
    public function getPendingCompanies(Request $request)
    {
        $search = $request->query('search');

        $companies = Company::where('is_verified', 0) // hoặc is_verified = false tùy cấu trúc
            ->when($search, function ($query, $search) {
                return $query->where('company_name', 'like', "%{$search}%")
                             ->orWhere('tax_code', 'like', "%{$search}%");
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $companies
        ], 200);
    }

    // 3. Duyệt tin tuyển dụng
    public function approveJob($id)
    {
        $job = Job::find($id);
        if (!$job) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy tin đăng này!'], 404);
        }

        $job->update([
            'status' => 'active',
            'reject_reason' => null
        ]);
            // Lấy email nhà tuyển dụng từ mối quan hệ (Job -> Company -> User)
        $employerEmail = $job->company->user->email ?? null;

        if ($employerEmail) {
            // Thực hiện gửi mail tự động
            Mail::to($employerEmail)->send(new JobApprovedMail($job));
        }

        return response()->json(['success' => true, 'message' => 'Đã phê duyệt tin tuyển dụng thành công!'], 200);
    }

    // 4. Từ chối tin tuyển dụng
    public function rejectJob(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:500'
        ]);

        $job = Job::find($id);
        if (!$job) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy tin đăng này!'], 404);
        }
        $reason = $request->input('reason');
        
        $job->update([
            'status' => 'rejected',
            'reject_reason' => $request->input('reason')
        ]);
        // Lấy email nhà tuyển dụng
        $employerEmail = $job->company->user->email ?? null;

        if ($employerEmail) {
            // Thực hiện gửi mail tự động kèm theo lý do từ chối
            Mail::to($employerEmail)->send(new JobRejectedMail($job, $reason));
        }

        return response()->json(['success' => true, 'message' => 'Đã từ chối tin tuyển dụng thành công!'], 200);
    }

    // 5. Xác minh doanh nghiệp
    public function approveCompany($id)
    {
        $company = Company::find($id);
        if (!$company) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy doanh nghiệp!'], 404);
        }

        $company->update([
            'is_verified' => 1, // hoặc is_verified => true
            'reject_reason' => null
        ]);
        // Gửi mail thông báo duyệt thành công
            $employerEmail = $company->user->email ?? null;
            if ($employerEmail) {
                Mail::to($employerEmail)->send(new CompanyApprovedMail($company));
            }
        return response()->json(['success' => true, 'message' => 'Đã xác minh doanh nghiệp thành công!'], 200);
    }

    // 6. Từ chối doanh nghiệp
    public function rejectCompany(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:500'
        ]);

        // Lấy thông tin công ty kèm user TRƯỚC KHI thực hiện xóa dữ liệu
        $company = Company::with('user')->find($id);
        if (!$company) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy doanh nghiệp!'], 404);
        }

        $reason = $request->input('reason');

        // Lưu email của nhà tuyển dụng ra một biến tạm trước khi xóa tài khoản của họ
        $employerEmail = $company->user->email ?? null;

        $company->update([
            'is_verified' => 2,
            'reject_reason' => $reason
        ]);

        // GỬI MAIL TRƯỚC: Gửi lý do từ chối về email khi tài khoản còn tồn tại
        if ($employerEmail) {
            Mail::to($employerEmail)->send(new CompanyRejectedMail($company, $reason));
        }

        // XÓA USER SAU: Sau khi gửi thư xong xuôi thì mới xóa tài khoản
        if ($company->user_id) {
            User::where('id', $company->user_id)->delete();
        }

        return response()->json(['success' => true, 'message' => 'Đã từ chối xác minh và gửi mail thông báo thành công!'], 200);
    }
}
