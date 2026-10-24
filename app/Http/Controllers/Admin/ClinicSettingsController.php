<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ClinicSetting;
use App\Support\ClientContact;
use App\Support\ClinicEvents;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The settings the clinic owns: where it is on social media, and whether the
 * chat is on. These are the clinic's to change, so they are edited in the
 * office rather than in a config file a deployment would overwrite.
 */
class ClinicSettingsController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('admin/settings/index', [
            // Webhooks can carry client contacts, so they are the owner's to set up.
            'webhooks' => ClientContact::canSee($request->user()) ? [
                'url' => ClinicSetting::get('webhooks.url'),
                'secret' => ClinicSetting::get('webhooks.secret'),
                'events' => array_values(array_filter(explode(',', (string) ClinicSetting::get('webhooks.events', implode(',', array_keys(ClinicEvents::EVENTS)))))),
                'options' => ClinicEvents::EVENTS,
                'last' => ClinicSetting::get('webhooks.last'),
            ] : null,
            'socials' => collect(ClinicSetting::SOCIALS)
                ->map(fn (string $base, string $platform) => [
                    'platform' => $platform,
                    'label' => ucfirst($platform),
                    'base' => $base,
                    'handle' => ClinicSetting::get('social.'.$platform),
                ])
                ->values()
                ->all(),
            'chat_enabled' => ClinicSetting::flag('chat.enabled'),
            'chat_greeting' => ClinicSetting::get('chat.greeting', 'Hello. How can we help?'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'socials' => ['nullable', 'array'],
            'socials.*' => ['nullable', 'string', 'max:200'],
            'chat_enabled' => ['nullable', 'boolean'],
            'chat_greeting' => ['nullable', 'string', 'max:200'],
            'webhook_url' => ['nullable', 'url:https', 'max:300'],
            'webhook_events' => ['nullable', 'array'],
            'webhook_events.*' => [Rule::in(array_keys(ClinicEvents::EVENTS))],
        ], [
            'webhook_url.url' => 'Enter the full https:// address the events should go to.',
            'socials.*.max' => 'That link is too long to be a profile URL.',
        ]);

        foreach (ClinicSetting::SOCIALS as $platform => $base) {
            ClinicSetting::put(
                'social.'.$platform,
                ClinicSetting::normaliseHandle($data['socials'][$platform] ?? null),
            );
        }

        ClinicSetting::put('chat.enabled', ($data['chat_enabled'] ?? false) ? '1' : null);
        ClinicSetting::put('chat.greeting', $data['chat_greeting'] ?? null);

        if (ClientContact::canSee($request->user()) && $request->has('webhook_url')) {
            $url = $data['webhook_url'] ?? null;
            if ($url !== ClinicSetting::get('webhooks.url')) {
                AuditLog::record('settings.webhook', $url ? "Webhook pointed at {$url}" : 'Webhook switched off');
            }
            ClinicSetting::put('webhooks.url', $url);
            ClinicSetting::put('webhooks.events', implode(',', $data['webhook_events'] ?? []));
            // The signing key is made once, the first time a webhook is set up.
            if ($url && blank(ClinicSetting::get('webhooks.secret'))) {
                ClinicSetting::put('webhooks.secret', Str::random(40));
            }
        }

        return back()->with('success', 'Clinic settings saved. The website picks them up on its next load.');
    }

    /** Sends a sample event to the webhook straight away and reports what came back. */
    public function testWebhook(): RedirectResponse
    {
        $result = ClinicEvents::webhook('test', ['message' => 'A test from the clinic office.'], now: true);

        return back()->with('success', $result ? "Test sent. {$result}." : 'Set a webhook address and save it first.');
    }
}
