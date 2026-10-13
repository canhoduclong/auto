<?php

return [
    'google_mode' => env('BACKUP_GOOGLE_MODE', 'oauth'),
    'google_email' => env('BACKUP_GOOGLE_EMAIL', 'chi.hoanglongtnt@gmail.com'),
    'oauth_redirect_uri' => env('BACKUP_GOOGLE_OAUTH_REDIRECT_URI'),
    'oauth_client_id' => env('BACKUP_GOOGLE_OAUTH_CLIENT_ID'),
    'oauth_client_secret' => env('BACKUP_GOOGLE_OAUTH_CLIENT_SECRET'),
    'oauth_refresh_token' => env('BACKUP_GOOGLE_OAUTH_REFRESH_TOKEN'),
    'oauth_connected_email' => env('BACKUP_GOOGLE_OAUTH_CONNECTED_EMAIL'),
    'key' => env('BACKUP_ENCRYPTION_KEY'),
    'key_file' => env('BACKUP_KEY_FILE', storage_path('app/backup-encryption.key')),
    'credentials' => env('BACKUP_GOOGLE_CREDENTIALS'),
    'subject' => env('BACKUP_GOOGLE_SUBJECT'),
    'monthly_enabled' => env('BACKUP_MONTHLY_ENABLED', false),
    'dump_binary' => env('BACKUP_DUMP_BINARY', 'mysqldump'),
    'directories' => ['storage/app/public', 'storage/app/private/process-documents', 'public/avatars', 'public/images/brands'],
    'drive_folder_id' => env('BACKUP_GOOGLE_DRIVE_FOLDER_ID', '13tvG3zBm-bUlazWh7EHS_qGonbhEyta0'),
    'spreadsheet_id' => env('BACKUP_GOOGLE_SPREADSHEET_ID', '1OaQAehzoEbMEqH7bY4z2gjFGakaqPkoNGJtK6PJaSOg'),
];
