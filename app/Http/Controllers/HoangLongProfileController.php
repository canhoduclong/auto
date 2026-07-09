<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
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
        $priceProducts = $this->dailyPriceProducts();
        $totalPriceVariants = (int) $priceProducts->sum(fn (Product $product) => (int) ($product->total_variants_count ?? 0));
        $settings = Cache::remember('settings', 60, function () {
            return Setting::all()->keyBy('key');
        });

        return view('site.hoang-long-profile', compact(
            'profileInfo',
            'documents',
            'settings',
            'priceProducts',
            'totalPriceVariants'
        ));
    }

    private function dailyPriceProducts()
    {
        return Product::query()
            ->with([
                'avatar.media',
                'variants.latestPriceLog',
            ])
            ->where('status', true)
            ->whereHas('variants', function ($variantQuery) {
                $variantQuery->whereHas('latestPriceLog', function ($priceLogQuery) {
                    $priceLogQuery->where('new_price', '>', 0);
                });
            })
            ->orderBy('name')
            ->get()
            ->map(function (Product $product) {
                $variantRows = $product->variants
                    ->map(function (ProductVariant $variant) {
                        $price = (float) ($variant->latestPriceLog?->new_price ?? 0);

                        $variant->setAttribute('current_price', $price);
                        $variant->setAttribute('price_key', number_format($price, 4, '.', ''));

                        return $variant;
                    })
                    ->filter(fn (ProductVariant $variant) => (float) ($variant->current_price ?? 0) > 0)
                    ->values();

                if ($variantRows->isEmpty()) {
                    return null;
                }

                $groupedByPrice = $variantRows->groupBy(fn ($variant) => (string) $variant->price_key);
                $representativeGroup = $groupedByPrice
                    ->sortByDesc(fn ($items) => $items->count())
                    ->first();

                $representativePrice = (float) ($representativeGroup?->first()?->current_price ?? 0);
                $representativePriceKey = (string) ($representativeGroup?->first()?->price_key ?? number_format(0, 4, '.', ''));

                $differentVariants = $variantRows
                    ->filter(fn ($variant) => (string) $variant->price_key !== $representativePriceKey)
                    ->sortBy('name')
                    ->values();

                $product->setAttribute('current_price', $representativePrice);
                $product->setAttribute('total_variants_count', $variantRows->count());
                $product->setRelation('priceDiffVariants', $differentVariants);

                return $product;
            })
            ->filter()
            ->values();
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
