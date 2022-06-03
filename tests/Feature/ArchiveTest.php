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
use Tests\Trait\ResponseStructure;
use Tests\Trait\WithUser;

class ArchiveTest extends TestCase
{
    use RefreshDatabase, WithUser, ResponseStructure;

    private $ownerToken;
    private $publicVisibility;
    private $privateVisibility;
    private $tags;
    private $publicLinks;
    private $publicArchive;
    private $privateArchive;

    public function setUp(): void
    {
        parent::setUp();

        $this->setupUser();
        $this->ownerToken = $this->user->createToken('main')->plainTextToken;
        $this->publicVisibility = Visibility::create(['id' => 1, 'visibility' => 'Public']);
        $this->privateVisibility = Visibility::create(['id' => 2, 'visibility' => 'Private']);
        $this->tags = Tag::factory(3)->create();

        $this->publicLinks = Link::factory(5)
            ->for($this->user, 'author')
            ->for($this->publicVisibility, 'type')
            ->hasAttached($this->tags)
            ->create();

        $this->publicArchive = Archive::factory()
            ->for($this->user, 'author')
            ->for($this->publicVisibility, 'type')
            ->hasAttached($this->tags, [], 'tags')
            ->hasAttached($this->publicLinks, [], 'links')
            ->create();

        $this->privateArchive = Archive::factory()
            ->for($this->user, 'author')
            ->for($this->privateVisibility, 'type')
            ->hasAttached($this->tags, [], 'tags')
            ->hasAttached($this->publicLinks, [], 'links')
            ->create();
    }

    public function test_only_get_all_archives_that_has_link_and_visibility_is_public()
    {
        // private archive
        Archive::factory(3)
            ->for($this->user, 'author')
            ->for($this->privateVisibility, 'type')
            ->hasAttached($this->tags, [], 'tags')
            ->hasAttached($this->publicLinks, [], 'links')
            ->create();

        // public archive but doesnt have links
        Archive::factory(3)
            ->for($this->user, 'author')
            ->for($this->publicVisibility, 'type')
            ->hasAttached($this->tags, [], 'tags')
            ->create();

        // public archive and has links
        Archive::factory(20)
            ->for($this->user, 'author')
            ->for($this->publicVisibility, 'type')
            ->hasAttached($this->tags, [], 'tags')
            ->hasAttached($this->publicLinks, [], 'links')
            ->create();

        $response = $this->getJson('/api/archives');

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json) =>
                $json->has('links')
                    ->has('meta')
                    ->has(
                        'data',
                        20,
                        fn ($json) =>
                        $json->where('visibility', 'Public')
                            ->where('author.username', $this->user->username)
                            ->where('links_count', 5)
                            ->has('tags', 3)
                            ->etc()
                    )
            );
    }

    public function test_get_archive()
    {
        $response = $this->getJson('/api/archives/' . $this->publicArchive->slug);

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json) =>
                $json->where('archive.author.username', $this->user->username)
                    ->where('archive.title', $this->publicArchive->title)
                    ->where('archive.slug', $this->publicArchive->slug)
                    ->where('archive.links_count', 5)
                    ->where('archive.visibility', 'Public')
                    ->has('archive.tags', 3)
                    ->etc()
            );
    }

    public function test_owner_can_access_private_archive()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->ownerToken)
            ->getJson('/api/archives/' . $this->privateArchive->slug);

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json) =>
                $json->where('archive.author.username', $this->user->username)
                    ->where('archive.title', $this->privateArchive->title)
                    ->where('archive.slug', $this->privateArchive->slug)
                    ->where('archive.links_count', 5)
                    ->where('archive.visibility', 'Private')
                    ->has('archive.tags', 3)
                    ->etc()
            );
    }

    public function test_abort_if_user_access_private_archive()
    {
        /** @var \App\Models\User */
        $guestUser = User::factory()->create();
        $token = $guestUser->createToken('main')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/archives/' . $this->privateArchive->slug);

        $response->assertStatus(403)
            ->assertJsonStructure($this->standardApiProblemStructure)
            ->assertJsonPath('detail', 'You cannot access private archive that are not yours');
    }

    public function test_get_links_from_public_archive()
    {
        $response = $this->getJson('/api/archives/links/' . $this->publicArchive->slug);

        $response->assertStatus(200)
            ->assertJsonPath('data.0.hash', fn ($hash) => strlen($hash) == 10)
            ->assertJson(
                fn (AssertableJson $json) =>
                $json->has(
                    'data',
                    5,
                    fn ($json) =>
                    $json->where('author.username', $this->user->username)
                        ->where('visibility', 'Public')
                        ->has('tags', 3)
                        ->etc()
                )
            );
    }

    public function test_owner_can_get_links_from_private_archive()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->ownerToken)
            ->getJson('/api/archives/links/' . $this->privateArchive->slug);

        $response->assertStatus(200)
            ->assertJsonPath('data.0.hash', fn ($hash) => strlen($hash) == 10)
            ->assertJson(
                fn (AssertableJson $json) =>
                $json->has(
                    'data',
                    5,
                    fn ($json) =>
                    $json->where('author.username', $this->user->username)
                        ->where('visibility', 'Public')
                        ->has('tags', 3)
                        ->etc()
                )
            );
    }

    public function test_abort_if_user_access_links_from_private_archive()
    {
        /** @var \App\Models\User $guestUser */
        $guestUser = User::factory()->create();
        $token = $guestUser->createToken('main')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/archives/links/' . $this->privateArchive->slug);

        $response->assertStatus(403)
            ->assertJsonStructure($this->standardApiProblemStructure)
            ->assertJsonPath('detail', 'You cannot get links from private archives that are not yours');
    }

    public function test_save_archive_to_database()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->ownerToken)
            ->postJson('/api/archives', [
                'title' => 'test archive',
                'tags' => $this->tags->pluck('id')->all(),
                'visibility' => $this->publicVisibility->id
            ]);

        $response->assertStatus(201)
            ->assertJson(
                fn (AssertableJson $json) =>
                $json->where('archive.author.username', $this->user->username)
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
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->ownerToken)
            ->postJson('/api/archives', [
                'title' => 'test archive',
                'tags' => [0],
                'visibility' => $this->publicVisibility->id
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure($this->standardApiProblemStructure);
    }

    public function test_create_archive_failed_when_given_tags_more_than_five()
    {
        $tags = Tag::factory(6)->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->ownerToken)
            ->postJson('/api/archives', [
                'title' => 'test archive',
                'tags' => $tags->pluck('id')->all(),
                'visibility' => $this->publicVisibility->id
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure($this->standardApiProblemStructure);
    }

    public function test_create_archive_failed_when_given_visibility_doesnt_exist()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->ownerToken)
            ->postJson('/api/archives', [
                'title' => 'test archive',
                'tags' => $this->tags->pluck('id')->all(),
                'visibility' => 3
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure($this->standardApiProblemStructure);
    }

    public function test_update_archive()
    {
        $deletedTags = Tag::factory(3)->create();
        $newTags = Tag::factory(3)->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->ownerToken)
            ->putJson('/api/archives/' . $this->publicArchive->slug, [
                'title' => 'updated title',
                'tags' => $newTags->pluck('id')->all(),
                'visibility' => $this->privateVisibility->id
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
                'taggable_id' => $this->publicArchive->id,
                'taggable_type' => 'App\Models\Archive'
            ]);
        }
    }

    public function test_user_cannot_update_archive_if_not_owner()
    {
        /** @var \App\Models\User $guestUser */
        $guestUser = User::factory()->create();
        $token = $guestUser->createToken('main')->plainTextToken;
        $newTags = Tag::factory(3)->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/archives/' . $this->publicArchive->slug, [
                'title' => 'updated title',
                'tags' => $newTags->pluck('id')->all(),
                'visibility' => $this->privateVisibility->id
            ]);

        $response->assertStatus(403)
            ->assertJsonStructure($this->standardApiProblemStructure)
            ->assertJsonPath('detail', 'You cannot update archive that are not yours');
    }

    public function test_delete_archive()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->ownerToken)
            ->deleteJson('/api/archives/' . $this->publicArchive->slug);

        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'message' => 'Archive deleted successfully'
            ]);

        $this->assertDatabaseMissing('archives', [
            'id' => $this->publicArchive->id,
            'slug' => $this->publicArchive->slug,
        ]);

        foreach ($this->tags->pluck('id')->all() as $id) {
            $this->assertDatabaseMissing('taggables', [
                'tag_id' => $id,
                'taggable_id' => $this->publicArchive->id,
                'taggable_type' => 'App\Models\Archive'
            ]);
        }
    }

    public function test_user_cannot_delete_archive_if_not_owner()
    {
        /** @var \App\Models\User $guestUser */
        $guestUser = User::factory()->create();
        $token = $guestUser->createToken('main')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson('/api/archives/' . $this->publicArchive->slug);

        $response->assertStatus(403)
            ->assertJsonStructure($this->standardApiProblemStructure)
            ->assertJsonPath('detail', 'You cannot delete archive that are not yours');
    }

    public function test_add_new_link_to_archive()
    {
        $link = Link::factory()
            ->for($this->user, 'author')
            ->for($this->publicVisibility, 'type')
            ->hasAttached($this->tags)
            ->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->ownerToken)
            ->postJson("/api/archives/{$this->publicArchive->slug}/add/$link->hash");

        $response->assertStatus(201);

        $this->assertDatabaseHas('archive_link', [
            'archive_id' => $this->publicArchive->id,
            'link_id' => $link->id
        ]);
    }

    public function test_the_owner_can_add_a_private_link_to_the_archive_as_long_as_the_link_belongs_to_him()
    {
        $link = Link::factory()
            ->for($this->user, 'author')
            ->for($this->privateVisibility, 'type')
            ->hasAttached($this->tags)
            ->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->ownerToken)
            ->postJson("/api/archives/{$this->publicArchive->slug}/add/$link->hash");

        $response->assertStatus(201);

        $this->assertDatabaseHas('archive_link', [
            'archive_id' => $this->publicArchive->id,
            'link_id' => $link->id
        ]);
    }

    public function test_add_link_failed_when_link_not_found()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->ownerToken)
            ->postJson("/api/archives/{$this->publicArchive->slug}/add/hsgf733cxcbg");

        $response->assertStatus(404);
    }

    public function test_add_link_failed_when_link_already_exists_in_archive()
    {
        $link = $this->publicLinks->first();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->ownerToken)
            ->postJson("/api/archives/{$this->publicArchive->slug}/add/$link->hash");

        $response->assertStatus(400)
            ->assertJson([
                'error' => true,
                'message' => 'Cannot add link because already exist.'
            ]);
    }

    public function test_add_link_failed_when_link_is_private_and_link_doesnt_belongs_to_him()
    {
        /** @var \App\Models\User $guestUser */
        $guestUser = User::factory()->create();

        $link = Link::factory()
            ->for($guestUser, 'author')
            ->for($this->privateVisibility, 'type')
            ->hasAttached($this->tags)
            ->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->ownerToken)
            ->postJson("/api/archives/{$this->publicArchive->slug}/add/$link->hash");

        $response->assertStatus(400)
            ->assertJson([
                'error' => true,
                'message' => 'Cannot add link because link is private'
            ]);
    }

    public function test_add_link_failed_if_user_add_link_to_archive_but_that_archive_is_not_belong_to_him()
    {
        /** @var \App\Models\User $guestUser */
        $guestUser = User::factory()->create();
        $token = $guestUser->createToken('main')->plainTextToken;

        $link = Link::factory()
            ->for($guestUser, 'author')
            ->for($this->privateVisibility, 'type')
            ->hasAttached($this->tags)
            ->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/archives/{$this->publicArchive->slug}/add/$link->hash");

        $response->assertStatus(403)
            ->assertJsonStructure($this->standardApiProblemStructure)
            ->assertJsonPath('detail', 'You cannot add links to archives that are not yours');
    }

    public function test_link_deleted_from_archive()
    {
        $link = $this->publicLinks->first();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->ownerToken)
            ->deleteJson("/api/archives/{$this->publicArchive->slug}/del/$link->hash");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Link deleted from archive successfully.'
            ]);

        $this->assertDatabaseMissing('archive_link', [
            'archive_id' => $this->publicArchive->id,
            'link_id' => $link->id
        ]);
    }

    public function test_failed_deleted_link_when_user_delete_link_from_archive_that_doesnt_belong_to_him()
    {
        /** @var \App\Models\User $guestUser */
        $guestUser = User::factory()->create();
        $token = $guestUser->createToken('main')->plainTextToken;
        $link = $this->publicLinks->first();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/archives/{$this->publicArchive->slug}/del/$link->hash");

        $response->assertStatus(403)
            ->assertJsonStructure($this->standardApiProblemStructure)
            ->assertJsonPath('detail', 'You cannot delete links from archives that are not yours');
    }

    public function test_failed_deleted_link_when_link_not_found_in_the_archive()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->ownerToken)
            ->deleteJson("/api/archives/{$this->publicArchive->slug}/del/sssss");

        $response->assertStatus(404);
    }
}
