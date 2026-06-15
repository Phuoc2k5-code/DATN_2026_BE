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
use Illuminate\Support\Facades\DB;

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

        // 🚀 BỔ SUNG: TẠO TÀI KHOẢN CANDIDATE PHUOC@GMAIL.COM YÊU CẦU
        User::create([
            'email' => 'phuoc@gmail.com',
            'password' => Hash::make('123'),
            'role' => 'candidate',
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

        // 🚀 4. SEED BẢNG CV TEMPLATES (Dữ liệu chuẩn y chang trong ảnh của bro)
        $templates = [
            [
                'name' => 'Mẫu CV Hiện đại',
                'thumbnail_url' => 'uploads/templates/hiendai.png',
                'file_path' => 'modern'
            ],
            [
                'name' => 'Mẫu CV Cổ điển',
                'thumbnail_url' => 'uploads/templates/codien.png',
                'file_path' => 'classic'
            ],
            [
                'name' => 'Mẫu CV Thanh lịch',
                'thumbnail_url' => 'uploads/templates/thanhlich.png',
                'file_path' => 'elegant'
            ],
            [
                'name' => 'Mẫu CV Sáng tạo',
                'thumbnail_url' => 'uploads/templates/sangtao.png',
                'file_path' => 'creative'
            ],
            [
                'name' => 'Mẫu CV Tối giản',
                'thumbnail_url' => 'uploads/templates/toigian.png',
                'file_path' => 'tech-minimalist'
            ]
        ];
        foreach ($templates as $tmpl) {
            CvTemplate::create($tmpl);
        }

        // 5. TẠO DOANH NGHIỆP (Ít nhất 20 công ty và tài khoản đi kèm)
        Company::factory()->count(25)->create()->each(function ($company) {
            $company->user->update(['role' => 'employer']);
        });

        // 6. TẠO ỨNG VIÊN VÀ ĐỒNG BỘ LẠI BẢNG cv_files SỬA THEO user_id
        Candidate::factory()->count(30)->create()->each(function ($candidate) {
            $candidate->user->update(['role' => 'candidate']);

            $skillIds = Skill::pluck('id')->random(3);
            $levels = ['Cơ bản', 'Khá', 'Thành thạo', 'Chuyên gia'];

            foreach ($skillIds as $skillId) {
                $candidate->skills()->attach($skillId, [
                    'level' => $levels[array_rand($levels)]
                ]);
            }

            // 🧠 SỬA ĐỔI: Chuyển sang lưu trữ theo đúng cấu trúc `user_id` và thêm cột `type` ngẫu nhiên
            $type = fake()->randomElement(['uploaded', 'online']);
            
            $cvFileId = DB::table('cv_files')->insertGetId([
                'user_id' => $candidate->user_id, // Gắn trực tiếp với user_id của Candidate
                'file_name' => ($type === 'online' ? 'CV_Online_' : 'CV_Upload_') . $candidate->id . '.pdf',
                'file_path' => 'uploads/cvs/sample.pdf',
                'file_size' => rand(500, 2048),
                'type' => $type,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);            
        });

        // 7. TẠO TIN TUYỂN DỤNG (Ít nhất 20 tin tuyển dụng)
        Job::factory()->count(25)->create()->each(function ($job) {
            $randomSkills = Skill::pluck('id')->random(rand(2, 5))->toArray();
            $job->skills()->attach($randomSkills);
        });

        $jobIds = Job::pluck('id')->toArray();
        $candidateIds = Candidate::pluck('id')->toArray();

        // 8. SEED BẢNG APPLICATIONS (Đơn ứng tuyển)
        for ($i = 0; $i < 25; $i++) {
            $candId = $candidateIds[array_rand($candidateIds)];
            $candidate = Candidate::find($candId);
            
            // Tìm cv_file dựa trên user_id của candidate ứng tuyển
            $cvFileId = DB::table('cv_files')->where('user_id', $candidate->user_id)->value('id');
            
            $status = fake()->randomElement(['pending', 'viewed', 'interviewing', 'rejected', 'accepted']);
            $statusDetails = null;

            if ($status === 'rejected') {
                $rejectReasons = [
                    'Hồ sơ chưa đạt đủ số năm kinh nghiệm tối thiểu theo yêu cầu.',
                    'Kỹ năng chuyên môn chưa thực sự phù hợp với định hướng của dự án.',
                    'Vị trí tuyển dụng hiện tại đã nhận đủ số lượng nhân sự.'
                ];
                $statusDetails = json_encode([
                    'reject_reason' => fake()->randomElement($rejectReasons),
                    'rejected_at' => Carbon::now()->subDays(rand(1, 5))->toDateTimeString()
                ]);
            } elseif ($status === 'interviewing') {
                $interviewDate = Carbon::now()->addDays(rand(1, 7))->setHour(rand(8, 16))->setMinute(0)->setSecond(0);
                $statusDetails = json_encode([
                    'interview_time' => $interviewDate->toDateTimeString(),
                    'interview_location' => 'Trực tuyến qua Google Meet: https://meet.google.com/abc-xyz-def',
                    'note' => 'Vui lòng đến trước 10 phút và mang theo laptop cá nhân.',
                    'contact_person' => 'Ms. Linh (HR Dept)'
                ]);
            } elseif ($status === 'accepted') {
                $onboardingDate = Carbon::now()->addDays(rand(10, 20))->setHour(8)->setMinute(30)->setSecond(0);
                $statusDetails = json_encode([
                    'onboarding_time' => $onboardingDate->toDateTimeString(),
                    'onboarding_location' => 'Văn phòng Công ty tại Trụ sở chính',
                    'note' => 'Vui lòng mang theo CCCD công chứng và bằng cấp để đối chiếu.',
                    'contact_person' => 'Ms. Thủy (Head of HR)'
                ]);
            }

            DB::table('applications')->insert([
                'job_id' => $jobIds[array_rand($jobIds)],
                'user_id' => $candidate->user_id,
                'cv_file_id' => $cvFileId,
                'description' => 'Tôi rất mong muốn được ứng tuyển vào vị trí này. Xin cảm ơn!',
                'matching_score' => fake()->randomFloat(2, 50, 95),
                'status' => $status,
                'status_details' => $statusDetails,
                'applied_at' => Carbon::now()->subDays(rand(1, 15))
            ]);
        }

        // 9. SEED BẢNG WISHLISTS (Tin tuyển dụng đã lưu)
        $userIds = User::where('role', 'candidate')->pluck('id')->toArray();
        for ($i = 0; $i < 20; $i++) {
            DB::table('wishlists')->insert([
                'user_id' => $userIds[array_rand($userIds)],
                'job_id' => $jobIds[array_rand($jobIds)],
                'created_at' => Carbon::now()->subDays(rand(1, 30))
            ]);
        }

        // 10. SEED BẢNG REPORTS (Đã cập nhật linh hoạt cho cả Job và Company)
        $companyIds = Company::pluck('id')->toArray(); // Lấy thêm mảng ID công ty để fake dữ liệu

        $reportDescriptions = [
            'Tin tuyển dụng có dấu hiệu lừa đảo đóng tiền cọc trước khi nhận việc.',
            'Mô tả công việc không giống với thực tế khi đến phỏng vấn tại văn phòng.',
            'Mức lương ghi trên bài đăng không đúng với thỏa thuận ban đầu.',
            'Hồ sơ doanh nghiệp giả mạo, không có địa chỉ văn phòng thực tế.'
        ];
        $adminNotes = [
            'Đã khóa bài viết và cảnh cáo tài khoản nhà tuyển dụng.',
            'Đã duyệt ẩn tin tuyển dụng lỗi này khỏi trang chủ.',
            'Đã tạm thời ẩn thông tin doanh nghiệp chờ đối chiếu giấy phép.'
        ];

        for ($i = 0; $i < 20; $i++) {
            // 🎲 Tỷ lệ 50/50: Quyết định dòng này báo cáo Job hay báo cáo Công ty trực tiếp
            $isJobReport = fake()->boolean(50); 

            $jobId = null;
            $companyId = null;
            $reasonType = 'other';

            if ($isJobReport && !empty($jobIds)) {
                // 📌 Fake dữ liệu báo cáo bài đăng tuyển dụng (Job)
                $jobId = $jobIds[array_rand($jobIds)];
                $reasonType = fake()->randomElement(['fraud', 'wrong_info', 'expired']);
            } elseif (!empty($companyIds)) {
                // 📌 Fake dữ liệu báo cáo trực tiếp doanh nghiệp (Company)
                $companyId = $companyIds[array_rand($companyIds)];
                $reasonType = fake()->randomElement(['fake_company', 'bad_behavior', 'other']);
            }

            DB::table('reports')->insert([
                // Giữ nguyên logic bốc ngẫu nhiên ID ứng viên đi báo cáo của bạn
                'user_id' => $candidate->user_id, 
                
                // Cột nào không được chọn sẽ mang giá trị NULL chuẩn đét trong DB
                'job_id'       => $jobId,
                'company_id'   => $companyId,
                
                'reason_type'  => $reasonType,
                'description'  => fake()->randomElement($reportDescriptions),
                'status'       => fake()->randomElement(['pending', 'resolved', 'rejected']),
                'admin_note'   => fake()->optional(0.7)->randomElement($adminNotes),
                'created_at'   => Carbon::now()->subDays(rand(10, 20)),
                'updated_at'   => Carbon::now()->subDays(rand(0, 9))
            ]);
        }

        // 11. SEED BẢNG JOB_CLICKS
        foreach (array_slice($jobIds, 0, 20) as $jobId) {
            DB::table('job_clicks')->insert([
                'job_id' => $jobId,
                'click_date' => Carbon::now()->subDays(rand(0, 5))->format('Y-m-d'),
                'click_count' => rand(10, 150)
            ]);
        }
    }
}