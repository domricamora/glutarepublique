<?php

namespace App\Console\Commands;

use App\Models\InventoryMovement;
use App\Models\ProductBatch;
use App\Models\ProductStock;
use App\Models\StockDiscrepancy;
use Illuminate\Console\Command;

/**
 * The nightly stock check. Three records say how much of a product a branch
 * holds: the shelf count, the lots, and the stock ledger (every movement
 * summed). Every stock change writes all three, so they should agree; when
 * they do not, something changed stock outside the system (an import, a hand
 * edit) or a change failed halfway. Disagreements are flagged on the
 * inventory page for someone to count, never corrected here: picking which
 * record is right is a person's call.
 */
class ReconcileStock extends Command
{
    protected $signature = 'stock:reconcile';

    protected $description = 'Check that shelf counts, lots and the stock ledger agree, and flag where they do not';

    public function handle(): int
    {
        $sum = fn ($query) => $query->withoutGlobalScopes()
            ->selectRaw('product_id, branch_id, SUM(quantity) AS total')
            ->groupBy('product_id', 'branch_id')
            ->get()
            ->mapWithKeys(fn ($row) => ["{$row->product_id}:{$row->branch_id}" => (int) $row->total]);

        $lots = $sum(ProductBatch::query());
        $ledger = $sum(InventoryMovement::query());
        $now = now();
        $found = 0;

        foreach (ProductStock::withoutGlobalScopes()->get() as $stock) {
            $key = "{$stock->product_id}:{$stock->branch_id}";
            $figures = ['on_hand' => $stock->on_hand, 'lots' => $lots[$key] ?? 0, 'ledger' => $ledger[$key] ?? 0];
            $open = StockDiscrepancy::withoutGlobalScopes()
                ->where('product_id', $stock->product_id)
                ->where('branch_id', $stock->branch_id)
                ->whereNull('resolved_at')
                ->first();

            if (count(array_unique($figures)) === 1) {
                $open?->update(['checked_at' => $now, 'resolved_at' => $now]);

                continue;
            }

            $found++;
            $open
                ? $open->update([...$figures, 'checked_at' => $now])
                : StockDiscrepancy::create([
                    ...$figures,
                    'organization_id' => $stock->organization_id,
                    'product_id' => $stock->product_id,
                    'branch_id' => $stock->branch_id,
                    'found_at' => $now,
                    'checked_at' => $now,
                ]);
        }

        $this->info($found === 0 ? 'Stock records agree everywhere.' : "{$found} product(s) need a count.");

        return self::SUCCESS;
    }
}
