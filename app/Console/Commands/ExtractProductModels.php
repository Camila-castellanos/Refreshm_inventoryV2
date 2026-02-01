<?php

namespace App\Console\Commands;

use App\Models\Ecommerce\Market;
use App\Models\Item;
use App\Models\ProductModel;
use Illuminate\Console\Command;

class ExtractProductModels extends Command
{
    protected $signature = 'market:extract-product-models {--market= : Specific market ID}';

    protected $description = 'Extract product models from items and populate product_models table';

    public function handle()
    {
        $marketId = $this->option('market');
        $market = $marketId ? Market::find($marketId) : Market::first();

        if (! $market) {
            $this->error('No market found!');

            return 1;
        }

        $this->info('=== Extract Product Models ===');
        $this->info("Market: {$market->name}");
        $this->line('');

        // Get all models from getGroupedModels
        $models = $market->getGroupedModels(null, 1000, null, null, 'latest', true);

        $created = 0;
        $updated = 0;

        foreach ($models as $modelData) {
            $modelName = $modelData->model;
            $manufacturer = $modelData->manufacturer;
            $type = $modelData->type;

            // Get all items for this model to extract colours/capacities
            $items = Item::where('shop_id', $market->shop_id)
                ->where('model', 'LIKE', $modelName.'%')
                ->get();

            // Create or update ProductModel (first get or create to get existing values)
            $productModel = ProductModel::firstOrNew(
                [
                    'name' => $modelName,
                    'manufacturer' => $manufacturer,
                ]
            );

            // Extract colours and merge with existing (avoid duplicates)
            $newColours = $items->pluck('colour')->filter()->unique()->values()->toArray();
            $existingColours = $productModel->colours ?? [];
            $mergedColours = array_unique(array_merge($existingColours, $newColours));
            sort($mergedColours);

            // Extract capacities and merge with existing (avoid duplicates)
            $newCapacities = $items->map(function ($item) {
                $parsed = $this->parseModelStorage($item->model);

                return $parsed['storage'];
            })->filter()->unique()->values()->toArray();
            $existingCapacities = $productModel->capacities ?? [];
            $mergedCapacities = array_unique(array_merge($existingCapacities, $newCapacities));
            sort($mergedCapacities);

            // Save with merged values (type is overwritten)
            $productModel->type = $type;
            $productModel->colours = $mergedColours;
            $productModel->capacities = $mergedCapacities;
            $productModel->save();

            if ($productModel->wasRecentlyCreated) {
                $created++;
                $this->line("  [NEW] {$modelName} ({$manufacturer}): ".count($mergedColours).' colours, '.count($mergedCapacities).' capacities');
            } else {
                $updated++;
                $this->line("  [UPD] {$modelName} ({$manufacturer}): ".count($mergedColours).' colours, '.count($mergedCapacities).' capacities');
            }
        }

        $this->line('');
        $this->info('=== Summary ===');
        $this->info("Models found: {$models->count()}");
        $this->info("ProductModels created: {$created}");
        $this->info("ProductModels updated: {$updated}");
        $this->info('Done!');

        return 0;
    }

    /**
     * Parse model name to extract base model and storage capacity
     * Examples: "iPad 7 32GB" -> ['model' => 'iPad 7', 'storage' => '32GB']
     *           "iPhone 15 Pro Max 256GB" -> ['model' => 'iPhone 15 Pro Max', 'storage' => '256GB']
     *           "Galaxy S22 Ultra" -> ['model' => 'Galaxy S22 Ultra', 'storage' => null]
     */
    private function parseModelStorage(string $model): array
    {
        // Match patterns like: 32GB, 64GB, 128GB, 256GB, 512GB, 1TB, 2TB, etc.
        // This regex captures everything before the storage capacity as the model name
        if (preg_match('/^(.+?)\s+([\d]+(?:GB|TB))$/i', trim($model), $matches)) {
            return [
                'model' => trim($matches[1]),
                'storage' => strtoupper($matches[2]),
            ];
        }

        // If no storage found, return the full model name
        return [
            'model' => trim($model),
            'storage' => null,
        ];
    }
}
