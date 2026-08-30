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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
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

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'google_sheets' => [
        'credentials' => env(
            'GOOGLE_SHEETS_CREDENTIALS',
            env('GOOGLE_APPLICATION_CREDENTIALS', storage_path('app/google/service-account.json'))
        ),
        'spreadsheet_id' => env('GOOGLE_SHEETS_SPREADSHEET_ID', '1-8AJdim75RgLGTYD6DPpuKc7Or-5tHwHJzb_qljITp4'),
        'sheet_id' => (int) env('GOOGLE_SHEETS_JOURNAL_SHEET_ID', 1912361038),
        'sheet_name' => env('GOOGLE_SHEETS_JOURNAL_SHEET', 'Nhật ký bán hàng'),
        'inventory_spreadsheet_id' => env('GOOGLE_SHEETS_INVENTORY_SPREADSHEET_ID', '1SLq3sid9Z57jbi3qoAzRiNS4YyK4q5qzm3cWf8-9C6g'),
        'inventory_sheet_id' => (int) env('GOOGLE_SHEETS_INVENTORY_SHEET_ID', 943551638),
        'order_sync_enabled' => (bool) env('GOOGLE_SHEETS_ORDER_SYNC_ENABLED', false),
        'order_spreadsheet_id' => env('GOOGLE_SHEETS_ORDER_SPREADSHEET_ID'),
        'order_sheet_id' => (int) env('GOOGLE_SHEETS_ORDER_SHEET_ID', 282952252),
        'order_sheet_name' => env('GOOGLE_SHEETS_ORDER_SHEET_NAME', '01_DON_HANG'),
        'order_detail_sheet_id' => (int) env('GOOGLE_SHEETS_ORDER_DETAIL_SHEET_ID', 151191626),
        'order_detail_sheet_name' => env('GOOGLE_SHEETS_ORDER_DETAIL_SHEET_NAME', '02_CHI_TIET_DON_HANG'),
    ],

];
