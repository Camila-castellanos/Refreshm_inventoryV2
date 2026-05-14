<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\Shop;
use App\Models\Storage;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

class ItemFactory extends Factory
{
    protected $model = Item::class;

    public function definition(): array
    {
        $manufacturers = ['Apple', 'Samsung', 'Google', 'OnePlus', 'Xiaomi', 'LG', 'Motorola', 'Nokia'];
        $models = [
            'Apple' => ['iPhone 15', 'iPhone 14', 'iPhone 13', 'iPhone SE'],
            'Samsung' => ['Galaxy S24', 'Galaxy S23', 'Galaxy A54', 'Galaxy Z Fold'],
            'Google' => ['Pixel 8', 'Pixel 7', 'Pixel 6a'],
            'OnePlus' => ['OnePlus 12', 'OnePlus 11', 'OnePlus Nord'],
            'Xiaomi' => ['Redmi Note 12', 'POCO F5', 'Xiaomi 13'],
            'LG' => ['Wing', 'Velvet', 'K92'],
            'Motorola' => ['Edge 40', 'Moto G82', 'Razr 40'],
            'Nokia' => ['X30', 'G60', 'C32'],
        ];

        $manufacturer = fake()->randomElement($manufacturers);
        $modelName = fake()->randomElement($models[$manufacturer] ?? ['Generic Phone']);
        $grade = fake()->randomElement(['A', 'B', 'C', 'D', 'F']);
        $colour = fake()->randomElement(['Black', 'White', 'Blue', 'Red', 'Green', 'Purple', 'Gold', 'Silver']);

        return [
            'date' => fake()->dateTimeBetween('-6 months', 'now'),
            'sale_id' => null,
            'supplier' => null,
            'manufacturer' => $manufacturer,
            'storage_id' => null,
            'position' => null,
            'model' => $modelName.' '.$colour.' '.$grade,
            'colour' => $colour,
            'battery' => fake()->randomElement(['90%', '85%', '80%', '95%', '100%']),
            'grade' => $grade,
            'issues' => fake()->optional(0.3)->sentence(),
            'cost' => fake()->randomFloat(2, 50, 800),
            'imei' => fake()->unique()->numerify('###############'),
            'selling_price' => fake()->randomFloat(2, 100, 1500),
            'customer' => null,
            'sold' => null,
            'hold' => null,
            'discount' => null,
            'tax' => null,
            'subtotal' => null,
            'profit' => null,
            'user_id' => null,
            'vendor_id' => null,
            'shop_id' => null,
            'type' => 'device',
            'product_model_id' => null,
            'sold_storage_id' => null,
            'sold_position' => null,
            'sold_storage_name' => null,
            'status' => 'available',
        ];
    }

    public function forStorage(?Storage $storage = null): static
    {
        return $this->state(fn (array $attributes) => [
            'storage_id' => $storage?->id ?? Storage::factory(),
        ]);
    }

    public function forVendor(?Vendor $vendor = null): static
    {
        return $this->state(fn (array $attributes) => [
            'vendor_id' => $vendor?->id ?? Vendor::factory(),
        ]);
    }

    public function forShop(?Shop $shop = null): static
    {
        return $this->state(fn (array $attributes) => [
            'shop_id' => $shop?->id ?? Shop::factory(),
        ]);
    }

    public function forUser(?User $user = null): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user?->id ?? User::factory(),
        ]);
    }

    public function type(string $type): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => $type,
        ]);
    }

    public function accessory(): static
    {
        return $this->type('accessory');
    }

    public function device(): static
    {
        return $this->type('device');
    }

    public function sold(): static
    {
        return $this->state(fn (array $attributes) => [
            'sold' => now(),
        ]);
    }

    public function onHold(?string $customer = null): static
    {
        return $this->state(fn (array $attributes) => [
            'hold' => now(),
            'customer' => $customer ?? fake()->name(),
        ]);
    }

    public function withCost(float $cost): static
    {
        return $this->state(fn (array $attributes) => [
            'cost' => $cost,
        ]);
    }

    public function withSellingPrice(float $price): static
    {
        return $this->state(fn (array $attributes) => [
            'selling_price' => $price,
        ]);
    }

    public function withImei(string $imei): static
    {
        return $this->state(fn (array $attributes) => [
            'imei' => $imei,
        ]);
    }

    public function withPosition(int $position): static
    {
        return $this->state(fn (array $attributes) => [
            'position' => $position,
        ]);
    }

    public function withGrade(string $grade): static
    {
        return $this->state(fn (array $attributes) => [
            'grade' => $grade,
        ]);
    }

    public function withColour(string $colour): static
    {
        return $this->state(fn (array $attributes) => [
            'colour' => $colour,
        ]);
    }

    public function withManufacturer(string $manufacturer): static
    {
        return $this->state(fn (array $attributes) => [
            'manufacturer' => $manufacturer,
        ]);
    }

    public function withModel(string $model): static
    {
        return $this->state(fn (array $attributes) => [
            'model' => $model,
        ]);
    }
}
