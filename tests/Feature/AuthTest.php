<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Assert;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

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
}
