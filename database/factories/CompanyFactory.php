<?php
namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyFactory extends Factory
{
    public function definition(): array
    {
        // Danh sách hậu tố tên công ty tiếng Việt để bổ trợ cho Faker
        $suffixes = ['Công nghệ', 'Giải pháp số', 'Truyền thông', 'Đầu tư', 'Tập đoàn', 'Thương mại Điện tử'];
        
        return [
            'user_id' => User::factory(),
            'company_name' => 'Công ty Cổ phần ' . $this->faker->randomElement($suffixes) . ' ' . $this->faker->lastName(),
            'tax_code' => $this->faker->numerify('##########'),
            'business_license' => 'uploads/licenses/giay_phep_mau.pdf',
            'website_url' => $this->faker->url(),
            'description' => 'Chúng tôi là đơn vị tiên phong trong lĩnh vực cung cấp các giải pháp công nghệ và dịch vụ chuyên nghiệp tại Việt Nam. Với môi trường làm việc năng động, sáng tạo và nhiều cơ hội thăng tiến.',
            'is_verified' => $this->faker->boolean(80),
        ];
    }
}