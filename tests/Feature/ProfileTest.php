<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_user_profile()
    {
        $user = User::factory()->create();

        $response = $this->getJson('/api/user/' . $user->username);

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json) =>
                $json->where('user.name', $user->name)
                    ->where('user.email', $user->email)
                    ->missing('user.password')
                    ->etc()
            );
    }

    public function test_failed_get_user_profile()
    {
        $response = $this->getJson('/api/user/salah');
        $response->assertStatus(404);
    }

    public function test_update_banner()
    {
        $user = User::factory()->create();
        $token = $user->createToken('main')->plainTextToken;

        Storage::fake('public');
        $file = UploadedFile::fake()->image('banner.png', 800, 500);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/user/update-banner', [
                'banner' => $file
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('user.banner', fn ($path) => $path === $file->hashName('banner'));

        Storage::disk('public')->assertExists($file->hashName('banner'));
    }

    public function test_user_banner_replaced_when_banner_got_new_update()
    {
        $user = User::factory()->create();
        $token = $user->createToken('main')->plainTextToken;

        Storage::fake('public');
        $file1 = UploadedFile::fake()->image('banner-1.jpg', 800, 500);
        $file2 = UploadedFile::fake()->image('banner-2.jpg', 800, 500);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/user/update-banner', [
                'banner' => $file1
            ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/user/update-banner', [
                'banner' => $file2
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('user.banner', fn ($path) => $path === $file2->hashName('banner'));

        Storage::disk('public')->assertExists($file2->hashName('banner'))
            ->assertMissing($file1->hashName('banner'));
    }

    public function test_update_user_profile()
    {
        $user = User::factory()->create();
        $token = $user->createToken('main')->plainTextToken;

        Storage::fake('public');
        $file = UploadedFile::fake()->image('profile-picture.jpeg', 500, 400);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/user/update-profile', [
                'name' => 'Muhammad Pandu Royyan',
                'username' => $user->username,
                'image' => $file
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('user.image', fn ($path) => $path === $file->hashName('avatar'))
            ->assertJson(
                fn (AssertableJson $json) =>
                $json->where('user.name', 'Muhammad Pandu Royyan')
                    ->where('user.username', $user->username)
                    ->missing('user.password')
                    ->etc()
            );

        Storage::disk('public')->assertExists($file->hashName('avatar'));
    }

    public function test_old_image_deleted_when_got_new_image()
    {
        $user = User::factory()->create();
        $token = $user->createToken('main')->plainTextToken;

        Storage::fake('public');
        $file1 = UploadedFile::fake()->image('profile-picture-1.jpeg', 500, 400);
        $file2 = UploadedFile::fake()->image('profile-picture-2.jpeg', 500, 400);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/user/update-profile', [
                'image' => $file1
            ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/user/update-profile', [
                'image' => $file2
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('user.image', fn ($path) => $path === $file2->hashName('avatar'));

        Storage::disk('public')->assertExists($file2->hashName('avatar'))
            ->assertMissing($file1->hashName('avatar'));
    }
}
