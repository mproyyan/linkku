<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Tests\Trait\ResponseStructure;
use Tests\Trait\WithUser;

class LogoutTest extends TestCase
{
    use RefreshDatabase, WithUser, ResponseStructure;

    public function setUp(): void
    {
        parent::setUp();

        $this->setupUser();
    }

    public function test_authorized_user_can_logout()
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
