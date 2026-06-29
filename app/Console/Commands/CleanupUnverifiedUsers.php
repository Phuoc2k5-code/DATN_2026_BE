<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Carbon\Carbon;

class CleanupUnverifiedUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cleanup-unverified-users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Chỉ xóa những tài khoản ở trạng thái 'pending' (chờ xác thực) được tạo từ hơn 1 ngày trước
        User::where('status', 'pending')
        ->where('created_at', '<', Carbon::now()->subDay())
        ->delete();
        
        $this->info('Đã dọn dẹp các tài khoản rác chưa kích hoạt thành công!');
    }
}
