<?php

use App\Models\Post;
use App\Models\Promotion;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    $this->owner = User::where('email', 'owner@glutarepubliq.com')->firstOrFail();
});

afterEach(function () {
    File::deleteDirectory(public_path('media/photos/promotions'));
    File::deleteDirectory(public_path('media/photos/journal'));
});

it('adds a package with a photo and shows it on the promotions page', function () {
    $this->actingAs($this->owner)->get('/admin/promotions')->assertOk();

    $this->actingAs($this->owner)->post('/admin/promotions', [
        'title' => 'Underarm IPL 6 sessions',
        'summary' => 'Six sessions for the price of five.',
        'description' => 'A series works better than a single visit.',
        'details' => "6 sessions: 5,000 pesos\n\n12 sessions: 9,000 pesos",
        'is_active' => '1',
        'photo' => UploadedFile::fake()->image('ipl.jpg', 800, 600),
    ])->assertRedirect()->assertSessionHasNoErrors();

    $promo = Promotion::where('title', 'Underarm IPL 6 sessions')->sole();
    expect($promo->details)->toBe(['6 sessions: 5,000 pesos', '12 sessions: 9,000 pesos'])
        ->and($promo->getRawOriginal('image'))->toStartWith('/media/photos/promotions/');

    $this->get('/promotions')->assertOk()->assertSee('Underarm IPL 6 sessions');

    $this->actingAs($this->owner)->post("/admin/promotions/{$promo->id}", [
        'title' => $promo->title, 'summary' => $promo->summary, 'description' => $promo->description, 'is_active' => '0',
    ])->assertRedirect()->assertSessionHasNoErrors();
    // Asserted on the list itself: the flash message still names it on this request.
    $this->get('/promotions')->assertInertia(fn ($page) => $page->where('promotions', fn ($list) => collect($list)->pluck('title')->doesntContain('Underarm IPL 6 sessions')));
});

it('writes a journal post and publishes it', function () {
    $this->actingAs($this->owner)->get('/admin/journal')->assertOk();

    $this->actingAs($this->owner)->post('/admin/journal', [
        'title' => 'Caring for skin after a peel',
        'category' => 'Skin care',
        'excerpt' => 'What to do in the first week.',
        'body' => "Keep it simple.\n\nUse sunscreen every day.",
        'takeaways' => "Sunscreen daily\nNo scrubs for a week",
        'author_name' => 'Gluta RepubliQ clinical team',
        'is_published' => '1',
    ])->assertRedirect()->assertSessionHasNoErrors();

    $post = Post::where('slug', 'caring-for-skin-after-a-peel')->sole();
    expect($post->takeaways)->toHaveCount(2)->and($post->read_minutes)->toBe(1);
    $this->get('/journal/caring-for-skin-after-a-peel')->assertOk()->assertSee('Use sunscreen every day.');
});

it('keeps the content screens away from front desk staff', function () {
    $reception = User::where('email', 'reception@glutarepubliq.com')->firstOrFail();

    $this->actingAs($reception)->get('/admin/promotions')->assertForbidden();
    $this->actingAs($reception)->post('/admin/journal', ['title' => 'x'])->assertForbidden();
});
