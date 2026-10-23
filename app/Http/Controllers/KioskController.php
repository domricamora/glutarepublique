<?php

namespace App\Http\Controllers;

use App\Actions\Hr\ClockPunch;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The branch tablet (DTR kiosk). Nobody signs in: staff punch with their
 * employee number and PIN, and a photo when they clock in, so a colleague
 * cannot clock in for them. The tablet is bookmarked at /kiosk/{branch}.
 */
class KioskController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('kiosk/index', [
            'branches' => Branch::where('is_active', true)->orderBy('id')->get(['id', 'name', 'slug']),
        ]);
    }

    public function show(string $branch): Response
    {
        return Inertia::render('kiosk/show', [
            'branch' => Branch::where('slug', $branch)->firstOrFail()->only(['name', 'slug']),
        ]);
    }

    public function punch(Request $request, string $branch, ClockPunch $punch): RedirectResponse
    {
        $branch = Branch::where('slug', $branch)->firstOrFail();
        $data = $request->validate([
            'employee_no' => ['required', 'string', 'max:20'],
            'pin' => ['required', 'digits_between:4,6'],
            'photo' => ['nullable', 'image', 'max:8192'],
        ]);

        $key = 'kiosk:'.$branch->id.':'.mb_strtolower($data['employee_no']);
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['pin' => 'Too many tries. Wait '.ceil(RateLimiter::availableIn($key) / 60).' minutes or ask HR.']);
        }

        $employee = Employee::active()->where('employee_no', $data['employee_no'])->first();
        if (! $employee?->kiosk_pin || ! Hash::check($data['pin'], $employee->kiosk_pin)) {
            RateLimiter::hit($key, 300);
            throw ValidationException::withMessages(['pin' => 'Employee number or PIN is wrong.']);
        }
        RateLimiter::clear($key);

        // A night shift that began yesterday is clocked out against yesterday.
        $opened = fn (string $date) => Attendance::where('employee_id', $employee->id)->whereDate('work_date', $date)->whereNotNull('time_in');
        $yesterday = now()->subDay()->toDateString();
        $day = ! $opened(now()->toDateString())->exists() && $opened($yesterday)->whereNull('time_out')->exists()
            ? $yesterday
            : now()->toDateString();
        $clockingIn = ! $opened($day)->exists();

        $photo = null;
        if ($clockingIn) {
            if (! $request->hasFile('photo')) {
                throw ValidationException::withMessages(['photo' => 'Take a photo to clock in.']);
            }
            $photo = $request->file('photo')->storeAs('kiosk/'.now()->format('Y-m'), Str::uuid().'.jpg', 'local');
        }

        $attendance = $punch($employee, $day, 'kiosk', $photo ?: null);
        $first = Str::before($employee->name, ' ');

        return back()->with('success', $clockingIn
            ? "Good to see you, {$first}. Clocked in at ".substr((string) $attendance->time_in, 0, 5).'.'
            : "Goodbye, {$first}. Clocked out at ".substr((string) $attendance->time_out, 0, 5).'.');
    }
}
