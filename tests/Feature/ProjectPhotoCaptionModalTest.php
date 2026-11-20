<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Database\Seeders\PlannerRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectPhotoCaptionModalTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_photo_caption_editor_is_only_shown_in_its_modal(): void
    {
        $this->seed(PlannerRoleSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $project = Project::query()->create(['name' => 'Project photos']);
        $attachment = $project->attachments()->create([
            'uploaded_by' => $admin->id,
            'path' => 'attachments/projects/reference.png',
            'original_name' => 'reference.png',
            'mime_type' => 'image/png',
            'size' => 5,
            'caption' => 'Current caption',
        ]);

        $response = $this->actingAs($admin)->get(route('projects.show', $project));
        $response->assertOk()
            ->assertSee('Edit caption')
            ->assertSee('Edit photo caption')
            ->assertSee('data-bs-target="#attachment-caption-modal-project-photos-'.$attachment->id.'"', false)
            ->assertSee('action="'.route('projects.attachments.caption', [$project, $attachment]).'"', false);

        $html = $response->getContent();
        $galleryStart = strpos($html, 'data-project-photos');
        $modalStart = strpos($html, 'id="attachment-caption-modal-project-photos-'.$attachment->id.'"');
        $captionInput = strpos($html, 'id="attachment-caption-'.$attachment->id.'"');
        $this->assertNotFalse($galleryStart);
        $this->assertNotFalse($modalStart);
        $this->assertNotFalse($captionInput);
        $this->assertStringNotContainsString(
            'name="caption"',
            substr($html, $galleryStart, $modalStart - $galleryStart),
        );
        $this->assertGreaterThan($modalStart, $captionInput);
    }
}
