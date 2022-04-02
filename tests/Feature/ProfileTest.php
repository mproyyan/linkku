<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
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
}
