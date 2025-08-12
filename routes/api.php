<?php

use Illuminate\Support\Facades\Route;

use App\Http\Middleware\EnsureAdmin;

use App\Http\Controllers\Api\V1\{SystemAdminAuthController, 
    AdminRolesController, 
    AdminController, 
    UserController, 
    UserAuthController,
    PropertyController
};
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\App;

Route::prefix('system-admin')->group(function(){
    Route::post('/login', [SystemAdminAuthController::class, 'login']);

    Route::post('/confirm-user', [SystemAdminAuthController::class,'sendOtp']);
    Route::post('/verify-otp', [SystemAdminAuthController::class,'verifyOtp']);
    Route::post('/reset-password', [SystemAdminAuthController::class,'passwordReset']);

    Route::middleware(['auth:sanctum', 'admin'])->group(function(){
        Route::post('/logout', [SystemAdminAuthController::class, 'logout']);
        //System Admin Roles Crud
        Route::post('/create-role', [AdminRolesController::class,'create']);
        Route::post('/update-role/{id}', [AdminRolesController::class,'update']);
        Route::post('/delete-role', [AdminRolesController::class,'destroy']);
        Route::get('/view-roles', [AdminRolesController::class,'viewAll']);
        Route::get('/view-role/{id}', [AdminRolesController::class,'viewOne']);

        //System Admin Crud
        Route::post('/create-admin', [AdminController::class,'create']);
        Route::post('/update-admin/{id}', [AdminController::class,'update']);
        Route::post('/delete-admin', [AdminController::class,'destroy']);
        Route::get('/view-admins', [AdminController::class,'viewAll']);
        Route::get('/view-admin/{id}', [AdminController::class,'viewOne']);
    });
});

Route::post('/login', [UserAuthController::class, 'login']);
Route::post('/register', [UserAuthController::class, 'create']);
Route::post('/verify-otp', [UserAuthController::class, 'verifyOtp']);
Route::post('/resend-otp', [UserAuthController::class, 'resendOtp']);

Route::post('/confirm-user', [UserAuthController::class, 'confirmUser']);
Route::post('/reset-password', [UserAuthController::class, 'resetPasswordOtp']);

Route::middleware(['auth:sanctum'])->group(function(){
    Route::get('/view-own', [UserController::class, 'viewOwn']);
    Route::post('/update-profile', [UserController::class, 'update']);
    Route::post('/deactivate-profile', [UserController::class, 'deactivate']);

    Route::post('/add-property', [PropertyController::class, 'store']);


    Route::post('/change-password', [UserAuthController::class, 'changePassword']);
    Route::post('/logout', [UserAuthController::class, 'logout']);
});