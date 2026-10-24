<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'whatsapp' => [
        'enabled' => (bool) env('WHATSAPP_ENABLED', false),

        /*
        | "meta" posts straight to the WhatsApp Cloud API. "twilio" sends through
        | Twilio's Messages API, which bills WhatsApp fees on the Twilio invoice
        | instead of requiring a payment method on the Meta business account.
        */
        'provider' => env('WHATSAPP_PROVIDER', 'meta'),

        'twilio' => [
            'account_sid' => env('TWILIO_ACCOUNT_SID'),
            'auth_token' => env('TWILIO_AUTH_TOKEN'),

            // The WhatsApp-enabled sender registered in Twilio, e.g. +962797850865.
            'from' => env('TWILIO_WHATSAPP_FROM'),

            // Defaults to the named route; override when APP_URL is not the public URL.
            'status_callback' => env('TWILIO_STATUS_CALLBACK_URL'),

            // Set as the sender's "A message comes in" webhook in Twilio. Defaults
            // to the named route; override when APP_URL is not the public URL.
            'inbound_url' => env('TWILIO_INBOUND_URL'),

            /*
            | Twilio sends templates by Content SID (HX…), created in Twilio's
            | Content Template Builder with the same body variables, in the same
            | order, as the Meta templates mapped below.
            */
            'content_sids' => [
                'sales_request_submitted' => env('TWILIO_CONTENT_SALES_SUBMITTED'),
                'sales_request_stage_advanced' => env('TWILIO_CONTENT_SALES_ADVANCED'),
                'sales_request_approved' => env('TWILIO_CONTENT_SALES_APPROVED'),
                'sales_request_rejected' => env('TWILIO_CONTENT_SALES_REJECTED'),
                'sales_request_returned' => env('TWILIO_CONTENT_SALES_RETURNED'),
                'hr_request_submitted' => env('TWILIO_CONTENT_HR_SUBMITTED'),
                'hr_request_approved' => env('TWILIO_CONTENT_HR_APPROVED'),
                'hr_request_rejected' => env('TWILIO_CONTENT_HR_REJECTED'),
                'attendance_check_in_reminder' => env('TWILIO_CONTENT_CHECK_IN_REMINDER'),
                'attendance_check_out_reminder' => env('TWILIO_CONTENT_CHECK_OUT_REMINDER'),
            ],
        ],

        'base_url' => rtrim(env('WHATSAPP_BASE_URL', 'https://graph.facebook.com'), '/'),
        'api_version' => env('WHATSAPP_API_VERSION', 'v21.0'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'token' => env('WHATSAPP_TOKEN'),

        /*
        | "template" sends pre-approved Meta templates and is the only mode that
        | delivers business-initiated messages. "text" sends free-form bodies and
        | only reaches users who messaged the business within the last 24 hours;
        | use it for smoke-testing against a test number.
        */
        'mode' => env('WHATSAPP_MESSAGE_MODE', 'template'),

        'language' => env('WHATSAPP_TEMPLATE_LANGUAGE', 'ar'),

        'default_country_code' => (string) env('WHATSAPP_DEFAULT_COUNTRY_CODE', '966'),

        'timeout' => (int) env('WHATSAPP_TIMEOUT', 10),
        'retries' => (int) env('WHATSAPP_RETRIES', 2),

        /*
        | Shared with Meta when the webhook callback URL is registered. The app
        | secret is optional but, once set, every inbound POST must carry a
        | matching X-Hub-Signature-256 header or it is discarded.
        */
        'webhook_verify_token' => env('WHATSAPP_WEBHOOK_VERIFY_TOKEN'),
        'app_secret' => env('WHATSAPP_APP_SECRET'),

        /*
        | Maps an internal event key to the template name registered in Meta
        | Business Manager. Body parameter order is defined by each notification's
        | toWhatsApp() method and must match the approved template.
        */
        'templates' => [
            'sales_request_submitted' => env('WHATSAPP_TEMPLATE_SALES_SUBMITTED', 'sales_request_submitted'),
            'sales_request_stage_advanced' => env('WHATSAPP_TEMPLATE_SALES_ADVANCED', 'sales_request_stage_advanced'),
            'sales_request_approved' => env('WHATSAPP_TEMPLATE_SALES_APPROVED', 'sales_request_approved'),
            'sales_request_rejected' => env('WHATSAPP_TEMPLATE_SALES_REJECTED', 'sales_request_rejected'),
            'sales_request_returned' => env('WHATSAPP_TEMPLATE_SALES_RETURNED', 'sales_request_returned'),
            'hr_request_submitted' => env('WHATSAPP_TEMPLATE_HR_SUBMITTED', 'hr_request_submitted'),
            'hr_request_approved' => env('WHATSAPP_TEMPLATE_HR_APPROVED', 'hr_request_approved'),
            'hr_request_rejected' => env('WHATSAPP_TEMPLATE_HR_REJECTED', 'hr_request_rejected'),
            'attendance_check_in_reminder' => env('WHATSAPP_TEMPLATE_CHECK_IN_REMINDER', 'attendance_check_in_reminder'),
            'attendance_check_out_reminder' => env('WHATSAPP_TEMPLATE_CHECK_OUT_REMINDER', 'attendance_check_out_reminder'),
        ],

        /*
        | Employees check in and out by sharing their location with the business
        | number. Reminders go out on work days (0 = Sunday … 6 = Saturday) at
        | the given local times; each one is free-form text while the employee's
        | 24-hour session is open, and the approved template otherwise.
        */
        'attendance' => [
            'enabled' => (bool) env('ATTENDANCE_REMINDERS_ENABLED', false),
            'timezone' => env('ATTENDANCE_TIMEZONE', 'Asia/Amman'),
            'work_days' => array_map('intval', array_filter(explode(',', (string) env('ATTENDANCE_WORK_DAYS', '6,0,1,2,3,4')), 'is_numeric')),
            'check_in_reminder_at' => env('ATTENDANCE_CHECK_IN_REMINDER_AT', '07:30'),
            'check_out_reminder_at' => env('ATTENDANCE_CHECK_OUT_REMINDER_AT', '16:30'),
            'excluded_roles' => array_values(array_filter(array_map('trim', explode(',', (string) env('ATTENDANCE_EXCLUDED_ROLES', 'general_manager'))))),
        ],
    ],

];
