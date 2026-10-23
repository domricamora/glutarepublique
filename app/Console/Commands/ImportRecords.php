<?php

namespace App\Console\Commands;

use App\Actions\Inventory\ManageStock;
use App\Models\Branch;
use App\Models\Lead;
use App\Models\Product;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * Go-live migration of the clinic's existing records (contract Annex A.10).
 *
 *   php artisan clinic:import clients clients.csv
 *     columns: first_name, last_name, email, phone, branch, notes, marketing_consent
 *   php artisan clinic:import stock stock.csv
 *     columns: sku (or product), branch, quantity, lot, expires_on (Y-m-d), cost
 *
 * Clients become CRM contacts at the "customer" stage; no clinical data is
 * imported onto them (plan.md §15). Stock comes in as dated opening lots, so
 * first-expiry-first-out works from day one. Branch is the slug or the name.
 * All or nothing: one bad line and nothing is saved. --dry-run only checks.
 */
class ImportRecords extends Command
{
    protected $signature = 'clinic:import
        {type : clients or stock}
        {file : Path to a CSV with a header row}
        {--user= : Email of the staff member the stock ledger is written under (default: the first owner)}
        {--dry-run : Validate every row and report, without saving anything}';

    protected $description = 'Import existing clients or opening stock from a CSV file';

    private ?User $user = null;

    public function handle(ManageStock $stock): int
    {
        $rows = $this->rows($this->argument('file'));
        if ($rows === null) {
            return self::FAILURE;
        }

        $branches = Branch::get()->flatMap(fn (Branch $b) => [mb_strtolower($b->slug) => $b, mb_strtolower($b->name) => $b]);
        $import = match ($this->argument('type')) {
            'clients' => fn (array $r) => $this->client($r, $branches),
            'stock' => fn (array $r) => $this->stock($r, $branches, $stock),
            default => null,
        };
        if ($import === null) {
            $this->error('Type must be clients or stock.');

            return self::FAILURE;
        }

        $counts = ['imported' => 0, 'skipped' => 0, 'failed' => 0];
        $problems = [];

        DB::beginTransaction();
        foreach ($rows as $line => $row) {
            try {
                $result = $import($row);
                $counts[$result === true ? 'imported' : 'skipped']++;
                if (is_string($result)) {
                    $problems[] = [$line, $result];
                }
            } catch (Throwable $e) {
                $counts['failed']++;
                $problems[] = [$line, $e->getMessage()];
            }
        }

        $save = ! $this->option('dry-run') && $counts['failed'] === 0;
        $save ? DB::commit() : DB::rollBack();

        if ($problems) {
            $this->table(['Line', 'Problem'], $problems);
        }
        $this->table(['Rows read', 'Imported', 'Skipped', 'Failed'], [[count($rows), $counts['imported'], $counts['skipped'], $counts['failed']]]);

        if ($counts['failed'] > 0) {
            $this->error('Nothing was saved: fix the failed lines and run it again.');

            return self::FAILURE;
        }
        $this->info($save ? 'Saved.' : 'Dry run: nothing was saved.');

        return self::SUCCESS;
    }

    /** @return array<int, array<string, string>>|null rows keyed by their line number in the file */
    private function rows(string $file): ?array
    {
        if (! is_readable($file) || ($handle = fopen($file, 'r')) === false) {
            $this->error("Cannot read $file");

            return null;
        }

        $header = array_map(fn ($h) => mb_strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $h))), fgetcsv($handle) ?: []);
        $rows = [];
        for ($line = 2; ($cells = fgetcsv($handle)) !== false; $line++) {
            if ($cells === [null]) {
                continue; // blank line
            }
            $cells = array_slice(array_pad(array_map(fn ($c) => trim((string) $c), $cells), count($header), ''), 0, count($header));
            $rows[$line] = array_combine($header, $cells);
        }
        fclose($handle);

        return $rows;
    }

    /**
     * @param  array<string, string>  $r
     * @param  Collection<string, Branch>  $branches
     */
    private function client(array $r, Collection $branches): bool|string
    {
        $first = $r['first_name'] ?? '';
        if ($first === '') {
            throw new RuntimeException('first_name is empty');
        }
        $email = filled($r['email'] ?? null) ? mb_strtolower($r['email']) : null;
        $phone = filled($r['phone'] ?? null) ? $r['phone'] : null;

        if (($email || $phone) && Lead::where(fn ($q) => $q->when($email, fn ($q) => $q->orWhere('email', $email))->when($phone, fn ($q) => $q->orWhere('phone', $phone)))->exists()) {
            return "Already on file, skipped: $first ".($email ?? $phone);
        }

        Lead::create([
            'first_name' => $first,
            'last_name' => ($r['last_name'] ?? '') ?: null,
            'email' => $email,
            'phone' => $phone,
            'source' => 'walk_in',
            'stage' => 'customer',
            'form' => 'import',
            'branch_id' => $branches->get(mb_strtolower($r['branch'] ?? ''))?->id,
            'message' => ($r['notes'] ?? '') ?: null,
            'marketing_consent' => in_array(mb_strtolower($r['marketing_consent'] ?? ''), ['1', 'yes', 'y', 'true'], true),
        ]);

        return true;
    }

    /**
     * @param  array<string, string>  $r
     * @param  Collection<string, Branch>  $branches
     */
    private function stock(array $r, Collection $branches, ManageStock $stock): bool
    {
        $product = filled($r['sku'] ?? null)
            ? Product::where('sku', $r['sku'])->first()
            : Product::where('name', $r['product'] ?? '')->first();
        $branch = $branches->get(mb_strtolower($r['branch'] ?? ''));
        $quantity = (int) ($r['quantity'] ?? 0);

        if (! $product) {
            throw new RuntimeException('No product "'.(($r['sku'] ?? '') ?: ($r['product'] ?? '')).'". Add it in Catalogue first.');
        }
        if (! $branch) {
            throw new RuntimeException('Unknown branch "'.($r['branch'] ?? '').'"');
        }
        if ($quantity < 1) {
            throw new RuntimeException('quantity must be 1 or more');
        }

        $stock->receive($product, $branch, [
            'quantity' => $quantity,
            'lot_number' => ($r['lot'] ?? '') ?: null,
            'expires_on' => filled($r['expires_on'] ?? null) ? Carbon::createFromFormat('Y-m-d', $r['expires_on'])->toDateString() : null,
            'cost' => filled($r['cost'] ?? null) ? (float) $r['cost'] : null,
            'note' => 'Opening stock imported at go-live',
        ], $this->user(), 'opening');

        return true;
    }

    private function user(): User
    {
        return $this->user ??= $this->option('user')
            ? User::where('email', $this->option('user'))->firstOrFail()
            : User::role('Organization Owner')->orderBy('id')->firstOrFail();
    }
}
