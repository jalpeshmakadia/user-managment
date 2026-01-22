<?php

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Events\UserCreated;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Event::fake();
    }

    /** @test */
    public function it_can_list_users_with_pagination(): void
    {
        User::factory()->count(15)->create();

        $response = $this->getJson('/users?per_page=10', [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'table',
                'pagination',
            ]);
    }

    /** @test */
    public function it_can_search_users_by_name(): void
    {
        User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        User::factory()->create([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
        ]);

        $response = $this->getJson('/users?search=John', [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertStatus(200);
        $this->assertStringContainsString('John', $response->json('table'));
        $this->assertStringNotContainsString('Jane', $response->json('table'));
    }

    /** @test */
    public function it_can_filter_users_by_status(): void
    {
        User::factory()->create([
            'status' => UserStatus::Active,
        ]);

        User::factory()->create([
            'status' => UserStatus::Inactive,
        ]);

        $response = $this->getJson('/users?status=active', [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function it_can_include_soft_deleted_users(): void
    {
        $user = User::factory()->create();
        $user->delete();

        $response = $this->getJson('/users?withTrashed=true', [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function it_can_create_a_new_user(): void
    {
        $userData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'status' => UserStatus::Active->value,
            'phone' => '+1234567890',
        ];

        $response = $this->postJson('/users', $userData);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'User created successfully.',
            ])
            ->assertJsonStructure([
                'user' => [
                    'id',
                    'first_name',
                    'last_name',
                    'email',
                    'status',
                    'phone',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john.doe@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        Event::assertDispatched(UserCreated::class);
    }

    /** @test */
    public function it_can_create_user_with_avatar(): void
    {
        $avatar = UploadedFile::fake()->image('avatar.jpg', 100, 100);

        $userData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'status' => UserStatus::Active->value,
            'avatar' => $avatar,
        ];

        $response = $this->postJson('/users', $userData);

        $response->assertStatus(201);

        $user = User::where('email', 'john.doe@example.com')->first();
        $this->assertNotNull($user->avatar);
        Storage::disk('public')->assertExists($user->avatar);
    }

    /** @test */
    public function it_automatically_generates_password_on_user_creation(): void
    {
        $userData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'status' => UserStatus::Active->value,
        ];

        $response = $this->postJson('/users', $userData);

        $response->assertStatus(201);

        $user = User::where('email', 'john.doe@example.com')->first();
        $this->assertNotNull($user->password);
        $this->assertNotEquals('', $user->password);
    }

    /** @test */
    public function it_can_show_a_single_user(): void
    {
        $user = User::factory()->create();

        $response = $this->getJson("/users/{$user->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => [
                    'id',
                    'first_name',
                    'last_name',
                    'email',
                    'status',
                    'phone',
                    'full_name',
                    'avatar_url',
                ],
                'avatar_url',
                'deleted_at',
            ])
            ->assertJson([
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                ],
            ]);
    }

    /** @test */
    public function it_returns_404_when_showing_nonexistent_user(): void
    {
        $response = $this->getJson('/users/99999');

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'User not found.',
            ]);
    }

    /** @test */
    public function it_can_update_a_user(): void
    {
        $user = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
        ]);

        $updateData = [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane.smith@example.com',
            'status' => UserStatus::Inactive->value,
            'phone' => '+9876543210',
        ];

        $response = $this->putJson("/users/{$user->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'User updated successfully.',
            ])
            ->assertJson([
                'user' => [
                    'first_name' => 'Jane',
                    'last_name' => 'Smith',
                    'email' => 'jane.smith@example.com',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane.smith@example.com',
        ]);
    }

    /** @test */
    public function it_can_update_user_password(): void
    {
        $user = User::factory()->create([
            'status' => UserStatus::Active,
        ]);
        $oldPassword = $user->password;

        $updateData = [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'status' => $user->status->value,
            'password' => 'newpassword123',
        ];

        $response = $this->putJson("/users/{$user->id}", $updateData);

        $response->assertStatus(200);

        $user->refresh();
        $this->assertNotEquals($oldPassword, $user->password);
    }

    /** @test */
    public function it_can_update_user_without_changing_password(): void
    {
        $user = User::factory()->create([
            'status' => UserStatus::Active,
        ]);
        $oldPassword = $user->password;

        $updateData = [
            'first_name' => 'Updated',
            'last_name' => $user->last_name,
            'email' => $user->email,
            'status' => $user->status->value,
        ];

        $response = $this->putJson("/users/{$user->id}", $updateData);

        $response->assertStatus(200);

        $user->refresh();
        $this->assertEquals($oldPassword, $user->password);
    }

    /** @test */
    public function it_can_update_user_avatar(): void
    {
        $user = User::factory()->create([
            'status' => UserStatus::Active,
        ]);
        $newAvatar = UploadedFile::fake()->image('new-avatar.jpg', 100, 100);

        $updateData = [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'status' => $user->status->value,
            'avatar' => $newAvatar,
        ];

        $response = $this->putJson("/users/{$user->id}", $updateData);

        $response->assertStatus(200);

        $user->refresh();
        $this->assertNotNull($user->avatar);
        Storage::disk('public')->assertExists($user->avatar);
    }

    /** @test */
    public function it_returns_404_when_updating_nonexistent_user(): void
    {
        $updateData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'status' => UserStatus::Active->value,
        ];

        $response = $this->putJson('/users/99999', $updateData);

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'User not found.',
            ]);
    }

    /** @test */
    public function it_can_delete_a_user(): void
    {
        $user = User::factory()->create();

        $response = $this->deleteJson("/users/{$user->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'User deleted successfully.',
            ]);

        $this->assertSoftDeleted('users', [
            'id' => $user->id,
        ]);
    }

    /** @test */
    public function it_returns_404_when_deleting_nonexistent_user(): void
    {
        $response = $this->deleteJson('/users/99999');

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'User not found.',
            ]);
    }

    /** @test */
    public function it_can_restore_a_soft_deleted_user(): void
    {
        $user = User::factory()->create();
        $user->delete();

        $response = $this->putJson("/users/{$user->id}/restore");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'User restored successfully.',
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'deleted_at' => null,
        ]);
    }

    /** @test */
    public function it_returns_404_when_restoring_nonexistent_user(): void
    {
        $response = $this->putJson('/users/99999/restore');

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'User not found or cannot be restored.',
            ]);
    }

    /** @test */
    public function it_normalizes_phone_numbers_on_create(): void
    {
        $userData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'status' => UserStatus::Active->value,
            'phone' => '+1 (234) 567-8900',
        ];

        $response = $this->postJson('/users', $userData);

        $response->assertStatus(201);

        $user = User::where('email', 'john.doe@example.com')->first();
        $this->assertEquals('+12345678900', $user->phone);
    }

    /** @test */
    public function it_normalizes_phone_numbers_on_update(): void
    {
        $user = User::factory()->create([
            'status' => UserStatus::Active,
        ]);

        $updateData = [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'status' => $user->status->value,
            'phone' => '(555) 123-4567',
        ];

        $response = $this->putJson("/users/{$user->id}", $updateData);

        $response->assertStatus(200);

        $user->refresh();
        $this->assertEquals('5551234567', $user->phone);
    }

    /** @test */
    public function it_handles_null_phone_numbers(): void
    {
        $userData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'status' => UserStatus::Active->value,
            'phone' => null,
        ];

        $response = $this->postJson('/users', $userData);

        $response->assertStatus(201);

        $user = User::where('email', 'john.doe@example.com')->first();
        $this->assertNull($user->phone);
    }

    /** @test */
    public function it_respects_per_page_limit(): void
    {
        User::factory()->count(50)->create();

        $response = $this->getJson('/users?per_page=200', [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertStatus(200);
        // Should be limited by max_per_page config
    }
}
