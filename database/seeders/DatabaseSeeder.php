<?php

namespace Database\Seeders;

use App\Models\Archive;
use App\Models\Link;
use App\Models\Tag;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Visibility;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        Visibility::create(['id' => 1, 'visibility' => 'Public']);
        Visibility::create(['id' => 2, 'visibility' => 'Private']);

        $tags = Tag::factory(5)->create();
        $user = User::factory()->create();

        $links = Link::factory(20)
            ->for($user, 'author')
            ->hasAttached($tags)
            ->create();

        for ($i = 0; $i < 20; $i++) {
            Archive::factory()
                ->for($user, 'author')
                ->hasAttached($tags, [], 'tags')
                ->hasAttached($links, [], 'links')
                ->create();
        }
    }
}
