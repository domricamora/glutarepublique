<?php

namespace Database\Seeders;

use App\Actions\Accounting\PostPayroll;
use App\Actions\Inventory\ManageStock;
use App\Actions\Payroll\CalculatePayslip;
use App\Actions\Pos\RingUpSale;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\ClinicSetting;
use App\Models\Employee;
use App\Models\Lead;
use App\Models\Organization;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Models\Product;
use App\Models\Treatment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * A year in the life of the showcase clinic, so every chart and list in the
 * admin has real depth: thousands of visits, every completed one rung through
 * the real till at the moment it happened, with retail add-ons, discounts, the
 * odd refund, monthly stock deliveries, six paid payroll runs, a steady flow of
 * enquiries, the next fortnight's bookings and recent chats.
 *
 * Nothing is inserted behind the actions' backs. The clock is moved with
 * Carbon::setTestNow() and the same RingUpSale, ManageStock and PostPayroll the
 * screens use do the work, so the sales, stock ledger, payments and journal
 * agree with each other exactly as they would after a real year.
 *
 * Seeded from a fixed number, so a reset always rebuilds the same clinic.
 * Fictional people only. Skipped in tests. Runs once per database: the deploy
 * seeds on every upload, and a second year on top of the first would double
 * every chart.
 */
class ShowcaseHistorySeeder extends Seeder
{
    private const DAYS = 365;

    private const FIRST = [
        'Andrea', 'Bianca', 'Camille', 'Danica', 'Erika', 'Frances', 'Gabrielle', 'Hannah', 'Isabel', 'Jasmine', 'Katrina', 'Lianne',
        'Maxine', 'Nicole', 'Patricia', 'Queenie', 'Rhea', 'Samantha', 'Trisha', 'Victoria', 'Ysabel', 'Zia', 'Alyssa', 'Bea',
        'Clarisse', 'Dianne', 'Elaine', 'Faith', 'Gwen', 'Hazel', 'Ivy', 'Joanna', 'Kaye', 'Louise', 'Mariel', 'Nina', 'Pia',
        'Regina', 'Sofia', 'Therese', 'Mika', 'Chloe', 'Janelle', 'Krizia', 'Angela', 'Carla', 'Denise', 'Ella', 'Grace', 'Iris',
        'Miguel', 'Paolo', 'Rafael', 'Jericho', 'Marco', 'Carlo', 'Enzo', 'Gabriel', 'Luis', 'Nathan',
    ];

    private const LAST = [
        'Reyes', 'Santos', 'Cruz', 'Bautista', 'Ocampo', 'Garcia', 'Mendoza', 'Torres', 'Tan', 'Lim', 'Aquino', 'Ramos', 'Castillo',
        'Flores', 'Villanueva', 'Navarro', 'Chua', 'Dela Cruz', 'Gonzales', 'Salazar', 'Soriano', 'Uy', 'Pascual', 'Go', 'Manalo',
        'Yap', 'De Leon', 'Lopez', 'Rivera', 'Ong', 'Mercado', 'Sy', 'Javier', 'Co', 'Padilla', 'Valdez', 'Morales', 'Tiu',
        'Robles', 'Evangelista', 'Lacson', 'Del Rosario', 'Tolentino', 'Agustin', 'Mangubat', 'Sison', 'Velasco', 'Hizon',
    ];

    /** [source, weight, utm_source, utm_medium, utm_campaign] */
    private const SOURCES = [
        ['google', 24, 'google', 'cpc', 'search-aesthetics'], ['instagram', 18, 'instagram', 'social', 'glow-reels'],
        ['facebook', 14, 'facebook', 'social', 'first-visit-credit'], ['website', 13, null, null, null],
        ['referral', 12, null, null, null], ['tiktok', 9, 'tiktok', 'social', 'laser-myths'],
        ['walk_in', 6, null, null, null], ['phone', 4, null, null, null],
    ];

    /** Payment method => weight. */
    private const METHODS = ['gcash' => 30, 'card' => 30, 'cash' => 24, 'maya' => 11, 'bank_transfer' => 5];

    /** Month => demand factor: the holidays and the wedding season are busy, January is quiet. */
    private const SEASON = [1 => 0.85, 2 => 1.08, 3 => 1.0, 4 => 1.02, 5 => 1.08, 6 => 1.1, 7 => 0.95, 8 => 0.95, 9 => 1.0, 10 => 1.04, 11 => 1.12, 12 => 1.25];

    /** Branch slug => share of demand. */
    private const BRANCH_WEIGHT = ['cebu' => 1.2, 'san-pedro' => 1.0, 'sta-rosa' => 0.9, 'bacoor' => 0.85];

    private User $cashier;

    /** @var array<string, true> specialist|start already taken */
    private array $taken = [];

    /** @var array<int, int> lead id => completed visits */
    private array $visits = [];

    /** @var array<int, list<int>> branch id => patient lead ids */
    private array $patients = [];

    private int $names = 0;

    /** The real clock, kept before it is moved: nothing may be created after it. */
    private CarbonImmutable $now;

    public function run(RingUpSale $till, ManageStock $stock, CalculatePayslip $calculate, PostPayroll $postPayroll): void
    {
        if (ClinicSetting::flag('demo.history_seeded')) {
            return;
        }

        mt_srand(2026);
        $real = CarbonImmutable::now();
        $this->now = $real;

        Organization::where('slug', config('clinic.organization'))->firstOrFail();
        $this->cashier = User::where('email', 'reception@glutarepubliq.com')->firstOrFail();
        $owner = User::where('email', 'owner@glutarepubliq.com')->firstOrFail();

        // Tenant scopes and audit entries read the signed-in user.
        Auth::setUser($this->cashier);

        $branches = Branch::where('is_active', true)->orderBy('id')->with(['specialists' => fn ($q) => $q->where('is_active', true)])->get();
        $treatments = Treatment::where('is_active', true)->get();
        $products = Product::where('is_active', true)->get();
        $weights = $treatments->mapWithKeys(fn (Treatment $t) => [$t->id => ($t->is_featured ? 3 : 1) * (int) max(1, 12000 / max(1500, (float) $t->price))])->all();

        $today = $real->startOfDay();
        $start = $today->subDays(self::DAYS);

        for ($day = $start; $day->lt($today); $day = $day->addDay()) {
            if ($day->day === 1 || $day->eq($start)) {
                $this->restock($stock, $products, $branches, $day);
            }

            if ($day->isSunday()) {
                continue;
            }

            $progress = $start->diffInDays($day) / self::DAYS;
            $demand = (0.45 + 0.55 * $progress) * self::SEASON[$day->month] * match ($day->dayOfWeekIso) {
                6 => 1.25, 5 => 1.1, 1 => 0.85, default => 1.0,
            };

            foreach ($branches as $branch) {
                $count = (int) round(7 * $demand * (self::BRANCH_WEIGHT[$branch->slug] ?? 1) + mt_rand(-150, 150) / 100);
                $this->dayAtBranch($till, $branch, $day, $count, $treatments, $products, $weights, false);
            }

            $this->enquiries($day, $branches, $treatments, (int) round(9 * $demand + mt_rand(-150, 150) / 100));
        }

        // The fortnight ahead: booked, mostly unconfirmed, a little lighter than a full day.
        for ($day = $today->addDay(); $day->lte($today->addDays(14)); $day = $day->addDay()) {
            if (! $day->isSunday()) {
                foreach ($branches as $branch) {
                    $this->dayAtBranch($till, $branch, $day, (int) round(4.5 * (self::BRANCH_WEIGHT[$branch->slug] ?? 1)), $treatments, $products, $weights, true);
                }
            }
        }

        Carbon::setTestNow($real);
        $this->settleStages();
        $this->payroll($calculate, $postPayroll, $owner, $today);
        $this->chats($today);

        Carbon::setTestNow();
        Auth::logout();
        ClinicSetting::put('demo.history_seeded', '1');
    }

    /** @param array<int, int> $weights */
    private function dayAtBranch(RingUpSale $till, Branch $branch, CarbonImmutable $day, int $count, $treatments, $products, array $weights, bool $future): void
    {
        $staff = $branch->specialists;
        $hours = $branch->hoursOn($day);
        if ($staff->isEmpty() || $count <= 0 || $hours === null) {
            return;
        }

        // The branch's own schedule, the same one the booking page offers.
        [$h, $m] = array_map('intval', explode(':', $hours[1]));
        $close = $h * 60 + $m;
        $free = $staff->mapWithKeys(fn ($s) => [$s->id => 10 * 60 + 15 * mt_rand(0, 4)])->all();

        for ($n = 0; $n < $count; $n++) {
            $treatment = $treatments->firstWhere('id', $this->weighted($weights));
            $specialist = $staff[$n % $staff->count()];
            $at = $free[$specialist->id];

            if ($at + $treatment->duration_minutes > $close) {
                continue;
            }

            $free[$specialist->id] = $at + $treatment->duration_minutes + 15 * mt_rand(0, 3);
            $startsAt = $day->setTime(intdiv($at, 60), $at % 60);
            $key = $specialist->id.'|'.$startsAt->toDateTimeString();

            // Also ask the database: an install already holding bookings must not be double booked.
            if (isset($this->taken[$key]) || Appointment::withoutGlobalScopes()->where('specialist_id', $specialist->id)->where('starts_at', $startsAt)->exists()) {
                continue;
            }

            $this->taken[$key] = true;
            $lead = $this->patientFor($branch, $startsAt, $treatment);
            $roll = mt_rand(1, 100);
            $status = $future
                ? ($roll <= 55 ? 'confirmed' : 'pending')
                : ($roll <= 88 ? 'completed' : ($roll <= 94 ? 'no_show' : 'cancelled'));

            Carbon::setTestNow($this->past($startsAt->subDays(mt_rand(1, 12))->setTime(mt_rand(9, 21), mt_rand(0, 59))));
            Appointment::create([
                'reference' => 'PT-'.Str::upper(Str::random(7)),
                'lead_id' => $lead->id,
                'branch_id' => $branch->id,
                'treatment_id' => $treatment->id,
                'specialist_id' => $specialist->id,
                'starts_at' => $startsAt,
                'ends_at' => $startsAt->addMinutes($treatment->duration_minutes),
                'status' => $status,
            ]);

            if ($status === 'completed') {
                $this->visits[$lead->id] = ($this->visits[$lead->id] ?? 0) + 1;
                $this->ringUp($till, $branch, $lead, $treatment, $products, $startsAt->addMinutes($treatment->duration_minutes + 5));
            }
        }
    }

    private function ringUp(RingUpSale $till, Branch $branch, Lead $lead, Treatment $treatment, $products, CarbonImmutable $at): void
    {
        Carbon::setTestNow($at);

        $items = [['kind' => 'service', 'id' => $treatment->id, 'quantity' => 1]];
        if (mt_rand(1, 100) <= 30) {
            $items[] = ['kind' => 'product', 'id' => $products[mt_rand(0, $products->count() - 1)]->id, 'quantity' => mt_rand(1, 2)];
        }

        $discounted = mt_rand(1, 100) <= 9;
        $data = [
            'branch_id' => $branch->id,
            'lead_id' => $lead->id,
            'client_name' => trim($lead->first_name.' '.$lead->last_name),
            'client_phone' => $lead->phone,
            'items' => $items,
            'discount_type' => $discounted ? 'percent' : 'none',
            'discount_value' => $discounted ? [10, 15][mt_rand(0, 1)] : 0,
            'method' => $this->weighted(self::METHODS),
        ];

        try {
            $sale = $till($data, $this->cashier)->sale;
        } catch (ValidationException) {
            // A shelf ran dry before the next delivery: the treatment is still sold.
            $sale = $till(['items' => [$items[0]]] + $data, $this->cashier)->sale;
        }

        if (mt_rand(1, 1000) <= 7) {
            Carbon::setTestNow($at->addDays(mt_rand(1, 4)));
            $till->refund($sale, round((float) $sale->total * 0.5, 2), $data['method'], 'Goodwill refund after a follow-up review', $this->cashier);
        }
    }

    /** A returning patient of this branch about four times in ten, otherwise a new one. */
    private function patientFor(Branch $branch, CarbonImmutable $at, Treatment $treatment): Lead
    {
        $pool = $this->patients[$branch->id] ?? [];

        if (count($pool) > 25 && mt_rand(1, 100) <= 55) {
            // Recent patients come back more often than old ones.
            $index = max(0, count($pool) - 1 - (int) abs(mt_rand(0, count($pool) - 1) * (mt_rand(0, 100) / 100)));

            return Lead::findOrFail($pool[$index]);
        }

        $lead = $this->newLead($branch, $at->subDays(mt_rand(1, 10))->setTime(mt_rand(8, 22), mt_rand(0, 59)), $treatment, 'customer');
        $this->patients[$branch->id][] = $lead->id;

        return $lead;
    }

    private function newLead(Branch $branch, CarbonImmutable $created, Treatment $treatment, string $stage): Lead
    {
        $first = self::FIRST[($this->names * 7) % count(self::FIRST)];
        $last = self::LAST[intdiv($this->names, count(self::FIRST)) % count(self::LAST)];
        $this->names++;
        [$source, , $utmSource, $utmMedium, $campaign] = $this->source();

        $created = $this->past($created);
        Carbon::setTestNow($created);

        return Lead::create([
            'first_name' => $first,
            'last_name' => $last,
            'email' => Str::lower(Str::slug($first.' '.$last.' '.$this->names, '.')).'@example.com',
            'phone' => sprintf('09%02d %03d %04d', mt_rand(15, 99), mt_rand(100, 999), mt_rand(1000, 9999)),
            'source' => $source,
            'stage' => $stage,
            'form' => mt_rand(0, 2) === 0 ? 'enquiry' : 'booking',
            'treatment_id' => $treatment->id,
            'branch_id' => $branch->id,
            'utm_source' => $utmSource,
            'utm_medium' => $utmMedium,
            'utm_campaign' => $campaign,
            'device' => mt_rand(1, 100) <= 72 ? 'mobile' : 'desktop',
            'privacy_consent_at' => $created,
            'marketing_consent' => mt_rand(0, 1) === 1,
        ]);
    }

    /** Enquiries that have not become patients (yet): younger ones are earlier in the pipeline. */
    private function enquiries(CarbonImmutable $day, $branches, $treatments, int $count): void
    {
        $age = $this->now->startOfDay()->diffInDays($day, true);

        for ($i = 0; $i < $count; $i++) {
            $stage = match (true) {
                $age < 3 => 'new',
                $age < 14 => ['new', 'contacted', 'contacted', 'consultation_booked'][mt_rand(0, 3)],
                default => ['contacted', 'contacted', 'consultation_completed', 'treatment_recommended'][mt_rand(0, 3)],
            };

            $this->newLead($branches[mt_rand(0, $branches->count() - 1)], $day->setTime(mt_rand(8, 22), mt_rand(0, 59)), $treatments[mt_rand(0, $treatments->count() - 1)], $stage);
        }
    }

    /** Patients move to customer, repeat customer or VIP by how often they came. */
    private function settleStages(): void
    {
        foreach ($this->visits as $leadId => $visits) {
            Lead::whereKey($leadId)->update([
                'stage' => $visits >= 6 ? 'vip' : ($visits >= 2 ? 'repeat_customer' : 'customer'),
            ]);
        }
    }

    /** A delivery for every product at every branch on the first of the month. */
    private function restock(ManageStock $stock, $products, $branches, CarbonImmutable $day): void
    {
        Carbon::setTestNow($day->setTime(9, 0));

        foreach ($branches as $branch) {
            foreach ($products as $product) {
                $stock->receive($product, $branch, [
                    'quantity' => mt_rand(7, 11),
                    'cost' => (float) $product->cost,
                    'lot_number' => 'L'.$day->format('ym').'-'.str_pad((string) $product->id, 3, '0', STR_PAD_LEFT),
                    'expires_on' => $day->addMonths(mt_rand(5, 14))->toDateString(),
                    'received_on' => $day->toDateString(),
                    'note' => 'Monthly delivery',
                ], $this->cashier);
            }
        }
    }

    /** The last six full months, calculated, approved and paid as the payroll screen would. */
    private function payroll(CalculatePayslip $calculate, PostPayroll $post, User $owner, CarbonImmutable $today): void
    {
        Auth::setUser($owner);

        for ($m = 6; $m >= 1; $m--) {
            $from = $today->startOfMonth()->subMonths($m);
            $to = $from->endOfMonth()->startOfDay();
            $label = $from->format('F Y');

            if (PayrollRun::where('label', $label)->exists()) {
                continue;
            }

            Carbon::setTestNow($to->setTime(17, 0));
            $run = PayrollRun::create([
                'user_id' => $owner->id,
                'label' => $label,
                'period_start' => $from->toDateString(),
                'period_end' => $to->toDateString(),
                'paid_on' => $to->toDateString(),
                'status' => 'draft',
            ]);

            foreach (Employee::employedOn($from)->active()->get() as $employee) {
                Payslip::create($calculate($employee, $from, $to) + ['payroll_run_id' => $run->id, 'employee_id' => $employee->id]);
            }

            $run->recalculate();
            $post($run, $owner);
            $run->update(['status' => 'approved', 'approved_at' => now(), 'posted_at' => now()]);
            AuditLog::record('payroll.approve', $label.' approved and posted to the books.', $run);

            Carbon::setTestNow($to->setTime(18, 0));
            $post->paid($run, $owner);
            $run->update(['status' => 'paid']);
            AuditLog::record('payroll.pay', $label.' marked as paid.', $run);
        }

        Auth::setUser($this->cashier);
    }

    /** A handful of website chats from the last fortnight, answered and not. */
    private function chats(CarbonImmutable $today): void
    {
        $threads = [
            ['Hi! Do you have a slot for a Hydra Facial this Saturday?', 'Good afternoon! We have 11:00 and 2:30 at Makati. Shall I hold one for you?', 'The 2:30 please. Thank you!'],
            ['How long does the numbing take before skin boosters?', 'About 20 minutes. The treatment itself takes around 25.'],
            ['Is the first visit consultation fee credited if I book a peel?', 'Yes, within 30 days of the consultation it is credited in full.', 'Perfect, booking now.'],
            ['Do you accept GCash at the Cebu branch?', 'We do, along with Maya, cards and cash.'],
            ['Can I come in for laser if I have a beach trip next week?', 'We would suggest waiting until after the trip. Sun exposure right after can cause pigmentation.'],
            ['Hello, what are your hours on Saturday at BGC?'],
            ['Is there parking at the Makati clinic?'],
            ['How many sessions would I need for acne scars?', 'It depends on the scarring. Your doctor will plan it at the consultation, usually 3 to 6 sessions.'],
        ];

        $leads = Lead::whereIn('stage', ['new', 'contacted'])->latest('id')->limit(count($threads))->pluck('id');

        foreach ($threads as $i => $lines) {
            $started = $today->subDays($i * 2)->setTime(10 + $i, 15);
            Carbon::setTestNow($started);

            $conversation = ChatConversation::create([
                'lead_id' => $leads[$i] ?? null,
                'token' => (string) Str::uuid(),
                'status' => count($lines) === 1 ? 'open' : 'closed',
                'page' => ['/', '/treatments', '/book', '/contact'][$i % 4],
                'last_message_at' => $started->addMinutes(4 * count($lines)),
            ]);

            foreach ($lines as $n => $body) {
                $at = $started->addMinutes(4 * $n);
                ChatMessage::create([
                    'chat_conversation_id' => $conversation->id,
                    'user_id' => $n % 2 ? $this->cashier->id : null,
                    'from' => $n % 2 ? 'staff' : 'visitor',
                    'body' => $body,
                    'read_at' => $at,
                    'created_at' => $at,
                    'updated_at' => $at,
                ]);
            }
        }
    }

    /** A moment no later than an hour before the real now, so nothing is dated in the future. */
    private function past(CarbonImmutable $at): CarbonImmutable
    {
        $limit = $this->now->subHour();

        return $at->gt($limit) ? $limit->subMinutes(mt_rand(0, 600)) : $at;
    }

    /** @return array{0: string, 1: int, 2: ?string, 3: ?string, 4: ?string} */
    private function source(): array
    {
        $pick = mt_rand(1, array_sum(array_column(self::SOURCES, 1)));

        foreach (self::SOURCES as $row) {
            if (($pick -= $row[1]) <= 0) {
                return $row;
            }
        }

        return self::SOURCES[0];
    }

    /** @param array<int|string, int> $weights key => weight */
    private function weighted(array $weights): int|string
    {
        $pick = mt_rand(1, array_sum($weights));

        foreach ($weights as $key => $weight) {
            if (($pick -= $weight) <= 0) {
                return $key;
            }
        }

        return array_key_first($weights);
    }
}
