<?php
namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class JobFactory extends Factory
{
    public function definition(): array
    {
        // Mảng tiêu đề tin tuyển dụng tiếng Việt mẫu
        $jobTitles = [
            'Lập trình viên Laravel / PHP', 'Chuyên viên Marketing Online', 'Nhân viên Kinh doanh', 
            'Kế toán tổng hợp', 'Nhân viên Hành chính Nhân sự', 'Thiết kế đồ họa UI/UX', 
            'Kỹ sư Xây dựng Giám sát', 'Biên dịch viên tiếng Anh', 'Trợ lý Giám đốc', 'Thực tập sinh Công nghệ'
        ];

        $salaryMin = $this->faker->numberBetween(5, 15) * 1000000;
        return [
            'company_id' => Company::pluck('id')->random(),
            'category_id' => $this->faker->numberBetween(1, 10),
            'title' => $this->faker->randomElement($jobTitles),
            'level' => $this->faker->randomElement(['Thực tập sinh', 'Mới tốt nghiệp', 'Nhân viên', 'Trưởng nhóm', 'Quản lý']),
            'salary_min' => $salaryMin,
            'salary_max' => $salaryMin + ($this->faker->numberBetween(5, 15) * 1000000),
            'is_negotiable' => $this->faker->boolean(30),
            'location' => $this->faker->randomElement(['Hà Nội', 'TP. Hồ Chí Minh', 'Đà Nẵng', 'Bình Dương']),
            'description' => 'Thực hiện các công việc theo yêu cầu của trưởng bộ phận. Tham gia vào quy trình phát triển sản phẩm, tối ưu hóa hệ thống và báo cáo tiến độ định kỳ.',
            'requirements' => 'Có kiến thức nền tảng vững chắc về vị trí ứng tuyển. Chăm chỉ, chịu khó học hỏi, có tinh thần làm việc nhóm và chịu được áp lực công việc tốt.',
            'benefits' => 'Hưởng đầy đủ chế độ BHXH, BHYT. Lương tháng 13 và thưởng dự án dựa trên hiệu suất công việc. Được tham gia các hoạt động team building của công ty.',
            'expired_at' => $this->faker->dateTimeBetween('+2 weeks', '+2 months')->format('Y-m-d'),
            'status' => $this->faker->randomElement(['active', 'closed']),
        ];
    }
}