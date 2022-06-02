<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Database\Factories\UserFactory;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\Trait\ResponseStructure;
use Tests\Trait\WithUser;
use Illuminate\Support\Str;
use App\Http\Requests\LoginRequest;
use Illuminate\Cache\RateLimiter;

class LoginTest extends TestCase
{
    use RefreshDatabase, WithUser, ResponseStructure;

    public function setUp(): void
    {
        parent::setUp();

        $this->setupUser();
    }

    public function test_user_can_login()
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

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $this->user->id
        ]);
    }

    public function test_user_not_exists()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'wrong@example.com',
            'password' => '123'
        ]);

        $response->assertUnprocessable()
            ->assertJsonStructure($this->standardApiProblemStructure)
            ->assertJsonPath('detail', 'The selected email is invalid.');
    }

    public function test_invalid_credential()
    {
        $response = $this->postJson('/api/login', [
            'email' => $this->user->email,
            'password' => '123'
        ]);

        $response->assertUnprocessable()
            ->assertJsonStructure($this->standardApiProblemStructure)
            ->assertJsonPath('detail', 'These credentials do not match our records.');
    }

    public function test_too_many_request()
    {
        /** @var \Illuminate\Cache\RateLimiter $rateLimiter */
        $rateLimiter = $this->app->make(RateLimiter::class);
        $throttleKey = Str::lower("{$this->user->email}|") . request()->ip();

        collect(range(1, LoginRequest::MAX_ATTEMPTS))->each(function () use ($rateLimiter, $throttleKey) {
            $this->app->call([$rateLimiter, 'hit'], ['key' => $throttleKey]);
        });

        $response = $this->postJson('/api/login', [
            'email' => $this->user->email,
            'password' => UserFactory::DEFAULT_PLAIN_TEXT_PASSWORD
        ]);

        $response->assertStatus(429)
            ->assertJsonStructure($this->standardApiProblemStructure);
    }
}
