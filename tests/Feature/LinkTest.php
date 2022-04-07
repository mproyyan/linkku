<?php

namespace Tests\Feature;

use App\Models\Link;
use App\Models\Tag;
use App\Models\User;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

use function PHPUnit\Framework\assertJson;

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

    public function test_save_link_to_database()
    {
        $user = User::factory()->create();
        $token = $user->createToken('main')->plainTextToken;
        $tags = Tag::factory(5)->create();
        $tagsId = $tags->pluck('id')->all();
        $visibility = Visibility::create(['visibility' => 'Public']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/links', [
                'title' => 'laravel relationship documentation',
                'url' => 'https://laravel.com/docs/9.x/database-testing#many-to-many-relationships',
                'tags' => $tags->pluck('id')->shuffle()->take(mt_rand(1, 3)),
                'visibility' => $visibility->id
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('link.tags.0.id', fn ($id) => in_array($id, $tagsId))
            ->assertJson(
                fn (AssertableJson $json) =>
                $json->where('link.title', 'laravel relationship documentation')
                    ->where('link.slug', 'laravel-relationship-documentation')
                    ->where('link.author.username', $user->username)
                    ->where('link.visibility', 'Public')
                    ->etc()
            );
    }

    public function test_error_when_given_tags_more_than_five()
    {
        $user = User::factory()->create();
        $token = $user->createToken('main')->plainTextToken;
        $tags = Tag::factory(10)->create();
        $visibility = Visibility::create(['visibility' => 'Public']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/links', [
                'title' => 'laravel relationship documentation',
                'url' => 'https://laravel.com/docs/9.x/database-testing#many-to-many-relationships',
                'tags' => [1, 2, 3, 4, 5, 6],
                'visibility' => $visibility->id
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('errors.tags.0', 'The tags must not have more than 5 items.');
    }

    public function test_error_when_given_tags_doesnt_exist_in_database()
    {
        $user = User::factory()->create();
        $token = $user->createToken('main')->plainTextToken;
        $tags = Tag::factory(1)->create();
        $visibility = Visibility::create(['visibility' => 'Public']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/links', [
                'title' => 'laravel relationship documentation',
                'url' => 'https://laravel.com/docs/9.x/database-testing#many-to-many-relationships',
                'tags' => [6],
                'visibility' => $visibility->id
            ]);

        $response->assertStatus(422);
    }

    public function test_error_when_given_visibility_doesnt_exist_in_database()
    {
        $user = User::factory()->create();
        $token = $user->createToken('main')->plainTextToken;
        $tags = Tag::factory(1)->create();
        $visibility = Visibility::create(['visibility' => 'Public']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/links', [
                'title' => 'laravel relationship documentation',
                'url' => 'https://laravel.com/docs/9.x/database-testing#many-to-many-relationships',
                'tags' => [1],
                'visibility' => 2
            ]);

        $response->assertStatus(422);
    }

    public function test_get_link_by_slug()
    {
        $user = User::factory()->create();
        $link = Link::factory()
            ->for($user, 'author')
            ->for(Visibility::create(['id' => 1, 'visibility' => 'Public']), 'type')
            ->hasAttached(Tag::factory(3)->create())
            ->create();

        $response = $this->getJson('/api/links/' . $link->slug);

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json) =>
                $json->where('link.title', $link->title)
                    ->where('link.slug', $link->slug)
                    ->where('link.author.username', $user->username)
                    ->where('link.visibility', 'Public')
                    ->has('link.tags', 3)
                    ->etc()
            );
    }

    public function test_abort_if_link_visibility_is_private()
    {
        $user = User::factory()->create();
        Visibility::create(['visibility' => 'Public']);
        $link = Link::factory()
            ->for($user, 'author')
            ->for(Visibility::create(['visibility' => 'Private']), 'type')
            ->hasAttached(Tag::factory(1)->create())
            ->create();

        $response = $this->getJson('/api/links/' . $link->slug);

        $response->assertStatus(403);
    }

    public function test_owner_can_access_private_link()
    {
        $user = User::factory()->create();
        $token = $user->createToken('main')->plainTextToken;
        Visibility::create(['visibility' => 'Public']);
        $link = Link::factory()
            ->for($user, 'author')
            ->for(Visibility::create(['visibility' => 'Private']), 'type')
            ->hasAttached(Tag::factory(1)->create())
            ->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/links/' . $link->slug);

        $response->assertStatus(200);
    }

    public function test_update_link_by_authenticated_owner()
    {
        $user = User::factory()->create();
        $token = $user->createToken('main')->plainTextToken;
        $link = Link::factory()
            ->for($user, 'author')
            ->for(Visibility::create(['id' => 1, 'visibility' => 'Public']), 'type')
            ->hasAttached(Tag::factory(3)->create())
            ->create();

        $newTags = Tag::factory(5)->create();
        $newVisibility = Visibility::create(['id' => 2, 'visibility' => 'Private']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/links/' . $link->slug, [
                'title' => 'updated title',
                'url' => 'https://laravel.com/docs/9.x/database-testing#main-content',
                'tags' => $newTags->pluck('id')->shuffle()->take(3),
                'visibility' => $newVisibility->id,
                'description' => 'added description'
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('link.tags.0.id', fn ($id) => in_array($id, $newTags->pluck('id')->all()))
            ->assertJsonPath('link.tags.0.id', fn ($id) => !in_array($id, [1, 2, 3]))
            ->assertJson(
                fn (AssertableJson $json) =>
                $json->where('link.title', 'updated title')
                    ->where('link.slug', 'updated-title')
                    ->where('link.url', 'https://laravel.com/docs/9.x/database-testing#main-content')
                    ->where('link.description', 'added description')
                    ->where('link.visibility', 'Private')
                    ->whereType('link.excerpt', 'string')
                    ->etc()
            );

        $this->assertDatabaseMissing('taggables', [
            'tag_id' => 1,
            'taggable_type' => 'App\Models\Link'
        ]);

        $this->assertDatabaseMissing('taggables', [
            'tag_id' => 2,
            'taggable_type' => 'App\Models\Link'
        ]);

        $this->assertDatabaseMissing('taggables', [
            'tag_id' => 3,
            'taggable_type' => 'App\Models\Link'
        ]);
    }

    public function test_update_link_failed_if_not_owner()
    {
        $user = User::factory()->create();
        $token = $user->createToken('main')->plainTextToken;
        $link = Link::factory()
            ->for(User::factory()->create(), 'author')
            ->for(Visibility::create(['id' => 1, 'visibility' => 'Public']), 'type')
            ->hasAttached(Tag::factory(3)->create())
            ->create();

        $newTags = Tag::factory(5)->create();
        $newVisibility = Visibility::create(['id' => 2, 'visibility' => 'Private']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/links/' . $link->slug, [
                'title' => 'updated title',
                'url' => 'https://laravel.com/docs/9.x/database-testing#main-content',
                'tags' => $newTags->pluck('id')->shuffle()->take(3),
                'visibility' => $newVisibility->id,
                'description' => 'added description'
            ]);

        $response->assertStatus(403);
    }
}
