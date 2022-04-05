<?php

namespace Tests\Feature;

use App\Models\Link;
use App\Models\User;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class LinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_all_links()
    {
        $user = User::factory()->create();
        $links = Link::factory()
            ->count(20)
            ->for($user, 'author')
            ->for(Visibility::create(['visibility' => 'Public']), 'type')
            ->create();

        $response = $this->getJson('/api/links');

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json) =>
                $json->has('meta')
                    ->has('links')
                    ->has(
                        'data',
                        20,
                        fn ($json) =>
                        $json->where('visibility', 'Public')
                            ->where('author.username', $user->username)
                            ->etc()
                    )
            );
    }
}
