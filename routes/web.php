<?php


use App\Http\Controllers\DevelopmentController;
use App\Http\Controllers\FlowChartController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;




Route::get('/', function () {
    return view('welcome');
});
Route::redirect('/about', '/#about')->name('about');
Route::redirect('/help', '/#help')->name('help');
Route::redirect('/contact', '/#contact')->name('contact');
Route::view('/privacy', 'pages.privacy')->name('privacy');
Route::view('/toc', 'pages.toc')->name('toc');
Route::view('/terms', 'pages.toc')->name('terms');
Route::redirect('/faq', '/#faq')->name('faq');
Route::redirect('/qna', '/#faq')->name('qna');
Route::redirect('/qa', '/#faq')->name('qa');



Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/billing', [BillingController::class, 'index'])->name('billing.index');
    Route::get('/billing/create', [BillingController::class, 'create'])->name('billing.create');
    Route::post('/billing', [BillingController::class, 'store'])->name('billing.store');
    Route::get('/billing/{document}', [BillingController::class, 'show'])->name('billing.documents.show');
    Route::post('/billing/{document}/issue', [BillingController::class, 'issue'])->name('billing.documents.convert');
    Route::post('/billing/{document}/payments', [BillingController::class, 'storePayment'])->name('billing.documents.payments.store');
    Route::post('/billing/{document}/void', [BillingController::class, 'void'])->name('billing.documents.void');
    Route::get('/billing/{document}/pdf', [BillingController::class, 'pdf'])->name('billing.documents.pdf');

    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::get('/inventory/parts', [InventoryController::class, 'parts'])->name('inventory.parts');
    Route::post('/inventory', [InventoryController::class, 'store'])->name('inventory.store');
    Route::post('/inventory/{part}/movements', [InventoryController::class, 'adjust'])->name('inventory.movements.store');
    Route::get('/inventory/movements', [InventoryController::class, 'movements'])->name('inventory.movements');

    Route::get('/reports', [ReportsController::class, 'index'])->name('reports.index');
    Route::get('/reports/sales', [ReportsController::class, 'sales'])->name('reports.sales');
    Route::get('/reports/inventory', [ReportsController::class, 'inventory'])->name('reports.inventory');
    Route::get('/reports/teams', [ReportsController::class, 'teams'])->name('reports.teams');
    Route::get('/reports/marketing', [ReportsController::class, 'marketing'])->name('reports.marketing');
    Route::post('/reports/marketing/campaigns', [ReportsController::class, 'storeCampaign'])->name('reports.marketing.store');
    Route::get('/reports/export', [ReportsController::class, 'export'])->name('reports.export');

    Route::resource('projects', ProjectController::class);
    Route::get('/tasks', [TaskController::class, 'index'])->name('tasks.index');
    Route::get('/projects/{project}/tasks/create', [TaskController::class, 'create'])->name('projects.tasks.create');
    Route::post('/projects/{project}/tasks', [TaskController::class, 'store'])->name('projects.tasks.store');
    Route::post('/tasks/{task}/subtasks', [TaskController::class, 'storeSubtask'])->name('tasks.subtasks.store');
    Route::patch('/tasks/{task}/status', [TaskController::class, 'updateStatus'])->name('tasks.update-status');
    Route::get('/tasks/{task}', [TaskController::class, 'show'])->name('tasks.show');
    Route::get('/tasks/{task}/edit', [TaskController::class, 'edit'])->name('tasks.edit');
    Route::put('/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
    Route::patch('/tasks/{task}', [TaskController::class, 'update']);
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');

    Route::prefix('/development')
        ->name('development.')
        ->group(function () {

            Route::get('/overview', [DevelopmentController::class, 'index'])
                ->name('overview');

                  Route::get('/plan', [DevelopmentController::class, 'plan'])
                ->name('plan');

                  Route::resource('flowcharts', FlowChartController::class);
        });

});

require __DIR__ . '/auth.php';
