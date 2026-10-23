<?php

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\PageView;
use App\Models\User;
use App\Models\WorkSchedule;
use Carbon\CarbonImmutable;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    Storage::fake('local');
});

function kioskOwner(): User
{
    return User::where('email', 'owner@glutarepubliq.com')->firstOrFail();
}

function kioskPerson(): Employee
{
    $person = Employee::active()->whereNull('user_id')->orderBy('id')->firstOrFail();
    $person->forceFill(['kiosk_pin' => Hash::make('4321')])->save();
    Attendance::where('employee_id', $person->id)->whereDate('work_date', today())->delete();

    return $person;
}

it('clocks a person in with a photo and out again at the branch kiosk', function () {
    $this->travelTo(today()->setTime(9, 12));
    $person = kioskPerson();
    WorkSchedule::updateOrCreate(['employee_id' => $person->id, 'work_date' => today()->toDateString()], ['is_rest_day' => false, 'starts_at' => '09:00', 'ends_at' => '18:00']);

    $this->get('/kiosk/cebu')->assertOk()->assertInertia(fn ($p) => $p->component('kiosk/show'));

    // No photo, no clock-in.
    $this->post('/kiosk/cebu', ['employee_no' => $person->employee_no, 'pin' => '4321'])->assertSessionHasErrors('photo');

    $this->post('/kiosk/cebu', [
        'employee_no' => $person->employee_no,
        'pin' => '4321',
        'photo' => UploadedFile::fake()->image('me.jpg', 640, 480),
    ])->assertSessionHasNoErrors()->assertSessionHas('success');

    $day = Attendance::where('employee_id', $person->id)->whereDate('work_date', today())->sole();
    expect($day->source)->toBe('kiosk')->and($day->photo_in)->not->toBeNull();
    Storage::disk('local')->assertExists($day->photo_in);

    $this->travelTo(today()->setTime(19, 12));
    $this->post('/kiosk/cebu', ['employee_no' => $person->employee_no, 'pin' => '4321'])->assertSessionHasNoErrors();
    // Planned nine hours, worked ten: one hour of overtime.
    expect($day->fresh()->time_out)->not->toBeNull()->and($day->fresh()->overtime_minutes)->toBe(60);

    // The time record marks them late, past the five-minute grace.
    $this->actingAs(kioskOwner())->get('/admin/hr/attendance')->assertInertia(fn ($p) => $p
        ->where('rows', fn ($rows) => collect($rows)->firstWhere('id', $person->id)['status'] === 'late'
            && collect($rows)->firstWhere('id', $person->id)['late_minutes'] === 7));
});

it('refuses a wrong PIN and locks out after five tries', function () {
    $person = kioskPerson();

    foreach (range(1, 5) as $i) {
        $this->post('/kiosk/cebu', ['employee_no' => $person->employee_no, 'pin' => '0000'])->assertSessionHasErrors('pin');
    }
    $this->post('/kiosk/cebu', ['employee_no' => $person->employee_no, 'pin' => '4321', 'photo' => UploadedFile::fake()->image('me.jpg')])
        ->assertSessionHasErrors(['pin' => 'Too many tries. Wait 5 minutes or ask HR.']);

    expect(Attendance::where('employee_id', $person->id)->whereDate('work_date', today())->exists())->toBeFalse();
});

it('lets HR set a kiosk PIN without ever showing it', function () {
    $person = Employee::active()->firstOrFail();

    $this->actingAs(kioskOwner())->patch("/admin/hr/employees/{$person->id}/kiosk-pin", ['pin' => '98765'])->assertSessionHasNoErrors();

    expect(Hash::check('98765', $person->fresh()->kiosk_pin))->toBeTrue();
    $this->get("/admin/hr/employees/{$person->id}")->assertInertia(fn ($p) => $p->where('employee.has_kiosk_pin', true)->missing('employee.kiosk_pin'));
});

it('plans a week, including a night shift, and copies it forward', function () {
    $person = Employee::active()->firstOrFail();
    $monday = CarbonImmutable::today()->startOfWeek(CarbonImmutable::MONDAY)->addWeeks(3);
    $this->actingAs(kioskOwner());

    $this->post('/admin/hr/schedule', ['employee_id' => $person->id, 'date' => $monday->toDateString(), 'rest' => false, 'starts' => '22:00', 'ends' => '06:00'])
        ->assertSessionHasNoErrors();
    $this->post('/admin/hr/schedule', ['employee_id' => $person->id, 'date' => $monday->addDay()->toDateString(), 'rest' => true])
        ->assertSessionHasNoErrors();

    $night = WorkSchedule::where('employee_id', $person->id)->whereDate('work_date', $monday)->sole();
    expect($night->minutes())->toBe(480);

    $this->post('/admin/hr/schedule/copy', ['week' => $monday->addWeek()->toDateString()])->assertSessionHas('success');
    expect(WorkSchedule::where('employee_id', $person->id)->whereDate('work_date', $monday->addWeek())->value('starts_at'))->toStartWith('22:00')
        ->and((bool) WorkSchedule::where('employee_id', $person->id)->whereDate('work_date', $monday->addWeek()->addDay())->value('is_rest_day'))->toBeTrue();

    $this->get('/admin/hr/schedule?week='.$monday->toDateString())->assertOk()
        ->assertInertia(fn ($p) => $p->component('admin/hr/schedule')->has("grid.{$person->id}|{$monday->toDateString()}"));

    $this->actingAs(User::where('email', 'reception@glutarepubliq.com')->firstOrFail())
        ->post('/admin/hr/schedule', ['employee_id' => $person->id, 'date' => $monday->toDateString(), 'rest' => true])->assertForbidden();
});

it('logs public page views without keeping an IP address, and skips staff and bots', function () {
    $this->withHeader('User-Agent', 'Mozilla/5.0 (iPhone)')->get('/treatments?utm_source=facebook&utm_campaign=glow-promo')->assertOk();
    $this->withHeader('User-Agent', 'Googlebot/2.1')->get('/about')->assertOk();
    $this->actingAs(kioskOwner())->get('/about')->assertOk();

    $view = PageView::sole();
    expect($view->path)->toBe('/treatments')
        ->and($view->source)->toBe('facebook')
        ->and($view->campaign)->toBe('glow-promo')
        ->and($view->device)->toBe('mobile')
        ->and(strlen($view->visitor))->toBe(16)
        ->and(json_encode($view->getAttributes()))->not->toContain('127.0.0.1');

    $this->get('/admin/traffic')->assertOk()->assertInertia(fn ($p) => $p
        ->component('admin/traffic/index')
        ->where('totals.views', 1)
        ->where('sources.0.label', 'facebook'));

    $this->actingAs(User::where('email', 'reception@glutarepubliq.com')->firstOrFail())->get('/admin/traffic')->assertForbidden();
});
