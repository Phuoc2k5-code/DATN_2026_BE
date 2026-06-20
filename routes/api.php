<?php

use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\Candidate\WishlistController;
use App\Http\Controllers\Candidate\AppliedJobController;
use App\Http\Controllers\Candidate\CandidateController;
use App\Http\Controllers\Candidate\CVFileController;
use App\Http\Controllers\Candidate\AIController;
use App\Http\Controllers\Employer\CompanyController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\CvTemplateController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\SkillController;
use App\Http\Controllers\Admin\SystemModerationController;
use App\Http\Controllers\Admin\StatisticController;



Route::post('/login-user', [LoginController::class, 'LoginUser']);
Route::post('/login-admin', [LoginController::class, 'LoginAdmin']);

Route::get('/home', [HomeController::class, 'index']);
Route::get('/job-detail/{id}', [HomeController::class, 'JobDetail']);
Route::get('/companies/{id}', [HomeController::class, 'getCompanyDetail']);
Route::get('categories', [HomeController::class, 'getCategories']);
Route::post('/jobs/{id}/click', [HomeController::class, 'trackClick']);

Route::middleware('auth:sanctum')->group(function(){
    //--- Profile 
    Route::get('/user-profile', [UserController::class, 'userProfile']);
    Route::post('/user-profile/update', [UserController::class, 'updateProfile']);
    Route::post('/user-password/update', [UserController::class, 'updatePassword']);
    //--- Quản lý lưu tin
    Route::post('/save-job/{jobId}', [WishlistController::class, 'saveJob']);
    Route::get('/wishlist', [WishlistController::class, 'index']);
    //--- 
    Route::get('/applications-history', [AppliedJobController::class, 'history']);
    //--- Quản lý CV
    Route::get('/show-candidate', [CandidateController::class, 'show']);
    Route::post('/save', [CandidateController::class, 'storeOrUpdate']);
    Route::get('get-category', [CandidateController::class, 'getFormData']);
    Route::delete('/delete-candidate', [CandidateController::class, 'deleteCandidate']);
    Route::get('/cv-management', [CandidateController::class, 'index']);
    //--- Mẫu CV    
    Route::get('/cv-templates', [CvTemplateController::class, 'index']);
    Route::get('/cv-management/preview-cv', [CandidateController::class, 'previewCV']);
    Route::post('/cv-management/updateCvTemplate/{id}', [CandidateController::class, 'updateCVTemplate']);
    //--- upload Cv
    Route::post('/cv-management/upload-cv', [CVFileController::class, 'uploadCV']);
    Route::delete('/cv-management/destroy-file', [CVFileController::class, 'destroyFile']);
    Route::get('/cv-management/download-cv', [CVFileController::class, 'downloadAndSaveCV']);
    Route::get('/cv-management/download-cv/{id}', [CVFileController::class, 'downloadAndSaveCV']);
    // --- ứng tuyển nhanh
    Route::get('/quick-applyInit', [AppliedJobController::class, 'quickApplyInit']);
    Route::post('/quick-apply', [AppliedJobController::class, 'quickApply']);
    // --- AI gợi ý việc làm 
    Route::get('/user-cv-list', [AIController::class, 'getUserCvList']);
    Route::get('/ai-recomment', [AIController::class, 'getRecommendations']);
    // --- báo cáo vi phạm
    Route::post('/reports', [ReportController::class, 'store']);

    Route::get('/employer/company', [CompanyController::class, 'getOwnCompany']);
    Route::post('/employer/company/update', [CompanyController::class, 'updateOwnCompany']);

    // --- tạo danh mục
    Route::post('/create-category', [CategoryController::class, 'createCategory']);
    Route::post('/create-skill', [SkillController::class, 'createSkill']);

});

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    //--- quản lý user
    Route::get('/users-except-admin', [AdminUserController::class, 'getUsersExceptAdmin']);
    Route::delete('/users/{id}/lock', [AdminUserController::class, 'lockUser']);
    Route::patch('/users/{id}/unlock', [AdminUserController::class, 'unlockUser']);

    //--- Báo cáo vi phạm 
    Route::get('/violation-reports', [ReportController::class, 'getViolationReports']);
    Route::get('/reports/{id}', [ReportController::class, 'showReportDetail']);
    Route::patch('/reports/{id}/resolve', [ReportController::class, 'resolveReport']);
    Route::patch('/reports/{id}/dismiss', [ReportController::class, 'dismissReport']);

    //--- Quản lý mẫu CV
    Route::get('/cv-templates-management', [CvTemplateController::class, 'index']);
    Route::post('/create-cv-template', [CvTemplateController::class, 'store']);
    Route::put('/update-cv-templates/{id}', [CvTemplateController::class, 'update']);
    Route::delete('/delete-cv-templates/{id}', [CvTemplateController::class, 'destroy']);

    // --- Trang dashboard
    Route::get('/dashboard-stats', [AdminController::class, 'getDashboardStats']);

    // --- Hệ thống kiểm duyệt (Tin đăng & Doanh nghiệp) ---
    Route::prefix('moderation')->group(function () {
        Route::get('/jobs', [SystemModerationController::class, 'getPendingJobs']);
        Route::get('/companies', [SystemModerationController::class, 'getPendingCompanies']);
        Route::put('/jobs/{id}/reject', [SystemModerationController::class, 'rejectJob']);
        Route::put('/jobs/{id}/approve', [SystemModerationController::class, 'approveJob']);
        Route::put('/companies/{id}/reject', [SystemModerationController::class, 'rejectCompany']);
        Route::put('/companies/{id}/approve', [SystemModerationController::class, 'approveCompany']);
    });    // --- KHỐI DANH MỤC NGÀNH NGHỀ ---
    Route::prefix('categories')->group(function () {
        Route::get('/', [CategoryController::class, 'getCategories']);         // Lấy danh sách
        Route::post('/', [CategoryController::class, 'createCategory']);       // Tạo mới
        Route::put('/{id}', [CategoryController::class, 'updateCategory']);    // Cập nhật (Sửa)
        Route::delete('/{id}', [CategoryController::class, 'destroyCategory']); // Xóa
    });

    // --- KHỐI DANH MỤC KỸ NĂNG ---
    Route::prefix('skills')->group(function () {
        Route::get('/', [SkillController::class, 'getSkills']);            // Lấy danh sách
        Route::post('/', [SkillController::class, 'createSkill']);          // Tạo mới
        Route::put('/{id}', [SkillController::class, 'updateSkill']);       // Cập nhật (Sửa)
        Route::delete('/{id}', [SkillController::class, 'destroySkill']);    // Xóa
    });

    // --- báo cáo thống kê
    Route::get('/statistics', [StatisticController::class, 'getStatistics']);

});
