<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Organizer\OrganizerController;
use App\Http\Controllers\Api\V1\PaymentController; // ✅ تأكد من إضافة هذا

use App\Http\Controllers\Organizer\EventController as OrganizerEventController;
use App\Http\Controllers\Organizer\OrderController as OrganizerOrderController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\EventController as AdminEventController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\V1\AttendeeController;
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;


// Public routes
Route::prefix('v1')->group(function () {
    // Authentication
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    
    // Public events (for browsing)
    Route::get('/events', [EventController::class, 'indexPublic']);
    Route::get('/events/{event}', [EventController::class, 'showPublic']);

    Route::get('/email/verify/{id}/{hash}', [VerificationController::class, 'verify'])
    ->name('verification.verify');

    Route::post('/email/resend', [VerificationController::class, 'resend'])
    ->name('verification.resend');

    // استعادة كلمة المرور
    Route::post('/password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])
        ->name('password.email');

    Route::post('/password/reset', [ResetPasswordController::class, 'reset'])
        ->name('password.reset');


 Route::post('/password/verify-code', [ForgotPasswordController::class, 'verifyCode']);



        

});

// Protected routes (require authentication)
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    // Authentication
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    
        // User profile
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::put('/password', [AuthController::class, 'updatePassword']);

    
  // Organizer routes
    Route::prefix('organizer')->middleware(['auth:sanctum'])->group(function () {
        // Dashboard
        Route::get('/dashboard/stats', [OrganizerController::class, 'dashboardStats']);
        Route::get('/dashboard/recent-events', [OrganizerController::class, 'recentEvents']);
        Route::get('/dashboard/recent-orders', [OrganizerController::class, 'recentOrders']);
        // في api.php
        Route::get('/dashboard/review-stats', [OrganizerController::class, 'reviewStats']);
        // Events Management
        Route::get('/events', [OrganizerEventController::class, 'index']);
        Route::post('/events', [OrganizerEventController::class, 'store']);
        Route::get('/events/{event}', [OrganizerEventController::class, 'show']);
        Route::put('/events/{event}', [OrganizerEventController::class, 'update']);
        Route::delete('/events/{event}', [OrganizerEventController::class, 'destroy']);
        Route::put('/events/{event}/status', [OrganizerEventController::class, 'updateStatus']);
       

        // Attendees Management
        Route::get('/events/{event}/attendees', [OrganizerEventController::class, 'attendees']);
        Route::get('/events/{event}/reviews', [OrganizerEventController::class, 'reviews']);
        
        // Orders Management
        Route::get('/orders', [OrganizerOrderController::class, 'index']);
        Route::get('/orders/{order}', [OrganizerOrderController::class, 'show']);
        Route::put('/orders/{order}/status', [OrganizerOrderController::class, 'updateStatus']);
        Route::post('/orders/{order}/generate-tickets', [OrganizerOrderController::class, 'generateTickets']);
        Route::get('/orders/{order}/tickets/{ticket}/download', [OrganizerOrderController::class, 'downloadTicketPdf']);
     
    
       
       
    });
    



    Route::prefix('admin')->middleware(['auth:sanctum'])->group(function () {
    // Dashboard
    Route::get('/dashboard/stats', [AdminController::class, 'dashboardStats']);
    Route::get('/dashboard/monthly-revenue', [AdminController::class, 'monthlyRevenue']);
    Route::get('/dashboard/recent-events', [AdminController::class, 'recentEvents']);
    Route::get('/dashboard/recent-orders', [AdminController::class, 'recentOrders']);
    
    // Users Management
    Route::get('/users', [AdminUserController::class, 'index']);
    Route::post('/users', [AdminUserController::class, 'store']); 
    Route::get('/users/{user}', [AdminUserController::class, 'show']);
    Route::put('/users/{user}', [AdminUserController::class, 'update']);
    Route::delete('/users/{user}', [AdminUserController::class, 'destroy']);
    Route::put('/users/{user}/password', [AdminUserController::class, 'updatePassword']);

    
    // Events Management
    Route::get('/events', [AdminEventController::class, 'index']);
    Route::get('/events/{event}', [AdminEventController::class, 'show']);
    Route::put('/events/{event}', [AdminEventController::class, 'update']);
    Route::delete('/events/{event}', [AdminEventController::class, 'destroy']);
    Route::put('/events/{event}/status', [AdminEventController::class, 'updateStatus']);
    
    // Orders Management
    Route::get('/orders', [AdminOrderController::class, 'index']);
    Route::get('/orders/{order}', [AdminOrderController::class, 'show']);
    Route::put('/orders/{order}/status', [AdminOrderController::class, 'updateStatus']);
    Route::get('/orders/export', [AdminOrderController::class, 'export']);
});


Route::prefix('attendee')->middleware('auth:sanctum')->group(function () {
    // الفعاليات
    Route::get('/events', [AttendeeController::class, 'events']);
    Route::get('/events/{id}', [AttendeeController::class, 'showEvent']);
    
    // الطلبات والتذاكر
    Route::post('/orders', [AttendeeController::class, 'createOrder']);
    Route::post('/orders/{orderId}/confirm-payment', [AttendeeController::class, 'confirmPayment']);
    Route::get('/my-tickets', [AttendeeController::class, 'myTickets']);
  


     Route::get('/tickets/{ticket}/download', [AttendeeController::class, 'downloadTicket']);
    Route::get('/tickets/{ticket}/download-qr', [AttendeeController::class, 'downloadQRCode']);
    Route::get('/orders/{order}/download-all', [AttendeeController::class, 'downloadAllTickets']);
    // التقييمات
    Route::post('/reviews', [AttendeeController::class, 'createReview']);


 
});

Route::prefix('payment')->group(function () {
        Route::get('/order/{orderId}', [PaymentController::class, 'getOrderDetails']);
        Route::post('/process/{orderId}', [PaymentController::class, 'processPayment']);
        Route::get('/success/{orderId}', [PaymentController::class, 'paymentSuccess']);
       Route::post('/cancel/{orderId}', [PaymentController::class, 'paymentCancel']);
    });
});


