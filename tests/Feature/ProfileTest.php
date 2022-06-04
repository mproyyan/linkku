<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;
use Tests\Trait\ResponseStructure;
use Tests\Trait\WithUser;

class ProfileTest extends TestCase
{
    use RefreshDatabase, WithUser, ResponseStructure;

    private $user2;
    private $token;
    private $ownerToken;

    public function setUp(): void
    {
        parent::setUp();

        $this->setupUser();
        $this->user2 = User::factory()->create();
        $this->token = $this->user2->createToken('main')->plainTextToken;
        $this->ownerToken = $this->user->createToken('main')->plainTextToken;
    }

    public function test_get_user_profile_as_owner()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->ownerToken)
            ->getJson('/api/user/' . $this->user->username);

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json) =>
                $json->where('user.name', $this->user->name)
                    ->where('user.email', $this->user->email)
                    ->where('owner', true)
                    ->missing('user.password')
                    ->etc()
            );
    }

    public function test_get_user_profile_but_not_as_owner()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/user/' . $this->user->username);

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json) =>
                $json->where('user.name', $this->user->name)
                    ->where('user.email', $this->user->email)
                    ->where('owner', false)
                    ->missing('user.password')
                    ->etc()
            );
    }

    public function test_user_profile_not_found()
    {
        $response = $this->getJson('/api/user/salah');

        $response->assertStatus(404)
            ->assertJsonStructure($this->standardApiProblemStructure);
    }

    public function test_get_authenticated_user()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->ownerToken)
            ->getJson('/api/user');

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json) =>
                $json->where('user.name', $this->user->name)
                    ->where('user.email', $this->user->email)
                    ->missing('user.password')
                    ->etc()
            );
    }

    public function test_failed_get_authenticated_user_when_token_not_included()
    {
        $response = $this->getJson('/api/user');

        $response->assertStatus(401)
            ->assertJsonStructure($this->standardApiProblemStructure);
    }

    public function test_update_banner()
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->image('banner.png', 800, 500);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->ownerToken)
            ->putJson("/api/user/{$this->user->username}/update-banner", [
                'banner' => $file
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('user.banner_url', fn ($url) => $url === URL::to('storage/' . $file->hashName('banner')));

        Storage::disk('public')->assertExists($file->hashName('banner'));
    }

    public function test_user_banner_replaced_when_banner_got_new_update()
    {
        Storage::fake('public');
        $file1 = UploadedFile::fake()->image('banner-1.jpg', 800, 500);
        $file2 = UploadedFile::fake()->image('banner-2.jpg', 800, 500);

        $this->withHeader('Authorization', 'Bearer ' . $this->ownerToken)
            ->putJson("/api/user/{$this->user->username}/update-banner", [
                'banner' => $file1
            ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->ownerToken)
            ->putJson("/api/user/{$this->user->username}/update-banner", [
                'banner' => $file2
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('user.banner_url', fn ($url) => $url === URL::to('storage/' . $file2->hashName('banner')));

        Storage::disk('public')->assertExists($file2->hashName('banner'))
            ->assertMissing($file1->hashName('banner'));
    }

    public function test_update_banner_failed_when_user_update_an_account_that_doesnt_belong_to_him()
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->image('banner.png', 800, 500);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->putJson("/api/user/{$this->user->username}/update-banner", [
                'banner' => $file
            ]);

        $response->assertStatus(403)
            ->assertJsonStructure($this->standardApiProblemStructure)
            ->assertJsonPath('detail', 'You cannot update profile that are not yours');
    }

    public function test_update_user_profile()
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->image('profile-picture.jpeg', 500, 400);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->ownerToken)
            ->putJson("/api/user/{$this->user->username}/update-profile", [
                'name' => 'Muhammad Pandu Royyan',
                'username' => $this->user->username,
                'image' => $file
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('user.image_url', fn ($url) => $url === URL::to('storage/' . $file->hashName('avatar')))
            ->assertJson(
                fn (AssertableJson $json) =>
                $json->where('user.name', 'Muhammad Pandu Royyan')
                    ->where('user.username', $this->user->username)
                    ->missing('user.password')
                    ->etc()
            );

        Storage::disk('public')->assertExists($file->hashName('avatar'));
    }

    public function test_old_image_deleted_when_got_new_image()
    {
        Storage::fake('public');
        $file1 = UploadedFile::fake()->image('profile-picture-1.jpeg', 500, 400);
        $file2 = UploadedFile::fake()->image('profile-picture-2.jpeg', 500, 400);

        $this->withHeader('Authorization', 'Bearer ' . $this->ownerToken)
            ->putJson("/api/user/{$this->user->username}/update-profile", [
                'image' => $file1
            ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->ownerToken)
            ->putJson("/api/user/{$this->user->username}/update-profile", [
                'image' => $file2
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('user.image_url', fn ($url) => $url === URL::to('storage/' . $file2->hashName('avatar')));

        Storage::disk('public')->assertExists($file2->hashName('avatar'))
            ->assertMissing($file1->hashName('avatar'));
    }

    public function test_update_profile_failed_when_given_username_already_used()
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->image('profile-picture.jpeg', 500, 400);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->ownerToken)
            ->putJson("/api/user/{$this->user->username}/update-profile", [
                'name' => 'Muhammad Pandu Royyan',
                'username' => $this->user2->username,
                'image' => $file
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure($this->standardApiProblemStructure);
    }

    public function test_update_profile_failed_when_user_update_an_account_that_doesnt_belong_to_him()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->putJson("/api/user/{$this->user->username}/update-profile", [
                'name' => 'Muhammad Pandu Royyan',
            ]);

        $response->assertStatus(403)
            ->assertJsonStructure($this->standardApiProblemStructure)
            ->assertJsonPath('detail', 'You cannot update profile that are not yours');
    }
}
