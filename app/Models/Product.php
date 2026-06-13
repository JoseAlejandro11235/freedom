<?php

namespace App\Models;

use App\Enums\HomepageSection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'brand_id',
        'category_id',
        'code',
        'name',
        'slug',
        'selling_price',
        'original_price',
        'badge',
        'exclusive_web',
        'image_fit',
        'href',
        'homepage_section',
        'sort_order',
        'is_published',
        'track_inventory',
        'stock_quantity',
        'low_stock_threshold',
    ];

    protected function casts(): array
    {
        return [
            'selling_price' => 'decimal:2',
            'original_price' => 'decimal:2',
            'exclusive_web' => 'boolean',
            'is_published' => 'boolean',
            'track_inventory' => 'boolean',
            'stock_quantity' => 'integer',
            'low_stock_threshold' => 'integer',
            'sort_order' => 'integer',
            'homepage_section' => HomepageSection::class,
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Product $product) {
            if (blank($product->slug) && filled($product->name)) {
                $base = Str::slug($product->name);
                $product->slug = static::query()
                    ->where('slug', 'like', $base.'%')
                    ->whereKeyNot($product->id)
                    ->exists()
                    ? $base.'-'.Str::lower(Str::random(4))
                    : $base;
            }
        });
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function purchaseLines(): HasMany
    {
        return $this->hasMany(PurchaseLine::class);
    }

    public function sellingLines(): HasMany
    {
        return $this->hasMany(SellingLine::class);
    }

    public function lots(): HasMany
    {
        return $this->hasMany(Lot::class);
    }

    public function lotLines(): HasMany
    {
        return $this->hasMany(LotLine::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function isInStock(): bool
    {
        if (! $this->track_inventory) {
            return true;
        }

        return $this->stock_quantity > 0;
    }

    public function isLowStock(): bool
    {
        if (! $this->track_inventory || ! $this->isInStock()) {
            return false;
        }

        if ($this->low_stock_threshold === null) {
            return false;
        }

        return $this->stock_quantity <= $this->low_stock_threshold;
    }

    /**
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopeInStock(Builder $query): Builder
    {
        return $query->where(function (Builder $query) {
            $query->where('track_inventory', false)
                ->orWhere('stock_quantity', '>', 0);
        });
    }

    /**
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopeLowStock(Builder $query): Builder
    {
        return $query
            ->where('track_inventory', true)
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->whereNotNull('low_stock_threshold');
    }

    /**
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /**
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopeForHomepageSection(Builder $query, HomepageSection $section): Builder
    {
        return $query
            ->published()
            ->inStock()
            ->where('homepage_section', $section)
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    public function primaryImage(): ?ProductImage
    {
        if ($this->relationLoaded('images')) {
            return $this->images->first();
        }

        return $this->images()->orderBy('sort_order')->first();
    }

    public function imageUrl(): ?string
    {
        return $this->primaryImage()?->url();
    }

    public function discountPercent(): ?int
    {
        if ($this->original_price === null || $this->original_price <= $this->selling_price) {
            return null;
        }

        return (int) round((1 - ($this->selling_price / $this->original_price)) * 100);
    }
}
