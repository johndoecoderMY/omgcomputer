<?php

namespace App\Http\Controllers;

use App\Services\GitPullService;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class AdminMaintenanceController extends Controller
{
    private const SEEDER_GROUPS = [
        [
            'name' => 'Roles and workspace setup',
            'seeders' => [
                [
                    'class' => \Database\Seeders\PlannerRoleSeeder::class,
                    'name' => 'Planner roles and permissions',
                    'description' => 'Creates the admin, member, and customer roles and permissions. Users without a role are assigned the member role.',
                ],
                [
                    'class' => \Database\Seeders\DepartmentTeamSeeder::class,
                    'name' => 'Departments and teams',
                    'description' => 'Adds the sample departments and teams used by the workspace.',
                ],
            ],
        ],
        [
            'name' => 'Projects and plans',
            'seeders' => [
                [
                    'class' => \Database\Seeders\OmgComputerProjectSeeder::class,
                    'name' => 'OMG Computer project',
                    'description' => 'Adds starter requirements, technology, estimate items, and events to the OMG Computer project.',
                ],
                [
                    'class' => \Database\Seeders\BusinessPlanTaskSeeder::class,
                    'name' => 'Business plan tasks',
                    'description' => 'Adds sample planning tasks to the OMG Computer project.',
                ],
                [
                    'class' => \Database\Seeders\SiemProjectSeeder::class,
                    'name' => 'SIEM learning project',
                    'description' => 'Adds a sample SIEM project and tasks.',
                ],
            ],
        ],
        [
            'name' => 'Messages and SOP examples',
            'seeders' => [
                [
                    'class' => \Database\Seeders\CustomerMessageTemplateSeeder::class,
                    'name' => 'Customer message templates',
                    'description' => 'Adds example customer email, SMS, and WhatsApp templates.',
                ],
                [
                    'class' => \Database\Seeders\ServiceSopExampleSeeder::class,
                    'name' => 'Service SOP example',
                    'description' => 'Adds an example laptop repair service workflow.',
                ],
                [
                    'class' => \Database\Seeders\StudentFypSopSeeder::class,
                    'name' => 'Student FYP SOP examples',
                    'description' => 'Adds student consultation, debugging, mentoring, and review workflows.',
                ],
                [
                    'class' => \Database\Seeders\SoftwareStructureSopSeeder::class,
                    'name' => 'Software structure SOP examples',
                    'description' => 'Adds website structure and database design workflow examples.',
                ],
                [
                    'class' => \Database\Seeders\DeveloperSetupFlowChartSeeder::class,
                    'name' => 'Developer setup workflows',
                    'description' => 'Adds Arduino, ESP32, Laravel setup, and database relationship examples.',
                ],
            ],
        ],
        [
            'name' => 'Sample inventory and business data',
            'seeders' => [
                [
                    'class' => \Database\Seeders\SupplierSeeder::class,
                    'name' => 'Sample suppliers',
                    'description' => 'Adds demo supplier records used by the sample inventory seeders.',
                ],
                [
                    'class' => \Database\Seeders\PcPartsSeeder::class,
                    'name' => 'Sample PC parts',
                    'description' => 'Adds demo parts, sample opening-stock movements, and marks the parts for sale. Requires the inventory migrations.',
                ],
                [
                    'class' => \Database\Seeders\ReadyBuiltPcSeeder::class,
                    'name' => 'Sample ready-built PCs',
                    'description' => 'Adds demo ready-built PCs and sample opening-stock movements. Requires the inventory migrations.',
                ],
                [
                    'class' => \Database\Seeders\RentalExpenseSeeder::class,
                    'name' => 'Sample rental expense',
                    'description' => 'Adds a sample monthly shop rental expense.',
                ],
                [
                    'class' => \Database\Seeders\DemoDataSeeder::class,
                    'name' => 'General demo data',
                    'description' => 'Adds sample project, inventory, customer, task, and marketing data. Requires the support@omgcomputer.my user and related migrations.',
                ],
            ],
        ],
        [
            'name' => 'Shop demo orders',
            'seeders' => [
                [
                    'class' => \Database\Seeders\ShopDemoSeeder::class,
                    'name' => 'Sample shop orders',
                    'description' => 'Adds five example shop orders. Requires test@example.com, the demo customers, and the sample PC parts to exist.',
                ],
            ],
        ],
    ];

    public function index(): View
    {
        return view('admin.maintenance.index', [
            'seederGroups' => self::SEEDER_GROUPS,
        ]);
    }

    public function seedSelected(Request $request): RedirectResponse
    {
        $seederClasses = collect(self::SEEDER_GROUPS)
            ->flatMap(fn (array $group): array => array_column($group['seeders'], 'class'))
            ->all();

        $validated = $request->validate([
            'seeders' => ['required', 'array', 'min:1'],
            'seeders.*' => ['required', 'string', 'distinct', Rule::in($seederClasses)],
        ]);

        $output = [];
        foreach ($validated['seeders'] as $seederClass) {
            try {
                $exitCode = Artisan::call('db:seed', [
                    '--class' => $seederClass,
                    '--force' => true,
                ]);
                $seederOutput = trim(Artisan::output());
            } catch (QueryException|RuntimeException $exception) {
                Log::error('Admin selected database seeder failed.', [
                    'seeder' => $seederClass,
                    'exception' => $exception->getMessage(),
                ]);

                $output[] = $seederClass."\nSeeder execution stopped before command output was available.";

                return redirect()->route('admin.maintenance.index')
                    ->with('maintenance_error', 'A selected seeder could not be completed. Any earlier selected seeders may already have completed; review the application logs.')
                    ->with('maintenance_output', implode("\n\n", $output));
            }

            $output[] = $seederClass.($seederOutput === '' ? '' : "\n".$seederOutput);
            if ($exitCode !== 0) {
                Log::error('Admin selected database seeder returned a failure status.', [
                    'seeder' => $seederClass,
                    'exit_code' => $exitCode,
                    'output' => $seederOutput,
                ]);

                return redirect()->route('admin.maintenance.index')
                    ->with('maintenance_error', 'A selected seeder failed. Any earlier selected seeders may already have completed; review the output and application logs.')
                    ->with('maintenance_output', implode("\n\n", $output));
            }
        }

        $names = collect(self::SEEDER_GROUPS)
            ->flatMap(fn (array $group): array => $group['seeders'])
            ->keyBy('class');
        $selectedNames = collect($validated['seeders'])
            ->map(fn (string $class): string => $names[$class]['name'])
            ->implode(', ');

        return redirect()->route('admin.maintenance.index')
            ->with('maintenance_success', 'Selected seeders completed: '.$selectedNames.'.')
            ->with('maintenance_output', implode("\n\n", $output));
    }

    public function migrate(): RedirectResponse
    {
        return $this->runCommand(
            'migrate',
            ['--force' => true],
            'Database migrations completed.',
        );
    }

    public function freshSeed(Request $request): RedirectResponse
    {
        $request->validate([
            'confirmation' => ['required', 'in:RESET AND SEED DATABASE'],
        ]);

        return $this->runCommand(
            'migrate:fresh',
            ['--force' => true, '--seed' => true],
            'Database tables were recreated and seeders completed.',
        );
    }

    public function optimizeClear(): RedirectResponse
    {
        return $this->runCommand(
            'optimize:clear',
            [],
            'Laravel optimization caches cleared.',
        );
    }

    public function storageLink(): RedirectResponse
    {
        $link = public_path('storage');
        $target = storage_path('app/public');
        $resolvedTarget = realpath($target);

        if ($resolvedTarget !== false && realpath($link) === $resolvedTarget) {
            return redirect()->route('admin.maintenance.index')
                ->with('maintenance_success', 'The public storage link is already configured.');
        }

        if (file_exists($link) || is_link($link)) {
            Log::warning('Admin storage link was not created because the public path is occupied.', [
                'link' => $link,
                'target' => $target,
            ]);

            return redirect()->route('admin.maintenance.index')
                ->with('maintenance_error', 'The public/storage path already exists but does not point to the application storage directory. Resolve it manually before retrying.');
        }

        try {
            $exitCode = Artisan::call('storage:link');
            $output = trim(Artisan::output());
        } catch (RuntimeException $exception) {
            Log::error('Admin storage link command failed.', [
                'reason' => Str::limit($exception->getMessage(), 200),
            ]);

            return redirect()->route('admin.maintenance.index')
                ->with('maintenance_error', 'The storage link could not be created. Check filesystem permissions and application logs.');
        }

        if ($exitCode !== 0 || $resolvedTarget === false || realpath($link) !== $resolvedTarget) {
            Log::error('Admin storage link command did not create the expected link.', [
                'exit_code' => $exitCode,
                'output' => $output,
                'link' => $link,
                'target' => $target,
            ]);

            return redirect()->route('admin.maintenance.index')
                ->with('maintenance_error', 'The storage link could not be verified after creation. Check filesystem permissions and application logs.')
                ->with('maintenance_output', $output);
        }

        return redirect()->route('admin.maintenance.index')
            ->with('maintenance_success', 'The public storage link was created successfully.')
            ->with('maintenance_output', $output);
    }

    public function buildAssets(): RedirectResponse
    {
        try {
            $result = Process::path(base_path())->timeout(180)->run(['npm', 'run', 'build']);
        } catch (RuntimeException $exception) {
            Log::warning('Admin frontend asset build could not start.', [
                'reason' => Str::limit($exception->getMessage(), 200),
            ]);

            return redirect()->route('admin.maintenance.index')
                ->with('maintenance_error', 'The frontend build could not start. Confirm Node.js, npm, and the project dependencies are available on the server.');
        }

        $output = trim($result->output()."\n".$result->errorOutput());

        if (! $result->successful()) {
            Log::error('Admin frontend asset build failed.', [
                'exit_code' => $result->exitCode(),
                'output' => $output,
            ]);

            return redirect()->route('admin.maintenance.index')
                ->with('maintenance_error', 'The frontend asset build failed. Review the output and confirm Node.js, npm, and the project dependencies are available on the server.')
                ->with('maintenance_output', $output);
        }

        return redirect()->route('admin.maintenance.index')
            ->with('maintenance_success', 'Frontend assets built successfully.')
            ->with('maintenance_output', $output);
    }

    public function gitPull(GitPullService $git): RedirectResponse
    {
        try {
            $git->pull();
        } catch (RuntimeException $exception) {
            Log::warning('Admin Git pull could not be completed.', [
                'reason' => Str::limit($exception->getMessage(), 200),
            ]);

            return redirect()->route('admin.maintenance.index')
                ->with('maintenance_error', $exception->getMessage());
        }

        return redirect()->route('admin.maintenance.index')
            ->with('maintenance_success', 'Git pull completed. Review the site, then run migrations or clear caches if needed.');
    }

    private function runCommand(string $command, array $arguments, string $successMessage): RedirectResponse
    {
        try {
            $exitCode = Artisan::call($command, $arguments);
            $output = trim(Artisan::output());
        } catch (QueryException $exception) {
            Log::warning('Admin maintenance command could not access the database.', [
                'command' => $command,
                'exception' => $exception->getMessage(),
            ]);

            return redirect()->route('admin.maintenance.index')
                ->with('maintenance_error', 'The command could not access the database. Check the database connection and user privileges, then try again.');
        }

        if ($exitCode !== 0) {
            Log::error('Admin maintenance command failed.', [
                'command' => $command,
                'exit_code' => $exitCode,
                'output' => $output,
            ]);

            return redirect()->route('admin.maintenance.index')
                ->with('maintenance_error', 'The command failed. Review its output below and check the application logs.')
                ->with('maintenance_output', $output);
        }

        return redirect()->route('admin.maintenance.index')
            ->with('maintenance_success', $successMessage)
            ->with('maintenance_output', $output);
    }
}
