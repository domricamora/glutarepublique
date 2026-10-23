<?php

namespace App\Actions\Dashboard;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\ChatConversation;
use App\Models\Lead;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Specialist;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * The numbers behind the dashboard's graphs.
 *
 * Three things worth being explicit about, because the alternative is a chart
 * that looks authoritative and is quietly wrong:
 *
 * 1. Revenue is net of refunds. A refund is money the clinic took back, and
 *    grossing it up would mean the graph disagreed with the till.
 *
 * 2. "Profit" here is gross profit -- revenue less the cost of the goods
 *    sold. It is not what is left after paying anyone. Calling it profit
 *    without saying so is how a clinic owner concludes a 90% margin means a
 *    good month, when payroll has not been subtracted. Treatments are labour
 *    and carry no cost of goods here, which flatters the margin; the UI says
 *    as much next to the number.
 *
 * 3. Days with nothing on them come back as zero rather than missing, so a
 *    gap in the data reads as a quiet day and not as a broken chart.
 */
class ClinicAnalytics
{
    public function __construct(private readonly int $days = 30) {}

    public function toArray(): array
    {
        $today = now()->startOfDay();
        $from = $today->copy()->subDays($this->days - 1);
        $previousFrom = $from->copy()->subDays($this->days);

        return [
            'window' => [
                'days' => $this->days,
                'from' => $from->toDateString(),
                'to' => $today->toDateString(),
            ],
            'money' => $this->money($from, $previousFrom),
            'daily' => $this->daily($from, $today),
            'bookings' => $this->bookingBreakdown($from, $today),
            'top_treatments' => $this->topTreatments($from, $today),
            'payment_mix' => $this->paymentMix($from, $today),
            'front_desk' => $this->frontDesk($from, $previousFrom),
            'patients' => [
                ...$this->patientSplit($from, $today->endOfDay()),
                'previous' => $this->patientSplit($previousFrom, $from),
            ],
            'utilisation' => [
                ...$this->utilisation($from, $today->endOfDay()),
                'previous' => $this->utilisation($previousFrom, $from),
            ],
            'branches' => $this->branches($from, $today),
            'categories' => $this->categories($from, $today),
            'practitioners' => $this->practitioners($from, $today),
            'funnel' => $this->funnel($from),
            'sources' => $this->sources($from),
            'monthly' => $this->monthly($today),
        ];
    }

    /**
     * Patients seen in the window: first-timers against people coming back.
     *
     * A patient is a lead with a completed visit. "Returning" means they had a
     * completed visit before the window opened, so a person seen twice inside
     * one window still counts once, as new.
     *
     * @return array{new: int, returning: int, retention: float|null}
     */
    private function patientSplit(CarbonImmutable $start, CarbonImmutable $end): array
    {
        $seen = Appointment::where('status', 'completed')
            ->whereBetween('starts_at', [$start, $end])
            ->whereNotNull('lead_id')
            ->distinct()
            ->pluck('lead_id');

        $returning = $seen->isEmpty() ? 0 : Appointment::where('status', 'completed')
            ->where('starts_at', '<', $start)
            ->whereIn('lead_id', $seen)
            ->distinct()
            ->count('lead_id');

        $total = $seen->count();

        return [
            'new' => $total - $returning,
            'returning' => $returning,
            // Null, not zero, when nobody was seen: see conversion below.
            'retention' => $total > 0 ? round($returning / $total, 4) : null,
        ];
    }

    /**
     * How full the diary was: minutes booked against minutes on offer.
     *
     * Capacity is each active practitioner working the clinic's opening hours
     * on each day of the window. Booked time counts no-shows, because the
     * chair was held for them; cancelled and rescheduled visits gave the time
     * back, so they do not count.
     *
     * @return array{rate: float|null, booked_hours: float, capacity_hours: float, no_show_rate: float|null}
     */
    private function utilisation(CarbonImmutable $start, CarbonImmutable $end): array
    {
        $practitioners = Specialist::where('is_active', true)->count();
        $open = self::openMinutes();
        $capacity = 0;

        for ($day = $start->startOfDay(); $day->lt($end); $day = $day->addDay()) {
            $capacity += ($open[$day->dayOfWeekIso] ?? 0) * $practitioners;
        }

        $visits = Appointment::whereBetween('starts_at', [$start, $end])
            ->whereNotIn('status', ['cancelled', 'rescheduled'])
            ->get(['starts_at', 'ends_at', 'status']);

        $booked = $visits->sum(fn (Appointment $a) => max(0, $a->starts_at->diffInMinutes($a->ends_at)));
        $settled = $visits->whereIn('status', ['completed', 'no_show'])->count();

        return [
            'rate' => $capacity > 0 ? round(min(1, $booked / $capacity), 4) : null,
            'booked_hours' => round($booked / 60, 1),
            'capacity_hours' => round($capacity / 60, 1),
            'no_show_rate' => $settled > 0 ? round($visits->where('status', 'no_show')->count() / $settled, 4) : null,
        ];
    }

    /** Opening minutes per ISO weekday (1 = Monday), read from config('clinic.hours'). */
    private static function openMinutes(): array
    {
        $days = ['mon' => 1, 'tue' => 2, 'wed' => 3, 'thu' => 4, 'fri' => 5, 'sat' => 6, 'sun' => 7];
        $minutes = [];

        foreach (config('clinic.hours', []) as $span => $hours) {
            if (! preg_match('/^(\d\d):(\d\d)-(\d\d):(\d\d)$/', (string) $hours, $m)) {
                continue;
            }

            $length = ($m[3] * 60 + $m[4]) - ($m[1] * 60 + $m[2]);
            [$first, $last] = array_pad(explode('-', $span), 2, null);
            $last ??= $first;

            for ($d = $days[$first] ?? 0; $d >= 1 && $d <= ($days[$last] ?? 0); $d++) {
                $minutes[$d] = $length;
            }
        }

        return $minutes;
    }

    /** Takings and visits per branch, every active branch present. */
    private function branches(CarbonImmutable $from, CarbonImmutable $today): array
    {
        $end = $today->endOfDay();
        $revenue = Sale::whereBetween('created_at', [$from, $end])
            ->selectRaw('branch_id, COALESCE(SUM(total), 0) AS revenue')
            ->groupBy('branch_id')
            ->pluck('revenue', 'branch_id');
        $visits = Appointment::where('status', 'completed')
            ->whereBetween('starts_at', [$from, $end])
            ->selectRaw('branch_id, COUNT(*) AS visits')
            ->groupBy('branch_id')
            ->pluck('visits', 'branch_id');

        return Branch::where('is_active', true)->orderBy('id')->get(['id', 'name'])
            ->map(fn (Branch $b) => [
                'name' => $b->name,
                'revenue' => round((float) ($revenue[$b->id] ?? 0), 2),
                'visits' => (int) ($visits[$b->id] ?? 0),
            ])
            ->all();
    }

    /**
     * What the money was spent on: treatment categories, with retail as one
     * more row. A treatment line whose treatment has since been deleted is
     * kept, under "Other treatments", so the rows still add up to revenue.
     */
    private function categories(CarbonImmutable $from, CarbonImmutable $today): array
    {
        $rows = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->leftJoin('treatments', 'treatments.id', '=', 'sale_items.treatment_id')
            ->leftJoin('treatment_categories', 'treatment_categories.id', '=', 'treatments.treatment_category_id')
            ->whereBetween('sales.created_at', [$from, $today->endOfDay()])
            ->where('sales.organization_id', Organization::current()?->id)
            ->selectRaw("CASE WHEN sale_items.kind = 'product' THEN 'Retail products' ELSE COALESCE(treatment_categories.name, 'Other treatments') END AS name")
            ->selectRaw('SUM(sale_items.line_total) AS revenue')
            ->groupBy('name')
            ->orderByDesc('revenue')
            ->get();

        return $rows->map(fn ($r) => ['name' => $r->name, 'revenue' => round((float) $r->revenue, 2)])->all();
    }

    /** The busiest practitioners by completed visits, with the list value of what they did. */
    private function practitioners(CarbonImmutable $from, CarbonImmutable $today): array
    {
        return Appointment::query()
            ->join('specialists', 'specialists.id', '=', 'appointments.specialist_id')
            ->leftJoin('treatments', 'treatments.id', '=', 'appointments.treatment_id')
            ->where('appointments.status', 'completed')
            ->whereBetween('appointments.starts_at', [$from, $today->endOfDay()])
            ->selectRaw('specialists.name, specialists.title, COUNT(*) AS visits')
            ->selectRaw('COALESCE(SUM(COALESCE(treatments.promo_price, treatments.price)), 0) AS value')
            ->groupBy('specialists.id', 'specialists.name', 'specialists.title')
            ->orderByDesc('visits')
            ->limit(5)
            ->get()
            ->map(fn ($r) => [
                'name' => $r->name,
                'title' => $r->title,
                'visits' => (int) $r->visits,
                'value' => round((float) $r->value, 2),
            ])
            ->all();
    }

    /** Enquiries from the window by the stage they have reached, in pipeline order. */
    private function funnel(CarbonImmutable $from): array
    {
        $counts = Lead::where('created_at', '>=', $from)
            ->selectRaw('stage, COUNT(*) AS total')
            ->groupBy('stage')
            ->pluck('total', 'stage');

        return collect(Lead::STAGES)
            ->map(fn ($label, $stage) => ['stage' => $stage, 'label' => $label, 'count' => (int) ($counts[$stage] ?? 0)])
            ->values()
            ->all();
    }

    /** Where enquiries came from, and how many of each became customers. */
    private function sources(CarbonImmutable $from): array
    {
        return Lead::where('created_at', '>=', $from)
            ->selectRaw('source, COUNT(*) AS total')
            ->selectRaw("SUM(CASE WHEN stage IN ('customer', 'repeat_customer', 'vip') THEN 1 ELSE 0 END) AS won")
            ->groupBy('source')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r) => ['source' => $r->source, 'leads' => (int) $r->total, 'won' => (int) $r->won])
            ->all();
    }

    /**
     * The last twelve calendar months, this one included: net takings and
     * completed visits. Independent of the window, so the long view is always
     * on the page whatever range is picked above it.
     */
    private function monthly(CarbonImmutable $today): array
    {
        $start = $today->startOfMonth()->subMonths(11);
        $months = [];

        for ($m = $start; $m->lte($today); $m = $m->addMonth()) {
            $months[$m->format('Y-m')] = ['month' => $m->format('Y-m'), 'revenue' => 0.0, 'visits' => 0];
        }

        foreach (Sale::where('created_at', '>=', $start)->get(['created_at', 'total']) as $sale) {
            $key = $sale->created_at->format('Y-m');
            if (isset($months[$key])) {
                $months[$key]['revenue'] += (float) $sale->total;
            }
        }

        foreach (Payment::where('type', 'refund')->where('paid_at', '>=', $start)->get(['paid_at', 'amount']) as $refund) {
            $key = $refund->paid_at->format('Y-m');
            if (isset($months[$key])) {
                $months[$key]['revenue'] -= abs((float) $refund->amount);
            }
        }

        foreach (Appointment::where('status', 'completed')->where('starts_at', '>=', $start)->pluck('starts_at') as $at) {
            $key = $at->format('Y-m');
            if (isset($months[$key])) {
                $months[$key]['visits']++;
            }
        }

        return array_values(array_map(fn ($m) => [...$m, 'revenue' => round($m['revenue'], 2)], $months));
    }

    /**
     * Headline figures, each against the equivalent stretch before it.
     *
     * A single number says "42,000 this month" and leaves the reader to guess
     * whether that is good. Set against the previous period it says something
     * they can act on, which is the only reason to put a number here at all.
     */
    private function money(CarbonImmutable $from, CarbonImmutable $previousFrom): array
    {
        [$revenue, $refunds, $discount] = $this->revenueTotals($from);
        [$cost] = $this->costOfGoods($from);
        [$priorRevenue, $priorRefunds] = $this->revenueTotals($previousFrom, $from);
        [$priorCost] = $this->costOfGoods($previousFrom, $from);

        $net = $revenue - $refunds;
        $priorNet = $priorRevenue - $priorRefunds;
        $saleCount = Sale::where('created_at', '>=', $from)->count();

        return [
            'revenue' => $net,
            'profit' => $net - $cost,
            'previous_revenue' => $priorNet,
            'previous_profit' => $priorNet - $priorCost,
            'refunds' => $refunds,
            'discounts' => $discount,
            'cost_of_goods' => $cost,
            'sales' => $saleCount,
            'average_sale' => $saleCount > 0 ? round($net / $saleCount, 2) : 0.0,
        ];
    }

    /** @return array{0: float, 1: float, 2: float} revenue, refunds, discounts */
    private function revenueTotals(CarbonImmutable $from, ?CarbonImmutable $until = null): array
    {
        // $until bounds the previous period. Without it the "previous" total ran
        // from the start of the previous window to today, so it included the
        // current window and every comparison read as a decline.
        $row = Sale::where('created_at', '>=', $from)
            ->when($until, fn ($q) => $q->where('created_at', '<', $until))
            ->selectRaw('COALESCE(SUM(total), 0) AS revenue')
            ->selectRaw('COALESCE(SUM(discount_amount), 0) AS discount')
            ->first();

        $refunds = (float) Payment::where('type', 'refund')
            ->where('paid_at', '>=', $from)
            ->when($until, fn ($q) => $q->where('paid_at', '<', $until))
            ->sum(DB::raw('ABS(amount)'));

        return [(float) $row->revenue, $refunds, (float) $row->discount];
    }

    /**
     * Cost of goods, joined at the line rather than read off the sale.
     *
     * product_id is nulled when a product is deleted, so a line whose product
     * is gone contributes revenue and no cost. That understates cost, which is
     * the safe direction to be wrong in: a slightly better margin rather than
     * a loss that never happened.
     */
    private function costOfGoods(CarbonImmutable $from, ?CarbonImmutable $until = null): array
    {
        $cost = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->leftJoin('products', 'products.id', '=', 'sale_items.product_id')
            ->where('sales.created_at', '>=', $from)
            ->when($until, fn ($q) => $q->where('sales.created_at', '<', $until))
            ->where('sale_items.kind', 'product')
            ->selectRaw('COALESCE(SUM(sale_items.quantity * products.cost), 0) AS cost')
            ->value('cost');

        return [(float) $cost];
    }

    /**
     * One row per day, every day present.
     *
     * Grouped in PHP rather than SQL so the zeroes are guaranteed. A DATE()
     * group over a window with no sales returns nothing at all, and a chart
     * that silently starts a fortnight late is worse than no chart.
     */
    private function daily(CarbonImmutable $from, CarbonImmutable $today): array
    {
        $keys = [];

        // Advanced by reassignment rather than in place. CarbonImmutable::addDay
        // returns a new instance, so `$day->addDay()` on its own would leave the
        // loop variable where it started and this would never finish.
        $day = $from;

        while ($day->lte($today)) {
            $keys[$day->toDateString()] = [
                'date' => $day->toDateString(),
                'revenue' => 0.0,
                'profit' => 0.0,
                'bookings' => 0,
                'completed' => 0,
                'no_show' => 0,
                'leads' => 0,
            ];

            $day = $day->addDay();
        }

        $refunds = Payment::where('type', 'refund')
            ->where('paid_at', '>=', $from)
            ->get(['paid_at', 'amount'])
            ->groupBy(fn (Payment $p) => $p->paid_at->toDateString());

        $sales = Sale::where('created_at', '>=', $from)
            ->get(['created_at', 'total'])
            ->groupBy(fn (Sale $s) => $s->created_at->toDateString());

        foreach ($sales as $date => $rows) {
            if (! isset($keys[$date])) {
                continue;
            }

            $keys[$date]['revenue'] = round((float) $rows->sum('total'), 2);
        }

        // Refunds are applied on their own, not folded in with the sales above.
        // Subtracting them inside that loop would only ever reach days that
        // also had a sale, so a refund given on a quiet day would vanish from
        // the chart while still being in the headline figure -- and the daily
        // bars would stop adding up to the total above them.
        foreach ($refunds as $date => $rows) {
            if (isset($keys[$date])) {
                $keys[$date]['revenue'] = round(
                    $keys[$date]['revenue'] - (float) $rows->sum(fn (Payment $p) => abs((float) $p->amount)),
                    2,
                );
            }
        }

        // Cost per day, so the profit line follows the revenue line rather
        // than being one flat total spread across the window.
        $costs = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->leftJoin('products', 'products.id', '=', 'sale_items.product_id')
            ->where('sales.created_at', '>=', $from)
            ->where('sale_items.kind', 'product')
            ->get(['sales.created_at', DB::raw('sale_items.quantity * products.cost AS line_cost')])
            ->groupBy(fn ($row) => CarbonImmutable::parse($row->created_at)->toDateString());

        foreach ($costs as $date => $rows) {
            if (isset($keys[$date])) {
                $keys[$date]['profit'] = round(
                    $keys[$date]['revenue'] - (float) $rows->sum('line_cost'),
                    2,
                );
            }
        }

        $bookings = Appointment::where('created_at', '>=', $from)
            ->get(['created_at', 'status'])
            ->groupBy(fn (Appointment $a) => $a->created_at->toDateString());

        foreach ($bookings as $date => $rows) {
            if (! isset($keys[$date])) {
                continue;
            }

            $keys[$date]['bookings'] = $rows->count();
            $keys[$date]['completed'] = $rows->where('status', 'completed')->count();
            $keys[$date]['no_show'] = $rows->where('status', 'no_show')->count();
        }

        $leads = Lead::where('created_at', '>=', $from)
            ->get(['created_at'])
            ->groupBy(fn (Lead $l) => $l->created_at->toDateString());

        foreach ($leads as $date => $rows) {
            if (isset($keys[$date])) {
                $keys[$date]['leads'] = $rows->count();
            }
        }

        return array_values($keys);
    }

    /** Where the appointments in the window actually ended up. */

    /** Where the appointments in the window actually ended up. */
    private function bookingBreakdown(CarbonImmutable $from, CarbonImmutable $today): array
    {
        return Appointment::whereBetween('starts_at', [$from, $today->copy()->endOfDay()])
            ->whereNotIn('status', ['cancelled', 'rescheduled'])
            ->selectRaw('status, COUNT(*) AS total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn ($count, $status) => [
                'status' => $status,
                'label' => Appointment::STATUSES[$status] ?? $status,
                'count' => (int) $count,
            ])
            ->values()
            ->all();
    }

    /**
     * What the counter actually sold.
     *
     * Grouped on the snapshot description rather than treatment_id, because
     * the description is what the customer was charged and it survives a
     * treatment being renamed or deleted since.
     */
    private function topTreatments(CarbonImmutable $from, CarbonImmutable $today): array
    {
        return SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->whereBetween('sales.created_at', [$from, $today->copy()->endOfDay()])
            ->where('sale_items.kind', 'service')
            ->selectRaw('sale_items.description, SUM(sale_items.quantity) AS sold, SUM(sale_items.line_total) AS revenue')
            ->groupBy('sale_items.description')
            ->orderByDesc('revenue')
            ->limit(6)
            ->get()
            ->map(fn ($row) => [
                'name' => $row->description,
                'sold' => (int) $row->sold,
                'revenue' => round((float) $row->revenue, 2),
            ])
            ->all();
    }

    /** Money actually taken, by how it was taken. Refunds count as negative. */
    private function paymentMix(CarbonImmutable $from, CarbonImmutable $today): array
    {
        $rows = Payment::whereBetween('paid_at', [$from, $today->copy()->endOfDay()])
            ->selectRaw('method, type, COUNT(*) AS count, COALESCE(SUM(amount), 0) AS amount')
            ->groupBy('method', 'type')
            ->get();

        $mix = [];

        foreach ($rows as $row) {
            $method = $row->method;
            $mix[$method] ??= [
                'method' => $method,
                'label' => Payment::METHODS[$method] ?? $method,
                'amount' => 0.0,
                'count' => 0,
            ];

            $mix[$method]['amount'] += ($row->type === 'refund' ? -1 : 1) * abs((float) $row->amount);
            $mix[$method]['count'] += (int) $row->count;
        }

        $mix = array_values($mix);
        usort($mix, fn ($a, $b) => $b['amount'] <=> $a['amount']);

        return array_map(fn (array $row) => [
            ...$row,
            'amount' => round($row['amount'], 2),
        ], $mix);
    }

    /** Enquiries and conversations, the two things the website produces. */
    private function frontDesk(CarbonImmutable $from): array
    {
        $newLeads = Lead::where('created_at', '>=', $from)->count();
        $won = Lead::where('created_at', '>=', $from)
            ->whereIn('stage', ['customer', 'repeat_customer', 'vip'])
            ->count();

        return [
            'leads' => $newLeads,
            'won' => $won,
            // Share of enquiries that became customers. Null rather than zero
            // when nothing was asked: 0% is a claim about performance, and
            // "nobody asked" is a claim about volume.
            'conversion' => $newLeads > 0 ? round($won / $newLeads, 4) : null,
            'conversations' => ChatConversation::where('created_at', '>=', $from)->count(),
            'open_conversations' => ChatConversation::where('status', 'open')
                ->where('created_at', '>=', $from)
                ->count(),
        ];
    }
}
