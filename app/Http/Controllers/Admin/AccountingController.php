<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Accounting\PostEntry;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\Sale;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The books (plan.md §24): a chart of accounts, the journal, and the three
 * statements that fall out of it. Entries are never edited; a wrong one is
 * voided so the trail stays readable.
 */
class AccountingController extends Controller
{
    public function index(Request $request): Response
    {
        $to = CarbonImmutable::parse($request->date('to') ?? now()->toDateString());
        $from = CarbonImmutable::parse($request->date('from') ?? $to->startOfMonth()->toDateString());

        return Inertia::render('admin/accounting/index', [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'cash' => $this->balanceOf('1000', $from, $to),
            'receivable' => $this->balanceOf('1200', $from, $to),
            'payable' => $this->balanceOf('2000', $from, $to),
            'payroll_payable' => $this->balanceOf('2200', $from, $to),
            'revenue' => $this->income('revenue', $from, $to),
            'expenses' => $this->income('expense', $from, $to),
            'net' => round($this->income('revenue', $from, $to) - $this->income('expense', $from, $to), 2),
            'entries' => JournalEntry::with('author:id,name')->latest('entry_date')->latest('id')->limit(10)->get()
                ->map(fn (JournalEntry $e) => $this->entryPayload($e)),
            'periods' => AccountingPeriod::orderByDesc('year')->orderByDesc('month')->limit(6)->get()
                ->map(fn (AccountingPeriod $p) => [
                    'id' => $p->id,
                    'label' => date('F Y', mktime(0, 0, 0, $p->month, 1, $p->year)),
                    'status' => $p->status,
                ]),
            'unposted' => Sale::whereNotNull('note')->where('note', 'like', '%Not posted%')->count(),
            'sources' => JournalEntry::SOURCES,
        ]);
    }

    public function accounts(): Response
    {
        return Inertia::render('admin/accounting/accounts', [
            'accounts' => Account::withCount('lines')->orderBy('sort')->get()
                ->map(fn (Account $a) => [
                    'id' => $a->id,
                    'code' => $a->code,
                    'name' => $a->name,
                    'type' => $a->type,
                    'normal_balance' => $a->normal_balance,
                    'balance' => $a->balance(),
                    'is_system' => $a->is_system,
                    'is_active' => $a->is_active,
                ]),
            'types' => Account::TYPES,
        ]);
    }

    public function entries(Request $request): Response
    {
        return Inertia::render('admin/accounting/entries', [
            'entries' => JournalEntry::with('author:id,name')->withSum('lines', 'debit')
                ->latest('entry_date')->latest('id')->limit(60)->get()
                ->map(fn (JournalEntry $e) => $this->entryPayload($e)),
            'accounts' => Account::postable()->orderBy('sort')->get(['id', 'code', 'name', 'type']),
            'sources' => JournalEntry::SOURCES,
        ]);
    }

    public function store(Request $request, PostEntry $post): RedirectResponse
    {
        $data = $request->validate([
            'entry_date' => ['required', 'date', 'before_or_equal:today'],
            'memo' => ['required', 'string', 'max:200'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.account' => ['required', 'string'],
            'lines.*.debit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.credit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.memo' => ['nullable', 'string', 'max:200'],
        ]);

        $post($data['lines'], $data + ['source_type' => 'manual'], $request->user());

        return back()->with('success', 'Journal entry posted.');
    }

    /** A wrong entry is voided, never edited: the trail has to stay readable. */
    public function void(Request $request, JournalEntry $entry): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:200']]);

        if ($entry->isVoid()) {
            return back()->with('error', 'That entry is already void.');
        }

        $entry->update(['status' => 'void', 'voided_at' => now(), 'void_reason' => $data['reason']]);

        return back()->with('success', $entry->entry_no.' voided.');
    }

    /** Trial balance, income statement and balance sheet from the same journal. */
    public function reports(Request $request): Response
    {
        return Inertia::render('admin/accounting/reports', $this->reportData($request));
    }

    /** The same three statements as the screen, as one CSV an accountant can open in a spreadsheet. */
    public function reportsExport(Request $request): StreamedResponse
    {
        $r = $this->reportData($request);

        return response()->streamDownload(function () use ($r) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Trial balance', 'Year to '.$r['to']]);
            fputcsv($out, ['Code', 'Account', 'Debit', 'Credit']);
            foreach ($r['trial_balance'] as $line) {
                fputcsv($out, [$line['code'], $line['name'], $line['debit'], $line['credit']]);
            }
            fputcsv($out, ['', 'Total', $r['trial_totals']['debit'], $r['trial_totals']['credit']]);
            fputcsv($out, []);
            fputcsv($out, ['Income statement', $r['from'].' to '.$r['to'], 'Compared with '.$r['compare']['label'].' ('.$r['compare']['from'].' to '.$r['compare']['to'].')']);
            fputcsv($out, ['Code', 'Account', 'Type', 'This period', 'Compared period', 'Change']);
            foreach (['revenue' => 'Revenue', 'expenses' => 'Expenses'] as $key => $label) {
                foreach ($r['income_statement'][$key] as $b) {
                    fputcsv($out, [$b['code'], $b['name'], $label, $b['period'], $b['previous'], round($b['period'] - $b['previous'], 2)]);
                }
            }
            fputcsv($out, ['', 'Net income', '', $r['income_statement']['net'], $r['income_statement']['previous_net'], round($r['income_statement']['net'] - $r['income_statement']['previous_net'], 2)]);
            fputcsv($out, []);
            fputcsv($out, ['Balance sheet', 'As at '.$r['to']]);
            foreach (['assets' => 'Asset', 'liabilities' => 'Liability', 'equity' => 'Equity'] as $key => $label) {
                foreach ($r['balance_sheet'][$key] as $b) {
                    fputcsv($out, [$b['code'], $b['name'], $label, $b['year']]);
                }
            }
            fputcsv($out, ['', 'Current year earnings', 'Equity', $r['balance_sheet']['retained']]);
            fclose($out);
        }, 'financial-reports-'.($r['preset'] ? "{$r['preset']}-" : '')."{$r['from']}-to-{$r['to']}.csv", ['Content-Type' => 'text/csv']);
    }

    /**
     * The report window and the one it is compared with. A preset (month,
     * quarter, year) covers the whole period around `to` and is set against
     * the period before it; a custom range is set against the same dates a
     * year earlier.
     *
     * @return array{0: string|null, 1: CarbonImmutable, 2: CarbonImmutable, 3: CarbonImmutable, 4: CarbonImmutable, 5: string}
     */
    private function window(Request $request): array
    {
        $preset = in_array($request->query('preset'), ['month', 'quarter', 'year'], true) ? (string) $request->query('preset') : null;
        $at = CarbonImmutable::parse($request->date('to') ?? now()->toDateString());

        return match ($preset) {
            'month' => [$preset, $at->startOfMonth(), $at->endOfMonth()->startOfDay(), $at->startOfMonth()->subMonthNoOverflow(), $at->startOfMonth()->subDay(), 'previous month'],
            'quarter' => [$preset, $at->startOfQuarter(), $at->endOfQuarter()->startOfDay(), $at->startOfQuarter()->subMonthsNoOverflow(3), $at->startOfQuarter()->subDay(), 'previous quarter'],
            'year' => [$preset, $at->startOfYear(), $at->endOfYear()->startOfDay(), $at->startOfYear()->subYear(), $at->startOfYear()->subDay(), 'previous year'],
            default => (function () use ($request, $at) {
                $from = CarbonImmutable::parse($request->date('from') ?? $at->startOfYear()->toDateString());

                return [null, $from, $at, $from->subYearNoOverflow(), $at->subYearNoOverflow(), 'same dates last year'];
            })(),
        };
    }

    /** @return array<string, mixed> */
    private function reportData(Request $request): array
    {
        [$preset, $from, $to, $previousFrom, $previousTo, $compareLabel] = $this->window($request);
        $since = CarbonImmutable::createFromDate($to->year, 1, 1);

        $balances = Account::orderBy('sort')->get()
            ->map(fn (Account $a) => [
                'code' => $a->code,
                'name' => $a->name,
                'type' => $a->type,
                'normal_balance' => $a->normal_balance,
                'period' => $this->signedBetween($a, $from, $to),
                'previous' => in_array($a->type, ['revenue', 'expense'], true) ? $this->signedBetween($a, $previousFrom, $previousTo) : 0.0,
                'year' => $this->signedBetween($a, $since, $to),
            ]);

        // The income statement covers the window; the balance sheet stands at its end.
        $withMoney = fn (string $type) => $balances->filter(fn ($b) => $b['type'] === $type && (abs($b['period']) > 0.005 || abs($b['previous']) > 0.005));
        $sum = fn (string $type, string $column) => round((float) $balances->where('type', $type)->sum($column), 2);
        $revenue = $sum('revenue', 'year');
        $expenses = $sum('expense', 'year');

        $assets = round((float) $balances->where('type', 'asset')->sum('year'), 2);
        $liabilities = round((float) $balances->where('type', 'liability')->sum('year'), 2);
        $equity = round((float) $balances->where('type', 'equity')->sum('year'), 2);

        $trial = $this->trialLines($since, $to);

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'preset' => $preset,
            'compare' => [
                'label' => $compareLabel,
                'from' => $previousFrom->toDateString(),
                'to' => $previousTo->toDateString(),
            ],
            'trial_balance' => $trial,
            'trial_totals' => [
                'debit' => round((float) $trial->sum('debit'), 2),
                'credit' => round((float) $trial->sum('credit'), 2),
            ],
            'income_statement' => [
                'revenue' => $withMoney('revenue')->values(),
                'expenses' => $withMoney('expense')->values(),
                'total_revenue' => $sum('revenue', 'period'),
                'total_expenses' => $sum('expense', 'period'),
                'net' => round($sum('revenue', 'period') - $sum('expense', 'period'), 2),
                'previous_revenue' => $sum('revenue', 'previous'),
                'previous_expenses' => $sum('expense', 'previous'),
                'previous_net' => round($sum('revenue', 'previous') - $sum('expense', 'previous'), 2),
            ],
            'balance_sheet' => [
                'assets' => $balances->where('type', 'asset')->filter(fn ($b) => abs($b['year']) > 0.005)->values(),
                'liabilities' => $balances->where('type', 'liability')->filter(fn ($b) => abs($b['year']) > 0.005)->values(),
                'equity' => $balances->where('type', 'equity')->filter(fn ($b) => abs($b['year']) > 0.005)->values(),
                'total_assets' => $assets,
                'total_liabilities' => $liabilities,
                'total_equity' => $equity,
                'retained' => round($revenue - $expenses, 2),
                'balances' => abs($assets - ($liabilities + $equity + ($revenue - $expenses))) < 0.05,
            ],
        ];
    }

    /** Closing a month is how a set of books stops moving under you. */
    public function togglePeriod(Request $request, AccountingPeriod $period): RedirectResponse
    {
        $closing = ! $period->isClosed();

        $period->update([
            'status' => $closing ? 'closed' : 'open',
            'closed_at' => $closing ? now() : null,
            'closed_by' => $closing ? $request->user()->id : null,
        ]);

        return back()->with('success', $closing ? 'Period closed.' : 'Period reopened.');
    }

    /** @return array<string, mixed> */
    private function entryPayload(JournalEntry $entry): array
    {
        return [
            'id' => $entry->id,
            'entry_no' => $entry->entry_no,
            'entry_date' => $entry->entry_date->toDateString(),
            'memo' => $entry->memo,
            'source_type' => $entry->source_type,
            'source_label' => $entry->sourceLabel(),
            'source_id' => $entry->source_id,
            'status' => $entry->status,
            'author' => $entry->author?->name,
            'debit' => $entry->lines->sum('debit') ?: (float) ($entry->lines_sum_debit ?? 0),
        ];
    }

    /**
     * The trial balance, as gross debits and credits per account.
     *
     * Deliberately not the normalised balance from signedBetween(). A trial
     * balance is the arithmetic proof that the book balances, so it has to sum
     * what was actually posted: every debit to the debit column, every credit to
     * the credit column. Normalising each account to its normal side first
     * would report revenue as a debit and total the two columns from different
     * numbers, which is how a balanced book prints as out by hundreds of pesos.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function trialLines(CarbonImmutable $since, CarbonImmutable $to)
    {
        $gross = JournalLine::query()
            ->whereHas('entry', fn ($q) => $q->posted()->between($since->toDateString(), $to->toDateString()))
            ->selectRaw('account_id, COALESCE(SUM(debit), 0) AS gross_debit, COALESCE(SUM(credit), 0) AS gross_credit')
            ->groupBy('account_id')
            ->get()
            ->keyBy('account_id');

        return Account::orderBy('sort')->get()
            ->map(function (Account $a) use ($gross, $since, $to) {
                $debit = round((float) ($gross[$a->id]->gross_debit ?? 0), 2);
                $credit = round((float) ($gross[$a->id]->gross_credit ?? 0), 2);

                return [
                    'code' => $a->code,
                    'name' => $a->name,
                    'type' => $a->type,
                    'normal_balance' => $a->normal_balance,
                    'debit' => $debit,
                    'credit' => $credit,
                    'period' => $this->signedBetween($a, $since, $to),
                    'year' => $this->signedBetween($a, $since, $to),
                ];
            })
            ->filter(fn (array $line) => $line['debit'] > 0.005 || $line['credit'] > 0.005)
            ->values();
    }

    /** An account balance on its normal side, over a window. */
    private function balanceOf(string $code, CarbonImmutable $from, CarbonImmutable $to): float
    {
        $account = Account::where('code', $code)->first();

        return $account ? $this->signedBetween($account, $from, $to) : 0.0;
    }

    private function income(string $type, CarbonImmutable $from, CarbonImmutable $to): float
    {
        $debit = (float) JournalLine::whereIn('account_id', Account::ofType($type)->pluck('id'))
            ->whereHas('entry', fn ($q) => $q->posted()->between($from->toDateString(), $to->toDateString()))
            ->sum('debit');
        $credit = (float) JournalLine::whereIn('account_id', Account::ofType($type)->pluck('id'))
            ->whereHas('entry', fn ($q) => $q->posted()->between($from->toDateString(), $to->toDateString()))
            ->sum('credit');

        // Revenue and expenses both grow on the credit side.
        return round($type === 'revenue' ? $credit - $debit : $debit - $credit, 2);
    }

    private function signedBetween(Account $account, CarbonImmutable $from, CarbonImmutable $to): float
    {
        $lines = JournalLine::where('account_id', $account->id)
            ->whereHas('entry', fn ($q) => $q->posted()->between($from->toDateString(), $to->toDateString()));

        $balance = round((float) $lines->sum('debit') - (float) $lines->sum('credit'), 2);

        // + 0.0 turns a negated zero into a plain one, so nothing prints as -0.00.
        return ($account->normal_balance === 'debit' ? $balance : round(-$balance, 2)) + 0.0;
    }
}
