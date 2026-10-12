<?php

return [
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
