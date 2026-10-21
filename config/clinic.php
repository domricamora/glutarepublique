<?php

/*
| Brand and business settings for Gluta RepubliQ Aesthetics.
*/

return [

    // Organization slug the public website belongs to.
    'organization' => env('CLINIC_ORGANIZATION', 'glutarepubliq'),

    'name' => env('CLINIC_NAME', 'Gluta RepubliQ Aesthetics'),
    'short_name' => env('CLINIC_SHORT_NAME', 'Gluta RepubliQ'),
    'tagline' => env('CLINIC_TAGLINE', 'Hindi mahal ang magpaganda. Mag-Gluta RepubliQ ka na.'),

    /*
     * Brand marks in public/media/brand
     */
    'logo' => env('CLINIC_LOGO', 'media/brand/logo.webp'),
    'logo_fallback' => env('CLINIC_LOGO_FALLBACK', 'media/brand/logo.png'),
    'logo_light' => env('CLINIC_LOGO_LIGHT', 'media/brand/logo-light.webp'),

    /*
     * Brand palette according to Gluta Republiq brand specifications.
     */
    'colors' => [
        'deep_ocean_navy' => '#123B5D',
        'royal_clinical_blue' => '#2D668F',
        'gluta_aqua' => '#6AB5B1',
        'soft_aqua' => '#A7D8D2',
        'ice_blue' => '#DCECF4',
        'pure_white' => '#FFFFFF',
        'pearl_white' => '#F7FAFA',
        'champagne_mist' => '#D8C5A4',
        'deep_charcoal' => '#26343B',
        // Legacy system mappings for admin / UI consistency
        'primary' => '#2D668F',
        'primary_dark' => '#123B5D',
        'blush' => '#DCECF4',
        'ivory' => '#FFFFFF',
        'plum' => '#123B5D',
        'champagne' => '#D8C5A4',
        'text' => '#26343B',
        'soft_rose' => '#F7FAFA',
        'rose_ink' => '#2D668F',
    ],

    'contact' => [
        'address' => env('CLINIC_ADDRESS', 'Robinsons Galleria Cebu II, General Maxilom Ave Ext, Cebu City'),
        'phone' => env('CLINIC_PHONE', '+63 995 510 9095'),
        'email' => env('CLINIC_EMAIL', 'info@glutarepubliq.com'),
    ],

    // As linked from glutarepubliq.com. No TikTok account is published there.
    'social' => [
        'facebook' => env('CLINIC_FACEBOOK', 'https://www.facebook.com/Gluta-RepubliQ-566381640410768'),
        'instagram' => env('CLINIC_INSTAGRAM', 'https://www.instagram.com/gluta_republiq'),
        'tiktok' => env('CLINIC_TIKTOK'),
    ],

    'currency' => env('CLINIC_CURRENCY', 'PHP'),
    'currency_symbol' => env('CLINIC_CURRENCY_SYMBOL', '₱'),
    'timezone' => env('CLINIC_TIMEZONE', 'Asia/Manila'),

    'hours' => [
        'mon-fri' => '10:00-20:00',
        'sat' => '10:00-19:00',
        'sun' => '11:00-18:00',
    ],

    // Default for each module flag (Laravel Pennant, per organization).
    'modules' => [
        'booking' => true,
        'crm' => true,
        'pos' => true,
        'inventory' => true,
        'accounting' => true,
        'payroll' => true,
        'ai' => true,
        'multi_branch' => true,
    ],

];
