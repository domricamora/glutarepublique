<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Organization;
use App\Models\User;
use App\Models\WorkSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

/**
 * The staff roll (plan.md 28): the people who sign in, plus the ones who do
 * not, with a month of clock-ins and a couple of leave requests so payroll
 * has something to work from.
 */
class HrDemoSeeder extends Seeder
{
    /**
     * The clinic's own roster (payroll intake sheet, 2026-10-23); TBA vacancies
     * left out. The sheet has no pay data yet, so salary, allowance and hire
     * date are placeholders by position until the client fills it in. Only real
     * staff: the demo doctors were removed at the owner's request.
     *
     * email, name, position, department, salary, allowance, hired, branch
     *
     * @var array<int, array<int, mixed>>
     */
    private const STAFF = [
        ['admin@glutarepubliq.com', 'Mary Grace Agulto', 'HR Associate', 'HR', 26000, 1500, '2024-03-01', 'southwoods'],
        ['owner@glutarepubliq.com', 'Karla Ugalino Velasco', 'Owner', 'Management', 120000, 10000, '2018-01-15', 'cebu'],
        ['reception@glutarepubliq.com', 'Jeankie Panon', 'Receptionist', 'Front Desk', 20000, 1000, '2023-06-01', 'cebu'],
        [null, 'Jermaine Ramirez', 'Registered Nurse', 'Clinic', 34000, 1500, '2022-02-14', 'bacoor'],
        [null, 'Ivy Neri', 'Registered Nurse / Branch Manager', 'Clinic', 42000, 2500, '2021-05-03', 'cebu'],
        [null, 'Janet Culango', 'Medical Aesthetician', 'Clinic', 30000, 1200, '2022-08-01', 'cebu'],
        [null, 'Suzette Jacalan', 'Senior Facialist', 'Clinic', 26000, 1200, '2021-09-15', 'cebu'],
        [null, 'Jenrose Rosacena', 'Junior Facialist', 'Clinic', 20000, 800, '2024-01-08', 'cebu'],
        [null, 'Jamaica Dupilas', 'Junior Facialist', 'Clinic', 20000, 800, '2024-06-03', 'cebu'],
        [null, 'Mery Joy Legita', 'Registered Nurse', 'Clinic', 32000, 1500, '2025-11-03', 'southwoods'],
        [null, 'Tesha Tolentino Emaas', 'Senior Facialist', 'Clinic', 26000, 1200, '2025-11-03', 'southwoods'],
        [null, 'Marinel Bombaes', 'Facialist', 'Clinic', 22000, 1000, '2023-02-01', 'bacoor'],
        [null, 'Nenet Canilas', 'Facialist', 'Clinic', 22000, 1000, '2023-02-01', 'bacoor'],
    ];

    /** Everyone who treats clients, doctors included (plan.md §28). */
    private const CLINICIANS = [
        'Ivy Neri',
        'Jermaine Ramirez',
        'Mery Joy Legita',
        'Janet Culango',
        'Suzette Jacalan',
        'Tesha Tolentino Emaas',
        'Jenrose Rosacena',
        'Jamaica Dupilas',
        'Marinel Bombaes',
        'Nenet Canilas',
    ];

    /** @var array<string, string> */
    private const CREDENTIALS = [
        'Ivy Neri' => 'RN',
        'Jermaine Ramirez' => 'RN',
        'Mery Joy Legita' => 'RN',
    ];

    public function run(): void
    {
        $credentials = self::CREDENTIALS;
        $organization = Organization::where('slug', config('clinic.organization'))->firstOrFail();
        $branches = Branch::pluck('id', 'slug');
        $reviewer = User::where('organization_id', $organization->id)->orderBy('id')->first();

        foreach (self::STAFF as $index => [$email, $name, $position, $department, $salary, $allowance, $hired, $at]) {
            $employee = Employee::updateOrCreate(
                ['organization_id' => $organization->id, 'employee_no' => 'EMP-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT)],
                [
                    'user_id' => $email ? User::where('email', $email)->value('id') : null,
                    'branch_id' => $branches[$at] ?? $branches['cebu'],
                    'name' => $name,
                    'position' => $position,
                    'department' => $department,
                    'hire_date' => $hired,
                    'employment_type' => $index === 3 ? 'probationary' : 'regular',
                    'pay_schedule' => 'monthly',
                    'base_salary' => $salary,
                    'monthly_allowance' => $allowance,
                    'status' => 'active',
                    'practitioner' => in_array($name, self::CLINICIANS, true),
                    'credentials' => in_array($name, self::CLINICIANS, true)
                        ? ($credentials[$name] ?? null)
                        : null,
                    'focus' => match ($name) {
                        default => null,
                    },
                ]
            );

            $this->clockIn($employee, 18);
            $this->schedule($employee);
        }

        // A clinic puts its clinicians on the website and keeps the rest behind
        // the counter, so the public roster is a decision somebody made rather
        // than the whole staff roll. Ticked here, after the roll exists.
        //
        // The default is the safe one: an employee added later is not published
        // until an administrator says so, because a name and a photograph going
        // live by accident is not a mistake anybody wants to find out about
        // from a customer.
        Employee::whereIn('name', self::CLINICIANS)->update(['show_on_site' => true]);

        $nurse = Employee::where('name', 'Jermaine Ramirez')->firstOrFail();
        $therapist = Employee::where('name', 'Marinel Bombaes')->firstOrFail();

        LeaveRequest::updateOrCreate(
            ['employee_id' => $nurse->id, 'from_date' => now()->subDays(6)->toDateString()],
            [
                'organization_id' => $organization->id,
                'to_date' => now()->subDays(5)->toDateString(),
                'type' => 'sick',
                'days' => 2,
                'reason' => 'Fever, advised to rest.',
                'status' => 'approved',
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now()->subDays(7),
            ]
        );

        LeaveRequest::updateOrCreate(
            ['employee_id' => $therapist->id, 'from_date' => now()->addDays(9)->toDateString()],
            [
                'organization_id' => $organization->id,
                'to_date' => now()->addDays(12)->toDateString(),
                'type' => 'annual',
                'days' => 4,
                'reason' => 'Family trip.',
                'status' => 'pending',
            ]
        );
    }

    /**
     * A plain rota from four weeks back to the end of next week: 09:00 to 18:00
     * on weekdays, weekends off, matching the demo clock-ins above. A day
     * already planned in the office is left as it is. Not in tests, whose
     * payroll figures are worked out without a rota.
     */
    private function schedule(Employee $employee): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        $end = CarbonImmutable::today()->startOfWeek(CarbonImmutable::MONDAY)->addDays(13);

        for ($day = CarbonImmutable::today()->subWeeks(4)->startOfWeek(CarbonImmutable::MONDAY); $day->lte($end); $day = $day->addDay()) {
            WorkSchedule::firstOrCreate(
                ['employee_id' => $employee->id, 'work_date' => $day->toDateString()],
                [
                    'organization_id' => $employee->organization_id,
                    'branch_id' => $employee->branch_id,
                    'is_rest_day' => $day->isWeekend(),
                    'starts_at' => $day->isWeekend() ? null : '09:00',
                    'ends_at' => $day->isWeekend() ? null : '18:00',
                ],
            );
        }
    }

    /** A run of ordinary days, with the odd hour past eight. */
    private function clockIn(Employee $employee, int $days): void
    {
        $cursor = CarbonImmutable::today()->subDays($days);
        $overtime = 0;

        while ($cursor->lessThan(CarbonImmutable::today())) {
            if (! $cursor->isWeekend()) {
                $in = '09:'.str_pad((string) mt_rand(0, 20), 2, '0', STR_PAD_LEFT);
                $late = $overtime % 5 === 0;
                $out = $late ? '19:00' : '18:00';
                $minutes = (strtotime($out) - strtotime($in)) / 60;

                Attendance::updateOrCreate(
                    ['employee_id' => $employee->id, 'work_date' => $cursor->toDateString()],
                    [
                        'organization_id' => $employee->organization_id,
                        'time_in' => $in,
                        'time_out' => $out,
                        'minutes' => (int) $minutes,
                        'overtime_minutes' => (int) max(0, $minutes - 480),
                    ]
                );

                $overtime++;
            }

            $cursor = $cursor->addDay();
        }
    }
}
