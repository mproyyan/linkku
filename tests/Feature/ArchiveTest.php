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

    public function test_get_links_from_public_archive()
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

        $response = $this->getJson('/api/archives/links/' . $archive->slug);

        $response->assertStatus(200)
            ->assertJsonPath('data.0.hash', fn ($hash) => strlen($hash) == 10)
            ->assertJson(
                fn (AssertableJson $json) =>
                $json->has(
                    'data',
                    5,
                    fn ($json) =>
                    $json->where('author.username', $user->username)
                        ->where('visibility', 'Public')
                        ->has('tags', 3)
                        ->etc()
                )
            );
    }

    public function test_owner_can_get_links_from_private_archive()
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
            ->getJson('/api/archives/links/' . $archive->slug);

        $response->assertStatus(200)
            ->assertJsonPath('data.0.hash', fn ($hash) => strlen($hash) == 10)
            ->assertJson(
                fn (AssertableJson $json) =>
                $json->has(
                    'data',
                    5,
                    fn ($json) =>
                    $json->where('author.username', $user->username)
                        ->where('visibility', 'Public')
                        ->has('tags', 3)
                        ->etc()
                )
            );
    }

    public function test_abort_if_user_access_links_from_private_archive()
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
            ->getJson('/api/archives/links/' . $archive->slug);

        $response->assertStatus(403);
    }

    public function test_save_archive_to_database()
    {
        $user = User::factory()->create();
        $token = $user->createToken('main')->plainTextToken;
        $tags = Tag::factory(3)->create();
        $visibility = Visibility::create(['id' => 1, 'visibility' => 'Public']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/archives', [
                'title' => 'test archive',
                'tags' => $tags->pluck('id')->all(),
                'visibility' => $visibility->id
            ]);

        $response->assertStatus(201)
            ->assertJson(
                fn (AssertableJson $json) =>
                $json->where('archive.author.username', $user->username)
                    ->where('archive.title', 'test archive')
                    ->where('archive.slug', 'test-archive')
                    ->where('archive.visibility', 'Public')
                    ->has('archive.tags', 3)
                    ->etc()
            );

        $this->assertDatabaseHas('archives', [
            'title' => 'test archive',
            'slug' => 'test-archive'
        ]);
    }

    public function test_create_archive_failed_when_given_tags_doesnt_exists()
    {
        $user = User::factory()->create();
        $token = $user->createToken('main')->plainTextToken;
        $tags = Tag::factory(3)->create();
        $visibility = Visibility::create(['id' => 1, 'visibility' => 'Public']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/archives', [
                'title' => 'test archive',
                'tags' => [8, 9, 6],
                'visibility' => $visibility->id
            ]);

        $response->assertStatus(422);
    }

    public function test_create_archive_failed_when_given_tags_more_than_five()
    {
        $user = User::factory()->create();
        $token = $user->createToken('main')->plainTextToken;
        $tags = Tag::factory(6)->create();
        $visibility = Visibility::create(['id' => 1, 'visibility' => 'Public']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/archives', [
                'title' => 'test archive',
                'tags' => $tags->pluck('id')->all(),
                'visibility' => $visibility->id
            ]);

        $response->assertStatus(422);
    }

    public function test_create_archive_failed_when_given_visibility_doesnt_exist()
    {
        $user = User::factory()->create();
        $token = $user->createToken('main')->plainTextToken;
        $tags = Tag::factory(3)->create();
        $visibility = Visibility::create(['id' => 1, 'visibility' => 'Public']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/archives', [
                'title' => 'test archive',
                'tags' => $tags->pluck('id')->all(),
                'visibility' => 2
            ]);

        $response->assertStatus(422);
    }

    public function test_update_archive()
    {
        $user = User::factory()->create();
        $token = $user->createToken('main')->plainTextToken;
        $deletedTags = Tag::factory(3)->create();
        $newTags = Tag::factory(3)->create();
        $public = Visibility::create(['id' => 1, 'visibility' => 'Public']);
        $private = Visibility::create(['id' => 2, 'visibility' => 'Private']);

        $archive = Archive::factory()
            ->for($user, 'author')
            ->for($public, 'type')
            ->hasAttached($deletedTags)
            ->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/archives/' . $archive->slug, [
                'title' => 'updated title',
                'tags' => $newTags->pluck('id')->all(),
                'visibility' => $private->id
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('archive.tags.0.id', fn ($id) => in_array($id, $newTags->pluck('id')->all()))
            ->assertJson(
                fn (AssertableJson $json) =>
                $json->where('archive.title', 'updated title')
                    ->where('archive.slug', 'updated-title')
                    ->where('archive.visibility', 'Private')
                    ->etc()
            );

        foreach ($deletedTags->pluck('id')->all() as $id) {
            $this->assertDatabaseMissing('taggables', [
                'tag_id' => $id,
                'taggable_id' => $archive->id,
                'taggable_type' => 'App\Models\Archive'
            ]);
        }
    }

    public function test_user_cannot_update_archive_if_not_owner()
    {
        $owner = User::factory()->create();
        $user = User::factory()->create();
        $token = $user->createToken('main')->plainTextToken;
        $deletedTags = Tag::factory(3)->create();
        $newTags = Tag::factory(3)->create();
        $public = Visibility::create(['id' => 1, 'visibility' => 'Public']);
        $private = Visibility::create(['id' => 2, 'visibility' => 'Private']);

        $archive = Archive::factory()
            ->for($owner, 'author')
            ->for($public, 'type')
            ->hasAttached($deletedTags)
            ->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/archives/' . $archive->slug, [
                'title' => 'updated title',
                'tags' => $newTags->pluck('id')->all(),
                'visibility' => $private->id
            ]);

        $response->assertStatus(403);
    }

    public function test_delete_archive()
    {
        $user = User::factory()->create();
        $token = $user->createToken('main')->plainTextToken;
        $tags = Tag::factory(3)->create();

        $archive = Archive::factory()
            ->for($user, 'author')
            ->for(Visibility::create(['id' => 1, 'visibility' => 'Public']), 'type')
            ->hasAttached($tags)
            ->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson('/api/archives/' . $archive->slug);

        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'message' => 'Archive deleted successfully'
            ]);

        $this->assertDatabaseMissing('archives', [
            'id' => $archive->id,
            'slug' => $archive->slug,
        ]);

        foreach ($tags->pluck('id')->all() as $id) {
            $this->assertDatabaseMissing('taggables', [
                'tag_id' => $id,
                'taggable_id' => $archive->id,
                'taggable_type' => 'App\Models\Archive'
            ]);
        }
    }

    public function test_user_cannot_delete_archive_if_not_owner()
    {
        $owner = User::factory()->create();
        $user = User::factory()->create();
        $token = $user->createToken('main')->plainTextToken;
        $tags = Tag::factory(3)->create();

        $archive = Archive::factory()
            ->for($owner, 'author')
            ->for(Visibility::create(['id' => 1, 'visibility' => 'Public']), 'type')
            ->hasAttached($tags)
            ->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson('/api/archives/' . $archive->slug);

        $response->assertStatus(403);
    }

    public function test_add_new_link_to_archive()
    {
        $user = User::factory()->create();
        $token = $user->createToken('main')->plainTextToken;
        $tags = Tag::factory(3)->create();
        $public = Visibility::create(['id' => 1, 'visibility' => 'Public']);
        $private = Visibility::create(['id' => 2, 'visibility' => 'Private']);

        $link = Link::factory()
            ->for($user, 'author')
            ->for($public, 'type')
            ->hasAttached($tags)
            ->create();

        $archive = Archive::factory()
            ->for($user, 'author')
            ->for($public, 'type')
            ->hasAttached($tags)
            ->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/archives/$archive->slug/add/$link->hash");

        $response->assertStatus(201);

        $this->assertDatabaseHas('archive_link', [
            'archive_id' => $archive->id,
            'link_id' => $link->id
        ]);
    }

    public function test_the_owner_can_add_a_private_link_to_the_archive_as_long_as_the_link_belongs_to_him()
    {
        $user = User::factory()->create();
        $token = $user->createToken('main')->plainTextToken;
        $tags = Tag::factory(3)->create();
        $public = Visibility::create(['id' => 1, 'visibility' => 'Public']);
        $private = Visibility::create(['id' => 2, 'visibility' => 'Private']);

        $link = Link::factory()
            ->for($user, 'author')
            ->for($private, 'type')
            ->hasAttached($tags)
            ->create();

        $archive = Archive::factory()
            ->for($user, 'author')
            ->for($public, 'type')
            ->hasAttached($tags)
            ->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/archives/$archive->slug/add/$link->hash");

        $response->assertStatus(201);

        $this->assertDatabaseHas('archive_link', [
            'archive_id' => $archive->id,
            'link_id' => $link->id
        ]);
    }

    public function test_add_link_failed_when_link_not_found()
    {
        $user = User::factory()->create();
        $token = $user->createToken('main')->plainTextToken;
        $tags = Tag::factory(3)->create();
        $public = Visibility::create(['id' => 1, 'visibility' => 'Public']);

        $archive = Archive::factory()
            ->for($user, 'author')
            ->for($public, 'type')
            ->hasAttached($tags)
            ->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/archives/$archive->slug/add/hsgf733cxcbg");

        $response->assertStatus(404);
    }

    public function test_add_link_failed_when_link_already_exists_in_archive()
    {
        $user = User::factory()->create();
        $token = $user->createToken('main')->plainTextToken;
        $tags = Tag::factory(3)->create();
        $public = Visibility::create(['id' => 1, 'visibility' => 'Public']);
        $private = Visibility::create(['id' => 2, 'visibility' => 'Private']);

        $link = Link::factory()
            ->for($user, 'author')
            ->for($public, 'type')
            ->hasAttached($tags)
            ->create();

        $archive = Archive::factory()
            ->for($user, 'author')
            ->for($public, 'type')
            ->hasAttached($tags)
            ->hasAttached($link)
            ->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/archives/$archive->slug/add/$link->hash");

        $response->assertStatus(400)
            ->assertJson([
                'error' => true,
                'message' => 'Cannot add link because already exist.'
            ]);
    }

    public function test_add_link_failed_when_link_is_private()
    {
        $owner = User::factory()->create();
        $user = User::factory()->create();
        $token = $user->createToken('main')->plainTextToken;
        $tags = Tag::factory(3)->create();
        $public = Visibility::create(['id' => 1, 'visibility' => 'Public']);
        $private = Visibility::create(['id' => 2, 'visibility' => 'Private']);

        $link = Link::factory()
            ->for($owner, 'author')
            ->for($private, 'type')
            ->hasAttached($tags)
            ->create();

        $archive = Archive::factory()
            ->for($user, 'author')
            ->for($public, 'type')
            ->hasAttached($tags)
            ->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/archives/$archive->slug/add/$link->hash");

        $response->assertStatus(400)
            ->assertJson([
                'error' => true,
                'message' => 'Cannot add link because link is private'
            ]);
    }

    public function test_add_link_failed_if_user_add_link_to_archive_but_that_archive_is_not_belong_to_him()
    {
        $owner = User::factory()->create();
        $user = User::factory()->create();
        $token = $user->createToken('main')->plainTextToken;
        $tags = Tag::factory(3)->create();
        $public = Visibility::create(['id' => 1, 'visibility' => 'Public']);
        $private = Visibility::create(['id' => 2, 'visibility' => 'Private']);

        $link = Link::factory()
            ->for($user, 'author')
            ->for($public, 'type')
            ->hasAttached($tags)
            ->create();

        $archive = Archive::factory()
            ->for($owner, 'author')
            ->for($public, 'type')
            ->hasAttached($tags)
            ->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/archives/$archive->slug/add/$link->hash");

        $response->assertStatus(403);
    }
}
