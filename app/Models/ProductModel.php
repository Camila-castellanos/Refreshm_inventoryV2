<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ProductModel extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'name',
        'manufacturer',
        'type',
        'colours',
        'capacities',
        'description',
    ];

    protected $casts = [
        'colours' => 'array',
        'capacities' => 'array',
    ];

    protected $appends = [
        'main_photo_url',
        'main_photo_thumb',
        'photo_urls',
        'photo_count',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('product-photos')
            ->useFallbackUrl('/images/item-placeholder.svg')
            ->useFallbackPath(public_path('/images/item-placeholder.svg'))
            ->registerMediaConversions(function (Media $media) {
                $this->addMediaConversion('thumb')
                    ->width(300)
                    ->height(300)
                    ->sharpen(10)
                    ->nonQueued();

                $this->addMediaConversion('preview')
                    ->width(800)
                    ->height(600)
                    ->sharpen(10)
                    ->nonQueued();

                $this->addMediaConversion('detail')
                    ->width(1200)
                    ->height(900)
                    ->sharpen(10)
                    ->nonQueued();
            });
    }

    public function getMainPhotoUrlAttribute(): string
    {
        return $this->getFirstMediaUrl('product-photos', 'preview')
            ?: asset('images/item-placeholder.svg');
    }

    public function getMainPhotoThumbAttribute(): string
    {
        return $this->getFirstMediaUrl('product-photos', 'thumb')
            ?: asset('images/item-placeholder.svg');
    }

    public function getPhotoUrlsAttribute(): array
    {
        return $this->getMedia('product-photos')
            ->map(function (Media $media) {
                return [
                    'id' => $media->id,
                    'original' => $media->getUrl(),
                    'thumb' => $media->getUrl('thumb'),
                    'preview' => $media->getUrl('preview'),
                    'detail' => $media->getUrl('detail'),
                    'name' => $media->file_name,
                    'size' => $media->size,
                    'colour' => $media->custom_properties['colour'] ?? null,
                ];
            })
            ->toArray();
    }

    public function getPhotoCountAttribute(): int
    {
        return $this->getMedia('product-photos')->count();
    }

    public function hasPhotos(): bool
    {
        return $this->getMedia('product-photos')->isNotEmpty();
    }

    public function getPhotoByColour(string $colour): ?Media
    {
        return $this->getMedia('product-photos')
            ->first(function (Media $media) use ($colour) {
                return ($media->custom_properties['colour'] ?? null) === $colour;
            });
    }

    public function getFirstMediaUrlByColour(string $colour, string $conversionName = ''): string
    {
        $photo = $this->getPhotoByColour($colour);

        if (! $photo) {
            return asset('images/item-placeholder.svg');
        }

        return $conversionName
            ? $photo->getUrl($conversionName)
            : $photo->getUrl();
    }

    public function getAllColoursFromPhotos(): array
    {
        return $this->getMedia('product-photos')
            ->pluck('custom_properties.colour')
            ->filter()
            ->unique()
            ->values()
            ->toArray();
    }

    public function getAllCapacitiesFromPhotos(): array
    {
        return $this->getMedia('product-photos')
            ->pluck('custom_properties.capacity')
            ->filter()
            ->unique()
            ->values()
            ->toArray();
    }
}
