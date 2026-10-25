<?php

use App\Http\Controllers\AdminAccessController;
use App\Http\Controllers\AdminBackupController;
use App\Http\Controllers\AdminMaintenanceController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\BookingTimeSlotController;
use App\Http\Controllers\BusinessExpenseController;
use App\Http\Controllers\ChipPaymentController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerMessageTemplateController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DevelopmentController;
use App\Http\Controllers\DramaScriptController;
use App\Http\Controllers\EmailMailboxController;
use App\Http\Controllers\EmailServerSettingsController;
use App\Http\Controllers\FlowChartController;
use App\Http\Controllers\IntegrationTestController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\MarketplaceShopController;
use App\Http\Controllers\MetaAdsController;
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
use App\Http\Controllers\ShopController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\WhatsAppInboxController;
use App\Http\Controllers\WhatsAppWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('welcome'));
Route::get('/track-repair', [RepairTicketController::class, 'trackingForm'])
    ->name('repairs.track.form');
Route::post('/track-repair', [RepairTicketController::class, 'track'])
    ->middleware('throttle:5,1')
    ->name('repairs.track');
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

    return response()->view("errors.{$code}", [], (int) $code);
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
Route::get('/webhooks/meta/whatsapp', [WhatsAppWebhookController::class, 'verify'])
    ->name('webhooks.whatsapp.verify');
Route::post('/webhooks/meta/whatsapp', [WhatsAppWebhookController::class, 'receive'])
    ->name('webhooks.whatsapp.receive');
Route::post('/webhooks/chip/collect/{attempt:uuid}', [ChipPaymentController::class, 'callback'])
    ->name('webhooks.chip.collect');
Route::post('/webhooks/chip/shop/{uuid}', [ShopController::class, 'chipCallback'])
    ->name('webhooks.chip.shop');

Route::prefix('shop/preview')->name('shop.preview.')->controller(ShopController::class)->group(function (): void {
    Route::get('/products', 'previewProducts')->name('products.index');
    Route::get('/products/grid', 'previewProductsGrid')->name('products.grid');
    Route::get('/products/{product}', 'previewProductDetails')->name('products.show');
});

Route::middleware(['auth', 'password.changed', 'verified', 'workspace.access'])->group(function (): void {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::get('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/profile/activity', [ProfileController::class, 'activity'])->name('profile.activity');
    Route::get('/members/{member}', [ProfileController::class, 'showMember'])->name('members.show');
    Route::prefix('email')->name('email.')->group(function (): void {
        Route::get('/', [EmailMailboxController::class, 'index'])->name('inbox');
        Route::get('/poll', [EmailMailboxController::class, 'poll'])->name('poll');
        Route::post('/bulk-action', [EmailMailboxController::class, 'bulkAction'])->name('bulk-action');
        Route::get('/compose', [EmailMailboxController::class, 'compose'])->name('compose');
        Route::post('/send', [EmailMailboxController::class, 'send'])->name('send');
        Route::get('/{uid}', [EmailMailboxController::class, 'show'])->whereNumber('uid')->name('show');
        Route::post('/{uid}/actions/{action}', [EmailMailboxController::class, 'action'])
            ->whereNumber('uid')
            ->where('action', 'archive|delete|read|unread|flag|unflag')
            ->name('action');
        Route::get('/mailbox/settings', [EmailMailboxController::class, 'mailboxSettings'])->name('mailbox-settings');
        Route::put('/mailbox/settings', [EmailMailboxController::class, 'updateMailboxSettings'])->name('mailbox-settings.update');
        Route::post('/mailbox/settings/test', [EmailMailboxController::class, 'testMailboxConnection'])->name('mailbox-settings.test');
        Route::delete('/mailbox/settings', [EmailMailboxController::class, 'deleteMailboxSettings'])->name('mailbox-settings.delete');
    });
    Route::prefix('email')->name('email.')->middleware('role:admin')->group(function (): void {
        Route::get('/settings/server', [EmailServerSettingsController::class, 'edit'])->name('settings');
        Route::put('/settings/server', [EmailServerSettingsController::class, 'update'])->name('settings.update');
        Route::post('/settings/test-smtp', [EmailServerSettingsController::class, 'sendTest'])->name('settings.test-smtp');
        Route::post('/settings/test-imap', [EmailServerSettingsController::class, 'testImap'])->name('settings.test-imap');
    });
    Route::prefix('admin/access')->name('admin.access.')->middleware('role:admin')->controller(AdminAccessController::class)->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::post('/roles', 'storeRole')->name('roles.store');
        Route::put('/roles/{role}', 'updateRole')->name('roles.update');
        Route::delete('/roles/{role}', 'destroyRole')->name('roles.destroy');
        Route::post('/permissions', 'storePermission')->name('permissions.store');
        Route::delete('/permissions/{permission}', 'destroyPermission')->name('permissions.destroy');
        Route::put('/users/{user}/roles', 'updateUserRoles')->name('users.roles.update');
    });
    Route::prefix('admin/users')->name('admin.users.')->controller(AdminUserController::class)->group(function (): void {
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
    Route::prefix('admin/maintenance')->name('admin.maintenance.')->middleware('role:admin')->controller(AdminMaintenanceController::class)->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::post('/migrate', 'migrate')->middleware('throttle:10,1')->name('migrate');
        Route::post('/optimize-clear', 'optimizeClear')->middleware('throttle:10,1')->name('optimize-clear');
        Route::post('/build-assets', 'buildAssets')->middleware('throttle:10,1')->name('build-assets');
        Route::post('/git-pull', 'gitPull')->middleware('throttle:10,1')->name('git-pull');
    });
    Route::prefix('attachments')->name('attachments.')->controller(AttachmentController::class)->group(function (): void {
        Route::get('/{attachment}/file', 'show')->name('show');
    });

    Route::prefix('bookings')->name('bookings.')->controller(BookingController::class)->group(function (): void {
        Route::get('/create', 'createAuthenticated')->middleware('role:customer')->name('account-create');
        Route::post('/create', 'storeAuthenticated')->middleware('role:customer')->name('account-store');
        Route::get('/calendar', 'calendar')->name('calendar');
        Route::get('/calendar/events', 'calendarEvents')->name('calendar.events');
        Route::get('/', 'index')->name('index');
        Route::get('/datatable', 'datatable')->name('datatable');
        Route::get('/{booking}', 'show')->name('show');
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
        Route::get('/suggestions', 'suggestions')->name('suggestions');
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
        Route::post('/customers', [CustomerController::class, 'quickStore'])->name('customers.store');
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

    Route::prefix('shop')->name('shop.')->middleware('not-customer')->controller(ShopController::class)->group(function (): void {
        Route::get('/products', 'products')->name('products.index');
        Route::get('/products/datatable', 'productsDatatable')->name('products.datatable');
        Route::get('/products/grid', 'productsGrid')->name('products.grid');
        Route::get('/products/create', 'createProduct')->name('products.create');
        Route::post('/products', 'storeProduct')->name('products.store');
        Route::post('/products/{product}/stock', 'adjustProductStock')->name('products.stock');
        Route::get('/products/{product}/edit', 'editProduct')->name('products.edit');
        Route::put('/products/{product}', 'updateProduct')->name('products.update');
        Route::get('/products/{product}', 'showProduct')->name('products.show');
        Route::get('/orders', 'orders')->name('orders.index');
        Route::get('/orders/datatable', 'ordersDatatable')->name('orders.datatable');
        Route::get('/orders/status-board', 'orderStatusBoard')->name('orders.status-board');
        Route::get('/orders/status-board/datatable', 'orderStatusDatatable')->name('orders.status-board.datatable');
        Route::get('/orders/customer-preview', 'orderStatusPreview')->name('orders.customer-preview');
        Route::patch('/orders/{order}/tracking-number', 'updateOrderTrackingNumber')->name('orders.tracking-number');
        Route::get('/orders/{order}', 'showOrder')->name('orders.show');
        Route::patch('/orders/{order}/status', 'updateOrderStatus')->name('orders.status');
    });

    Route::get('/shop/chip-return/{uuid}', [ShopController::class, 'chipReturn'])->name('shop.chip.return');
    Route::prefix('shop')->name('shop.')->middleware('role:customer')->controller(ShopController::class)->group(function (): void {
        Route::get('/products/{product}/purchase', 'beginProductPurchase')->name('products.purchase');
        Route::get('/cart', 'cart')->name('cart');
        Route::post('/cart/items/{product}', 'addToCart')->name('cart.add');
        Route::put('/cart', 'updateCart')->name('cart.update');
        Route::delete('/cart/items/{product}', 'removeFromCart')->name('cart.remove');
        Route::get('/checkout', 'checkout')->name('checkout');
        Route::post('/checkout', 'placeOrder')->name('checkout.place');
        Route::get('/tracking', 'customerTracking')->name('tracking');
        Route::post('/tracking', 'trackCustomerParcel')->middleware('throttle:5,1')->name('tracking.lookup');
        Route::get('/my-orders', 'customerOrders')->name('my-orders.index');
        Route::get('/my-orders/{order}', 'customerOrder')->name('my-orders.show');
        Route::post('/my-orders/{order}/tracking', 'refreshCustomerOrderTracking')
            ->middleware('throttle:5,1')
            ->name('my-orders.tracking');
    });

    Route::prefix('billing')->name('billing.')->controller(BillingController::class)->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::get('/datatable', 'datatable')->name('datatable');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::post('/{document}/chip-checkout', [ChipPaymentController::class, 'checkout'])->name('chip.checkout');
        Route::get('/chip-return/{attempt:uuid}', [ChipPaymentController::class, 'return'])->name('chip.return');
        Route::get('/{document}/edit', 'edit')->name('documents.edit');
        Route::put('/{document}', 'update')->name('documents.update');
        Route::post('/{document}/quotation-status', 'updateQuotationStatus')->name('documents.quotation-status');
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
        Route::get('/notifications', 'notifications')->name('notifications');
        Route::post('/notifications/send', 'sendTestNotification')->name('notifications.send');
        Route::post('/email/send', 'sendTestEmail')->name('email.send');
        Route::get('/whatsapp', 'whatsapp')->name('whatsapp');
        Route::post('/whatsapp/check', 'checkWhatsapp')->name('whatsapp.check');
        Route::get('/parcel-tracking', 'parcelTracking')->name('tracking');
        Route::post('/parcel-tracking', 'trackParcel')->name('tracking.lookup');
    });
    Route::prefix('reports/integrations/whatsapp')->name('reports.integrations.whatsapp.')->middleware('role:admin')->controller(WhatsAppInboxController::class)->group(function (): void {
        Route::get('/inbox', 'index')->name('inbox');
        Route::get('/inbox/poll', 'poll')->name('inbox.poll');
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
    Route::prefix('meta-ads')->name('meta-ads.')->middleware('role:admin')->controller(MetaAdsController::class)->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::get('/connect', 'connect')->name('connect');
        Route::get('/callback', 'callback')->name('callback');
        Route::post('/account', 'selectAccount')->name('account.select');
        Route::delete('/connection', 'disconnect')->name('disconnect');
        Route::post('/campaigns', 'storeCampaign')->name('campaigns.store');
        Route::patch('/campaigns/{campaignId}', 'updateCampaign')->whereNumber('campaignId')->name('campaigns.update');
        Route::post('/page', 'selectPage')->name('page.select');
        Route::delete('/page', 'disconnectPage')->name('page.disconnect');
        Route::post('/page/posts', 'storePagePost')->name('page.posts.store');
        Route::patch('/page/posts/{postId}', 'updatePagePost')
            ->where('postId', '[0-9]+_[0-9]+')
            ->name('page.posts.update');
    });

    Route::post('/events', [ProjectEventController::class, 'store'])->middleware('not-customer')->name('events.store');
    Route::delete('/events/{event}', [ProjectEventController::class, 'destroy'])->middleware('not-customer')->name('events.destroy');

    Route::get('/calendar', [ProjectEventController::class, 'index'])->middleware('not-customer')->name('calendar');
    Route::get('/calendar/events', [ProjectEventController::class, 'events'])->middleware('not-customer')->name('calendar.events');

    Route::prefix('projects')->name('projects.')->group(function (): void {
        Route::get('/datatable', [ProjectController::class, 'datatable'])->name('datatable');
        Route::middleware('not-customer')->group(function (): void {
            Route::get('/{project}/calendar/events', [ProjectEventController::class, 'projectEvents'])->name('calendar.events');
            Route::get('/{project}/calendar', [ProjectEventController::class, 'projectIndex'])->name('calendar');
            Route::get('/create', [ProjectController::class, 'create'])->name('create');
            Route::get('/{project}/tasks', [TaskController::class, 'projectIndex'])->name('tasks.index');
            Route::get('/{project}/tasks/kanban', [TaskController::class, 'projectKanban'])->name('tasks.kanban');
            Route::get('/{project}/tasks/datatable', [TaskController::class, 'projectDatatable'])->name('tasks.datatable');
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
            Route::patch('/{project}/events/{event}/visibility', [ProjectController::class, 'updateEventVisibility'])->name('events.visibility');
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
        Route::get('/kanban', 'kanban')->name('kanban');
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
    Route::prefix('drama-content')->name('drama-content.')->controller(DramaScriptController::class)->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{dramaScript}/edit', 'edit')->name('edit');
        Route::put('/{dramaScript}', 'update')->name('update');
        Route::delete('/{dramaScript}', 'destroy')->name('destroy');
    });
});

require __DIR__.'/auth.php';
