<?php
namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use File; // Thao tác xóa ảnh cũ trong thư mục public
use App\Models\Category;
use App\Models\Skill;
use App\Models\User;

class CandidateController extends Controller
{
  //hàm danh sách danh mục 
  public function getFormData()
  {
    try {
      // Lấy gọn gàng bằng Eloquent Model
      $categories = Category::select('id', 'name')->orderBy('name', 'asc')->get();
      $skills = Skill::select('id', 'name')->orderBy('name', 'asc')->get();

      return response()->json([
        'success' => true,
        'message' => 'Lấy danh sách thành công!',
        'data' => [
          'categories' => $categories,
          'skills' => $skills
        ]
      ], 200);

    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Lỗi hệ thống',
        'error' => $e->getMessage()
      ], 500);
    }
  }

  // hàm xử lý upload ảnh, tối ưu bộ nhớ xóa ảnh cũ
  private function handleUploadAvatar($request, $userId, $currentAvatarUrl = null)
  {
    // Nếu Frontend có gửi file ảnh lên (ví dụ: name="avatar")
    if ($request->hasFile('avatar')) {
      $file = $request->file('avatar');

      // Tạo tên file duy nhất theo userId và thời gian
      $fileName = 'avatar_' . $userId . '_' . time() . '.' . $file->getClientOriginalExtension();

      // TỐI ƯU BỘ NHỚ: Nếu ứng viên đã có ảnh cũ và ảnh đó không phải ảnh mặc định thì xóa đi
      if ($currentAvatarUrl && $currentAvatarUrl !== 'avatars/default-avatar.png') {
        $oldPath = public_path($currentAvatarUrl);
        if (File::exists($oldPath)) {
          File::delete($oldPath);
        }
      }

      // Lưu file ảnh mới thẳng vào thư mục public/avatars/
      $file->move(public_path('avatars'), $fileName);

      // Trả về đường dẫn để gán vào database
      return 'avatars/' . $fileName;
    }

    // Nếu Frontend không gửi ảnh mới, giữ nguyên ảnh cũ (nếu có), hoặc trả về null
    return $currentAvatarUrl;
  }

  // HÀM HIỂN THỊ THÔNG TIN HỒ SƠ (SHOW)
  public function show()
{
    try {
        $userId = Auth::id();
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Chưa đăng nhập.'], 401);
        }

        // 1. Tìm hồ sơ ứng viên
        $candidate = Candidate::with(['skills', 'category'])->where('user_id', $userId)->first();

        // 🚀 BỌC LÓT THẦN THÁNH: Nếu CHƯA CÓ hồ sơ ứng viên (User mới tinh)
        if (!$candidate) {
            // Lấy thông tin tài khoản gốc để hỗ trợ Frontend điền sẵn (Họ tên, email...)
            $user = \App\Models\User::find($userId);

            return response()->json([
                'success' => true,
                'has_filled_form' => false, // Chưa điền form chuyên sâu
                'data' => [
                    'full_name' => $user->name ?? '', // Lấy từ bảng users nếu có
                    'email'     => $user->email ?? '',
                    'phone'     => '',
                    'address'   => '',
                    'gender'    => 'Nam',
                    'birthday'  => '',
                    'links'     => ['github' => '', 'linkedin' => ''],
                    'contact_reference' => ['name' => '', 'phone' => '', 'relationship' => ''],
                    'project'   => []
                ]
            ], 200);
        }

        // 2. Nếu ĐÃ CÓ hồ sơ -> Tính toán xem họ đã lưu kỹ năng nào chưa
        $hasFilledForm = $candidate->skills && $candidate->skills->count() > 0;

        return response()->json([
            'success' => true,
            'data' => $candidate,
            'has_filled_form' => $hasFilledForm
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Lỗi xử lý lấy hồ sơ (500): ' . $e->getMessage()
        ], 500);
    }
}

  // HÀM CHÍNH: LƯU HOẶC CẬP NHẬT HỒ SƠ (STORE OR UPDATE)
  public function storeOrUpdate(Request $request)
{
    $userId = Auth::id();
    if (!$userId) {
      return response()->json(['success' => false, 'message' => 'Chưa đăng nhập.'], 401);
    }

    // 🚀 GIẢI NÉN MẢNG: Chuyển chuỗi JSON từ Frontend gửi lên thành Array để vượt vòng gửi xe Validation
    if ($request->has('skills') && !is_array($request->skills)) {
        $request->merge(['skills' => json_decode($request->skills, true)]);
    }
    if ($request->has('project') && !is_array($request->project)) {
        $request->merge(['project' => json_decode($request->project, true)]);
    }
    if ($request->has('links') && !is_array($request->links)) {
        $request->merge(['links' => json_decode($request->links, true)]);
    }
    if ($request->has('contact_reference') && !is_array($request->contact_reference)) {
        $request->merge(['contact_reference' => json_decode($request->contact_reference, true)]);
    }

    // 1. Validate dữ liệu
    $validator = Validator::make($request->all(), [
      'category_id' => 'required|exists:categories,id',
      'cv_template_id' => 'nullable|exists:cv_templates,id',
      'title' => 'required|string|max:100',
      'full_name' => 'required|string|max:255',
      'gender' => 'required|in:Nam,Nữ,Khác',
      'birthday' => 'nullable|date',
      'phone' => 'required|string|max:20',
      'email' => 'required|email|max:255',
      'address' => 'nullable|string|max:255',
      'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', 
      'summary' => 'nullable|string',
      'objective' => 'nullable|string',
      'links' => 'nullable|array',
      'experience_years' => 'nullable|integer|min:0',
      'project' => 'nullable|array', 
      'education' => 'nullable|string',
      'contact_reference' => 'nullable|array',
      'skills' => 'required|array',
      'skills.*.id' => 'required|exists:skills,id',
      'skills.*.level' => 'required|string|max:50'
    ]);

    if ($validator->fails()) {
      return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
    }

    DB::beginTransaction();

    try {
      $validatedData = $validator->validated();
      
      // 🚀 BƯỚC CHÍ MẠNG 1: Tách mảng skills ra riêng, không để nó chạy bậy vào câu lệnh sql create/update
      $skillsData = $validatedData['skills'];
      unset($validatedData['skills']); 

      // 🚀 BƯỚC CHÍ MẠNG 2: Mã hóa các trường dữ liệu dạng mảng thành string JSON để lưu vào cột TEXT/JSON trong DB
      if (isset($validatedData['links'])) {
          $validatedData['links'] = json_encode($validatedData['links']);
      }
      if (isset($validatedData['contact_reference'])) {
          $validatedData['contact_reference'] = json_encode($validatedData['contact_reference']);
      }
      if (isset($validatedData['project'])) {
          $validatedData['project'] = json_encode($validatedData['project']);
      }

      // Kiểm tra xem User này đã tồn tại bản ghi chưa
      $candidate = Candidate::where('user_id', $userId)->first();

      if ($candidate) {
        // TRƯỜNG HỢP 1: UPDATE
        $avatarUrl = $this->handleUploadAvatar($request, $userId, $candidate->avatar_url);
        if ($avatarUrl) {
            $validatedData['avatar_url'] = $avatarUrl;
        }

        $candidate->update($validatedData);
      } else {
        // TRƯỜNG HỢP 2: CREATE NEW
        $avatarUrl = $this->handleUploadAvatar($request, $userId, null);

        $validatedData['user_id'] = $userId;
        $validatedData['avatar_url'] = $avatarUrl ?? 'avatars/default-avatar.png';
        $validatedData['cv_template_id'] = $validatedData['cv_template_id'] ?? 1;

        $candidate = Candidate::create($validatedData);
      }

      // 2. XỬ LÝ LƯU VÀO BẢNG TRUNG GIAN (candidate_skill)
      $syncSkills = [];
      foreach ($skillsData as $skill) {
        $syncSkills[$skill['id']] = [
          'level' => $skill['level']
        ];
      }
      $candidate->skills()->sync($syncSkills);

      DB::commit();

      return response()->json([
        'success' => true,
        'message' => 'Lưu hồ sơ cá nhân và cập nhật kỹ năng thành công!',
        'data' => $candidate->load('skills')
      ], 200);

    } catch (\Exception $e) {
      DB::rollBack();
      return response()->json([
        'success' => false,
        'message' => 'Đã có lỗi xảy ra trong quá trình lưu dữ liệu.',
        'error' => $e->getMessage()
      ], 500);
    }
}

  /**
   * Xóa thông tin chuyên sâu của hồ sơ ứng viên (Kỹ năng, dự án, ảnh...)
   * Đưa hồ sơ về trạng thái thô mặc định ban đầu.
   */
  public function deleteCandidate(Request $request)
{
  try {
    $user = $request->user();
    if (!$user) {
      return response()->json(['success' => false, 'message' => 'Hết phiên đăng nhập!'], 401);
    }

    $candidate = Candidate::where('user_id', $user->id)->first();
    if (!$candidate) {
      return response()->json(['success' => false, 'message' => 'Không tìm thấy hồ sơ!'], 404);
    }

    // Gỡ bỏ kỹ năng trong bảng trung gian
    if (method_exists($candidate, 'skills')) {
      $candidate->skills()->detach();
    }

    $currentAvatar = $candidate->avatar_url;

    // 🚀 SỬA TẠI ĐÂY: Dùng json_encode để tránh lỗi 500 ép kiểu Array thành String
    $candidate->update([
      'title' => '',
      'summary' => null,
      'objective' => null,
      'experience_years' => 0,
      'education' => null,
      'project' => json_encode([]), 
      'contact_reference' => json_encode(['name' => '', 'phone' => '', 'relationship' => '']),
    ]);

    return response()->json([
      'success' => true,
      'message' => 'Đã dọn dẹp hồ sơ thành công!',
      'data' => [
        'full_name' => $candidate->full_name,
        'email' => $candidate->email,
        'phone' => $candidate->phone,
        'address' => $candidate->address,
        'gender' => $candidate->gender,
        'birthday' => $candidate->birthday,
        'avatar_url' => $currentAvatar
      ]
    ], 200);

  } catch (\Exception $e) {
    \Log::error('Lỗi deleteCandidate: ' . $e->getMessage());
    return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
  }
}

  // hàm hiện CV trong trang quản lý CV
  public function index(Request $request)
{
    try {
        $userId = Auth::id();

        // 1. Tìm candidate của user này
        $candidate = Candidate::where('user_id', $userId)->first();

        // 2. Tìm file pdf của user này
        $cvFiles = \App\Models\CvFile::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            // Nếu có candidate thì map data, không thì trả về null an toàn, không sợ crash!
            'cv_online' => $candidate ? [
                'id'         => $candidate->id,
                'title'      => $candidate->title,
                'experience' => $candidate->experience,
                'education'  => $candidate->education,
            ] : null,

            'cv_files' => $cvFiles, 
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Lỗi tải danh sách quản lý CV',
            'error' => $e->getMessage()
        ], 500);
    }
}

  // hàm hiện dữ liệu 
  public function getDataCV(){
    $userId = Auth::id();
    $data = Candidate::with(['skills', 'category'])->where('user_id', $userId)->first();

    return response()->json([
      'success' => true,
      'data' => $data,
    ], 200);
  }

  // hàm cập nhật CV_template
  public function updateCVTemplate(Request $request, $id){
    // 1. Validate cả id của CV và id của Template
    $request->validate([
        'cv_template_id' => 'required|integer|exists:cv_templates,id'
    ]);

    // 2. Tìm CV cần sửa (Có thể kết hợp check quyền sở hữu để tránh người dùng sửa bậy CV của người khác)
    $cv = Candidate::where('id', $id)
            ->where('user_id', auth()->id()) // Khuyên dùng: chỉ cho sửa nếu đúng là CV của họ
            ->firstOrFail();

    // 3. Cập nhật duy nhất template id
    $cv->update([
        'cv_template_id' => $request->cv_template_id
    ]);

    // 4. Trả về kết quả
    return response()->json([
        'success' => true,
        'message' => 'Đổi mẫu thiết kế CV thành công!',
    ], 200);
  }

}