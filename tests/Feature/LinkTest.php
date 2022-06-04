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
use Tests\Trait\ResponseStructure;
use Tests\Trait\WithUser;

class LinkTest extends TestCase
{
    use RefreshDatabase, WithUser, ResponseStructure;

    private $guestUser;
    private $token;
    private $ownerToken;
    private $publicVisibility;
    private $privateVisibility;
    private $tags;
    private $publicLink;
    private $privateLink;

    public function setUp(): void
    {
        parent::setUp();

        $this->setupUser();
        $this->guestUser = User::factory()->create();
        $this->token = $this->guestUser->createToken('main')->plainTextToken;
        $this->ownerToken = $this->user->createToken('main')->plainTextToken;
        $this->publicVisibility = Visibility::create(['id' => 1, 'visibility' => 'Public']);
        $this->privateVisibility = Visibility::create(['id' => 2, 'visibility' => 'Private']);
        $this->tags = Tag::factory(3)->create();

        $this->publicLink = Link::factory()
            ->for($this->user, 'author')
            ->for($this->publicVisibility, 'type')
            ->hasAttached($this->tags)
            ->create();

        $this->privateLink = Link::factory()
            ->for($this->user, 'author')
            ->for($this->privateVisibility, 'type')
            ->hasAttached($this->tags)
            ->create();
    }

    public function test_get_all_links()
    {
        Link::factory()
            ->count(20)
            ->for($this->user, 'author')
            ->for($this->publicVisibility, 'type')
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
                            ->where('author.username', $this->user->username)
                            ->etc()
                    )
            );
    }

    public function test_save_link_to_database()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->ownerToken)
            ->postJson('/api/links', [
                'title' => 'laravel relationship documentation',
                'url' => 'https://laravel.com/docs/9.x/database-testing#many-to-many-relationships',
                'tags' => $this->tags->pluck('id')->shuffle()->take(mt_rand(1, 3)),
                'visibility' => $this->publicVisibility->id
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('link.tags.0.id', fn ($id) => in_array($id, $this->tags->pluck('id')->all()))
            ->assertJson(
                fn (AssertableJson $json) =>
                $json->where('link.title', 'laravel relationship documentation')
                    ->where('link.slug', 'laravel-relationship-documentation')
                    ->where('link.author.username', $this->user->username)
                    ->where('link.visibility', 'Public')
                    ->etc()
            );
    }

    public function test_error_when_given_tags_more_than_five()
    {
        $tags = Tag::factory(10)->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->ownerToken)
            ->postJson('/api/links', [
                'title' => 'laravel relationship documentation',
                'url' => 'https://laravel.com/docs/9.x/database-testing#many-to-many-relationships',
                'tags' => $tags->pluck('id')->all(),
                'visibility' => $this->publicVisibility->id
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure($this->standardApiProblemStructure)
            ->assertJsonPath('detail', 'The tags must not have more than 5 items.');
    }

    public function test_error_when_given_tags_doesnt_exist_in_database()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->ownerToken)
            ->postJson('/api/links', [
                'title' => 'laravel relationship documentation',
                'url' => 'https://laravel.com/docs/9.x/database-testing#many-to-many-relationships',
                'tags' => [0],
                'visibility' => $this->publicVisibility->id
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure($this->standardApiProblemStructure);
    }

    public function test_error_when_given_visibility_doesnt_exist_in_database()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->ownerToken)
            ->postJson('/api/links', [
                'title' => 'laravel relationship documentation',
                'url' => 'https://laravel.com/docs/9.x/database-testing#many-to-many-relationships',
                'tags' => $this->tags->pluck('id')->all(),
                'visibility' => 0
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure($this->standardApiProblemStructure)
            ->assertJsonPath('detail', 'The selected visibility is invalid.');
    }

    public function test_get_link_by_slug()
    {
        $response = $this->getJson('/api/links/' . $this->publicLink->slug);

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json) =>
                $json->where('link.title', $this->publicLink->title)
                    ->where('link.slug', $this->publicLink->slug)
                    ->where('link.author.username', $this->user->username)
                    ->where('link.visibility', 'Public')
                    ->has('link.tags', 3)
                    ->etc()
            );
    }

    public function test_abort_if_user_access_private_link_and_its_not_his()
    {
        $response = $this->getJson('/api/links/' . $this->privateLink->slug);

        $response->assertStatus(403)
            ->assertJsonStructure($this->standardApiProblemStructure)
            ->assertJsonPath('detail', 'You cannot access private link that are not yours');
    }

    public function test_owner_can_access_private_link()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->ownerToken)
            ->getJson('/api/links/' . $this->privateLink->slug);

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json) =>
                $json->where('link.title', $this->privateLink->title)
                    ->where('link.slug', $this->privateLink->slug)
                    ->where('link.author.username', $this->user->username)
                    ->where('link.visibility', 'Private')
                    ->has('link.tags', 3)
                    ->etc()
            );;
    }

    public function test_update_link_by_authenticated_owner()
    {
        $newTags = Tag::factory(5)->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->ownerToken)
            ->putJson('/api/links/' . $this->publicLink->slug, [
                'title' => 'updated title',
                'url' => 'https://laravel.com/docs/9.x/database-testing#main-content',
                'tags' => $newTags->pluck('id')->shuffle()->take(3),
                'visibility' => $this->privateVisibility->id,
                'description' => 'added description'
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('link.tags.0.id', fn ($id) => in_array($id, $newTags->pluck('id')->all()))
            ->assertJsonPath('link.tags.0.id', fn ($id) => !in_array($id, $this->tags->pluck('id')->all()))
            ->assertJson(
                fn (AssertableJson $json) =>
                $json->where('link.title', 'updated title')
                    ->where('link.slug', 'updated-title')
                    ->where('link.description', 'added description')
                    ->where('link.visibility', 'Private')
                    ->whereType('link.excerpt', 'string')
                    ->etc()
            );

        $this->assertDatabaseHas('links', [
            'url' => 'https://laravel.com/docs/9.x/database-testing#main-content'
        ]);

        foreach ($this->tags->pluck('id')->all() as $id) {
            $this->assertDatabaseMissing('taggables', [
                'tag_id' => $id,
                'taggable_type' => 'App\Models\Link',
                'taggable_id' => $this->publicLink->id
            ]);
        }
    }

    public function test_update_link_failed_if_not_owner()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->putJson('/api/links/' . $this->publicLink->slug, [
                'title' => 'updated title',
                'url' => 'https://laravel.com/docs/9.x/database-testing#main-content',
                'description' => 'added description'
            ]);

        $response->assertStatus(403)
            ->assertJsonStructure($this->standardApiProblemStructure)
            ->assertJsonPath('detail', 'You cannot update link that are not yours');
    }

    public function test_delete_link_by_authenticated_owner()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->ownerToken)
            ->deleteJson('/api/links/' . $this->publicLink->slug);

        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'message' => 'Link deleted successfully'
            ]);

        $this->assertDatabaseMissing('links', [
            'title' => $this->publicLink->title
        ]);

        foreach ($this->tags->pluck('id')->all() as $id) {
            $this->assertDatabaseMissing('taggables', [
                'tag_id' => $id,
                'taggable_id' => $this->publicLink->id,
                'taggable_type' => 'App\Models\Link'
            ]);
        }
    }

    public function test_delete_link_failed_if_not_owner()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->deleteJson('/api/links/' . $this->privateLink->slug);

        $response->assertStatus(403)
            ->assertJsonStructure($this->standardApiProblemStructure)
            ->assertJsonPath('detail', 'You cannot delete link that are not yours');
    }

    public function test_visit_link_url()
    {
        $response = $this->getJson('/api/g/' . $this->publicLink->hash);

        $response->assertStatus(200)
            ->assertJsonPath('url', $this->publicLink->url);

        $this->assertDatabaseHas('links', [
            'id' => $this->publicLink->id,
            'views' => 1
        ]);
    }

    public function test_owner_can_visit_private_link()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->ownerToken)
            ->getJson('/api/g/' . $this->privateLink->hash);

        $response->assertStatus(200)
            ->assertJsonPath('url', $this->privateLink->url);
    }

    public function test_abort_if_user_visit_private_link()
    {
        $response = $this->getJson('/api/g/' . $this->privateLink->hash);

        $response->assertStatus(403)
            ->assertJsonStructure($this->standardApiProblemStructure)
            ->assertJsonPath('detail', 'You cannot access private link that are not yours');
    }
}
