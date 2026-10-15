<?php


use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerMessageTemplateController;
use App\Http\Controllers\DevelopmentController;
use App\Http\Controllers\FlowChartController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\RepairTicketController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('welcome'));
Route::redirect('/about', '/#about')->name('about');
Route::redirect('/help', '/#help')->name('help');
Route::redirect('/contact', '/#contact')->name('contact');
Route::view('/privacy', 'pages.privacy')->name('privacy');
Route::view('/toc', 'pages.toc')->name('toc');
Route::view('/terms', 'pages.toc')->name('terms');
Route::redirect('/faq', '/#faq')->name('faq');
Route::redirect('/qna', '/#faq')->name('qna');
Route::redirect('/qa', '/#faq')->name('qa');

Route::get('/dashboard', fn () => view('dashboard'))
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::prefix('book')->name('bookings.')->controller(BookingController::class)->group(function (): void {
    Route::get('/', 'create')->name('create');
    Route::get('/events', 'events')->name('events');
    Route::get('/availability', 'availability')->name('availability');
    Route::post('/', 'store')->name('store');
    Route::get('/confirmation/{reference}', 'confirmation')->name('confirmation');
});

Route::middleware('auth')->group(function (): void {
    Route::prefix('attachments')->name('attachments.')->controller(AttachmentController::class)->group(function (): void {
        Route::get('/{attachment}/file', 'show')->name('show');
    });

    Route::prefix('bookings')->name('bookings.')->controller(BookingController::class)->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::put('/{booking}', 'update')->name('update');
    });

    Route::prefix('customer-messages')->name('customer-messages.')->controller(CustomerMessageTemplateController::class)->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{customerMessage}', 'show')->name('show');
        Route::get('/{customerMessage}/edit', 'edit')->name('edit');
        Route::put('/{customerMessage}', 'update')->name('update');
        Route::delete('/{customerMessage}', 'destroy')->name('destroy');
        Route::post('/{customerMessage}/attachments', [AttachmentController::class, 'storeMessageTemplate'])->name('attachments.store');
    });

    Route::prefix('customers')->name('customers.')->controller(CustomerController::class)->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{customer}', 'show')->name('show');
        Route::get('/{customer}/edit', 'edit')->name('edit');
        Route::put('/{customer}', 'update')->name('update');
    });

    Route::prefix('tickets')->name('tickets.')->group(function (): void {
        Route::get('/', [RepairTicketController::class, 'index'])->name('index');
        Route::get('/create', [RepairTicketController::class, 'create'])->name('create');
        Route::post('/', [RepairTicketController::class, 'store'])->name('store');
        Route::post('/{ticket}/attachments', [AttachmentController::class, 'storeTicket'])->name('attachments.store');
        Route::post('/{ticket}/steps', [RepairTicketController::class, 'storeUpdate'])->name('steps.store');
        Route::post('/{ticket}/steps/{update}/attachments', [AttachmentController::class, 'storeTicketUpdate'])->name('steps.attachments.store');
        Route::get('/{ticket}', [RepairTicketController::class, 'show'])->name('show');
        Route::put('/{ticket}', [RepairTicketController::class, 'update'])->name('update');
    });

    Route::prefix('products')->name('products.')->controller(ProductController::class)->group(function (): void {
        Route::get('/', 'index')->name('index');
    });

    Route::prefix('billing')->name('billing.')->controller(BillingController::class)->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{document}/edit', 'edit')->name('documents.edit');
        Route::put('/{document}', 'update')->name('documents.update');
        Route::get('/{document}', 'show')->name('documents.show');
        Route::post('/{document}/issue', 'issue')->name('documents.convert');
        Route::post('/{document}/payments', 'storePayment')->name('documents.payments.store');
        Route::post('/{document}/void', 'void')->name('documents.void');
        Route::get('/{document}/pdf', 'pdf')->name('documents.pdf');
    });

    Route::prefix('inventory')->name('inventory.')->controller(InventoryController::class)->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::get('/parts', 'parts')->name('parts');
        Route::post('/', 'store')->name('store');
        Route::post('/{part}/movements', 'adjust')->name('movements.store');
        Route::get('/movements', 'movements')->name('movements');
    });

    Route::prefix('reports')->name('reports.')->controller(ReportsController::class)->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::get('/sales', 'sales')->name('sales');
        Route::get('/inventory', 'inventory')->name('inventory');
        Route::get('/teams', 'teams')->name('teams');
        Route::get('/marketing', 'marketing')->name('marketing');
        Route::post('/marketing/campaigns', 'storeCampaign')->name('marketing.store');
        Route::get('/export', 'export')->name('export');
    });

    Route::prefix('projects')->name('projects.')->group(function (): void {
        Route::get('/', [ProjectController::class, 'index'])->name('index');
        Route::get('/create', [ProjectController::class, 'create'])->name('create');
        Route::post('/', [ProjectController::class, 'store'])->name('store');
        Route::get('/{project}', [ProjectController::class, 'show'])->name('show');
        Route::get('/{project}/edit', [ProjectController::class, 'edit'])->name('edit');
        Route::put('/{project}', [ProjectController::class, 'update'])->name('update');
        Route::delete('/{project}', [ProjectController::class, 'destroy'])->name('destroy');
        Route::get('/{project}/tasks/create', [TaskController::class, 'create'])->name('tasks.create');
        Route::post('/{project}/tasks', [TaskController::class, 'store'])->name('tasks.store');
    });

    Route::prefix('tasks')->name('tasks.')->controller(TaskController::class)->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::post('/{task}/attachments', [AttachmentController::class, 'storeTask'])->name('attachments.store');
        Route::post('/{task}/subtasks', 'storeSubtask')->name('subtasks.store');
        Route::patch('/{task}/status', 'updateStatus')->name('update-status');
        Route::get('/{task}', 'show')->name('show');
        Route::get('/{task}/edit', 'edit')->name('edit');
        Route::put('/{task}', 'update')->name('update');
        Route::patch('/{task}', 'update');
        Route::delete('/{task}', 'destroy')->name('destroy');
    });

    Route::prefix('development')->name('development.')->group(function (): void {
        Route::get('/overview', [DevelopmentController::class, 'index'])->name('overview');
        Route::get('/plan', [DevelopmentController::class, 'plan'])->name('plan');
        Route::post('/flowcharts/{flowchart}/tasks', [FlowChartController::class, 'storeTask'])->name('flowcharts.tasks.store');
        Route::patch('/flowcharts/{flowchart}/layout', [FlowChartController::class, 'saveLayout'])->name('flowcharts.layout');
        Route::resource('flowcharts', FlowChartController::class);
    });
});

require __DIR__ . '/auth.php';
