<?php

namespace App\Traits;

use Illuminate\Support\Collection;

trait HasNaturalModelSorting
{
    /**
     * Normalize model names for natural sorting (specifically for iPhone X family and SE)
     */
    protected function normalizeModelName(string $model): string
    {
        // Handle iPhone SE (push to end with generation priority)
        // Match "iPhone SE 3rd Gen", "iPhone SE (3rd Gen)", "iPhone SE 2nd Gen", etc.
        if (preg_match('/iPhone\s+SE\s+(3rd|2nd)/i', $model, $matches)) {
            $gen = strtolower($matches[1]);
            $val = '0.3'; // 3rd Gen
            if ($gen === '2nd') {
                $val = '0.2'; // 2nd Gen
            }

            return 'iPhone '.$val;
        }

        // Handle original iPhone SE (with or without extra text like carriers)
        // Match "iPhone SE", "iPhone SE 64GB", "iPhone SE (AT&T)", etc. but NOT "iPhone SE 3rd"
        if (preg_match('/iPhone\s+SE\b/i', $model)) {
            return 'iPhone 0.1';
        }

        // Handle iPhone X family
        if (preg_match('/iPhone (X[RS]?)(.*)/i', $model, $matches)) {
            $val = '10';
            if (strtoupper($matches[1]) === 'XR') {
                $val = '10.1';
            }
            if (strtoupper($matches[1]) === 'XS') {
                $val = '10.2';
            }

            return 'iPhone '.$val.$matches[2];
        }

        return $model;
    }

    /**
     * Get brand priority for hierarchical sorting (Apple -> Samsung -> Google -> Others)
     */
    protected function getBrandPriority(?string $brand): int
    {
        $brand = strtolower(trim((string) $brand));

        return match ($brand) {
            'apple' => 1,
            'samsung' => 2,
            'google' => 3,
            default => 4,
        };
    }

    /**
     * Apply hierarchical sorting:
     * 1. Brand priority (Apple -> Samsung -> Google -> Others)
     * 2. Natural model sorting (Newest first)
     */
    protected function applyHierarchicalModelSorting(Collection $collection): Collection
    {
        return $collection->sort(function ($a, $b) {
            $brandA = is_object($a) ? ($a->manufacturer ?? '') : ($a['manufacturer'] ?? '');
            $brandB = is_object($b) ? ($b->manufacturer ?? '') : ($b['manufacturer'] ?? '');

            $priorityA = $this->getBrandPriority($brandA);
            $priorityB = $this->getBrandPriority($brandB);

            // 1. Compare Brand Priority
            if ($priorityA !== $priorityB) {
                return $priorityA <=> $priorityB;
            }

            // 2. Compare Models Naturally (Descending - Newest first)
            $modelA = is_object($a) ? ($a->model ?? '') : ($a['model'] ?? '');
            $modelB = is_object($b) ? ($b->model ?? '') : ($b['model'] ?? '');

            $normA = $this->normalizeModelName($modelA);
            $normB = $this->normalizeModelName($modelB);

            return strnatcasecmp($normB, $normA);
        });
    }

    /**
     * Apply natural sorting to a collection of items based on their 'model' property.
     * Newest models first (descending order).
     */
    protected function applyNaturalModelSorting(Collection $collection): Collection
    {
        return $collection->sort(function ($a, $b) {
            $modelA = is_object($a) ? ($a->model ?? '') : ($a['model'] ?? '');
            $modelB = is_object($b) ? ($b->model ?? '') : ($b['model'] ?? '');

            $normA = $this->normalizeModelName($modelA);
            $normB = $this->normalizeModelName($modelB);

            // Natural case-insensitive comparison, reverse for descending (newest first)
            return strnatcasecmp($normB, $normA);
        });
    }
}
