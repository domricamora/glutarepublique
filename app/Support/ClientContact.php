<?php

namespace App\Support;

use App\Models\User;

/**
 * Client phone numbers and emails, masked for staff who may not hold them
 * (owner, 2026-10-24: conflict-of-interest safeguard). Only `clients.contacts`
 * (the owner) sees them in full; anyone else reveals one client at a time
 * from the lead page, and every reveal is in the audit log.
 */
final class ClientContact
{
    public static function canSee(?User $user = null): bool
    {
        return (bool) ($user ?? auth()->user())?->can('clients.contacts');
    }

    /** "0917 ••• ••34": enough to recognise a number, not enough to call it. */
    public static function phone(?string $phone, ?User $user = null): ?string
    {
        if (blank($phone) || self::canSee($user)) {
            return $phone;
        }

        $digits = preg_replace('/\D/', '', $phone);

        return strlen($digits) < 6 ? '•••' : substr($digits, 0, 4).' ••• ••'.substr($digits, -2);
    }

    /** "a•••@gmail.com" */
    public static function email(?string $email, ?User $user = null): ?string
    {
        if (blank($email) || self::canSee($user)) {
            return $email;
        }

        [$name, $domain] = array_pad(explode('@', $email, 2), 2, '');

        return mb_substr($name, 0, 1).'•••@'.$domain;
    }
}
