<?php

namespace App\Providers;

use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\User;
use App\Payments\PaymentManager;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Pennant\Feature;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Guests and platform admins (no organization) see the clinic this install serves.
        // ponytail: one organization per user; add a switcher when users span organizations.
        $this->app->scoped('organization.default', fn () => Organization::where('slug', config('clinic.organization'))->first());

        $this->app->singleton(PaymentManager::class, fn ($app) => new PaymentManager($app['config']->get('payments', [])));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureAccess();
        $this->auditSignIns();
    }

    /** Sign-ins, sign-outs and failed attempts go in the audit log (plan.md §31). */
    protected function auditSignIns(): void
    {
        // Every entry belongs to a clinic; with none set up yet (a fresh install) there is nothing to log against.
        $audit = fn (string $action, string $description) => Organization::current() && AuditLog::record($action, $description);

        Event::listen(Login::class, fn (Login $e) => $audit('auth.login', "{$e->user->name} signed in"));
        Event::listen(Logout::class, fn (Logout $e) => $e->user && $audit('auth.logout', "{$e->user->name} signed out"));
        // The address tried is kept, the password never is.
        Event::listen(Failed::class, fn (Failed $e) => $audit(
            'auth.failed',
            'Failed sign-in for '.str((string) ($e->credentials['email'] ?? 'unknown'))->limit(120),
        ));
    }

    /**
     * Super Admin passes every gate; module flags resolve per organization.
     */
    protected function configureAccess(): void
    {
        Gate::before(fn (User $user) => $user->hasRole('Super Admin') ? true : null);

        Feature::resolveScopeUsing(fn () => Organization::current());

        foreach (config('clinic.modules') as $module => $default) {
            Feature::define($module, fn () => $default);
        }
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
