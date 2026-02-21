<?php

namespace Database\Factories;

use App\Models\ProductModel;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductModelFactory extends Factory
{
    protected $model = ProductModel::class;

    public function definition(): array
    {
        $manufacturers = ['Apple', 'Samsung', 'Google', 'OnePlus', 'Xiaomi', 'LG', 'Motorola', 'Nokia'];
        $manufacturer = fake()->randomElement($manufacturers);

        $modelNames = [
            'Apple' => ['iPhone 15', 'iPhone 14', 'iPhone 13', 'iPhone SE', 'iPhone 16'],
            'Samsung' => ['Galaxy S24', 'Galaxy S23', 'Galaxy A54', 'Galaxy Z Fold', 'Galaxy S25'],
            'Google' => ['Pixel 8', 'Pixel 7', 'Pixel 6a', 'Pixel 9'],
            'OnePlus' => ['OnePlus 12', 'OnePlus 11', 'OnePlus Nord'],
            'Xiaomi' => ['Redmi Note 12', 'POCO F5', 'Xiaomi 13', 'Xiaomi 14'],
            'LG' => ['Wing', 'Velvet', 'K92'],
            'Motorola' => ['Edge 40', 'Moto G82', 'Razr 40'],
            'Nokia' => ['X30', 'G60', 'C32'],
        ];

        return [
            'name' => fake()->randomElement($modelNames[$manufacturer] ?? ['Generic Phone']),
            'manufacturer' => $manufacturer,
            'type' => 'device',
            'colours' => fake()->randomElements(['Black', 'White', 'Blue', 'Red', 'Green', 'Purple', 'Gold', 'Silver'], fake()->numberBetween(1, 4)),
            'capacities' => fake()->randomElements(['64GB', '128GB', '256GB', '512GB', '1TB'], fake()->numberBetween(1, 3)),
            'description' => fake()->optional(0.7)->sentence(),
        ];
    }

    public function forCompany(?int $companyId = null): static
    {
        return $this;
    }
}
