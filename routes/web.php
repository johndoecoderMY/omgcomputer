<?php

use App\Http\Controllers\BillingDocumentController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\InventoryPartController;
use App\Http\Controllers\PlanningProjectController;
use App\Http\Controllers\PlanningTaskController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RepairTicketController;
use App\Http\Controllers\ReportsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::redirect('/about', '/#about')->name('about');
Route::redirect('/help', '/#help')->name('help');
Route::redirect('/contact', '/#contact')->name('contact');
Route::view('/privacy', 'pages.privacy')->name('privacy');
Route::view('/toc', 'pages.toc')->name('toc');
Route::view('/terms', 'pages.toc')->name('terms');
Route::redirect('/faq', '/#faq')->name('faq');
Route::redirect('/qna', '/#faq')->name('qna');
Route::redirect('/qa', '/#faq')->name('qa');

Route::middleware('auth')->group(function () {
    Route::get('/repair-tickets/create', [RepairTicketController::class, 'create'])->name('tickets.create');
    Route::post('/repair-tickets', [RepairTicketController::class, 'store'])->name('tickets.store');
    Route::get('/repair-tickets', [RepairTicketController::class, 'index'])->name('tickets.index');
    Route::get('/repair-tickets/{repairTicket}', [RepairTicketController::class, 'show'])->name('tickets.show');
    Route::patch('/repair-tickets/{repairTicket}', [RepairTicketController::class, 'update'])->name('tickets.update');
    Route::get('/repair-tickets/{repairTicket}/images/{image}', [RepairTicketController::class, 'image'])->whereNumber('image')->name('tickets.images.show');

    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');

    Route::get('/inventory', [InventoryPartController::class, 'index'])->name('inventory.index');
    Route::get('/inventory/parts', [InventoryPartController::class, 'parts'])->name('inventory.parts');
    Route::get('/inventory/movements', [InventoryPartController::class, 'movements'])->name('inventory.movements');
    Route::post('/inventory', [InventoryPartController::class, 'store'])->name('inventory.store');
    Route::patch('/inventory/{inventoryPart}', [InventoryPartController::class, 'update'])->name('inventory.update');
    Route::post('/inventory/{inventoryPart}/movements', [InventoryPartController::class, 'moveStock'])->name('inventory.movements.store');

    Route::get('/billing', [BillingDocumentController::class, 'index'])->name('billing.index');
    Route::get('/billing/create', [BillingDocumentController::class, 'create'])->name('billing.create');
    Route::post('/billing', [BillingDocumentController::class, 'store'])->name('billing.store');
    Route::get('/billing/{billingDocument}', [BillingDocumentController::class, 'show'])->name('billing.documents.show');
    Route::get('/billing/{billingDocument}/pdf', [BillingDocumentController::class, 'pdf'])->name('billing.documents.pdf');
    Route::post('/billing/{billingDocument}/convert', [BillingDocumentController::class, 'convertQuote'])->name('billing.documents.convert');
    Route::post('/billing/{billingDocument}/payments', [BillingDocumentController::class, 'payment'])->name('billing.documents.payments.store');

    Route::get('/reports', [ReportsController::class, 'index'])->name('reports.index');
    Route::get('/reports/export', [ReportsController::class, 'export'])->name('reports.export');

    Route::get('/planner/projects', [PlanningProjectController::class, 'index'])->name('planner.projects');
    Route::get('/projects', [PlanningProjectController::class, 'listing'])->name('projects.index');
    Route::post('/projects', [PlanningProjectController::class, 'store'])->name('projects.store');
    Route::get('/projects/{planningProject}', [PlanningProjectController::class, 'show'])->name('projects.show');
    Route::post('/projects/{planningProject}/images', [PlanningProjectController::class, 'storeImages'])->name('projects.images.store');
    Route::get('/projects/{planningProject}/images/{image}', [PlanningProjectController::class, 'image'])->whereNumber('image')->name('projects.images.show');
    Route::get('/teams', [PlanningProjectController::class, 'teams'])->name('teams.index');
    Route::view('/planner/business-plan', 'planner.business-plan')->name('planner.business-plan');
    Route::get('/planner', [PlanningTaskController::class, 'index'])->name('planner.index');
    Route::post('/planner', [PlanningTaskController::class, 'store'])->name('planner.store');
    Route::get('/planner/{planningTask}', [PlanningTaskController::class, 'show'])->name('planner.show');
    Route::post('/planner/{planningTask}/comments', [PlanningTaskController::class, 'comment'])->name('planner.comments.store');
    Route::post('/planner/{planningTask}/subtasks', [PlanningTaskController::class, 'storeSubtask'])->name('planner.subtasks.store');
    Route::patch('/planner/{planningTask}', [PlanningTaskController::class, 'update'])->name('planner.update');
    Route::patch('/planner/{planningTask}/status', [PlanningTaskController::class, 'updateStatus'])->name('planner.update-status');
    Route::delete('/planner/{planningTask}', [PlanningTaskController::class, 'destroy'])->name('planner.destroy');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
