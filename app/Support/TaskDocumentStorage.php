<?php
namespace App\Support;

use Illuminate\Http\UploadedFile;

class TaskDocumentStorage
{
    public static function store(UploadedFile $file, string $directory): string
    {
        // Preserve readable Unicode names, removing path and control characters.
        $original = str_replace('\\', '/', $file->getClientOriginalName());
        $original = basename($original);
        $extension = pathinfo($original, PATHINFO_EXTENSION);
        $stem = pathinfo($original, PATHINFO_FILENAME);
        $stem = preg_replace('/[\x00-\x1f\x7f<>:"\/\\\\|?*]/u', '_', $stem) ?? 'Tai lieu';
        $stem = trim($stem, " .\t\n\r\0\x0B");
        if ($stem === '') $stem = 'Tai lieu';
        // Limit bytes for filesystems while retaining multibyte characters.
        $stem = mb_strcut($stem, 0, 170, 'UTF-8');
        $extension = preg_replace('/[^a-zA-Z0-9]/', '', $extension);
        $suffix = now()->format('Ymd_His') . '_' . bin2hex(random_bytes(5));
        $name = $stem . '_' . $suffix . ($extension !== '' ? '.' . $extension : '');
        return $file->storeAs($directory, $name, 'public');
    }
}
