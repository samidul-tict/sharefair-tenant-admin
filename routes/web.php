<?php

use App\Http\Controllers\backend\AuthController;
use App\Http\Controllers\frontend\HomeController;

use App\Http\Controllers\backend\DefaultController as AdminDefaultController;
use App\Http\Controllers\backend\TenantController as AdminTenantController;
use App\Http\Controllers\backend\UserController as AdminUserController;
use App\Http\Controllers\backend\CouponController as AdminCouponController;
use App\Http\Controllers\backend\PlanController as AdminPlanController;
use App\Http\Controllers\backend\RoleController as AdminRoleController;
use App\Http\Controllers\backend\CountryController as AdminCountryController;
use App\Http\Controllers\backend\StateController as AdminStateController;
use App\Http\Controllers\backend\CaseController as AdminCaseController;
use App\Http\Controllers\backend\ProfileController as AdminProfileController;
use App\Http\Controllers\backend\SubscriptionController as AdminSubscriptionController;
use Illuminate\Support\Facades\Route;

// default testing route
Route::get('/health-check', function () { return view('welcome'); });

// web sites routes
Route::get('/', [HomeController::class, 'index'])->name('home.index');
Route::get('/about', [HomeController::class, 'about'])->name('home.about');
Route::get('/service', [HomeController::class, 'service'])->name('home.service');
Route::get('/contact', [HomeController::class, 'contact'])->name('home.contact');
Route::get('/enquiry', [HomeController::class, 'enquiry'])->name('home.enquiry');
Route::get('/stories/{story}', [HomeController::class, 'storyShow'])->name('home.story.show');
Route::get('/privacy-policy', [HomeController::class, 'privacyPolicy'])->name('home.privacy-policy');
Route::get('/terms-of-use', [HomeController::class, 'termsOfUse'])->name('home.terms-of-use');
Route::get('/notice-of-practices', [HomeController::class, 'noticeOfPractices'])->name('home.notice-of-practices');

// site routes for admin login (OTP only; no password)
Route::get('/admin/login', [AuthController::class, 'index'])->name('admin.login');
Route::post('/admin/request-otp', [AuthController::class, 'requestOtp'])->name('admin.request-otp');
Route::post('/admin/login', [AuthController::class, 'login'])->name('admin.login.post');

// protected routes (authenticated users)
Route::middleware('auth')->group(function () {
    //  admin: main home routes
    Route::get('/admin/dashboard', [AdminDefaultController::class, 'dashboard'])->name('admin.dashboard');

    // admin: profile (current user)
    Route::get('/admin/profile', [AdminProfileController::class, 'show'])->name('admin.profile');
    Route::put('/admin/profile', [AdminProfileController::class, 'update'])->name('admin.profile.update');

    //admin: users CRUD routes (specific routes before {id} to avoid wrong matches)
    Route::get('/admin/users', [AdminUserController::class, 'index'])->name('admin.users.index');
    Route::get('/admin/users/create', [AdminUserController::class, 'create'])->name('admin.users.create');
    Route::get('/admin/users/search', [AdminUserController::class, 'search'])->name('admin.users.search');
    Route::post('/admin/users', [AdminUserController::class, 'store'])->name('admin.users.store');
    Route::get('/admin/users/{id}/reset-password', [AdminUserController::class, 'resetPasswordForm'])->name('admin.users.reset-password');
    Route::put('/admin/users/{id}/reset-password', [AdminUserController::class, 'resetPassword'])->name('admin.users.reset-password.submit');
    Route::get('/admin/users/{id}/edit', [AdminUserController::class, 'edit'])->name('admin.users.edit');
    Route::get('/admin/users/{id}', [AdminUserController::class, 'show'])->name('admin.users.show');
    Route::put('/admin/users/{id}', [AdminUserController::class, 'update'])->name('admin.users.update');
    Route::delete('/admin/users/{id}', [AdminUserController::class, 'destroy'])->name('admin.users.destroy');

    // admin: case CRUD routes  
    Route::get('/admin/cases', [AdminCaseController::class, 'index'])->name('admin.cases.index');
    Route::get('/admin/cases/create', [AdminCaseController::class, 'create'])->name('admin.cases.create');
    Route::post('/admin/cases', [AdminCaseController::class, 'store'])->name('admin.cases.store');
    Route::get('/admin/cases/{id}', [AdminCaseController::class, 'show'])->name('admin.cases.show');
    Route::get('/admin/cases/{id}/edit', [AdminCaseController::class, 'edit'])->name('admin.cases.edit');
    Route::put('/admin/cases/{id}', [AdminCaseController::class, 'update'])->name('admin.cases.update');
    Route::delete('/admin/cases/{id}', [AdminCaseController::class, 'destroy'])->name('admin.cases.destroy');

    Route::post('/admin/case-activity/store', [AdminCaseController::class, 'saveActivity'])->name('admin.case.activity.store');
    Route::get('/admin/case-activity/list/{caseId}', [AdminCaseController::class, 'list'])->name('admin.case.activity.list');
    Route::post('/cases/{id}/assign', [AdminCaseController::class, 'updateAssignUsers'])->name('admin.cases.updateAssign');
    Route::post('/admin/cases/{id}/remove-user', [AdminCaseController::class, 'removeCaseUser'])->name('admin.cases.removeUser');

    // Subscription Routes
    Route::get('/admin/my-subscriptions', [AdminSubscriptionController::class, 'index'])->name('admin.my-subscriptions');
    Route::get('/admin/subscriptions/{id}/invoice', [AdminSubscriptionController::class, 'downloadInvoice'])->name('admin.subscriptions.invoice');
    Route::post('/admin/subscriptions/auto-renew', [AdminSubscriptionController::class, 'toggleAutoRenew'])->name('admin.subscriptions.auto-renew');
    Route::post('/admin/subscriptions/store', [AdminSubscriptionController::class, 'store'])->name('admin.subscriptions.store');
    Route::post('/admin/subscriptions/check-coupon', [AdminSubscriptionController::class, 'checkCoupon'])->name('admin.subscriptions.check-coupon');

    // common routes for getting states by country id

    // admin: logout
    Route::post('/admin/logout', [AuthController::class, 'logout'])->name('admin.logout');
});

