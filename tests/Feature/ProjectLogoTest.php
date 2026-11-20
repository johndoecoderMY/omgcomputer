<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Database\Seeders\PlannerRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProjectLogoTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_upload_replace_serve_and_remove_a_project_logo(): void
    {
        Storage::fake('local');
        $this->actingAs($this->adminUser());
        $project = Project::query()->create(['name' => 'Cafe POS']);

        $this->from(route('projects.show', $project))
            ->post(route('projects.logo.store', $project), [
                'logo' => UploadedFile::fake()->image('cafe-logo.png'),
            ])
            ->assertRedirect(route('projects.show', $project))
            ->assertSessionHas('success', 'Project logo updated.');

        $firstPath = $project->fresh()->logo_path;
        $this->assertNotEmpty($firstPath);
        Storage::disk('local')->assertExists($firstPath);
        $this->get(route('projects.logo', $project))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee('data-project-logo', false)
            ->assertSee('src="'.route('projects.logo', $project).'"', false)
            ->assertSee('enctype="multipart/form-data"', false);

        $this->from(route('projects.show', $project))
            ->post(route('projects.logo.store', $project), [
                'logo' => UploadedFile::fake()->image('new-cafe-logo.webp'),
            ])
            ->assertRedirect(route('projects.show', $project));
        $secondPath = $project->fresh()->logo_path;
        $this->assertNotSame($firstPath, $secondPath);
        Storage::disk('local')->assertMissing($firstPath);
        Storage::disk('local')->assertExists($secondPath);

        $this->delete(route('projects.logo.destroy', $project))
            ->assertRedirect(route('projects.show', $project))
            ->assertSessionHas('success', 'Project logo removed.');
        $this->assertNull($project->fresh()->logo_path);
        Storage::disk('local')->assertMissing($secondPath);
        $this->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee('Add logo')
            ->assertDontSee('src="'.route('projects.logo', $project).'"', false);
    }

    public function test_project_logo_upload_rejects_non_image_files(): void
    {
        Storage::fake('local');
        $this->actingAs($this->adminUser());
        $project = Project::query()->create(['name' => 'Image validation']);

        $this->post(route('projects.logo.store', $project), [
            'logo' => UploadedFile::fake()->create('logo.svg', 100, 'image/svg+xml'),
        ])->assertSessionHasErrors('logo');

        $this->assertNull($project->fresh()->logo_path);
        $this->assertSame([], Storage::disk('local')->allFiles('project-logos'));
    }

    public function test_customer_can_only_load_a_logo_for_a_visible_project(): void
    {
        $this->seed(PlannerRoleSeeder::class);
        $customer = User::factory()->create();
        $customer->assignRole(Role::findByName('customer', 'web'));
        Storage::fake('local');
        $logo = UploadedFile::fake()->image('customer-logo.png')->store('project-logos', 'local');
        $visibleProject = Project::query()->create([
            'name' => 'Visible project',
            'customer_user_id' => $customer->id,
            'logo_path' => $logo,
        ]);
        $hiddenProject = Project::query()->create([
            'name' => 'Hidden project',
            'logo_path' => $logo,
        ]);

        $this->actingAs($customer)
            ->get(route('projects.logo', $visibleProject))
            ->assertOk();
        $this->get(route('projects.logo', $hiddenProject))->assertNotFound();
    }

    private function adminUser(): User
    {
        $this->seed(PlannerRoleSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }
}
