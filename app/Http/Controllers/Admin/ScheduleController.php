<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\WorkSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The work schedule: a week of shifts per person. The kiosk and the time
 * record read it to say who is late, who is absent and what counts as
 * overtime.
 */
class ScheduleController extends Controller
{
    public function index(Request $request): Response
    {
        $start = $this->monday($request->query('week'));
        $branch = $request->integer('branch') ?: null;
        $days = collect(range(0, 6))->map(fn (int $i) => $start->addDays($i));

        $staff = Employee::active()
            ->when($branch, fn ($q) => $q->where('branch_id', $branch))
            ->orderBy('name')
            ->get(['id', 'name', 'position', 'branch_id']);

        $grid = WorkSchedule::whereIn('employee_id', $staff->pluck('id'))
            ->whereBetween('work_date', [$start->toDateString(), $start->addDays(6)->toDateString()])
            ->get()
            ->mapWithKeys(fn (WorkSchedule $s) => [$s->employee_id.'|'.$s->work_date->toDateString() => [
                'rest' => $s->is_rest_day,
                'starts' => $s->starts_at ? substr($s->starts_at, 0, 5) : null,
                'ends' => $s->ends_at ? substr($s->ends_at, 0, 5) : null,
                'minutes' => $s->minutes(),
            ]]);

        return Inertia::render('admin/hr/schedule', [
            'week' => $start->toDateString(),
            'days' => $days->map(fn (CarbonImmutable $d) => ['date' => $d->toDateString(), 'label' => $d->format('D j')]),
            'staff' => $staff->map(fn (Employee $e) => ['id' => $e->id, 'name' => $e->name, 'position' => $e->position]),
            'grid' => (object) $grid->all(),
            'branches' => Branch::orderBy('id')->get(['id', 'name']),
            'branch' => $branch,
        ]);
    }

    public function save(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'employee_id' => ['required', 'integer'],
            'date' => ['required', 'date_format:Y-m-d'],
            'rest' => ['required', 'boolean'],
            'starts' => ['exclude_if:rest,true', 'required', 'date_format:H:i'],
            'ends' => ['exclude_if:rest,true', 'required', 'date_format:H:i', 'different:starts'],
        ]);
        $employee = Employee::findOrFail((int) $data['employee_id']);
        $rest = (bool) $data['rest'];

        WorkSchedule::updateOrCreate(
            ['employee_id' => $employee->id, 'work_date' => $data['date']],
            [
                'branch_id' => $employee->branch_id,
                'is_rest_day' => $rest,
                'starts_at' => $rest ? null : $data['starts'],
                'ends_at' => $rest ? null : $data['ends'],
                'created_by' => $request->user()->id,
            ],
        );

        return back();
    }

    public function clear(Request $request): RedirectResponse
    {
        $data = $request->validate(['employee_id' => ['required', 'integer'], 'date' => ['required', 'date_format:Y-m-d']]);
        WorkSchedule::where('employee_id', $data['employee_id'])->whereDate('work_date', $data['date'])->delete();

        return back();
    }

    /** Repeat last week's rota into this week, leaving any day already planned alone. */
    public function copyLastWeek(Request $request): RedirectResponse
    {
        $start = $this->monday($request->input('week'));
        $copied = 0;

        foreach (WorkSchedule::whereBetween('work_date', [$start->subWeek()->toDateString(), $start->subDay()->toDateString()])->get() as $s) {
            $date = $s->work_date->copy()->addWeek()->toDateString();
            if (! WorkSchedule::where('employee_id', $s->employee_id)->whereDate('work_date', $date)->exists()) {
                WorkSchedule::create($s->only(['employee_id', 'branch_id', 'is_rest_day', 'starts_at', 'ends_at']) + [
                    'work_date' => $date,
                    'created_by' => $request->user()->id,
                ]);
                $copied++;
            }
        }

        return back()->with('success', $copied ? "Copied {$copied} shifts from last week." : 'Nothing to copy: this week is already planned.');
    }

    private function monday(?string $date): CarbonImmutable
    {
        $day = rescue(fn () => CarbonImmutable::parse($date ?? 'today'), CarbonImmutable::today(), false);

        return $day->startOfWeek(CarbonImmutable::MONDAY);
    }
}
