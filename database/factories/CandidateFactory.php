<?php
namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CandidateFactory extends Factory
{
    public function definition(): array
    {
        $vietnameseNames = [
            'Nguyễn Văn Nam', 'Trần Thị Thu Hà', 'Lê Hoàng Long', 'Phạm Minh Tuấn', 
            'Vũ Hoàng Anh', 'Ngô Quốc Bảo', 'Đặng Thúy Nga', 'Bùi Văn Hùng', 
            'Đỗ Thùy Linh', 'Hồ Sĩ Nguyên', 'Dương Minh Triết', 'Võ Quốc Thắng', 
            'Phan Thanh Bình', 'Lý Hải Đăng', 'Nguyễn Bích Phương', 'Trần Đức Anh',
            'Trịnh Hoài Nam', 'Nguyễn Thảo Nguyên', 'Cao Minh Đạt', 'Mai Phương Thúy'
        ];

        // Tạo username ngẫu nhiên để làm link profile không bị dính chữ facebook
        $username = Str::slug($this->faker->userName(), ''); 

        return [
            'user_id' => User::factory(),
            'cv_template_id' => $this->faker->numberBetween(1, 5),
            'category_id' => $this->faker->numberBetween(1, 10),
            'full_name' => $this->faker->randomElement($vietnameseNames), 
            'gender' => $this->faker->randomElement(['Nam', 'Nữ']),
            'birthday' => $this->faker->date('Y-m-d', '-22 years'),
            'phone' => $this->faker->phoneNumber(),
            'email' => $this->faker->unique()->safeEmail(),
            'address' => $this->faker->randomElement(['Hà Nội', 'TP. Hồ Chí Minh', 'Đà Nẵng', 'Cần Thơ', 'Bình Dương']), 
            'avatar_url' => 'uploads/avatars/avatar_mau.png',
            'summary' => 'Là một người năng nổ, có trách nhiệm trong công việc. Tôi luôn mong muốn được cống hiến hết mình cho công ty và học hỏi thêm nhiều kinh nghiệm thực tế.',
            'objective' => 'Tìm kiếm cơ hội việc làm ổn định, nâng cao kỹ năng chuyên môn và thăng tiến lên các vị trí cao hơn.',
            'links' => json_encode([
                'github' => "https://github.com/" . $username, 
                'linkedin' => "https://linkedin.com/in/" . $username
            ]),
            'experience_years' => $this->faker->numberBetween(0, 5),
            'project' => json_encode([['name' => 'Hệ thống website thông minh', 'description' => 'Xây dựng ứng dụng quản lý cốt lõi cho doanh nghiệp.']]),
            'education' => 'Tốt nghiệp chuyên ngành Công nghệ phần mềm',
            'contact_reference' => json_encode(['name' => 'Người tham chiếu', 'phone' => '0901234567']),
        ];
    }
}