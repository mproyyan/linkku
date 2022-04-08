<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Archive>
 */
class ArchiveFactory extends Factory
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
            'user_id' => 1,
            'title' => $title,
            'slug' => $slug,
            'description' => $description,
            'excerpt' => $excerpt,
            'visibility' => 1
        ];
    }
}
