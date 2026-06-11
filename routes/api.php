<?php

use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Candidate\WishlistController;
use App\Http\Controllers\Candidate\AppliedJobController;
use App\Http\Controllers\Candidate\CandidateController;
use App\Http\Controllers\Candidate\CVFileController;


Route::post('/login-user', [LoginController::class, 'LoginUser']);
Route::post('/login-admin', [LoginController::class, 'LoginAdmin']);

Route::get('/home', [HomeController::class, 'index']);
Route::get('/job-detail/{id}', [HomeController::class, 'JobDetail']);
Route::get('/companies/{id}', [HomeController::class, 'getCompanyDetail']);
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
    Route::get('/cv-management/getDataCV', [CandidateController::class, 'getDataCV']);
    Route::post('/cv-management/updateCvTemplate/{id}', [CandidateController::class, 'updateCVTemplate']);
    Route::post('/cv-management/upload-cv', [CVFileController::class, 'uploadCV']);
});
