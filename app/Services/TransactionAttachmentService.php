<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class TransactionAttachmentService
{
    public function store(array $files, array $existing = [], bool $hasLegacyReceipt = false): array
    {
        $files = array_values(array_filter($files));
        if (count($files) + count($existing) + (int) $hasLegacyReceipt > 10) {
            throw ValidationException::withMessages(['attachments' => 'Mỗi giao dịch chỉ được lưu tối đa 10 chứng từ.']);
        }
        $stored = [];
        try {
            foreach ($files as $file) {
                $path = $file->store('transactions/receipts', 'public');
                if (! $path) {
                    throw new \RuntimeException('Không lưu được chứng từ. Vui lòng thử lại.');
                }
                $stored[] = ['name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'mime_type' => $file->getMimeType(), 'size' => $file->getSize()];
            }
        } catch (\Throwable $exception) {
            foreach ($stored as $attachment) {
                Storage::disk('public')->delete($attachment['path']);
            }
            throw $exception;
        }

        return array_merge($existing, $stored);
    }
}
