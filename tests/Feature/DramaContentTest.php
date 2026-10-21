<?php

namespace Tests\Feature;

use App\Models\DramaScript;
use App\Models\User;
use Database\Seeders\PlannerRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class DramaContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_edit_and_delete_drama_scripts_with_multiple_characters_scenes_and_references(): void
    {
        Storage::fake('public');
        $this->seed(PlannerRoleSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $leadId = (string) Str::uuid();
        $friendId = (string) Str::uuid();
        $firstSceneId = (string) Str::uuid();
        $secondSceneId = (string) Str::uuid();

        $this->get(route('drama-content.index'))
            ->assertOk()
            ->assertSee('Drama content')
            ->assertSee(route('drama-content.create'), false)
            ->assertSee('Drama Content');

        $this->post(route('drama-content.store'), [
            ...$this->dramaData($leadId, $friendId, $firstSceneId, $secondSceneId),
            'character_images' => [$leadId => UploadedFile::fake()->image('mara.jpg')],
            'scene_images' => [$firstSceneId => UploadedFile::fake()->image('workshop.jpg')],
        ])
            ->assertRedirect();

        $script = DramaScript::query()->firstOrFail();
        $this->assertSame('The Last Signal', $script->title);
        $this->assertCount(2, $script->characters);
        $this->assertCount(2, $script->scenes);
        $this->assertSame([$leadId, $friendId], $script->scenes[0]['character_ids']);
        $this->assertSame([
            'https://example.test/reference.jpg',
            'Rainy street with blue neon reflections',
        ], $script->scenes[0]['references']);
        $characterImagePath = $script->characters[0]['image_path'];
        $sceneImagePath = $script->scenes[0]['image_path'];
        Storage::disk('public')->assertExists($characterImagePath);
        Storage::disk('public')->assertExists($sceneImagePath);
        $this->assertSame('2026-10-22T21:30', $script->scenes[0]['scheduled_at']);
        $this->assertSame(30, $script->scenes[0]['duration_minutes']);

        $this->get(route('drama-content.edit', $script))
            ->assertOk()
            ->assertSee('Mara')
            ->assertSee('Rainy street with blue neon reflections')
            ->assertSee('AI generation prompt')
            ->assertSee('name="scenes[0][scheduled_at]" type="datetime-local"', false)
            ->assertSee('name="scenes[0][duration_minutes]" type="number"', false)
            ->assertSee('character_images[', false)
            ->assertSee('scene_images[', false);

        $updated = $this->dramaData($leadId, $friendId, $firstSceneId, $secondSceneId);
        $updated['title'] = 'The Last Signal: Revised';
        $this->put(route('drama-content.update', $script), $updated)
            ->assertRedirect(route('drama-content.edit', $script));
        $this->assertSame('The Last Signal: Revised', $script->fresh()->title);
        $this->assertSame($characterImagePath, $script->fresh()->characters[0]['image_path']);
        $this->assertSame($sceneImagePath, $script->fresh()->scenes[0]['image_path']);
        Storage::disk('public')->assertExists($characterImagePath);
        Storage::disk('public')->assertExists($sceneImagePath);

        $this->delete(route('drama-content.destroy', $script))
            ->assertRedirect(route('drama-content.index'));
        $this->assertDatabaseMissing('drama_scripts', ['id' => $script->id]);
        Storage::disk('public')->assertMissing($characterImagePath);
        Storage::disk('public')->assertMissing($sceneImagePath);
    }

    public function test_drama_content_is_denied_to_members_without_the_permission(): void
    {
        $this->seed(PlannerRoleSeeder::class);
        $member = User::factory()->create();
        $member->assignRole('member');

        $this->actingAs($member)
            ->get(route('drama-content.index'))
            ->assertForbidden();
    }

    private function dramaData(string $leadId, string $friendId, string $firstSceneId, string $secondSceneId): array
    {
        return [
            'title' => 'The Last Signal',
            'genre' => 'Mystery drama',
            'synopsis' => 'Two friends investigate a strange radio signal.',
            'status' => 'ready',
            'characters' => [
                ['id' => $leadId, 'name' => 'Mara', 'description' => 'A careful radio engineer.'],
                ['id' => $friendId, 'name' => 'Jon', 'description' => "Mara's impulsive childhood friend."],
            ],
            'scenes' => [
                [
                    'id' => $firstSceneId,
                    'title' => 'The signal',
                    'setting' => 'Workshop at night',
                    'scheduled_at' => '2026-10-22T21:30',
                    'duration_minutes' => '30',
                    'script' => 'Mara tunes the receiver. A voice answers.',
                    'ai_prompt' => 'Cinematic close-up, quiet suspense.',
                    'character_ids' => [$leadId, $friendId],
                    'references' => "https://example.test/reference.jpg\nRainy street with blue neon reflections",
                ],
                [
                    'id' => $secondSceneId,
                    'title' => 'The choice',
                    'setting' => 'Rooftop before dawn',
                    'scheduled_at' => '2026-10-23T05:45',
                    'duration_minutes' => '15',
                    'script' => 'Jon asks Mara to follow the signal.',
                    'ai_prompt' => 'Wide shot at blue hour.',
                    'character_ids' => [$leadId],
                    'references' => 'Cold dawn color palette',
                ],
            ],
        ];
    }
}
