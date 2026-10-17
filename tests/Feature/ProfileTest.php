<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\ProfileActivity;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk()
            ->assertSee('Overview')
            ->assertSee('Edit profile')
            ->assertSee('Security')
            ->assertSee('Activity');
    }

    public function test_member_profiles_show_phone_but_hide_private_address(): void
    {
        $viewer = User::factory()->create();
        $member = User::factory()->create([
            'phone' => '+60123456789',
            'address' => 'Private home address',
        ]);
        $department = Department::query()->create(['name' => 'Repairs']);
        $team = Team::query()->create([
            'name' => 'Laptop Support',
            'department_id' => $department->id,
        ]);
        $member->team()->associate($team);
        $member->save();

        $this->actingAs($viewer)
            ->get(route('members.show', $member))
            ->assertOk()
            ->assertSee($member->name)
            ->assertSee($member->email)
            ->assertSee('+60123456789')
            ->assertSee('Laptop Support')
            ->assertSee('Repairs')
            ->assertDontSee('Private home address')
            ->assertDontSee('Delete my account')
            ->assertDontSee('Save profile');

        $this->get(route('members.show', $viewer))
            ->assertRedirect(route('profile.edit'));
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'phone' => '+60123456789',
                'address' => '123 Example Street, Kampar',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertSame('+60123456789', $user->phone);
        $this->assertSame('123 Example Street, Kampar', $user->address);
        $this->assertNotNull($user->email_verified_at);
        $this->assertDatabaseHas('profile_activities', [
            'actor_id' => $user->id,
            'event' => 'profile_updated',
        ]);
    }

    public function test_user_can_upload_replace_and_remove_their_profile_photo(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $this->actingAs($user);
        $profileDetails = [
            'name' => $user->name,
            'email' => $user->email,
        ];

        $this->patch(route('profile.update'), [
            ...$profileDetails,
            'photo' => UploadedFile::fake()->image('first-photo.jpg'),
        ])->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $firstPhotoPath = $user->fresh()->profile_photo_path;
        $this->assertNotNull($firstPhotoPath);
        Storage::disk('public')->assertExists($firstPhotoPath);
        $this->get(route('profile.edit'))
            ->assertOk()
            ->assertSee(Storage::disk('public')->url($firstPhotoPath), false);

        $this->patch(route('profile.update'), [
            ...$profileDetails,
            'photo' => UploadedFile::fake()->image('replacement.webp'),
        ])->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $replacementPath = $user->fresh()->profile_photo_path;
        $this->assertNotSame($firstPhotoPath, $replacementPath);
        Storage::disk('public')->assertMissing($firstPhotoPath);
        Storage::disk('public')->assertExists($replacementPath);

        $this->patch(route('profile.update'), [
            ...$profileDetails,
            'remove_photo' => '1',
        ])->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $this->assertNull($user->fresh()->profile_photo_path);
        Storage::disk('public')->assertMissing($replacementPath);
    }

    public function test_profile_photo_must_be_a_supported_image_under_two_megabytes(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('profile.edit'))
            ->patch(route('profile.update'), [
                'name' => $user->name,
                'email' => $user->email,
                'photo' => UploadedFile::fake()->create('notes.txt', 1, 'text/plain'),
            ])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHasErrors('photo');

        $this->assertNull($user->fresh()->profile_photo_path);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
        $this->assertDatabaseMissing('profile_activities', [
            'actor_id' => $user->id,
            'event' => 'profile_updated',
        ]);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
        $this->assertDatabaseHas('profile_activities', [
            'actor_id' => $user->id,
            'event' => 'account_deleted',
        ]);
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }

    public function test_profile_activity_is_private_and_password_changes_are_logged(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($user)->get('/profile')
            ->assertOk()
            ->assertSee('Account activity')
            ->assertSee('data-datatable', false);

        $this->actingAs($user)->put('/password', [
            'current_password' => 'password',
            'password' => 'New-password-123!',
        ])->assertRedirect();

        $this->getJson(route('profile.activity', ['draw' => 1, 'start' => 0, 'length' => 25]))
            ->assertOk()
            ->assertJsonPath('data.0.event', 'Password changed');

        $this->actingAs($otherUser)
            ->getJson(route('profile.activity', ['draw' => 1, 'start' => 0, 'length' => 25]))
            ->assertOk()
            ->assertJsonPath('recordsTotal', 0);

        $this->assertDatabaseHas('profile_activities', [
            'actor_id' => $user->id,
            'event' => 'password_updated',
        ]);
        $this->assertInstanceOf(ProfileActivity::class, $user->profileActivities()->first());
    }
}
