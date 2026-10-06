<?php

use App\Models\ClinicSetting;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

function office(): User
{
    $user = User::where('email', 'owner@glutarepubliq.com')->firstOrFail();

    return $user;
}

it('starts with the configured social links on the footer', function () {
    expect(ClinicSetting::socials())->toBe([
        'facebook' => 'https://facebook.com/GlutaRepubliQ',
        'instagram' => 'https://instagram.com/glutarepubliq',
        'tiktok' => 'https://tiktok.com/@glutarepubliq',
    ]);
});

it('saves a social handle and puts it on every public page', function () {
    $this->actingAs(office())->patch(route('admin.settings.clinic.update'), [
        'socials' => ['instagram' => 'glutarepubliq'],
    ])->assertRedirect()->assertSessionHas('success');

    // Asserted on the prop rather than the markup: the page carries it as
    // JSON, where the slashes are escaped, so neither the escaped nor the
    // unescaped text is what actually appears in the response.
    $this->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where(
            'socials.instagram',
            'https://instagram.com/glutarepubliq',
        ));
});

it('accepts a full profile link as well as a handle', function () {
    // Somebody will paste the url from their browser bar. Without this the
    // footer would read facebook.com/https://facebook.com/x.
    $this->actingAs(office())->patch(route('admin.settings.clinic.update'), [
        'socials' => ['facebook' => 'https://www.facebook.com/GlutaRepubliQ', 'instagram' => ' ', 'tiktok' => ' '],
    ]);

    expect(ClinicSetting::socials())->toBe([
        'facebook' => 'https://facebook.com/GlutaRepubliQ',
    ]);
});

it('drops a platform the office cleared', function () {
    // A blank box still wins over the shipped config handle: clearing the
    // box is the office saying "show nothing here".
    $this->actingAs(office())->patch(route('admin.settings.clinic.update'), [
        'socials' => ['facebook' => ' ', 'instagram' => 'glutarepubliq', 'tiktok' => 'glutarepubliq'],
    ]);

    expect(ClinicSetting::socials())->toHaveCount(2);

    $this->actingAs(office())->patch(route('admin.settings.clinic.update'), [
        'socials' => ['facebook' => ' ', 'instagram' => 'glutarepubliq', 'tiktok' => ' '],
    ]);

    expect(ClinicSetting::socials())->toBe([
        'instagram' => 'https://instagram.com/glutarepubliq',
    ]);
});

it('refuses a handle that would build a broken link', function () {
    $this->actingAs(office())->patch(route('admin.settings.clinic.update'), [
        'socials' => ['facebook' => '', 'instagram' => '', 'tiktok' => ''],
    ]);

    expect(ClinicSetting::socials())->toBe([]);

    $this->actingAs(office())->patch(route('admin.settings.clinic.update'), [
        'socials' => ['facebook' => '', 'instagram' => 'has spaces and <script>', 'tiktok' => ''],
    ]);

    // The invalid handle is dropped along with the cleared platforms.
    expect(ClinicSetting::socials())->toBe([]);
});

it('keeps the settings screen behind the settings permission', function () {
    $desk = User::where('email', 'reception@glutarepubliq.com')->firstOrFail();

    $this->actingAs($desk)->get(route('admin.settings.clinic.index'))->assertForbidden();
    $this->actingAs($desk)->patch(route('admin.settings.clinic.update'), [])->assertForbidden();
});

it('gives each clinic its own handles', function () {
    $this->actingAs(office())->patch(route('admin.settings.clinic.update'), [
        'socials' => ['facebook' => '', 'instagram' => 'glutarepubliq', 'tiktok' => ''],
    ]);

    $other = Organization::create(['name' => 'Other Clinic', 'slug' => 'other-clinic']);
    $otherUser = User::factory()->create(['organization_id' => $other->id]);
    $otherUser->assignRole('Clinic Administrator');

    $this->actingAs($otherUser)->get('/')
        ->assertOk()
        ->assertDontSee('instagram.com/glutarepubliq');
});
