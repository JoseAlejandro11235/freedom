<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CategoryHierarchyTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_can_have_nullable_parent(): void
    {
        $parent = Category::query()->create([
            'name' => 'Fragancias',
            'slug' => 'fragancias',
        ]);

        $child = Category::query()->create([
            'name' => 'Para ellos',
            'slug' => 'para-ellos',
            'category_father_id' => $parent->id,
        ]);

        $this->assertNull($parent->category_father_id);
        $this->assertSame($parent->id, $child->category_father_id);
        $this->assertSame('Fragancias', $child->father->name);
    }

    public function test_category_cannot_be_its_own_parent(): void
    {
        $category = Category::query()->create([
            'name' => 'Skincare',
            'slug' => 'skincare',
        ]);

        $this->expectException(ValidationException::class);

        $category->update(['category_father_id' => $category->id]);
    }

    public function test_category_cannot_use_descendant_as_parent(): void
    {
        $root = Category::query()->create(['name' => 'Root', 'slug' => 'root']);
        $child = Category::query()->create([
            'name' => 'Child',
            'slug' => 'child',
            'category_father_id' => $root->id,
        ]);

        $this->expectException(ValidationException::class);

        $root->update(['category_father_id' => $child->id]);
    }

    public function test_homepage_only_shows_root_categories(): void
    {
        $root = Category::query()->create([
            'name' => 'Visible Root',
            'slug' => 'visible-root',
            'is_published' => true,
            'sort_order' => 1,
        ]);

        Category::query()->create([
            'name' => 'Hidden Child',
            'slug' => 'hidden-child',
            'category_father_id' => $root->id,
            'is_published' => true,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('categories', 1)
                ->where('categories.0.name', 'Visible Root'));
    }
}
