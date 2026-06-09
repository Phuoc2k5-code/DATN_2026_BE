<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyFactory extends Factory
{
    public function definition(): array
    {
        // Danh sách hậu tố tên công ty tiếng Việt thực tế
        $suffixes = ['Công nghệ', 'Giải pháp số', 'Truyền thông', 'Đầu tư', 'Tập đoàn', 'Thương mại Điện tử', 'Phần mềm'];
        
        // Mẫu dữ liệu thực tế cho các trường mới bổ sung
        $industries = ['Công nghệ thông tin', 'Thương mại điện tử', 'Tài chính / Ngân hàng', 'Marketing / Truyền thông', 'Giáo dục / Đào tạo', 'Logistics / Vận tải'];
        $sizes = ['10-50 nhân viên', '50-100 nhân viên', '100-500 nhân viên', '500-1000 nhân viên', 'Trên 1000 nhân viên'];
        $benefits = [
            "Môi trường làm việc hiện đại, kích thích tư duy phát triển tối đa.\nChế độ đãi ngộ cạnh tranh, thưởng tháng 13 và các dịp lễ Tết.\nĐược tham gia các khóa đào tạo chuyên sâu nội bộ và quốc tế.",
            "Trang thiết bị làm việc hiện đại (cung cấp Macbook/Laptop).\nBảo hiểm sức khỏe cao cấp (PVI) dành cho nhân viên chính thức.\nDu lịch hàng năm (Teambuilding), hoạt động văn hóa thể thao phong phú.",
            "Lương thưởng hấp dẫn theo năng lực và hiệu quả dự án.\nXét tăng lương định kỳ 2 lần/năm.\nCơ hội làm việc với các chuyên gia nước ngoài, nâng cao năng lực ngoại ngữ."
        ];
        $provinces = ['TP. Hồ Chí Minh', 'Hà Nội', 'Đà Nẵng', 'Bình Dương', 'Cần Thơ'];

        return [
            'user_id' => User::factory(),
            'company_name' => 'Công ty Cổ phần ' . $this->faker->randomElement($suffixes) . ' ' . $this->faker->lastName(),
            'logo_url' => 'uploads/logos/company_default.png', 
            'tax_code' => $this->faker->numerify('##########'),
            'business_license' => 'uploads/licenses/giay_phep_mau.pdf',
            'website_url' => $this->faker->url(),
            'description' => 'Chúng tôi là đơn vị tiên phong trong lĩnh vực cung cấp các giải pháp công nghệ và dịch vụ chuyên nghiệp tại Việt Nam. Với môi trường làm việc năng động, sáng tạo và nhiều cơ hội thăng tiến.',
            
            // 🚀 ĐÃ BỔ SUNG CÁC TRƯỜNG DỮ LIỆU ĐỂ LẤP ĐẦY KHUNG GIAO DIỆN CỦA BẠN
            'industry' => $this->faker->randomElement($industries), // Lĩnh vực hoạt động ngẫu nhiên
            'size' => $this->faker->randomElement($sizes),           // Quy mô công ty ngẫu nhiên
            'founded_year' => $this->faker->numberBetween(2010, 2024), // Năm thành lập từ 2010 đến 2024
            'address' => $this->faker->numberBetween(1, 299) . ' Đường ' . $this->faker->streetName() . ', ' . $this->faker->randomElement($provinces), // Địa chỉ dạng Việt Nam vờ
            'benefits' => $this->faker->randomElement($benefits),   // Quyền lợi thực tế bám sát UI của bạn

            'is_verified' => $this->faker->boolean(80),
        ];
    }
}