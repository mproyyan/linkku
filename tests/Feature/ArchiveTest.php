<?php

namespace Tests\Feature;

use App\Models\Archive;
use App\Models\Link;
use App\Models\Tag;
use App\Models\User;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class ArchiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_get_all_archives_that_has_link_and_visibility_is_public()
    {
        $user = User::factory()->create();
        $public = Visibility::create(['id' => 1, 'visibility' => 'Public']);
        $private = Visibility::create(['id' => 2, 'visibility' => 'Private']);
        $tags = Tag::factory(3)->create();
        $links = Link::factory(5)
            ->for($user, 'author')
            ->for($public, 'type')
            ->hasAttached($tags)
            ->create();

        // private archive
        Archive::factory(3)
            ->for($user, 'author')
            ->for($private, 'type')
            ->hasAttached($tags, [], 'tags')
            ->hasAttached($links, [], 'links')
            ->create();

        // public archive but doesnt have links
        Archive::factory(3)
            ->for($user, 'author')
            ->for($public, 'type')
            ->hasAttached($tags, [], 'tags')
            ->create();

        // public archive and has links
        Archive::factory(16)
            ->for($user, 'author')
            ->for($public, 'type')
            ->hasAttached($tags, [], 'tags')
            ->hasAttached($links, [], 'links')
            ->create();

        $response = $this->getJson('/api/archives');

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json) =>
                $json->has('links')
                    ->has('meta')
                    ->has(
                        'data',
                        16,
                        fn ($json) =>
                        $json->where('visibility', 'Public')
                            ->where('author.username', $user->username)
                            ->where('links_count', 5)
                            ->has('tags', 3)
                            ->etc()
                    )
            );
    }

    public function test_get_archive()
    {
        $user = User::factory()->create();
        $public = Visibility::create(['id' => 1, 'visibility' => 'Public']);
        $private = Visibility::create(['id' => 2, 'visibility' => 'Private']);
        $tags = Tag::factory(3)->create();
        $links = Link::factory(5)
            ->for($user, 'author')
            ->for($public, 'type')
            ->hasAttached($tags)
            ->create();

        $archive = Archive::factory()
            ->for($user, 'author')
            ->for($public, 'type')
            ->hasAttached($tags, [], 'tags')
            ->hasAttached($links, [], 'links')
            ->create();

        $response = $this->getJson('/api/archives/' . $archive->slug);

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json) =>
                $json->where('archive.author.username', $user->username)
                    ->where('archive.title', $archive->title)
                    ->where('archive.slug', $archive->slug)
                    ->where('archive.links_count', 5)
                    ->where('archive.visibility', 'Public')
                    ->has('archive.tags', 3)
                    ->etc()
            );
    }

    public function test_owner_can_access_private_archive()
    {
        $user = User::factory()->create();
        $token = $user->createToken('main')->plainTextToken;
        $public = Visibility::create(['id' => 1, 'visibility' => 'Public']);
        $private = Visibility::create(['id' => 2, 'visibility' => 'Private']);
        $tags = Tag::factory(3)->create();
        $links = Link::factory(5)
            ->for($user, 'author')
            ->for($public, 'type')
            ->hasAttached($tags)
            ->create();

        $archive = Archive::factory()
            ->for($user, 'author')
            ->for($private, 'type')
            ->hasAttached($tags, [], 'tags')
            ->hasAttached($links, [], 'links')
            ->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/archives/' . $archive->slug);

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json) =>
                $json->where('archive.author.username', $user->username)
                    ->where('archive.title', $archive->title)
                    ->where('archive.slug', $archive->slug)
                    ->where('archive.links_count', 5)
                    ->where('archive.visibility', 'Private')
                    ->has('archive.tags', 3)
                    ->etc()
            );
    }

    public function test_abort_if_user_access_private_archive()
    {
        $owner = User::factory()->create();
        $user = User::factory()->create();
        $token = $user->createToken('main')->plainTextToken;
        $public = Visibility::create(['id' => 1, 'visibility' => 'Public']);
        $private = Visibility::create(['id' => 2, 'visibility' => 'Private']);
        $tags = Tag::factory(3)->create();
        $links = Link::factory(5)
            ->for($user, 'author')
            ->for($public, 'type')
            ->hasAttached($tags)
            ->create();

        $archive = Archive::factory()
            ->for($owner, 'author')
            ->for($private, 'type')
            ->hasAttached($tags, [], 'tags')
            ->hasAttached($links, [], 'links')
            ->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/archives/' . $archive->slug);

        $response->assertStatus(403);
    }
}
