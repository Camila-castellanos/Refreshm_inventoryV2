<?php

namespace App\Console\Commands;

use App\Models\Item;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixSoldDateFromPayments extends Command
{
    protected $signature = 'items:fix-sold-date-from-payments
                            {--dry-run : Show what would be fixed without making changes}';

    protected $description = 'Fix items whose sold date was overwritten by payment date';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $lastPaymentSub = DB::table('payments')
            ->select('sale_id', DB::raw('MAX(payment_date) as last_payment_date'))
            ->groupBy('sale_id');

        $query = DB::table('items')
            ->join('sales', 'sales.id', '=', 'items.sale_id')
            ->joinSub($lastPaymentSub, 'p', function ($join) {
                $join->on('p.sale_id', '=', 'sales.id');
            })
            ->whereNotNull('items.sold')
            ->where('sales.paid', 1)
            ->whereRaw('DATE(items.sold) = DATE(p.last_payment_date)')
            ->where(function ($q) {
                $q->whereNotNull('items.partially_sold_at')
                    ->orWhereNotNull('sales.date')
                    ->orWhereNotNull('sales.created_at');
            })
            ->select(
                'items.id',
                'items.model',
                'items.imei',
                'items.sale_id',
                'items.sold',
                'items.partially_sold_at',
                'sales.date as sale_date',
                'sales.created_at as sale_created_at',
                'p.last_payment_date'
            );

        $count = $query->count();

        if ($count === 0) {
            $this->line('✓ No items found with sold date matching payment date.');
            return Command::SUCCESS;
        }

        $this->warn("Found {$count} items with sold date matching payment date.");

        $rows = $query->get();
        $updates = 0;
        $saleUpdates = 0;

        if ($dryRun) {
            $table = [];
            $affected = 0;
            $salesToUpdate = [];
            foreach ($rows as $row) {
                $target = $row->partially_sold_at
                    ?? $row->sale_date
                    ?? $row->sale_created_at;

                if (! $target) {
                    continue;
                }

                $currentDate = Carbon::parse($row->sold)->format('Y-m-d');
                $targetDate = Carbon::parse($target)->format('Y-m-d');

                if ($currentDate !== $targetDate) {
                    $affected++;
                }

                $saleCurrentDate = $row->sale_date ? Carbon::parse($row->sale_date)->format('Y-m-d') : null;
                $lastPaymentDate = $row->last_payment_date ? Carbon::parse($row->last_payment_date)->format('Y-m-d') : null;
                if ($saleCurrentDate && $lastPaymentDate
                    && $saleCurrentDate === $lastPaymentDate
                    && $saleCurrentDate !== $targetDate) {
                    $salesToUpdate[$row->sale_id] = true;
                }

                $table[] = [
                    $row->id,
                    $row->model,
                    $row->imei ?: '—',
                    $currentDate,
                    $targetDate,
                    Carbon::parse($row->last_payment_date)->format('Y-m-d'),
                ];
            }

            $this->table(
                ['ID', 'Model', 'IMEI', 'Sold (current)', 'Sold (target)', 'Last Payment'],
                $table
            );

            $this->newLine();
            $this->line(str_repeat('─', 60));
            $this->info("Total items afectados (cambios reales): {$affected}");
            $this->info('Total ventas a actualizar (sale.date): '.count($salesToUpdate));

            return Command::SUCCESS;
        }

        $itemIds = $rows->pluck('id');
        $items = Item::whereIn('id', $itemIds)->get();

        $salesToUpdate = [];

        foreach ($items as $item) {
            $sale = Sale::find($item->sale_id);
            $target = $item->partially_sold_at
                ?? $sale?->date
                ?? $sale?->created_at;

            if (! $target) {
                continue;
            }

            $currentDate = Carbon::parse($item->sold)->format('Y-m-d');
            $targetDate = Carbon::parse($target)->format('Y-m-d');

            if ($currentDate !== $targetDate) {
                $item->sold = $target;
                $item->save();
                $updates++;
            }

            if ($sale) {
                $saleCurrentDate = $sale->date ? Carbon::parse($sale->date)->format('Y-m-d') : null;
                $lastPaymentDate = DB::table('payments')
                    ->where('sale_id', $sale->id)
                    ->max('payment_date');

                if ($lastPaymentDate) {
                    $lastPaymentDate = Carbon::parse($lastPaymentDate)->format('Y-m-d');
                }

                if ($saleCurrentDate && $lastPaymentDate
                    && $saleCurrentDate === $lastPaymentDate
                    && $saleCurrentDate !== $targetDate) {
                    $salesToUpdate[$sale->id] = $target;
                }
            }
        }

        foreach ($salesToUpdate as $saleId => $targetDate) {
            Sale::where('id', $saleId)->update(['date' => $targetDate]);
            $saleUpdates++;
        }

        $this->info("Updated {$updates} items with corrected sold dates.");
        $this->info("Updated {$saleUpdates} sales with corrected sale dates.");

        $this->newLine();
        $this->line(str_repeat('─', 60));
        $this->info("Total items afectados (cambios reales): {$updates}");
        $this->info("Total ventas afectadas (sale.date): {$saleUpdates}");

        return Command::SUCCESS;
    }
}
