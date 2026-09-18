<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Legal Page Details
    |--------------------------------------------------------------------------
    |
    | Rendered into the public privacy policy and terms of service pages. Meta
    | reviewers and app users read these, so the contact details must be real
    | and monitored.
    |
    */

    'company' => env('LEGAL_COMPANY_NAME', 'Al-Jabali'),
    'email' => env('LEGAL_CONTACT_EMAIL', 'info@al-jabali.com'),
    'phone' => env('LEGAL_CONTACT_PHONE'),
    'address' => env('LEGAL_ADDRESS', 'Amman, Jordan'),
    'effective_date' => env('LEGAL_EFFECTIVE_DATE', '2026-07-26'),

];
