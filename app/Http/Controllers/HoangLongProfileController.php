<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class HoangLongProfileController extends Controller
{
    private const INFO_KEY = 'hoang_long_tnt_profile_info';
    private const DOCUMENTS_KEY = 'hoang_long_tnt_profile_documents';

    public function edit()
    {
        $profileInfo = Setting::get(self::INFO_KEY, '');
        $documents = $this->documents();

        return view('admin.hoang-long-profile.edit', compact('profileInfo', 'documents'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'profile_info' => ['nullable', 'string'],
            'documents' => ['nullable', 'array'],
            'documents.*.title' => ['nullable', 'string', 'max:255'],
            'documents.*.url' => ['nullable', 'url', 'max:2048'],
            'documents.*.existing_file' => ['nullable', 'string', 'max:2048'],
            'documents.*.file' => ['nullable', 'file', 'max:20480'],
        ], [
            'documents.*.url.url' => 'URL tài liệu không hợp lệ.',
            'documents.*.file.max' => 'File đính kèm không được vượt quá 20MB.',
        ]);

        $documents = [];
        foreach (($validated['documents'] ?? []) as $index => $document) {
            $title = trim((string) ($document['title'] ?? ''));
            $url = trim((string) ($document['url'] ?? ''));
            $filePath = trim((string) ($document['existing_file'] ?? ''));

            if ($request->hasFile("documents.$index.file")) {
                $filePath = $request->file("documents.$index.file")->store('hoang-long-profile', 'public');
                $url = '';
            }

            if ($url !== '') {
                $filePath = '';
            }

            if ($title === '' && $url === '' && $filePath === '') {
                continue;
            }

            $documents[] = [
                'title' => $title !== '' ? $title : 'Tài liệu đính kèm',
                'url' => $url,
                'file_path' => $filePath,
            ];
        }

        Setting::set(self::INFO_KEY, trim((string) ($validated['profile_info'] ?? '')));
        Setting::set(self::DOCUMENTS_KEY, json_encode($documents, JSON_UNESCAPED_UNICODE));

        Cache::forget('settings');

        return redirect()->route('admin.hoang-long-profile.edit')
            ->with('success', 'Đã cập nhật Profile Hoàng Long TNT.');
    }

    public function show()
    {
        $profileInfo = Setting::get(self::INFO_KEY, '');
        $documents = $this->documents();
        $settings = Cache::remember('settings', 60, function () {
            return Setting::all()->keyBy('key');
        });

        return view('site.hoang-long-profile', compact('profileInfo', 'documents', 'settings'));
    }

    private function documents(): array
    {
        $raw = Setting::get(self::DOCUMENTS_KEY, '[]');
        $documents = json_decode((string) $raw, true);

        if (!is_array($documents)) {
            return [];
        }

        return collect($documents)
            ->filter(fn ($document) => is_array($document))
            ->map(function (array $document): array {
                $filePath = trim((string) ($document['file_path'] ?? ''));

                return [
                    'title' => trim((string) ($document['title'] ?? 'Tài liệu đính kèm')),
                    'url' => trim((string) ($document['url'] ?? '')),
                    'file_path' => $filePath,
                    'file_url' => $filePath !== '' ? Storage::url($filePath) : '',
                ];
            })
            ->values()
            ->all();
    }
}
