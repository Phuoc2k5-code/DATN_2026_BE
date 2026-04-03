<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;

Route::post('/login-user', [LoginController::class, 'LoginUser']);
Route::post('/login-admin', [LoginController::class, 'LoginAdmin']);
