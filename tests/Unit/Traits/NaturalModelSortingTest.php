<?php

namespace Tests\Unit\Traits;

use App\Traits\HasNaturalModelSorting;
use Tests\TestCase;

class NaturalModelSortingTest extends TestCase
{
    use HasNaturalModelSorting;

    public function test_natural_sorting_orders_models_correctly(): void
    {
        $items = collect([
            ['model' => 'iPhone 11 64GB'],
            ['model' => 'iPhone 16 Pro 256GB'],
            ['model' => 'iPhone 8 64GB'],
            ['model' => 'iPhone XR 128GB'],
            ['model' => 'iPhone 15 128GB'],
            ['model' => 'iPhone X 64GB'],
            ['model' => 'iPhone XS Max 256GB'],
        ]);

        $sorted = $this->applyNaturalModelSorting($items)->values();

        // Expected order (Newest first): 16 Pro, 15, 11, XS Max, XR, X, 8
        $this->assertEquals('iPhone 16 Pro 256GB', $sorted[0]['model']);
        $this->assertEquals('iPhone 15 128GB', $sorted[1]['model']);
        $this->assertEquals('iPhone 11 64GB', $sorted[2]['model']);
        $this->assertEquals('iPhone XS Max 256GB', $sorted[3]['model']);
        $this->assertEquals('iPhone XR 128GB', $sorted[4]['model']);
        $this->assertEquals('iPhone X 64GB', $sorted[5]['model']);
        $this->assertEquals('iPhone 8 64GB', $sorted[6]['model']);
    }

    public function test_hierarchical_sorting_orders_brands_and_models_correctly(): void
    {
        $items = collect([
            ['model' => 'Galaxy S24', 'manufacturer' => 'Samsung'],
            ['model' => 'iPhone 15', 'manufacturer' => 'Apple'],
            ['model' => 'Pixel 8', 'manufacturer' => 'Google'],
            ['model' => 'iPhone 16', 'manufacturer' => 'Apple'],
            ['model' => 'Xiaomi 14', 'manufacturer' => 'Xiaomi'],
            ['model' => 'Galaxy S23', 'manufacturer' => 'Samsung'],
        ]);

        $sorted = $this->applyHierarchicalModelSorting($items)->values();

        // Expected order: Apple (16, 15), Samsung (S24, S23), Google (Pixel 8), Xiaomi (Xiaomi 14)
        $this->assertEquals('Apple', $sorted[0]['manufacturer']);
        $this->assertEquals('iPhone 16', $sorted[0]['model']);
        $this->assertEquals('iPhone 15', $sorted[1]['model']);

        $this->assertEquals('Samsung', $sorted[2]['manufacturer']);
        $this->assertEquals('Galaxy S24', $sorted[2]['model']);
        $this->assertEquals('Galaxy S23', $sorted[3]['model']);

        $this->assertEquals('Google', $sorted[4]['manufacturer']);
        $this->assertEquals('Pixel 8', $sorted[4]['model']);

        $this->assertEquals('Xiaomi', $sorted[5]['manufacturer']);
        $this->assertEquals('Xiaomi 14', $sorted[5]['model']);
    }

    public function test_normalization_handles_iphone_x_family(): void
    {
        $this->assertEquals('iPhone 10', $this->normalizeModelName('iPhone X'));
        $this->assertEquals('iPhone 10.1 64GB', $this->normalizeModelName('iPhone XR 64GB'));
        $this->assertEquals('iPhone 10.2 Max', $this->normalizeModelName('iPhone XS Max'));
    }
}
