<?php

use Illuminate\Support\Facades\Route;

use App\Http\Middleware\SetEstateManagerFromUrl as setEstate;
use App\Http\Middleware\EnsureAdmin as Admin;
use App\Http\Middleware\SanitizeInput as Sanitize;
use App\Http\Middleware\EnsureLandlord as Landlord;


use App\Http\Controllers\Api\V1\{SystemAdminAuthController, 
    AdminRolesController, 
    AdminController, 
    UserController, 
    UserAuthController,
    PropertyController,
    EstateManagerController,
    ApartmentController,
};

Route::prefix('system-admin')->group(function(){
    Route::post('/login', [SystemAdminAuthController::class, 'login']);

    Route::post('/confirm-user', [SystemAdminAuthController::class,'sendOtp']);
    Route::post('/verify-otp', [SystemAdminAuthController::class,'verifyOtp']);
    Route::post('/reset-password', [SystemAdminAuthController::class,'passwordReset']);

    Route::middleware(['auth:sanctum', Admin::class])->group(function(){
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

        //Estate Manager Endpoints
        Route::post('/create-estate-manager', [EstateManagerController::class,'create']);
        Route::post('/update-estate-manager/{id}', [EstateManagerController::class,'update']);
        Route::get('/view-estate-manager/{id}', [EstateManagerController::class,'getEstateManager']);
        Route::get('/view-estate-managers', [EstateManagerController::class,'getEstateManagers']);
        Route::post('/delete-estate-manager', [EstateManagerController::class,'destroy']);

        //Verification of Landlords and Agents
        Route::get('/view-users-for-verification', [UserController::class,'fetchLandlordsForVerification']);
        Route::post('/accept-verification/{id}', [UserController::class,'verifyLandlordDocuments']);
        Route::post('/reject-verification/{id}', [UserController::class,'rejectLandlordDocuments']);

//for estate managers


// Apartment Category routes
Route::get('/apartment/categories', [ApartmentController::class, 'CategoryIndex']);
Route::post('/apartment/categories', [ApartmentController::class, 'CategoryStore']);
Route::get('/apartment/category/{id}', [ApartmentController::class, 'CategoryShow']);
Route::put('/apartment/category/{id}', [ApartmentController::class, 'CategoryUpdate']);
Route::delete('/apartment/category/{id}', [ApartmentController::class, 'CategoryDestroy']);
    });
});

Route::prefix('{tenant_slug}')->middleware([setEstate::class])->group(function(){
    //Routes that don't need authentication
    Route::post('/login', [UserAuthController::class, 'login']);
    Route::post('/set-password', [UserAuthController::class, 'passwordReset']);
    
    Route::post('/register', [UserAuthController::class, 'create']);
    Route::post('/verify-otp', [UserAuthController::class, 'verifyOtp']);
    Route::post('/resend-otp', [UserAuthController::class, 'resendOtp']);

    Route::post('/confirm-user', [UserAuthController::class, 'confirmUser']);
    Route::post('/reset-password', [UserAuthController::class, 'resetPasswordOtp']);

    //Guarded Routes
    Route::middleware(['auth:sanctum'])->group(function(){
        Route::get('/view-own', [UserController::class, 'viewOwn']);
        Route::post('/update-profile', [UserController::class, 'update']);
        Route::post('/deactivate-profile', [UserController::class, 'deactivate']);

        Route::post('/add-property', [PropertyController::class, 'store']);


        Route::post('/change-password', [UserAuthController::class, 'changePassword']);
        Route::post('/logout', [UserAuthController::class, 'logout']);
        //accessible to all under tenant slug;

        Route::get('/apartments/{id}', [ApartmentController::class, 'show']);
        //Routes Accessible to only Landlords and Agents
        Route::middleware([Landlord::class, Sanitize::class])->group(function(){
            Route::post('/update-landlord', [UserController::class, 'completeLandlordProfile']);
            Route::get('/apartments', [ApartmentController::class, 'index']);
            Route::post('/apartment/create', [ApartmentController::class, 'store']);
            Route::put('/apartments/{id}', [ApartmentController::class, 'update']);
            Route::delete('/apartments/{id}', [ApartmentController::class, 'destroy']);
        });


    });

});



