<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Link>
 */
class LinkFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $title = $this->faker->sentence();
        $slug = Str::slug($title);
        $description = collect($this->faker->paragraph(mt_rand(3, 5)))
            ->map(fn ($p) => "<p>$p</p>")
            ->implode('');
        $excerpt = Str::limit(strip_tags($description), 200);

        return [
            'hash' => bin2hex(random_bytes(5)),
            'user_id' => 1,
            'title' => $title,
            'slug' => $slug,
            'url' => $this->faker->url(),
            'description' => $description,
            'excerpt' => $excerpt,
            'visibility' => 1
        ];
    }

    public function private()
    {
        return $this->state(function (array $attributes) {
            return [
                'visibility' => 2
            ];
        });
    }
}
