<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Project Planner

The authenticated planner supports shared team tasks and private personal tasks, with a Kanban board, assignments, priorities, due dates, milestones, and progress summaries. Run migrations and seed planner roles when setting up the database:

```sh
php artisan migrate
php artisan db:seed --class="Database\Seeders\PlannerRoleSeeder"
php artisan db:seed --class="Database\Seeders\DepartmentTeamSeeder"
```

To grant team-task administration to a user, assign the `admin` role:

```sh
php artisan tinker
>>> \App\Models\User::where('email', 'you@example.com')->firstOrFail()->assignRole('admin');
```

Users with the `admin` role can use the **Members** page to search, create, edit, and delete accounts, assign teams, and manage roles and individual permissions. New members receive the default `member` role and must change their temporary password at first sign-in. The `customer` role is available for customer accounts. **Roles & permissions** manages shared roles and permission definitions; additional permissions can be granted directly to individual members. The last administrator cannot be demoted or deleted.

Customer accounts can view projects linked to a customer record with the same email address, either directly or through one of that customer's repair tickets. Their project view is read-only and does not include internal tasks, estimates, or project-management controls.

The Project Overview is a single shared **OMG Computer** project for the website and the repair shop. Tasks are classified as website features/improvements, shop operations (such as buying parts), general tasks, or milestones. The dashboard shows task totals, completion, remaining work, progress, and repair-ticket counts. Existing projects are merged into the single project when the included migration runs.

The authenticated dashboard summarizes live project progress, assigned and overdue work, the repair queue, upcoming events, unread notifications, and example SOPs. Email verification and password-confirmation fields are temporarily disabled. Accounts provisioned with a temporary/default password are still required to replace it at first sign-in; self-registered accounts keep their chosen password.

## Billing, Inventory, and Reports

Authenticated users can manage stock parts and movements, create quotations and invoices, convert quotes into invoices, record partial or full payments, and render documents as PDFs with mPDF. The repair support desk records customer/device intake, assignments, priorities, diagnoses, estimates, target dates, and ticket progress; tickets are searchable and filterable in a server-side Yajra DataTable. Issuing an invoice deducts linked inventory in the same database transaction; insufficient stock prevents the invoice from being created. Reports summarize issued invoices, received payments, outstanding balances, service totals, and stock activity, with a date-filtered invoice CSV export.

Customers can pay eligible invoices through [CHIP Collect's hosted checkout](https://docs.chip-in.asia/chip-collect/overview/introduction). Set `CHIP_SECRET_KEY` and `CHIP_BRAND_ID` in the deployment environment; use a CHIP test-mode key first. CHIP callbacks are signature-verified, and payment status is checked against CHIP's API before the invoice balance is updated. Configure the application's public HTTPS URL for the return and callback URLs to be reachable by CHIP.

The staff-only Shop uses inventory parts as its product catalogue, with separate product-list, product-grid, cart, checkout, order, and order-status-board pages. Product and order lists use server-side Yajra DataTables where applicable. Staff are notified when orders are placed, paid, or moved through fulfillment. Guest-accessible, read-only product list, grid, and details previews are available under `/shop/preview`; only products marked for sale and currently in stock are shown, and guest cart and checkout routes remain protected. The customer-style order status screen is a staff-only sample preview. Local and testing seeds mark sample PC parts for sale and create representative orders for the catalogue and status board. CHiP hosted checkout creates customer orders, reserves stock, and only marks an order paid after CHiP confirms it; failed or cancelled payments release the reservation. Configure CHiP with test-mode credentials before enabling checkout; without credentials, the checkout remains disabled.

Run `php artisan migrate` to create the planning, inventory, billing, and shop tables. Keep a current database backup before applying migrations to an existing deployment.

Administrators can use **System maintenance** to run migrations, clear Laravel optimization caches, or pull the configured Git branch. Git pull requires Git and a configured remote on the server; it only fast-forwards and refuses to run when tracked files have local changes.

## Teams, Suppliers, and SOP Flow Charts

Authenticated users can maintain departments, teams, and many-to-many team memberships, so one member may belong to multiple teams; associate inventory parts with supplier contacts; and link reusable flow charts to projects using a fuzzy-searchable picker. The project table and linked flow-chart/SOP table use server-side Yajra DataTables; the linked-chart table shows only charts already associated with that project. Flow charts can also be created directly for a project or duplicated from selected templates. Product, service, and student FYP SOP flow charts are available from the Operations menu. Student FYP examples cover consultations, debugging, mentoring, project setup workshops, progress reviews, and small-group tech clinics. Developer setup examples cover Arduino CLI, ESP32 with Arduino IDE, Laravel project setup, and Laravel database table relationships. Seed examples with `php artisan db:seed` or run `php artisan db:seed --class="Database\\Seeders\\DeveloperSetupFlowChartSeeder"` against an existing database. Run `php artisan migrate` to add the related tables.

The SOP library also includes example web-application structure and database design/migration workflows. Projects can keep hardware recommendations linked to existing inventory parts, including quantities, a per-project selling, cost, or custom price, implementation notes, and multiple source URLs; these recommendations do not reserve or decrement stock. Add software, labor, and other estimate items to see their subtotals and combined project estimate. Projects themselves are listed in a server-side Yajra DataTable. Example repair tickets across every supported lifecycle status can be added with `php artisan db:seed --class="Database\Seeders\RepairTicketExampleSeeder"`. The full demo seeder runs these ticket examples in local and testing environments.

## Notifications, Email, and Profile Photos

Task assignments and project-linked calendar events create in-app notifications, which are stored per user and delivered live through Pusher. Set `BROADCAST_CONNECTION=pusher` and configure `PUSHER_APP_ID`, `PUSHER_APP_KEY`, `PUSHER_APP_SECRET`, and `PUSHER_APP_CLUSTER` in the local environment; never commit the secret. The example environment uses the log broadcaster until credentials are configured. For local development `MAIL_MAILER=log` writes notification emails to the application log, so no email API keys or SMTP credentials are required. Configure a mail transport only when enabling delivery outside development. Run a queue worker when processing queued broadcasts (`php artisan queue:work`). Build the browser client with `npm run build`.

Profile pictures use Laravel’s public disk. Run `php artisan storage:link` after setup so uploaded images can be served from `public/storage`.

## Meta Content Publishing

Staff can draft, schedule, and publish text/image/Reel posts to a Facebook Page and image/Reel posts to connected Instagram professional accounts from **OMG Computer → Meta Content**. Configure `META_PAGE_ID`, `META_PAGE_ACCESS_TOKEN`, `META_INSTAGRAM_USER_ID`, and `META_INSTAGRAM_ACCESS_TOKEN`; the existing `META_ACCESS_TOKEN` can be used as a fallback token. Tokens need Meta's publishing permissions, including `pages_manage_posts` for Facebook Pages and `instagram_business_basic` plus `instagram_business_content_publish` for Instagram. Set `META_PUBLIC_MEDIA_BASE_URL` (or `APP_URL`) to a publicly reachable HTTPS application URL and run `php artisan storage:link` so Meta can fetch uploaded media. Configure Laravel's scheduler to run `php artisan schedule:run` every minute to publish scheduled posts. Meta app review, token provisioning, quotas, supported media formats, and account eligibility remain subject to Meta's requirements.

## Access Control and Error Pages

The **Roles & permissions** page is restricted to users with the `admin` role. Assign that role to an initial administrator with the command shown above; administrators can then manage roles, permissions, and user assignments in the application. Branded error pages are provided for access denied, not found, expired session, rate limited, server error, and maintenance responses.

## User Profiles and Account Activity

Authenticated users can update their name, email, phone number, and address, change their password, review profile/password changes, and delete their account after confirming their password. Team directory links open read-only member profiles showing name, email, phone, team, and department; home addresses remain private to each account owner. Apply migrations with `php artisan migrate` to add the contact fields and account activity history.

## Backups

Spatie Laravel Backup creates verified, private local archives containing application files and the configured database. Planner administrators can review archive status, download backups, and run a backup from **Manage → Backups**. This app requires PHP 8.2 or newer and uses Laravel 11. Environment files, Git data, dependencies, temporary framework files, and backup archives are excluded from the file archive. Set `BACKUP_ARCHIVE_PASSWORD` to enable ZIP encryption and configure `BACKUP_NOTIFICATION_EMAIL` for backup alerts. `BACKUP_DISK` defaults to `backup_local`; set it to `s3` and configure the AWS variables below to store backups off-site. The scheduler runs cleanup at 01:00, creates a backup at 02:00, and checks backup health at 03:00 daily; ensure the Laravel scheduler is running in production. Use `php artisan backup:run` to create a backup manually. MySQL deployments also need the database dump utilities required by Spatie Backup.

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
