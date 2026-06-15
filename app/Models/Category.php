<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class Category extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'category_father_id',
        'name',
        'slug',
        'href',
        'image_path',
        'sort_order',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Category $category) {
            if (blank($category->slug) && filled($category->name)) {
                $category->slug = Str::slug($category->name);
            }

            $category->assertValidParent();
        });
    }

    public function father(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_father_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'category_father_id');
    }

    public function isRoot(): bool
    {
        return $this->category_father_id === null;
    }

    /**
     * @return list<string>
     */
    public function descendantIds(): array
    {
        $ids = [];
        $queue = $this->children()->pluck('id')->all();

        while ($queue !== []) {
            $id = array_shift($queue);
            $ids[] = $id;

            $childIds = static::query()
                ->where('category_father_id', $id)
                ->pluck('id')
                ->all();

            array_push($queue, ...$childIds);
        }

        return $ids;
    }

    public function assertValidParent(): void
    {
        if ($this->category_father_id === null) {
            return;
        }

        if ($this->category_father_id !== null && ! static::query()->whereKey($this->category_father_id)->exists()) {
            throw ValidationException::withMessages([
                'category_father_id' => __('The selected parent category does not exist.'),
            ]);
        }

        if (! $this->exists) {
            return;
        }

        if ($this->category_father_id === $this->id) {
            throw ValidationException::withMessages([
                'category_father_id' => __('A category cannot be its own parent.'),
            ]);
        }

        if (in_array($this->category_father_id, $this->descendantIds(), true)) {
            throw ValidationException::withMessages([
                'category_father_id' => __('A category cannot be placed under one of its descendants.'),
            ]);
        }
    }

    public function imageUrl(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        return Storage::disk(config('filesystems.default'))->url($this->image_path);
    }
}
