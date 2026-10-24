<?php

namespace App\Support;

use App\Models\ClinicSetting;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\ClinicAlert;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * Something happened that someone should hear about. Two ways out:
 *
 * - An in-app alert (the bell in the office header) for the staff whose job
 *   it is, by permission; staff tied to a branch only hear about their own.
 *   Alerts carry names and services, never phone numbers or emails.
 * - A webhook, if the owner set one up in Settings: the event as signed JSON
 *   to their own system (Zapier, a CRM, a group chat bot). Sent after the
 *   response so a slow receiver never slows the website, and never retried:
 *   the last result shows in Settings.
 */
final class ClinicEvents
{
    public const EVENTS = [
        'lead.created' => 'New enquiry from the website or chat',
        'appointment.booked' => 'New online booking',
        'sale.completed' => 'Sale rung up',
        'stock.discrepancy' => 'Stock records disagree',
    ];

    /**
     * @param  array<string, mixed>  $payload  what the webhook receives
     * @param  string|null  $permission  who gets the in-app alert; null sends none
     */
    public static function fire(string $event, string $title, string $body, ?string $url, array $payload, ?string $permission, ?int $branchId = null): void
    {
        if ($permission !== null) {
            $people = User::query()
                ->where('organization_id', Organization::current()?->id)
                ->get()
                ->filter(fn (User $u) => $u->can($permission)
                    && ($branchId === null || BranchAccess::canSeeAll($u) || in_array(BranchAccess::home($u), [null, $branchId], true)));

            Notification::send($people, new ClinicAlert($event, $title, $body, $url));
        }

        self::webhook($event, $payload);
    }

    /** @param array<string, mixed> $payload */
    public static function webhook(string $event, array $payload, bool $now = false): ?string
    {
        $target = ClinicSetting::get('webhooks.url');
        $wanted = array_filter(explode(',', (string) ClinicSetting::get('webhooks.events', implode(',', array_keys(self::EVENTS)))));

        if (blank($target) || (! $now && ! in_array($event, $wanted, true))) {
            return null;
        }

        $body = json_encode(['event' => $event, 'sent_at' => now()->toIso8601String(), 'clinic' => config('clinic.name'), 'data' => $payload], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $signature = 'sha256='.hash_hmac('sha256', (string) $body, (string) ClinicSetting::get('webhooks.secret'));

        $send = function () use ($target, $event, $body, $signature): string {
            try {
                $status = Http::timeout(5)
                    ->withHeaders(['X-Clinic-Event' => $event, 'X-Clinic-Signature' => $signature])
                    ->withBody((string) $body, 'application/json')
                    ->post($target)
                    ->status();
                $result = "{$event}: HTTP {$status}";
            } catch (Throwable $e) {
                $result = "{$event}: failed ({$e->getMessage()})";
            }

            ClinicSetting::put('webhooks.last', now()->format('Y-m-d H:i').' '.mb_substr($result, 0, 160));

            return $result;
        };

        if ($now) {
            return $send();
        }

        dispatch($send)->afterResponse();

        return null;
    }
}
