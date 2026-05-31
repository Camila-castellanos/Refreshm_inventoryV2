<?php

namespace App\Console\Commands;

use App\Models\Item;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixItemStatusConsistency extends Command
{
    protected $signature = 'items:fix-status-consistency
                            {--dry-run : Show what would be fixed without making changes}';

    protected $description = 'Fix items with inconsistent status, storage_id, and position after mass-update bug';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        // Track all affected items for the dry-run summary
        $affected = []; // ['item_id' => ['fixes' => [], 'model' => '', 'imei' => '']]

        // ──────────────────────────────────────────────────────────
        // Fix #1: Items with sold date but status != 'sold'
        // ──────────────────────────────────────────────────────────
        $fix1Query = DB::table('items')
            ->whereNotNull('sold')
            ->where('status', '!=', 'sold')
            ->whereNotNull('status');

        $count1 = $fix1Query->count();

        if ($count1 > 0) {
            $this->warn("Fix #1: {$count1} items have sold date but status != 'sold'");

            if ($dryRun) {
                $rows = $fix1Query->select('id', 'model', 'imei', 'status', 'sold', 'storage_id')->get();
                $this->displayRows($rows, ['id', 'model', 'imei', 'status', 'sold', 'storage_id']);
                foreach ($rows as $r) {
                    $affected[$r->id]['fixes'][] = '#1 sold→sold';
                    $affected[$r->id]['model'] = $r->model;
                    $affected[$r->id]['imei'] = $r->imei ?? '';
                }
            } else {
                $fix1Query->update(['status' => 'sold']);
                $this->info("   → Set status='sold' on {$count1} items.");
            }
        } else {
            $this->line('Fix #1: ✓ No items with sold date and wrong status.');
        }

        // ──────────────────────────────────────────────────────────
        // Fix #2: Sold items that still occupy storage
        // ──────────────────────────────────────────────────────────
        $fix2Query = DB::table('items')
            ->where('status', 'sold')
            ->where(function ($q) {
                $q->whereNotNull('storage_id')
                  ->orWhereNotNull('position');
            });

        $count2 = $fix2Query->count();

        if ($count2 > 0) {
            $this->warn("Fix #2: {$count2} sold items still have storage_id or position");

            if ($dryRun) {
                $rows = $fix2Query->select('id', 'model', 'imei', 'status', 'storage_id', 'position')->get();
                $this->displayRows($rows, ['id', 'model', 'imei', 'status', 'storage_id', 'position']);
                foreach ($rows as $r) {
                    $affected[$r->id]['fixes'][] = '#2 libera posición';
                    $affected[$r->id]['model'] = $r->model;
                    $affected[$r->id]['imei'] = $r->imei ?? '';
                }
            } else {
                DB::statement("
                    UPDATE items
                    SET
                        sold_storage_id   = CASE WHEN sold_storage_id IS NULL THEN storage_id   ELSE sold_storage_id END,
                        sold_position     = CASE WHEN sold_storage_id IS NULL THEN position     ELSE sold_position   END,
                        sold_storage_name = CASE
                            WHEN sold_storage_id IS NULL AND storage_id IS NOT NULL
                            THEN (SELECT name FROM storages WHERE storages.id = items.storage_id)
                            ELSE sold_storage_name
                        END,
                        storage_id = NULL,
                        position   = NULL
                    WHERE status = 'sold'
                      AND (storage_id IS NOT NULL OR position IS NOT NULL)
                ");
                $this->info("   → Cleared storage/position on {$count2} sold items (old location preserved).");
            }
        } else {
            $this->line('Fix #2: ✓ No sold items with stale storage positions.');
        }

        // ──────────────────────────────────────────────────────────
        // Fix #3: Items whose sale is paid (sales.paid=1) but items
        //         are still 'reserved' with sold=NULL
        //         (caused by PaymentController@paid never touching items)
        // ──────────────────────────────────────────────────────────
        $fix3Query = DB::table('items')
            ->join('sales', 'sales.id', '=', 'items.sale_id')
            ->where('items.status', 'reserved')
            ->whereNull('items.sold')
            ->where('sales.paid', 1);

        $count3 = $fix3Query->count();

        if ($count3 > 0) {
            $this->warn("Fix #3: {$count3} reserved items belong to fully paid sales");

            if ($dryRun) {
                $rows = $fix3Query->select('items.id', 'items.model', 'items.imei', 'items.status', 'items.sale_id', 'items.storage_id', 'items.position')->get();
                $this->displayRows($rows, ['id', 'model', 'imei', 'status', 'sale_id', 'storage_id', 'position']);
                foreach ($rows as $r) {
                    $affected[$r->id]['fixes'][] = '#3 paid→sold + libera';
                    $affected[$r->id]['model'] = $r->model;
                    $affected[$r->id]['imei'] = $r->imei ?? '';
                }
            } else {
                // Fetch items and transition via model save so the boot hook handles everything
                $itemIds = $fix3Query->pluck('items.id');
                $items = Item::whereIn('id', $itemIds)->get();
                foreach ($items as $itemModel) {
                    $sale = \App\Models\Sale::find($itemModel->sale_id);
                    $itemModel->sold = $sale?->date ?? $sale?->created_at ?? now();
                    $itemModel->save(); // boot hook: status → sold, position released
                }
                $this->info("   → Transitioned {$count3} items to sold (position released).");
            }
        } else {
            $this->line('Fix #3: ✓ No reserved items in paid sales.');
        }

        // ──────────────────────────────────────────────────────────
        // Fix #4: Items in a sale (sale_id set) but status='available'
        // ──────────────────────────────────────────────────────────
        $fix4Query = DB::table('items')
            ->whereNotNull('sale_id')
            ->where('status', 'available')
            ->whereNull('sold');

        $count4 = $fix4Query->count();

        if ($count4 > 0) {
            $this->warn("Fix #4: {$count4} items have sale_id but status='available'");

            if ($dryRun) {
                $rows = $fix4Query->select('id', 'model', 'imei', 'status', 'sale_id', 'storage_id', 'position')->get();
                $this->displayRows($rows, ['id', 'model', 'imei', 'status', 'sale_id', 'storage_id', 'position']);
                foreach ($rows as $r) {
                    $affected[$r->id]['fixes'][] = '#4 →reserved';
                    $affected[$r->id]['model'] = $r->model;
                    $affected[$r->id]['imei'] = $r->imei ?? '';
                }
            } else {
                $fix4Query->update(['status' => 'reserved']);
                $this->info("   → Set status='reserved' on {$count4} items.");
            }
        } else {
            $this->line('Fix #4: ✓ No items with sale_id and wrong status.');
        }

        // ──────────────────────────────────────────────────────────
        // Fix #5: Items with status='reserved' but no sale_id
        //         (orphaned reserved — should be 'available')
        // ──────────────────────────────────────────────────────────
        $fix5Query = DB::table('items')
            ->where('status', 'reserved')
            ->whereNull('sale_id')
            ->whereNull('sold');

        $count5 = $fix5Query->count();

        if ($count5 > 0) {
            $this->warn("Fix #5: {$count5} items have status='reserved' but no sale_id (orphaned)");

            if ($dryRun) {
                $rows = $fix5Query->select('id', 'model', 'imei', 'status', 'sale_id', 'storage_id', 'position')->get();
                $this->displayRows($rows, ['id', 'model', 'imei', 'status', 'sale_id', 'storage_id', 'position']);
                foreach ($rows as $r) {
                    $affected[$r->id]['fixes'][] = '#5 →available';
                    $affected[$r->id]['model'] = $r->model;
                    $affected[$r->id]['imei'] = $r->imei ?? '';
                }
            } else {
                $fix5Query->update(['status' => 'available']);
                $this->info("   → Reset status='available' on {$count5} items.");
            }
        } else {
            $this->line('Fix #5: ✓ No orphaned reserved items.');
        }

        // ──────────────────────────────────────────────────────────
        // Summary
        // ──────────────────────────────────────────────────────────
        $total = $count1 + $count2 + $count3 + $count4 + $count5;

        $this->newLine();
        $this->line(str_repeat('─', 60));

        if ($dryRun && count($affected) > 0) {
            $this->newLine();
            $this->info('📋 RESUMEN DE ITEMS AFECTADOS');
            $this->newLine();

            // Consolidated table of all unique affected items (scrollable, first)
            $summaryRows = [];
            foreach ($affected as $id => $data) {
                $summaryRows[] = [
                    (string) $id,
                    $data['model'],
                    $data['imei'] ?: '—',
                    implode(' + ', $data['fixes']),
                ];
            }

            $this->table(
                ['ID', 'Model', 'IMEI', 'Fixes aplicados'],
                $summaryRows
            );

            $this->newLine();

            // Count summary table (at the very end, always visible)
            $this->table(
                ['Tipo de cambio', 'Cantidad'],
                [
                    ['🏷️  Status → sold (sold date set)',    (string) $count1],
                    ['📍  Liberar posición',                  (string) $count2],
                    ['🏷️  Status → sold (sale paid)',        (string) $count3],
                    ['🏷️  Status → reserved',                (string) $count4],
                    ['🏷️  Status → available',               (string) $count5],
                    ['─',                                     '─'],
                    ['Items únicos afectados',                (string) count($affected)],
                    ['Total cambios a aplicar',               (string) $total],
                ]
            );

            $this->newLine();
            $this->comment("DRY RUN — {$total} cambios en " . count($affected) . " items únicos. Corré sin --dry-run para aplicar.");
        } elseif ($dryRun) {
            $this->info('✅ Ningún item requiere arreglo. Todo consistente.');
        } elseif ($total === 0) {
            $this->info('✅ All items are consistent. Nothing to fix.');
        } else {
            $this->info("✅ Done. {$total} items fixed across 5 categories.");
        }

        $this->line(str_repeat('─', 60));

        return self::SUCCESS;
    }

    /**
     * Display DB rows as a table, converting all values to strings.
     */
    private function displayRows($rows, array $columns): void
    {
        $formatted = [];
        foreach ($rows as $row) {
            $line = [];
            foreach ($columns as $col) {
                $val = $row->$col ?? null;
                if ($val instanceof \DateTimeInterface) {
                    $val = $val->format('Y-m-d H:i:s');
                } elseif (is_object($val) && method_exists($val, '__toString')) {
                    $val = (string) $val;
                } elseif (is_array($val)) {
                    $val = json_encode($val);
                } elseif (! is_scalar($val) && ! is_null($val)) {
                    $val = var_export($val, true);
                }
                $line[] = $val ?? 'NULL';
            }
            $formatted[] = $line;
        }

        $this->table($columns, $formatted);
    }

    /**
     * Display DB rows as a table, converting all values to strings.
     */
    private function twoColumnDetail(string $label, string $value): void
    {
        $this->line("  <fg=gray>{$label}</>" . str_repeat('.', max(1, 50 - mb_strlen($label))) . " <fg=bright-white>{$value}</>");
    }
}
