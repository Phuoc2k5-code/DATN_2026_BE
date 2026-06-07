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

    // 2. SEED BẢNG CATEGORIES (10 ngành nghề) [cite: 394]
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
      'PHP',
      'Laravel',
      'JavaScript',
      'Vue.js',
      'ReactJS',
      'Node.js',
      'Python',
      'Django',
      'Java',
      'Spring Boot',
      'C#',
      '.NET',
      'Ruby on Rails',
      'Swift',
      'Kotlin',
      'Flutter',
      'React Native',
      'SQL',
      'MySQL',
      'PostgreSQL',
      'MongoDB',
      'Docker',
      'Kubernetes',
      'AWS',
      'Git',
      'HTML5',
      'CSS3',
      'Tailwind CSS',
      'Bootstrap',
      'TypeScript',
      // Marketing & Sales
      'SEO',
      'Google Ads',
      'Facebook Ads',
      'Content Writing',
      'Copywriting',
      'Google Analytics',
      'Email Marketing',
      'B2B Sales',
      'B2C Sales',
      'Telemarketing',
      'Market Research',
      'Public Relations',
      'Branding',
      'Event Planning',
      // Design
      'Photoshop',
      'Illustrator',
      'Figma',
      'Adobe XD',
      'Premiere Pro',
      'After Effects',
      '3D Max',
      'AutoCAD',
      // Ngôn ngữ & Kỹ năng mềm
      'Tiếng Anh Giao Tiếp',
      'TOEIC 700+',
      'IELTS 6.5+',
      'Tiếng Nhật N3',
      'Tiếng Trung',
      'Kỹ năng giao tiếp',
      'Làm việc nhóm',
      'Giải quyết vấn đề',
      'Quản lý thời gian',
      'Thuyết trình',
      'Đàm phán',
      'Tư duy phản biện',
      // Kinh tế & Khác
      'Kế toán tổng hợp',
      'Misa',
      'Excel nâng cao',
      'Phân tích dữ liệu',
      'Quản trị nhân sự',
      'Tuyển dụng',
      'Tính lương (C&B)',
      'Chăm sóc khách hàng',
      'Quản lý dự án',
      'Scrum/Agile',
      'Kỹ năng lãnh đạo',
      'SEO Youtube'
    ];
    foreach ($skills as $skill) {
      Skill::create(['name' => $skill]);
    }

    // 4. SEED BẢNG CV TEMPLATES (5 mẫu) [cite: 390]
    for ($i = 1; $i <= 5; $i++) {
      CvTemplate::create([
        'name' => "Mẫu CV chuyên nghiệp v$i",
        'thumbnail_url' => "uploads/templates/thumb_$i.png",
        'file_path' => "templates/cv_template_$i.blade.php"
      ]);
    }

    // 5. TẠO DOANH NGHIỆP (Ít nhất 20 công ty và tài khoản đi kèm) [cite: 368, 382, 384]
    Company::factory()->count(25)->create()->each(function ($company) {
      // Cập nhật lại role của user sở hữu công ty này thành 'employer' [cite: 382, 384]
      $company->user->update(['role' => 'employer']);
    });

    // 6. TẠO ỨNG VIÊN (Ít nhất 20 ứng viên và tài khoản đi kèm) [cite: 369, 382, 388]
    $candidates = Candidate::factory()->count(30)->create()->each(function ($candidate) {
      // Cập nhật lại role của user ứng viên thành 'candidate' [cite: 382, 388]
      $candidate->user->update(['role' => 'candidate']);

      // Đồng thời đính kèm ngẫu nhiên từ 3 đến 6 kỹ năng cho ứng viên (bảng candidate_skill) [cite: 400]
      $randomSkills = Skill::pluck('id')->random(rand(3, 6))->toArray();
      $candidate->skills()->attach($randomSkills); // Hãy đảm bảo bạn đã định nghĩa quan hệ belongsToMany trong Model Candidate [cite: 400]

      // Tạo luôn file CV đính kèm cho mỗi ứng viên (Bảng cv_files) [cite: 392]
      DB::table('cv_files')->insert([
        'candidate_id' => $candidate->id,
        'file_name' => 'CV_Luu_Tru_' . $candidate->id . '.pdf',
        'file_path' => 'uploads/cvs/sample.pdf',
        'file_size' => rand(500, 2048),
        'uploaded_at' => Carbon::now()
      ]);
    });

    // 7. TẠO TIN TUYỂN DỤNG (Ít nhất 20 tin tuyển dụng) [cite: 370, 386]
    Job::factory()->count(25)->create()->each(function ($job) {
      // Mỗi tin tuyển dụng đính kèm ngẫu nhiên từ 2 đến 5 kỹ năng yêu cầu (bảng job_skill) [cite: 398]
      $randomSkills = Skill::pluck('id')->random(rand(2, 5))->toArray();
      $job->skills()->attach($randomSkills); // Hãy đảm bảo đã định nghĩa quan hệ belongsToMany trong Model Job [cite: 398]
    });

    // Lấy danh sách ID đã sinh ra để làm dữ liệu cho các bảng tương tác [cite: 386, 388]
    $candidateIds = Candidate::pluck('id')->toArray();
    $jobIds = Job::pluck('id')->toArray();

    // 8. SEED BẢNG APPLICATIONS (Đơn ứng tuyển - ít nhất 20 dòng) [cite: 402]
    for ($i = 0; $i < 25; $i++) {
      $candId = $candidateIds[array_rand($candidateIds)];
      // Tìm file CV tương ứng của candidate [cite: 392]
      $cvFileId = DB::table('cv_files')->where('candidate_id', $candId)->value('id');

      // Đoạn seed đơn ứng tuyển (Applications) trong DatabaseSeeder.php
      DB::table('applications')->insert([
        'job_id' => $jobIds[array_rand($jobIds)],
        'candidate_id' => $candId,
        'cv_type' => fake()->randomElement(['online', 'pdf']),
        'cv_file_id' => $cvFileId,
        'description' => 'Tôi rất mong muốn được ứng tuyển vào vị trí này của quý công ty. Xin cảm ơn!', // Thay đổi ở đây
        'matching_score' => fake()->randomFloat(2, 50, 95),
        'status' => fake()->randomElement(['pending', 'viewed', 'interviewing', 'rejected', 'accepted']),
        'applied_at' => Carbon::now()->subDays(rand(1, 15))
      ]);
    }

    // 9. SEED BẢNG WISHLISTS (Tin tuyển dụng đã lưu - ít nhất 20 dòng) [cite: 404]
    for ($i = 0; $i < 20; $i++) {
      DB::table('wishlists')->insert([
        'candidate_id' => $candidateIds[array_rand($candidateIds)],
        'job_id' => $jobIds[array_rand($jobIds)],
        'created_at' => Carbon::now()->subDays(rand(1, 30))
      ]);
    }

    // 10. SEED BẢNG REPORTS (Báo cáo tin tuyển dụng xấu - ít nhất 20 dòng) [cite: 408]
    for ($i = 0; $i < 20; $i++) {
      DB::table('reports')->insert([
        'candidate_id' => $candidateIds[array_rand($candidateIds)],
        'job_id' => $jobIds[array_rand($jobIds)],
        'reason_type' => fake()->randomElement(['fraud', 'multi_level', 'wrong_info', 'other']),
        'description' => fake()->sentence(15),
        'status' => fake()->randomElement(['pending', 'resolved', 'rejected']),
        'admin_note' => fake()->optional()->sentence(5),
        'created_at' => Carbon::now()->subDays(rand(10, 20)),
        'resolved_at' => fake()->optional()->dateTimeBetween('-5 days', 'now')
      ]);
    }

    // 11. SEED BẢNG JOB_CLICKS (Thống kê lượt xem - ít nhất 20 dòng) [cite: 406]
    foreach (array_slice($jobIds, 0, 20) as $jobId) {
      DB::table('job_clicks')->insert([
        'job_id' => $jobId,
        'click_date' => Carbon::now()->subDays(rand(0, 5))->format('Y-m-d'),
        'click_count' => rand(10, 150)
      ]);
    }
  }
}