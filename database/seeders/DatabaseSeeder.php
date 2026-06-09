<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Company;
use App\Models\Candidate;
use App\Models\Job;
use App\Models\Skill;
use App\Models\Category;
use App\Models\CvTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;
use DB;

class DatabaseSeeder extends Seeder
{
  public function run(): void
  {
    // 1. TẠO TÀI KHOẢN ADMIN MẪU
    User::create([
      'email' => 'admin@gmail.com',
      'password' => Hash::make('password'),
      'role' => 'admin',
      'status' => 'active',
    ]);

    // 2. SEED BẢNG CATEGORIES (10 ngành nghề)
    $categories = [
      'Công nghệ thông tin',
      'Marketing / PR',
      'Kinh doanh / Bán hàng',
      'Kế toán / Kiểm toán',
      'Hành chính / Nhân sự',
      'Thiết kế đồ họa',
      'Xây dựng',
      'Ngôn ngữ / Biên dịch',
      'Y tế / Dược',
      'Giáo dục / Đào tạo'
    ];
    foreach ($categories as $cat) {
      Category::create(['name' => $cat]);
    }

    // 3. SEED BẢNG SKILLS (Hơn 80 kỹ năng đa dạng các ngành nghề) 
    $skills = [
      // IT Skills
      'PHP', 'Laravel', 'JavaScript', 'Vue.js', 'ReactJS', 'Node.js', 'Python', 'Django', 'Java', 'Spring Boot',
      'C#', '.NET', 'Ruby on Rails', 'Swift', 'Kotlin', 'Flutter', 'React Native', 'SQL', 'MySQL', 'PostgreSQL',
      'MongoDB', 'Docker', 'Kubernetes', 'AWS', 'Git', 'HTML5', 'CSS3', 'Tailwind CSS', 'Bootstrap', 'TypeScript',
      // Marketing & Sales
      'SEO', 'Google Ads', 'Facebook Ads', 'Content Writing', 'Copywriting', 'Google Analytics', 'Email Marketing',
      'B2B Sales', 'B2C Sales', 'Telemarketing', 'Market Research', 'Public Relations', 'Branding', 'Event Planning',
      // Design
      'Photoshop', 'Illustrator', 'Figma', 'Adobe XD', 'Premiere Pro', 'After Effects', '3D Max', 'AutoCAD',
      // Ngôn ngữ & Kỹ năng mềm
      'Tiếng Anh Giao Tiếp', 'TOEIC 700+', 'IELTS 6.5+', 'Tiếng Nhật N3', 'Tiếng Trung', 'Kỹ năng giao tiếp',
      'Làm việc nhóm', 'Giải quyết vấn đề', 'Quản lý thời gian', 'Thuyết trình', 'Đàm phán', 'Tư duy phản biện',
      // Kinh tế & Khác
      'Kế toán tổng hợp', 'Misa', 'Excel nâng cao', 'Phân tích dữ liệu', 'Quản trị nhân sự', 'Tuyển dụng',
      'Tính lương (C&B)', 'Chăm sóc khách hàng', 'Quản lý dự án', 'Scrum/Agile', 'Kỹ năng lãnh đạo', 'SEO Youtube'
    ];
    foreach ($skills as $skill) {
      Skill::create(['name' => $skill]);
    }

    // 4. SEED BẢNG CV TEMPLATES (5 mẫu)
    for ($i = 1; $i <= 5; $i++) {
      CvTemplate::create([
        'name' => "Mẫu CV chuyên nghiệp v$i",
        'thumbnail_url' => "uploads/templates/thumb_$i.png",
        'file_path' => "templates/cv_template_$i.blade.php"
      ]);
    }

    // 5. TẠO DOANH NGHIỆP (Ít nhất 20 công ty và tài khoản đi kèm)
    Company::factory()->count(25)->create()->each(function ($company) {
      $company->user->update(['role' => 'employer']);
    });

    // 6. TẠO ỨNG VIÊN (Ít nhất 20 ứng viên và tài khoản đi kèm)
    $candidates = Candidate::factory()->count(30)->create()->each(function ($candidate) {
      $candidate->user->update(['role' => 'candidate']);

      $skillIds = Skill::pluck('id')->random(3);

      // Mảng mức độ thực tế
      $levels = ['Cơ bản', 'Khá', 'Thành thạo', 'Chuyên gia'];

      foreach ($skillIds as $skillId) {
          // Đính kèm skill kèm theo dữ liệu cho cột level ở bảng trung gian
          $candidate->skills()->attach($skillId, [
              'level' => $levels[array_rand($levels)]
          ]);
      }

      DB::table('cv_files')->insert([
        'candidate_id' => $candidate->id,
        'file_name' => 'CV_Luu_Tru_' . $candidate->id . '.pdf',
        'file_path' => 'uploads/cvs/sample.pdf',
        'file_size' => rand(500, 2048),
        'uploaded_at' => Carbon::now()
      ]);
    });

    // 7. TẠO TIN TUYỂN DỤNG (Ít nhất 20 tin tuyển dụng)
    Job::factory()->count(25)->create()->each(function ($job) {
      $randomSkills = Skill::pluck('id')->random(rand(2, 5))->toArray();
      $job->skills()->attach($randomSkills);
    });

    $candidateIds = Candidate::pluck('id')->toArray();
    $jobIds = Job::pluck('id')->toArray();

    // 8. SEED BẢNG APPLICATIONS (Đơn ứng tuyển - ít nhất 20 dòng)
    for ($i = 0; $i < 25; $i++) {
      $candId = $candidateIds[array_rand($candidateIds)];
      $cvFileId = DB::table('cv_files')->where('candidate_id', $candId)->value('id');
      
      // Chọn ngẫu nhiên trạng thái đơn ứng tuyển
      $status = fake()->randomElement(['pending', 'viewed', 'interviewing', 'rejected', 'accepted']);
      $statusDetails = null;

      // Giả lập dữ liệu JSON tùy thuộc vào trạng thái (Theo đúng chuẩn Job Board)
      if ($status === 'rejected') {
          $rejectReasons = [
              'Hồ sơ chưa đạt đủ số năm kinh nghiệm tối thiểu theo yêu cầu.',
              'Kỹ năng chuyên môn chưa thực sự phù hợp với định hướng của dự án.',
              'Vị trí tuyển dụng hiện tại đã nhận đủ số lượng nhân sự.',
              'Ứng viên không tham gia buổi làm bài test chuyên môn sơ loại.'
          ];
          $statusDetails = json_encode([
              'reject_reason' => fake()->randomElement($rejectReasons),
              'rejected_at' => Carbon::now()->subDays(rand(1, 5))->toDateTimeString()
          ]);
      } // Trường hợp 2: Hẹn phỏng vấn lần đầu (Lưu thời gian, địa điểm, ghi chú theo ý bạn)
      elseif ($status === 'interviewing') {
          $locations = [
              'Phòng họp tầng 3, Tòa nhà TechHub, 45 Đinh Tiên Hoàng, Quận 1, TP.HCM',
              'Tầng 12, Tòa nhà lntellect, 234 Trần Hưng Đạo, Quận Cầu Giấy, Hà Nội',
              'Trực tuyến qua Google Meet: https://meet.google.com/abc-xyz-def',
              'Trực tuyến qua Zoom - ID: 849 1234 5678 - Pass: 123456'
          ];
          $notes = [
              'Vui lòng đến trước 10 phút, mang theo laptop cá nhân và trang phục lịch sự.',
              'Buổi phỏng vấn nhằm đánh giá tư duy thuật toán sơ bộ. Liên hệ bộ phận lễ tân để được hướng dẫn.',
              'Bạn sẽ làm một bài test trực tiếp trong 30 phút trước khi vào phỏng vấn với Technical Leader.'
          ];
          
          // Tạo lịch hẹn ngẫu nhiên trong khoảng từ 1 đến 7 ngày tới
          $interviewDate = Carbon::now()->addDays(rand(1, 7))->setHour(rand(8, 16))->setMinute(fake()->randomElement([0, 30]))->setSecond(0);

          $statusDetails = json_encode([
              'interview_time' => $interviewDate->toDateTimeString(),
              'interview_location' => fake()->randomElement($locations),
              'note' => fake()->randomElement($notes),
              'contact_person' => fake()->randomElement(['Ms. Linh (HR Dept)', 'Mr. Tuấn (Recruitment Specialist)'])
          ]);
      }
      // Trường hợp 3: Chấp nhận tuyển dụng / Chúc mừng nhận việc (Lưu lịch hẹn nhận việc luôn!)
      elseif ($status === 'accepted') {
          $onboardingLocations = [
              'Phòng Nhân sự, Tầng 3, Tòa nhà TechHub, 45 Đinh Tiên Hoàng, Quận 1, TP.HCM',
              'Tầng 12, Tòa nhà lntellect, 234 Trần Hưng Đạo, Quận Cầu Giấy, Hà Nội',
              'Văn phòng Đại diện, Lô C Khu công nghệ cao, Quận 9, TP.HCM'
          ];
          $onboardingNotes = [
              'Vui lòng mang theo CCCD công chứng, 2 ảnh 3x4 và bằng tốt nghiệp/chứng chỉ gốc để đối chiếu.',
              'Trang phục công sở lịch sự. Khi đến nơi, bạn liên hệ quầy lễ tân để được hướng dẫn nhận vị trí chỗ ngồi.',
              'Vui lòng hoàn thiện hồ sơ nhận việc trực tuyến theo đường link đã gửi vào email trước ngày nhận việc.'
          ];

          // Tạo ngày nhận việc ngẫu nhiên sau ngày hiện tại từ 10 đến 20 ngày (để ứng viên kịp chuẩn bị)
          $onboardingDate = Carbon::now()->addDays(rand(10, 20))->setHour(8)->setMinute(30)->setSecond(0);

          $statusDetails = json_encode([
              'onboarding_time' => $onboardingDate->toDateTimeString(),
              'onboarding_location' => fake()->randomElement($onboardingLocations),
              'note' => fake()->randomElement($onboardingNotes),
              'contact_person' => fake()->randomElement(['Ms. Thủy (Head of HR)', 'Ms. Diệu Linh (C&B Specialist)'])
          ]);
      }
      // Các trạng thái như 'pending', 'viewed' thì $statusDetails giữ nguyên là null

      DB::table('applications')->insert([
        'job_id' => $jobIds[array_rand($jobIds)],
        'candidate_id' => $candId,
        'cv_type' => fake()->randomElement(['online', 'pdf']),
        'cv_file_id' => $cvFileId,
        'description' => 'Tôi rất mong muốn được ứng tuyển vào vị trí này của quý công ty. Hy vọng nhận được phản hồi sớm từ nhà tuyển dụng. Xin cảm ơn!',
        'matching_score' => fake()->randomFloat(2, 50, 95),
        'status' => $status,
        'status_details' => $statusDetails, // Đưa biến JSON vừa sinh ra vào đây
        'applied_at' => Carbon::now()->subDays(rand(1, 15))
      ]);
    }

    // 9. SEED BẢNG WISHLISTS (Tin tuyển dụng đã lưu - ít nhất 20 dòng)
    $userIds = User::where('role', 'candidate') // Đảm bảo lấy đúng các tài khoản ứng viên, tránh lấy nhầm admin/employer
    ->pluck('id')
    ->toArray();
    for ($i = 0; $i < 20; $i++) {
      DB::table('wishlists')->insert([
        'user_id' => $userIds[array_rand($userIds)],
        'job_id' => $jobIds[array_rand($jobIds)],
        'created_at' => Carbon::now()->subDays(rand(1, 30))
      ]);
    }

    // 10. SEED BẢNG REPORTS (Báo cáo tin tuyển dụng xấu - Việt hóa hoàn toàn phần nội dung)
    $reportDescriptions = [
      'Tin tuyển dụng có dấu hiệu lừa đảo đóng tiền cọc trước khi nhận việc.',
      'Công ty đa cấp núp bóng doanh nghiệp công nghệ, thông tin sai sự thật.',
      'Mô tả công việc không giống với thực tế khi đến phỏng vấn tại văn phòng.',
      'Yêu cầu ứng viên làm bài test quá nặng nề nhưng không có phản hồi kết quả.',
      'Mức lương ghi trên bài đăng không đúng với thỏa thuận ban đầu.'
    ];

    $adminNotes = [
      'Đã khóa bài viết và cảnh cáo tài khoản nhà tuyển dụng.',
      'Thông tin báo cáo chưa đủ căn cứ xác thực, tiếp tục theo dõi thêm.',
      'Đã liên hệ doanh nghiệp để đính chính lại thông tin tin đăng.',
      'Đã duyệt ẩn tin tuyển dụng lỗi này khỏi trang chủ.'
    ];

    for ($i = 0; $i < 20; $i++) {
      DB::table('reports')->insert([
        'candidate_id' => $candidateIds[array_rand($candidateIds)],
        'job_id' => $jobIds[array_rand($jobIds)],
        'reason_type' => fake()->randomElement(['fraud', 'multi_level', 'wrong_info', 'other']),
        'description' => fake()->randomElement($reportDescriptions), // Đổi sang tiếng Việt
        'status' => fake()->randomElement(['pending', 'resolved', 'rejected']),
        'admin_note' => fake()->optional(0.7)->randomElement($adminNotes), // Đổi sang tiếng Việt chuyên nghiệp
        'created_at' => Carbon::now()->subDays(rand(10, 20)),
        'resolved_at' => fake()->optional()->dateTimeBetween('-5 days', 'now')
      ]);
    }

    // 11. SEED BẢNG JOB_CLICKS (Thống kê lượt xem - ít nhất 20 dòng)
    foreach (array_slice($jobIds, 0, 20) as $jobId) {
      DB::table('job_clicks')->insert([
        'job_id' => $jobId,
        'click_date' => Carbon::now()->subDays(rand(0, 5))->format('Y-m-d'),
        'click_count' => rand(10, 150)
      ]);
    }
  }
}