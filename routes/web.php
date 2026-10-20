<?php

use App\Http\Controllers\AdminAccessController;
use App\Http\Controllers\AdminBackupController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\BookingTimeSlotController;
use App\Http\Controllers\BusinessExpenseController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerMessageTemplateController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DevelopmentController;
use App\Http\Controllers\FlowChartController;
use App\Http\Controllers\IntegrationTestController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\MarketplaceShopController;
use App\Http\Controllers\MetaContentController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectEventController;
use App\Http\Controllers\ProjectExportController;
use App\Http\Controllers\ProjectTechnologyController;
use App\Http\Controllers\RepairTicketController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TeamController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('welcome'));
// Route::get('/sitemap.xml', fn () => response()
//     ->view('sitemap', [], 200, ['Content-Type' => 'application/xml; charset=UTF-8']))
//     ->name('sitemap');
Route::redirect('/about', '/#about')->name('about');
Route::redirect('/help', '/#help')->name('help');
Route::redirect('/contact', '/#contact')->name('contact');
Route::view('/privacy', 'pages.privacy')->name('privacy');
Route::view('/toc', 'pages.toc')->name('toc');
Route::view('/terms', 'pages.toc')->name('terms');
Route::redirect('/faq', '/#faq')->name('faq');
Route::redirect('/qna', '/#faq')->name('qna');
Route::redirect('/qa', '/#faq')->name('qa');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'password.changed', 'verified'])
    ->name('dashboard');

Route::get('/error-pages/{code}', function (string $code) {
    abort_unless(in_array($code, ['403', '404', '419', '429', '500', '503'], true), 404);

    return response()->view("errors.{$code}", [], 200);
})->middleware(['auth', 'password.changed', 'verified', 'not-customer'])->name('error-pages.preview');

Route::prefix('book')->name('bookings.')->controller(BookingController::class)->group(function (): void {
    Route::get('/', 'create')->name('create');
    Route::get('/events', 'events')->name('events');
    Route::get('/availability', 'availability')->name('availability');
    Route::post('/', 'store')->name('store');
    Route::get('/confirmation/{reference}', 'confirmation')->name('confirmation');
});

Route::get('/marketplaces/callback/{platform}', [MarketplaceShopController::class, 'callback'])
    ->name('marketplace-shops.callback');

Route::middleware(['auth', 'password.changed', 'verified'])->group(function (): void {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::get('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/profile/activity', [ProfileController::class, 'activity'])->name('profile.activity');
    Route::get('/members/{member}', [ProfileController::class, 'showMember'])->name('members.show');
    Route::prefix('admin/access')->name('admin.access.')->middleware('role:admin')->controller(AdminAccessController::class)->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::post('/roles', 'storeRole')->name('roles.store');
        Route::put('/roles/{role}', 'updateRole')->name('roles.update');
        Route::delete('/roles/{role}', 'destroyRole')->name('roles.destroy');
        Route::post('/permissions', 'storePermission')->name('permissions.store');
        Route::delete('/permissions/{permission}', 'destroyPermission')->name('permissions.destroy');
        Route::put('/users/{user}/roles', 'updateUserRoles')->name('users.roles.update');
    });
    Route::prefix('admin/users')->name('admin.users.')->middleware('role:admin')->controller(AdminUserController::class)->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::get('/datatable', 'datatable')->name('datatable');
        Route::get('/customers', 'customers')->name('customers');
        Route::get('/customers/datatable', 'customersDatatable')->name('customers.datatable');
        Route::get('/customers/create', 'createCustomer')->name('customers.create');
        Route::post('/customers', 'storeCustomer')->name('customers.store');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{user}/edit', 'edit')->name('edit');
        Route::put('/{user}/permissions', 'updatePermissions')->name('permissions.update');
        Route::put('/{user}', 'update')->name('update');
        Route::delete('/{user}', 'destroy')->name('destroy');
    });
    Route::prefix('admin/backups')->name('admin.backups.')->middleware('role:admin')->controller(AdminBackupController::class)->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::post('/run', 'run')->name('run');
        Route::get('/{disk}/{backup}/download', 'download')->name('download');
    });
    Route::prefix('attachments')->name('attachments.')->controller(AttachmentController::class)->group(function (): void {
        Route::get('/{attachment}/file', 'show')->name('show');
    });

    Route::prefix('bookings')->name('bookings.')->controller(BookingController::class)->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::get('/datatable', 'datatable')->name('datatable');
        Route::put('/{booking}', 'update')->name('update');
    });
    Route::prefix('booking/slots')->name('booking-time-slots.')->middleware('not-customer')->controller(BookingTimeSlotController::class)->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::get('/datatable', 'datatable')->name('datatable');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{bookingTimeSlot}/delete', 'delete')->name('delete');
        Route::delete('/{bookingTimeSlot}', 'destroy')->name('destroy');
        Route::get('/{bookingTimeSlot}/edit', 'edit')->name('edit');
        Route::put('/{bookingTimeSlot}', 'update')->name('update');
        Route::get('/{bookingTimeSlot}', 'show')->name('show');
    });

    Route::prefix('customer-messages')->name('customer-messages.')->controller(CustomerMessageTemplateController::class)->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::get('/datatable', 'datatable')->name('datatable');
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
        Route::get('/datatable', 'datatable')->name('datatable');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{customer}', 'show')->name('show');
        Route::get('/{customer}/edit', 'edit')->name('edit');
        Route::put('/{customer}', 'update')->name('update');
    });

    Route::prefix('tickets')->name('tickets.')->group(function (): void {
        Route::get('/', [RepairTicketController::class, 'index'])->name('index');
        Route::get('/datatable', [RepairTicketController::class, 'datatable'])->name('datatable');
        Route::get('/create', [RepairTicketController::class, 'create'])->name('create');
        Route::post('/', [RepairTicketController::class, 'store'])->name('store');
        Route::post('/{ticket}/attachments', [AttachmentController::class, 'storeTicket'])->name('attachments.store');
        Route::post('/{ticket}/steps', [RepairTicketController::class, 'storeUpdate'])->name('steps.store');
        Route::post('/{ticket}/steps/{update}/attachments', [AttachmentController::class, 'storeTicketUpdate'])->name('steps.attachments.store');
        Route::get('/{ticket}', [RepairTicketController::class, 'show'])->name('show');
        Route::put('/{ticket}', [RepairTicketController::class, 'update'])->name('update');
    });

    Route::prefix('products')->name('products.')->middleware('not-customer')->controller(ProductController::class)->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{product}/delete', 'delete')->name('delete');
        Route::delete('/{product}', 'destroy')->name('destroy');
        Route::get('/{product}/edit', 'edit')->name('edit');
        Route::put('/{product}', 'update')->name('update');
        Route::get('/{product}', 'show')->name('show');
    });

    Route::prefix('billing')->name('billing.')->controller(BillingController::class)->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::get('/datatable', 'datatable')->name('datatable');
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

    Route::prefix('expenses')->name('expenses.')->middleware('not-customer')->controller(BusinessExpenseController::class)->group(function (): void {
        Route::post('/{category}/{expense}/attachments', [AttachmentController::class, 'storeExpense'])
            ->whereIn('category', ['rental', 'bills', 'services'])->name('attachments.store');
        Route::patch('/{category}/{expense}/attachments/{attachment}/caption', [AttachmentController::class, 'updateExpenseCaption'])
            ->whereIn('category', ['rental', 'bills', 'services'])->name('attachments.caption');
        Route::get('/{category}/{expense}/pdf', 'pdf')->whereIn('category', ['rental', 'bills', 'services'])->name('pdf');
        Route::get('/{category}/create', 'create')->whereIn('category', ['rental', 'bills', 'services'])->name('create');
        Route::post('/{category}', 'store')->whereIn('category', ['rental', 'bills', 'services'])->name('store');
        Route::get('/{category}/{expense}/edit', 'edit')->whereIn('category', ['rental', 'bills', 'services'])->name('edit');
        Route::put('/{category}/{expense}', 'update')->whereIn('category', ['rental', 'bills', 'services'])->name('update');
        Route::delete('/{category}/{expense}', 'destroy')->whereIn('category', ['rental', 'bills', 'services'])->name('destroy');
        Route::post('/{category}/{expense}/payments', 'storePayment')->whereIn('category', ['rental', 'bills', 'services'])->name('payments.store');
        Route::get('/{category}', 'index')->whereIn('category', ['rental', 'bills', 'services'])->name('index');
    });

    Route::prefix('inventory')->name('inventory.')->middleware('not-customer')->controller(InventoryController::class)->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::get('/parts/datatable', 'partsDatatable')->name('parts.datatable');
        Route::get('/parts', 'parts')->name('parts');
        Route::get('/parts/create', 'create')->name('parts.create');
        Route::post('/parts', 'store')->name('parts.store');
        Route::get('/parts/{part}/delete', 'delete')->name('parts.delete');
        Route::delete('/parts/{part}', 'destroy')->name('parts.destroy');
        Route::get('/parts/{part}/edit', 'edit')->name('parts.edit');
        Route::put('/parts/{part}', 'update')->name('parts.update');
        Route::get('/parts/{part}', 'show')->name('parts.show');
        Route::get('/movements/datatable', 'movementsDatatable')->name('movements.datatable');
        Route::post('/', 'store')->name('store');
        Route::post('/{part}/movements', 'adjust')->name('movements.store');
        Route::get('/movements', 'movements')->name('movements');
    });
    Route::prefix('inventory/suppliers')->name('inventory.suppliers.')->middleware('not-customer')->controller(SupplierController::class)->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::get('/datatable', 'datatable')->name('datatable');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{supplier}/delete', 'delete')->name('delete');
        Route::get('/{supplier}', 'show')->name('show');
        Route::get('/{supplier}/edit', 'edit')->name('edit');
        Route::put('/{supplier}', 'update')->name('update');
        Route::delete('/{supplier}', 'destroy')->name('destroy');
    });

    Route::prefix('teams')->name('teams.')->controller(TeamController::class)->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::get('/datatable', 'datatable')->name('datatable');
        Route::post('/', 'store')->name('store');
        Route::get('/{team}/edit', 'edit')->name('edit');
        Route::put('/{team}', 'update')->name('update');
        Route::delete('/{team}', 'destroy')->name('destroy');
    });
    Route::prefix('departments')->name('departments.')->middleware('not-customer')->controller(DepartmentController::class)->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::get('/datatable', 'datatable')->name('datatable');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{department}/delete', 'delete')->name('delete');
        Route::get('/{department}', 'show')->name('show');
        Route::get('/{department}/edit', 'edit')->name('edit');
        Route::put('/{department}', 'update')->name('update');
        Route::delete('/{department}', 'destroy')->name('destroy');
        Route::delete('/{department}', 'destroy')->name('destroy');
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
    Route::prefix('reports/integrations')->name('reports.integrations.')->middleware('role:admin')->controller(IntegrationTestController::class)->group(function (): void {
        Route::get('/whatsapp', 'whatsapp')->name('whatsapp');
        Route::post('/whatsapp/check', 'checkWhatsapp')->name('whatsapp.check');
        Route::get('/easyparcel', 'easyParcel')->name('easyparcel');
        Route::post('/easyparcel/rates', 'checkEasyParcel')->name('easyparcel.rates');
    });
    Route::prefix('marketplace-shops')->name('marketplace-shops.')->middleware('role:admin')->controller(MarketplaceShopController::class)->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::get('/{platform}/connect', 'connect')->name('connect');
        Route::post('/{marketplaceShop}/disconnect', 'disconnect')->name('disconnect');
        Route::post('/{marketplaceShop}/orders/import', 'importOrders')->name('orders.import');
    });
    Route::prefix('meta-content')->name('meta-content.')->middleware('not-customer')->controller(MetaContentController::class)->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{metaContentPost}/edit', 'edit')->name('edit');
        Route::put('/{metaContentPost}', 'update')->name('update');
        Route::post('/{metaContentPost}/publish', 'publish')->name('publish');
        Route::delete('/{metaContentPost}', 'destroy')->name('destroy');
    });

    Route::post('/events', [ProjectEventController::class, 'store'])->middleware('not-customer')->name('events.store');
    Route::delete('/events/{event}', [ProjectEventController::class, 'destroy'])->middleware('not-customer')->name('events.destroy');

    Route::prefix('projects')->name('projects.')->group(function (): void {
        Route::get('/datatable', [ProjectController::class, 'datatable'])->name('datatable');
        Route::middleware('not-customer')->group(function (): void {
            Route::get('/calendar/events', [ProjectEventController::class, 'events'])->name('calendar.events');
            Route::get('/calendar', [ProjectEventController::class, 'index'])->name('calendar');
            Route::get('/create', [ProjectController::class, 'create'])->name('create');
        });
        Route::get('/', [ProjectController::class, 'index'])->name('index');
        Route::get('/{project}', [ProjectController::class, 'show'])->name('show');
        Route::middleware('not-customer')->group(function (): void {
            Route::post('/{project}/attachments', [AttachmentController::class, 'storeProject'])->name('attachments.store');
            Route::patch('/{project}/attachments/{attachment}/caption', [AttachmentController::class, 'updateProjectCaption'])->name('attachments.caption');
            Route::post('/{project}/export', [ProjectExportController::class, 'export'])->name('export');
            Route::post('/{project}/technology-stack', [ProjectTechnologyController::class, 'store'])->name('technology-stack.store');
            Route::put('/{project}/technology-stack/{technology}', [ProjectTechnologyController::class, 'update'])->name('technology-stack.update');
            Route::delete('/{project}/technology-stack/{technology}', [ProjectTechnologyController::class, 'destroy'])->name('technology-stack.destroy');
            Route::get('/{project}/flowcharts/datatable', [ProjectController::class, 'flowChartsDatatable'])->name('flowcharts.datatable');
            Route::post('/{project}/flowcharts/link', [ProjectController::class, 'linkFlowCharts'])->name('flowcharts.link');
            Route::post('/{project}/flowcharts/duplicate', [ProjectController::class, 'duplicateFlowCharts'])->name('flowcharts.duplicate');
            Route::delete('/{project}/flowcharts/{flowChart}', [ProjectController::class, 'destroyFlowChart'])->name('flowcharts.destroy');
            Route::post('/{project}/parts', [ProjectController::class, 'storeRecommendedPart'])->name('parts.store');
            Route::put('/{project}/parts/{part}', [ProjectController::class, 'updateRecommendedPart'])->name('parts.update');
            Route::delete('/{project}/parts/{part}', [ProjectController::class, 'destroyRecommendedPart'])->name('parts.destroy');
            Route::post('/{project}/estimate-items', [ProjectController::class, 'storeEstimateItem'])->name('estimate-items.store');
            Route::delete('/{project}/estimate-items/{item}', [ProjectController::class, 'destroyEstimateItem'])->name('estimate-items.destroy');
            Route::post('/{project}/events', [ProjectController::class, 'storeEvent'])->name('events.store');
            Route::delete('/{project}/events/{event}', [ProjectController::class, 'destroyEvent'])->name('events.destroy');
            Route::post('/{project}/links', [ProjectController::class, 'storeLink'])->name('links.store');
            Route::put('/{project}/links/{link}', [ProjectController::class, 'updateLink'])->name('links.update');
            Route::delete('/{project}/links/{link}', [ProjectController::class, 'destroyLink'])->name('links.destroy');
            Route::post('/', [ProjectController::class, 'store'])->name('store');
            Route::get('/{project}/edit', [ProjectController::class, 'edit'])->name('edit');
            Route::put('/{project}', [ProjectController::class, 'update'])->name('update');
            Route::delete('/{project}', [ProjectController::class, 'destroy'])->name('destroy');
            Route::put('/{project}/flowcharts', [ProjectController::class, 'syncFlowCharts'])->name('flowcharts.sync');
            Route::get('/{project}/tasks/create', [TaskController::class, 'createForProject'])->name('tasks.create');
            Route::post('/{project}/tasks', [TaskController::class, 'storeForProject'])->name('tasks.store');
        });
    });

    Route::prefix('tasks')->name('tasks.')->controller(TaskController::class)->middleware('not-customer')->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::get('/datatable', 'datatable')->name('datatable');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::post('/{task}/attachments', [AttachmentController::class, 'storeTask'])->name('attachments.store');
        Route::patch('/{task}/attachments/{attachment}/caption', [AttachmentController::class, 'updateTaskAttachmentCaption'])->name('attachments.caption');
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
        Route::get('/sops', [FlowChartController::class, 'sops'])->name('sops');
        Route::get('/sops/create', [FlowChartController::class, 'createSop'])->name('sops.create');
        Route::get('/sops/datatable', [FlowChartController::class, 'sopsDatatable'])->name('sops.datatable');
        Route::get('/flowcharts/datatable', [FlowChartController::class, 'datatable'])->name('flowcharts.datatable');
        Route::post('/flowcharts/{flowchart}/tasks', [FlowChartController::class, 'addStepsToTasks'])->name('flowcharts.tasks.store');
        Route::patch('/flowcharts/{flowchart}/layout', [FlowChartController::class, 'saveLayout'])->name('flowcharts.layout');
        Route::resource('flowcharts', FlowChartController::class);
    });
});

require __DIR__.'/auth.php';
