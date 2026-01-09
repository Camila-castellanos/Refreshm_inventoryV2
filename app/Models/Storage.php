<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Scopes\CompanyScope;

class Storage extends Model
{
    use HasFactory;

    public $fillable = ['name', 'limit', 'company_id', 'priority', 'is_default'];

    public function items() {
        return $this->hasMany(Item::class);
    }

    public function draftItems() {
        return $this->hasMany(DraftItem::class);
    }

    protected static function boot() {
        parent::boot();

        static::deleting(function ($storage) {
            Item::where('storage_id', $storage->id)
                ->update([
                    'position' => null,
                ]);
        });
    }

    protected static function booted()
    {
        static::addGlobalScope(new CompanyScope());
    }

    /**
     * Define the relationship: A Storage BELONGS TO one Company.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    // =========================================================================
    // UNIFIED POSITION MANAGEMENT METHODS
    // =========================================================================

    /**
     * Get occupied positions for a single storage.
     * This is the SINGLE SOURCE OF TRUTH for position occupancy.
     * 
     * Rules:
     * - Items: must have position, must NOT be sold (whereNull('sold'))
     * - DraftItems: must have storage_position
     * 
     * @param int $storageId The storage ID
     * @param int|null $excludeDraftId Optional draft ID to exclude from check
     * @return array Array of occupied position numbers
     */
    public static function getOccupiedPositions(int $storageId, ?int $excludeDraftId = null): array
    {
        // Get positions from active (unsold) items in inventory
        $itemPositions = Item::where('storage_id', $storageId)
            ->whereNotNull('position')
            ->whereNull('sold')
            ->pluck('position')
            ->toArray();

        // Get positions from draft items
        $draftQuery = DraftItem::where('storage_id', $storageId)
            ->whereNotNull('storage_position');

        if ($excludeDraftId) {
            $draftQuery->where('draft_id', '!=', $excludeDraftId);
        }

        $draftPositions = $draftQuery->pluck('storage_position')->toArray();

        // Merge and return unique positions
        return array_values(array_unique(array_merge($itemPositions, $draftPositions)));
    }

    /**
     * Get occupied positions for multiple storages at once (optimized batch query).
     * 
     * @param array $storageIds Array of storage IDs
     * @param int|null $excludeDraftId Optional draft ID to exclude from check
     * @return array Associative array [storage_id => [positions...]]
     */
    public static function getOccupiedPositionsBatch(array $storageIds, ?int $excludeDraftId = null): array
    {
        if (empty($storageIds)) {
            return [];
        }

        $result = [];
        foreach ($storageIds as $sid) {
            $result[$sid] = [];
        }

        // Get positions from active (unsold) items in inventory
        $itemPositions = Item::whereIn('storage_id', $storageIds)
            ->whereNotNull('position')
            ->whereNull('sold')
            ->get(['storage_id', 'position']);

        foreach ($itemPositions as $item) {
            $result[$item->storage_id][] = $item->position;
        }

        // Get positions from draft items
        $draftQuery = DraftItem::whereIn('storage_id', $storageIds)
            ->whereNotNull('storage_position');

        if ($excludeDraftId) {
            $draftQuery->where('draft_id', '!=', $excludeDraftId);
        }

        $draftPositions = $draftQuery->get(['storage_id', 'storage_position']);

        foreach ($draftPositions as $draft) {
            $result[$draft->storage_id][] = $draft->storage_position;
        }

        // Make positions unique for each storage
        foreach ($result as $sid => $positions) {
            $result[$sid] = array_values(array_unique($positions));
        }

        return $result;
    }

    /**
     * Get the count of occupied positions for a storage.
     * 
     * @param int $storageId The storage ID
     * @param int|null $excludeDraftId Optional draft ID to exclude
     * @return int Number of occupied positions
     */
    public static function getOccupiedCount(int $storageId, ?int $excludeDraftId = null): int
    {
        return count(self::getOccupiedPositions($storageId, $excludeDraftId));
    }

    /**
     * Get the number of available slots for a storage.
     * 
     * @param int $storageId The storage ID
     * @param int|null $excludeDraftId Optional draft ID to exclude
     * @return int Number of available slots (never negative)
     */
    public static function getAvailableSlots(int $storageId, ?int $excludeDraftId = null): int
    {
        $storage = self::find($storageId);
        if (!$storage) {
            return 0;
        }

        $limit = (int) ($storage->limit ?? 0);
        $occupied = self::getOccupiedCount($storageId, $excludeDraftId);

        return max(0, $limit - $occupied);
    }

    /**
     * Check if a specific position is occupied in a storage.
     * 
     * @param int $storageId The storage ID
     * @param int $position The position to check
     * @param int|null $excludeDraftId Optional draft ID to exclude
     * @return bool True if position is occupied
     */
    public static function isPositionOccupied(int $storageId, int $position, ?int $excludeDraftId = null): bool
    {
        $occupied = self::getOccupiedPositions($storageId, $excludeDraftId);
        return in_array($position, $occupied);
    }

    /**
     * Find the first available position in a specific storage.
     * 
     * @param int $storageId The storage ID
     * @param int|null $excludeDraftId Optional draft ID to exclude
     * @param array $additionalOccupied Additional positions to consider as occupied
     * @return int|null The first available position, or null if storage is full
     */
    public static function findFirstAvailableInStorage(int $storageId, ?int $excludeDraftId = null, array $additionalOccupied = []): ?int
    {
        $storage = self::find($storageId);
        if (!$storage) {
            return null;
        }

        $limit = (int) ($storage->limit ?? 0);
        $occupied = array_merge(
            self::getOccupiedPositions($storageId, $excludeDraftId),
            $additionalOccupied
        );

        for ($i = 1; $i <= $limit; $i++) {
            if (!in_array($i, $occupied)) {
                return $i;
            }
        }

        return null;
    }

    /**
     * Find the first available position across ALL storages (ordered by priority).
     * 
     * @param int|null $excludeDraftId Optional draft ID to exclude
     * @param array $additionalOccupied Additional positions to consider as occupied: [['storage_id' => X, 'position' => Y], ...]
     * @return array|null ['storage_id' => X, 'position' => Y] or null if all storages are full
     */
    public static function findFirstAvailablePosition(?int $excludeDraftId = null, array $additionalOccupied = []): ?array
    {
        $storages = self::orderBy('priority', 'asc')->get();

        if ($storages->isEmpty()) {
            return null;
        }

        // Build a map of additional occupied positions by storage
        $additionalByStorage = [];
        foreach ($additionalOccupied as $occupied) {
            $sid = $occupied['storage_id'] ?? null;
            $pos = $occupied['position'] ?? null;
            if ($sid && $pos) {
                if (!isset($additionalByStorage[$sid])) {
                    $additionalByStorage[$sid] = [];
                }
                $additionalByStorage[$sid][] = $pos;
            }
        }

        // Try each storage in priority order
        foreach ($storages as $storage) {
            $additional = $additionalByStorage[$storage->id] ?? [];
            $position = self::findFirstAvailableInStorage($storage->id, $excludeDraftId, $additional);

            if ($position !== null) {
                return [
                    'storage_id' => $storage->id,
                    'position' => $position,
                ];
            }
        }

        return null;
    }

    /**
     * Get storage info with computed occupied/available counts.
     * Useful for API responses and UI display.
     * 
     * @param int|null $excludeDraftId Optional draft ID to exclude
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getAllWithOccupancy(?int $excludeDraftId = null)
    {
        $storages = self::orderBy('priority', 'asc')->get();

        if ($storages->isEmpty()) {
            return $storages;
        }

        $storageIds = $storages->pluck('id')->toArray();
        $occupiedBatch = self::getOccupiedPositionsBatch($storageIds, $excludeDraftId);

        foreach ($storages as $storage) {
            $occupied = $occupiedBatch[$storage->id] ?? [];
            $storage->occupied_count = count($occupied);
            $storage->available_slots = max(0, (int)($storage->limit ?? 0) - $storage->occupied_count);
        }

        return $storages;
    }
}
