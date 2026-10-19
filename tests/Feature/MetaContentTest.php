<?php

namespace Tests\Feature;

use App\Models\MetaContentPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MetaContentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        config([
            'app.url' => 'https://omgcomputer.example.test',
            'filesystems.disks.public.url' => 'https://omgcomputer.example.test/storage',
            'services.meta.public_media_base_url' => 'https://omgcomputer.example.test',
            'services.meta.access_token' => 'meta-test-token',
            'services.meta.page_id' => 'page-123',
            'services.meta.instagram_user_id' => 'instagram-456',
            'services.meta.graph_api_version' => 'v25.0',
        ]);
    }

    public function test_staff_can_save_and_schedule_meta_content_and_view_it_in_the_sidebar_section(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get(route('meta-content.index'))
            ->assertOk()
            ->assertSee('Meta Content')
            ->assertSee('Create content')
            ->assertSee('Meta Content');
        $this->post(route('meta-content.store'), [
            'platform' => 'facebook',
            'media_type' => 'text',
            'caption' => 'A draft announcement',
        ])->assertRedirect(route('meta-content.index'));
        $this->post(route('meta-content.store'), [
            'platform' => 'instagram',
            'media_type' => 'image',
            'caption' => 'A scheduled image',
            'media' => UploadedFile::fake()->image('post.jpg', 400, 400),
            'scheduled_at' => now()->addHour()->format('Y-m-d H:i:s'),
        ])->assertRedirect(route('meta-content.index'));

        $this->assertDatabaseCount('meta_content_posts', 2);
        $this->assertDatabaseHas('meta_content_posts', [
            'platform' => 'facebook',
            'status' => 'draft',
            'caption' => 'A draft announcement',
        ]);
        $scheduled = MetaContentPost::query()->where('platform', 'instagram')->firstOrFail();
        $this->assertSame('scheduled', $scheduled->status);
        $this->assertTrue(
            Storage::disk('public')->exists($scheduled->media_path),
            json_encode(Storage::disk('public')->allFiles()),
        );
    }

    public function test_facebook_text_can_publish_and_meta_errors_are_saved_for_retry(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        Http::fake([
            'https://graph.facebook.com/v25.0/page-123/feed' => Http::sequence()
                ->push(['id' => 'page-123_post-1'])
                ->push(['error' => ['message' => 'Missing pages_manage_posts permission.']], 403),
        ]);
        $this->post(route('meta-content.store'), [
            'platform' => 'facebook',
            'media_type' => 'text',
            'caption' => 'Published announcement',
            'publish_now' => '1',
        ])->assertRedirect(route('meta-content.index'));
        $post = MetaContentPost::query()->firstOrFail();
        $this->assertSame('published', $post->status, (string) $post->error_message);
        $this->assertSame('page-123_post-1', $post->meta_post_id);
        Http::assertSent(fn (ClientRequest $request): bool => $request->method() === 'POST'
            && str_contains($request->url(), '/page-123/feed'));

        $this->post(route('meta-content.store'), [
            'platform' => 'facebook',
            'media_type' => 'text',
            'caption' => 'Failed announcement',
            'publish_now' => '1',
        ])->assertRedirect(route('meta-content.index'));
        $failed = MetaContentPost::query()->where('caption', 'Failed announcement')->firstOrFail();
        $this->assertSame('failed', $failed->status);
        $this->assertSame('Missing pages_manage_posts permission.', $failed->error_message);
    }

    public function test_instagram_image_publishes_media_container_then_publishes_container(): void
    {
        $this->actingAs(User::factory()->create());
        Http::fake([
            'https://graph.facebook.com/v25.0/instagram-456/media' => Http::response(['id' => 'container-1']),
            'https://graph.facebook.com/v25.0/instagram-456/media_publish' => Http::response(['id' => 'instagram-post-1']),
        ]);

        $this->post(route('meta-content.store'), [
            'platform' => 'instagram',
            'media_type' => 'image',
            'caption' => 'Instagram image caption',
            'media' => UploadedFile::fake()->image('post.jpg', 400, 400),
            'publish_now' => '1',
        ])->assertRedirect(route('meta-content.index'));

        $post = MetaContentPost::query()->firstOrFail();
        $this->assertSame('published', $post->status, (string) $post->error_message);
        $this->assertSame('instagram-post-1', $post->meta_post_id);
        Http::assertSent(fn (ClientRequest $request): bool => $request->method() === 'POST'
            && str_ends_with($request->url(), '/instagram-456/media')
            && ($request['image_url'] ?? null) === 'https://omgcomputer.example.test/storage/'.$post->media_path);
    }

    public function test_instagram_reels_wait_for_media_processing_before_publishing(): void
    {
        $this->actingAs(User::factory()->create());
        Http::fake([
            'https://graph.facebook.com/v25.0/instagram-456/media' => Http::response(['id' => 'reel-container']),
            'https://graph.facebook.com/v25.0/reel-container*' => Http::response(['status_code' => 'FINISHED']),
            'https://graph.facebook.com/v25.0/instagram-456/media_publish' => Http::response(['id' => 'instagram-reel']),
        ]);

        $this->post(route('meta-content.store'), [
            'platform' => 'instagram',
            'media_type' => 'reel',
            'caption' => 'Instagram Reel',
            'media' => UploadedFile::fake()->create('reel.mp4', 100, 'video/mp4'),
            'publish_now' => '1',
        ])->assertRedirect(route('meta-content.index'));

        $post = MetaContentPost::query()->firstOrFail();
        $this->assertSame('published', $post->status, (string) $post->error_message);
        $this->assertSame('instagram-reel', $post->meta_post_id);
        Http::assertSent(fn (ClientRequest $request): bool => str_ends_with($request->url(), '/instagram-456/media')
            && ($request['media_type'] ?? null) === 'REELS');
        Http::assertSent(fn (ClientRequest $request): bool => str_ends_with($request->url(), '/instagram-456/media_publish')
            && ($request['creation_id'] ?? null) === 'reel-container');
    }

    public function test_staff_can_edit_schedule_and_delete_unpublished_content(): void
    {
        $this->actingAs(User::factory()->create());
        $this->post(route('meta-content.store'), [
            'platform' => 'facebook',
            'media_type' => 'text',
            'caption' => 'Editable post',
        ])->assertRedirect(route('meta-content.index'));
        $post = MetaContentPost::query()->firstOrFail();
        $scheduledAt = now()->addDay()->format('Y-m-d H:i:s');

        $this->put(route('meta-content.update', $post), [
            'platform' => 'facebook',
            'media_type' => 'text',
            'caption' => 'Updated post',
            'scheduled_at' => $scheduledAt,
        ])->assertRedirect(route('meta-content.index'));
        $this->assertSame('scheduled', $post->fresh()->status);
        $this->assertSame('Updated post', $post->fresh()->caption);

        $this->delete(route('meta-content.destroy', $post))
            ->assertRedirect(route('meta-content.index'));
        $this->assertDatabaseMissing('meta_content_posts', ['id' => $post->id]);
    }

    public function test_scheduled_posts_are_published_by_the_scheduled_command(): void
    {
        $post = MetaContentPost::query()->create([
            'platform' => 'facebook',
            'media_type' => 'text',
            'caption' => 'Due post',
            'status' => 'scheduled',
            'scheduled_at' => now()->subMinute(),
        ]);
        Http::fake([
            'https://graph.facebook.com/v25.0/page-123/feed' => Http::response(['id' => 'page-123_post-due']),
        ]);

        $this->artisan('meta-content:publish-scheduled')->assertSuccessful();

        $this->assertSame('published', $post->fresh()->status);
        $this->assertSame('page-123_post-due', $post->fresh()->meta_post_id);
    }

    public function test_facebook_reels_use_meta_upload_session_and_publish_the_video(): void
    {
        $this->actingAs(User::factory()->create());
        Http::fake([
            'https://graph.facebook.com/v25.0/page-123/video_reels' => Http::sequence()
                ->push(['video_id' => 'reel-123', 'upload_url' => 'https://rupload.facebook.com/video-upload/v25.0/reel-123'])
                ->push(['success' => true, 'video_id' => 'reel-123']),
            'https://rupload.facebook.com/video-upload/v25.0/reel-123' => Http::response(['success' => true]),
        ]);

        $this->post(route('meta-content.store'), [
            'platform' => 'facebook',
            'media_type' => 'reel',
            'caption' => 'Facebook reel',
            'media' => UploadedFile::fake()->create('reel.mp4', 100, 'video/mp4'),
            'publish_now' => '1',
        ])->assertRedirect(route('meta-content.index'));

        $post = MetaContentPost::query()->firstOrFail();
        $this->assertSame('published', $post->status, (string) $post->error_message);
        $this->assertSame('reel-123', $post->meta_post_id);
        Http::assertSent(fn (ClientRequest $request): bool => str_contains($request->url(), 'rupload.facebook.com'));
    }

    public function test_instagram_text_posts_are_rejected_and_customers_cannot_manage_meta_content(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('meta-content.store'), [
            'platform' => 'instagram',
            'media_type' => 'text',
            'caption' => 'Not supported on Instagram',
        ])->assertSessionHasErrors('media_type');

        Role::findOrCreate('customer', 'web');
        $customer = User::factory()->create();
        $customer->assignRole('customer');
        $this->actingAs($customer)->get(route('meta-content.index'))->assertForbidden();
    }
}
