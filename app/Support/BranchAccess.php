<?php

namespace App\Support;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

/**
 * Which branch an office screen shows (owner, 2026-10-24).
 *
 * Someone with `branches.all` (owner, administrators, accounts, HR, stock and
 * marketing) chooses a branch in the sidebar, or "All branches"; the choice
 * lives in the session. Everyone else works at their home branch, the branch
 * of the employee record linked to their login, and cannot look elsewhere: a
 * branch they ask for in the address bar is replaced by their own.
 */
final class BranchAccess
{
    public const SESSION_KEY = 'branch_id';

    public static function canSeeAll(?User $user): bool
    {
        return (bool) $user?->can('branches.all');
    }

    /** The branch of the employee linked to this login, if any. */
    public static function home(?User $user): ?int
    {
        if (! $user) {
            return null;
        }

        $id = Employee::where('user_id', $user->id)->value('branch_id');

        return $id === null ? null : (int) $id;
    }

    /** The branch the screens default to: the chosen one (null = all), or the person's own. */
    public static function current(Request $request): ?int
    {
        $user = $request->user();

        if (! self::canSeeAll($user)) {
            return self::home($user);
        }

        $chosen = $request->session()->get(self::SESSION_KEY);

        return $chosen !== null && Branch::whereKey($chosen)->exists() ? (int) $chosen : null;
    }

    /**
     * The branch a screen should show for a request that may name one: anyone
     * who sees every branch gets what they asked for (or their current choice);
     * everyone else gets their own branch, whatever they asked for.
     */
    public static function resolve(Request $request, mixed $asked = null): ?int
    {
        if (! self::canSeeAll($request->user())) {
            return self::home($request->user());
        }

        return filled($asked) ? (int) $asked : self::current($request);
    }

    /** Like resolve(), for a screen that always needs one branch (a till, a shelf). */
    public static function single(Request $request, mixed $asked = null): Branch
    {
        $id = self::resolve($request, $asked);

        return ($id ? Branch::find($id) : null)
            ?? Branch::where('is_active', true)->orderBy('id')->firstOrFail();
    }

    /**
     * Branches this person may pick from, for selectors.
     *
     * @return Collection<int, Branch>
     */
    public static function options(?User $user): Collection
    {
        $query = Branch::where('is_active', true)->orderBy('id');

        if (! self::canSeeAll($user)) {
            $query->whereKey(self::home($user) ?? 0);
        }

        return $query->get(['id', 'name', 'city']);
    }
}
