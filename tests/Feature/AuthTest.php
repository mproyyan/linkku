<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Assert;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;
use App\Models\User;
use Database\Factories\UserFactory;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var \App\Models\User
     */
    private $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_register_new_user()
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Muhammad Pandu Royyan',
            'username' => 'siroyan',
            'email' => 'mproyyan@gmail.com',
            'password' => 'password',
            'password_confirmation' => 'password'
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('token', fn ($token) => strlen($token) > 1)
            ->assertJson(
                fn (AssertableJson $json) =>
                $json->where('user.username', 'siroyan')
                    ->where('user.email', 'mproyyan@gmail.com')
                    ->missing('user.password')
                    ->whereType('token', 'string')
                    ->etc()
            );
    }

    public function test_user_login_success()
    {
        $response = $this->postJson('/api/login', [
            'email' => $this->user->email,
            'password' => UserFactory::DEFAULT_PLAIN_TEXT_PASSWORD
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('token', fn ($token) => strlen($token) > 1)
            ->assertJson(
                fn (AssertableJson $json) =>
                $json->where('user.name', $this->user->name)
                    ->where('user.email', $this->user->email)
                    ->missing('user.password')
                    ->whereType('token', 'string')
                    ->etc()
            );
    }

    public function test_user_login_failed()
    {
        $response = $this->postJson('/api/login', [
            'email' => $this->user->email,
            'password' => 'passwordssss'
        ]);

        $response->assertStatus(422)
            ->assertExactJson([
                'error' => 'The Provided credentials are not correct'
            ]);
    }

    public function test_user_logout()
    {
        $token = $this->user->createToken('main')->plainTextToken;
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/logout');

        $response->assertStatus(200);
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $this->user->id
        ]);
    }
}
