<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\CommissioningController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LinkController;
use App\Http\Controllers\NocController;
use App\Http\Controllers\ServiceRequestController;
use App\Http\Controllers\SurveyController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WorkOrderController;
use Illuminate\Support\Facades\Route;

// Guest Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/', function () {
        return redirect()->route('dashboard');
    });

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Surveys
    Route::get('/surveys', [SurveyController::class, 'index'])->name('surveys.index');
    Route::get('/surveys/create', [SurveyController::class, 'create'])->name('surveys.create');
    Route::post('/surveys', [SurveyController::class, 'store'])->name('surveys.store');
    Route::get('/surveys/{survey}', [SurveyController::class, 'show'])->name('surveys.show');
    Route::get('/surveys/{survey}/edit', [SurveyController::class, 'edit'])->name('surveys.edit');
    Route::put('/surveys/{survey}', [SurveyController::class, 'update'])->name('surveys.update');
    Route::post('/surveys/{survey}/cancel', [SurveyController::class, 'cancel'])->name('surveys.cancel');
    Route::post('/surveys/{survey}/issue-work-order', [WorkOrderController::class, 'issueFromSurvey'])->name('surveys.issue-work-order');

    // Transmission Feasibility
    Route::post('/surveys/{survey}/start-processing', [SurveyController::class, 'startProcessing'])
        ->middleware('role:transmission')
        ->name('surveys.start-processing');

    Route::post('/surveys/{survey}/feedback', [SurveyController::class, 'storeFeedback'])
        ->middleware('role:transmission')
        ->name('surveys.feedback');

    // Approvals (MD)
    Route::middleware('role:md')->group(function () {
        Route::get('/approvals', [ApprovalController::class, 'index'])->name('approvals.index');
        Route::post('/approvals/{survey}/decide', [ApprovalController::class, 'decide'])->name('approvals.decide');
        Route::post('/approvals/{survey}/final-commission', [CommissioningController::class, 'finalApprove'])->name('approvals.final-commission');
        Route::post('/approvals/{survey}/return-billing', [CommissioningController::class, 'returnToBilling'])->name('approvals.return-billing');
    });

    // Work Orders
    Route::get('/work-orders', [WorkOrderController::class, 'index'])->name('work-orders.index');
    Route::post('/work-orders/{workOrder}/start', [WorkOrderController::class, 'startWork'])
        ->middleware('role:transmission')
        ->name('work-orders.start');
    Route::post('/work-orders/{workOrder}/handover', [WorkOrderController::class, 'handoverToNoc'])
        ->middleware('role:transmission')
        ->name('work-orders.handover');

    // NOC Verification
    Route::middleware('role:noc')->group(function () {
        Route::get('/noc', [NocController::class, 'index'])->name('noc.index');
        Route::get('/noc/{handover}', [NocController::class, 'show'])->name('noc.show');
        Route::post('/noc/{handover}/start', [NocController::class, 'startTesting'])->name('noc.start');
        Route::post('/noc/{handover}/test', [NocController::class, 'submitTest'])->name('noc.submit-test');
    });

    // Billing
    Route::get('/billing', [BillingController::class, 'index'])
        ->middleware('role:billing,md,reseller')
        ->name('billing.index');
    Route::post('/billing/{survey}/process', [BillingController::class, 'process'])
        ->middleware('role:billing')
        ->name('billing.process');

    // Links Master Inventory
    Route::get('/links', [LinkController::class, 'index'])->name('links.index');
    Route::get('/links/{link}', [LinkController::class, 'show'])->name('links.show');

    // Trouble Tickets
    Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/create', [TicketController::class, 'create'])->name('tickets.create');
    Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
    Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
    Route::post('/tickets/{ticket}/status', [TicketController::class, 'updateStatus'])->name('tickets.update-status');
    Route::post('/tickets/{ticket}/messages', [TicketController::class, 'sendMessage'])->name('tickets.messages.send');
    Route::post('/tickets/{ticket}/verify-up', [TicketController::class, 'verifyCustomerUp'])->name('tickets.verify-up');

    // Service Requests (Upgrade/Downgrade)
    Route::get('/service-requests', [ServiceRequestController::class, 'index'])->name('service-requests.index');
    Route::post('/links/{link}/service-requests', [ServiceRequestController::class, 'store'])->name('links.service-requests.store');
    Route::post('/service-requests/{serviceRequest}/decide', [ServiceRequestController::class, 'decide'])
        ->middleware('role:billing,md,transmission,noc')
        ->name('service-requests.decide');

    // Link NOC Live Chat
    Route::get('/links/{link}/chat', [ChatController::class, 'show'])->name('links.chat');
    Route::post('/links/{link}/chat', [ChatController::class, 'send'])->name('links.chat.send');
    Route::get('/links/{link}/chat/messages', [ChatController::class, 'messages'])->name('links.chat.messages');

    // Activity Logs
    Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');

    // Quick Role Switch (Protected to Super Admin)
    Route::post('/quick-login', [AuthController::class, 'quickLogin'])->name('login.quick');

    // Super Admin User & Role Management
    Route::middleware('role:super_admin')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::post('/users/{user}/login-as', [UserController::class, 'impersonate'])->name('users.impersonate');
    });

    // Exit Impersonation (Available to any active impersonated session)
    Route::post('/users/stop-impersonate', [UserController::class, 'stopImpersonate'])->name('users.stop-impersonate');
});
