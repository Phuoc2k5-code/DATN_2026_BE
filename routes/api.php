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

Route::post('/login-user', [LoginController::class, 'LoginUser']);
Route::post('/login-admin', [LoginController::class, 'LoginAdmin']);

Route::get('/home', [HomeController::class, 'index']);
Route::get('/job-detail/{id}', [HomeController::class, 'JobDetail']);
Route::get('/companies/{id}', [HomeController::class, 'getCompanyDetail']);
Route::get('categories', [HomeController::class, 'getCategories']);
Route::post('/jobs/{id}/click', [HomeController::class, 'trackClick']);

Route::middleware('auth:sanctum')->group(function(){
    Route::get('/user-profile', [UserController::class, 'userProfile']);
    Route::post('/user-profile/update', [UserController::class, 'updateProfile']);
    Route::post('/user-password/update', [UserController::class, 'updatePassword']);
    Route::post('/save-job/{jobId}', [WishlistController::class, 'saveJob']);
    Route::get('/wishlist', [WishlistController::class, 'index']);
    Route::get('/applications-history', [AppliedJobController::class, 'history']);
    Route::get('/show-candidate', [CandidateController::class, 'show']);
    Route::post('/save', [CandidateController::class, 'storeOrUpdate']);
    Route::get('get-category', [CandidateController::class, 'getFormData']);
    Route::delete('/delete-candidate', [CandidateController::class, 'deleteCandidate']);
    Route::get('/cv-management', [CandidateController::class, 'index']);
    Route::get('/cv-templates', [CvTemplateController::class, 'index']);
    Route::get('/cv-management/preview-cv', [CandidateController::class, 'previewCV']);
    Route::post('/cv-management/updateCvTemplate/{id}', [CandidateController::class, 'updateCVTemplate']);
    Route::post('/cv-management/upload-cv', [CVFileController::class, 'uploadCV']);
    Route::delete('/cv-management/destroy-file', [CVFileController::class, 'destroyFile']);
    Route::get('/cv-management/download-cv', [CVFileController::class, 'downloadAndSaveCV']);
    Route::get('/cv-management/download-cv/{id}', [CVFileController::class, 'downloadAndSaveCV']);
    Route::get('/quick-applyInit', [AppliedJobController::class, 'quickApplyInit']);
    Route::post('/quick-apply', [AppliedJobController::class, 'quickApply']);
    Route::get('/user-cv-list', [AIController::class, 'getUserCvList']);
    Route::get('/ai-recomment', [AIController::class, 'getRecommendations']);
    Route::post('/reports', [ReportController::class, 'store']);
    Route::get('/employer/company', [CompanyController::class, 'getOwnCompany']);
    Route::post('/employer/company/update', [CompanyController::class, 'updateOwnCompany']);
    Route::get('/employer/jobs', [CompanyController::class, 'getOwnCompanyJobs']);
    Route::put('/employer/jobs/{id}/toggle-status', [CompanyController::class, 'toggleJobStatus']);
    Route::put('/employer/jobs/{id}/extend', [CompanyController::class, 'extendJob']);
    Route::post('/employer/jobs', [CompanyController::class, 'storeJob']);
    Route::put('/employer/jobs/{id}', [CompanyController::class, 'updateJob']);
});


Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::get('/users-except-admin', [AdminUserController::class, 'getUsersExceptAdmin']);
    Route::delete('/users/{id}/lock', [AdminUserController::class, 'lockUser']);
    Route::patch('/users/{id}/unlock', [AdminUserController::class, 'unlockUser']);
    Route::get('/violation-reports', [ReportController::class, 'getViolationReports']);
    Route::get('/reports/{id}', [ReportController::class, 'showReportDetail']);
    Route::patch('/reports/{id}/resolve', [ReportController::class, 'resolveReport']);
    Route::patch('/reports/{id}/dismiss', [ReportController::class, 'dismissReport']);
    Route::get('/cv-templates-management', [CvTemplateController::class, 'index']);
    Route::post('/create-cv-template', [CvTemplateController::class, 'store']);
    Route::put('/update-cv-templates/{id}', [CvTemplateController::class, 'update']);
    Route::delete('/delete-cv-templates/{id}', [CvTemplateController::class, 'destroy']);
    Route::get('/dashboard-stats', [AdminController::class, 'getDashboardStats']);
});
