<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Tests\Trait\WithUser;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\Trait\ResponseStructure;

class RegisterUserTest extends TestCase
{
    use RefreshDatabase, WithUser, ResponseStructure;

    public function setUp(): void
    {
        parent::setUp();

        $this->setupUser();
    }

    public function test_user_can_register_new_account()
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

        $this->assertDatabaseHas('users', [
            'name' => 'Muhammad Pandu Royyan',
            'username' => 'siroyan'
        ]);
    }

    public function test_user_cannot_create_new_account_because_many_problems()
    {
        $response = $this->postJson('/api/register');

        $response->assertUnprocessable()
            ->assertJsonStructure([
                ...$this->standardApiProblemStructure,
                'problems'
            ]);
    }

    public function test_user_cannot_create_new_account_because_one_problem()
    {
        $response = $this->postJson('/api/register');

        $response->assertUnprocessable()
            ->assertJsonStructure($this->standardApiProblemStructure);
    }
}
