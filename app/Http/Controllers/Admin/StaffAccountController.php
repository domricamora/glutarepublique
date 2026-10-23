<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Who can sign in to the clinic system and what each person may open.
 *
 * The role decides the screens (each route is gated on a permission, so a
 * cashier who types an inventory url still gets a 403). Super Admin is the
 * developer's platform account and is neither listed nor assignable here.
 */
class StaffAccountController extends Controller
{
    /** @return list<string> roles a clinic can hand out; Super Admin and Patient are not staff roles */
    public static function assignableRoles(): array
    {
        return array_values(array_diff(array_keys(RolesAndPermissionsSeeder::ROLES), ['Super Admin', 'Patient']));
    }

    public function index(Request $request): Response
    {
        return Inertia::render('admin/accounts/index', [
            'accounts' => $this->accounts()->with('roles')->orderBy('name')->get()
                ->map(fn (User $u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'role' => $u->getRoleNames()->first(),
                    'two_factor' => $u->two_factor_confirmed_at !== null,
                    'disabled' => $u->disabled_at !== null,
                    'created_at' => $u->created_at?->toDateString(),
                ]),
            'roles' => collect(self::assignableRoles())->map(fn (string $role) => [
                'name' => $role,
                'permissions' => RolesAndPermissionsSeeder::ROLES[$role],
            ]),
            'me' => $request->user()->id,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')],
            'role' => ['required', Rule::in(self::assignableRoles())],
            'password' => ['required', 'string', 'min:10', Password::defaults()],
        ]);

        $user = new User;
        // Created by the clinic, so the address is taken as confirmed.
        $user->forceFill([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'organization_id' => Organization::current()?->id,
            'email_verified_at' => now(),
        ])->save();
        $user->syncRoles($data['role']);

        AuditLog::record('settings.account.create', "Created an account for {$user->name} as {$data['role']}", $user, ['role' => $data['role']]);

        return back()->with('success', "{$user->name} can now sign in with {$user->email}.");
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($this->accounts()->whereKey($user->id)->exists(), 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'role' => ['required', Rule::in(self::assignableRoles())],
            'disabled' => ['required', 'boolean'],
            'password' => ['nullable', 'string', 'min:10', Password::defaults()],
        ]);

        // Nobody locks themselves out from this screen.
        if ($user->is($request->user()) && ($data['disabled'] || ! $user->hasRole($data['role']))) {
            return back()->withErrors(['role' => 'You cannot change your own role or switch off your own account.']);
        }

        $role = $user->getRoleNames()->first();
        $wasDisabled = $user->disabled_at !== null;
        $password = filled($data['password'] ?? null) ? $data['password'] : null;

        $user->forceFill([
            'name' => $data['name'],
            'disabled_at' => $data['disabled'] ? ($user->disabled_at ?? now()) : null,
        ] + ($password ? ['password' => $password] : []))->save();
        $user->syncRoles($data['role']);

        if ($role !== $data['role']) {
            AuditLog::record('settings.account.role', "Changed {$user->name} from ".($role ?? 'no role')." to {$data['role']}", $user, ['from' => $role, 'to' => $data['role']]);
        }
        if ($wasDisabled !== (bool) $data['disabled']) {
            AuditLog::record('settings.account.'.($data['disabled'] ? 'disable' : 'enable'), ($data['disabled'] ? 'Switched off ' : 'Switched on ').$user->name, $user);
        }
        if ($password) {
            AuditLog::record('settings.account.password', "Set a new password for {$user->name}", $user);
        }

        return back()->with('success', "Saved {$user->name}.");
    }

    /** @return Builder<User> */
    private function accounts(): Builder
    {
        return User::where('organization_id', Organization::current()?->id);
    }
}
