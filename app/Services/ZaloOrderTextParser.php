<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\ProductVariant;
use App\Models\TruckBrand;
use App\Models\TruckStation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ZaloOrderTextParser
{
    private ?Collection $sales = null;
    private ?Collection $variants = null;
    private ?Collection $truckBrands = null;
    private ?Collection $truckStations = null;

    public function parse(string $text): Collection
    {
        preg_match_all(
            '/^\[(\d{2}\/\d{2}\/\d{4})\s+\d{2}:\d{2}:\d{2}\]\s+([^:]+):\s*(.*?)(?=^\[\d{2}\/\d{2}\/\d{4}\s+\d{2}:\d{2}:\d{2}\]\s+[^:]+:|\z)/msu',
            trim($text),
            $matches,
            PREG_SET_ORDER
        );

        $drafts = collect();
        foreach ($matches as $match) {
            $body = trim($match[3]);
            if ($body === '' || preg_match('/^\[(Tin nhắn đã thu hồi|Danh thiếp|Hình ảnh)\]$/iu', $body)) {
                continue;
            }

            if (!$this->looksLikeOrder($body)) {
                for ($index = $drafts->count() - 1; $index >= 0; $index--) {
                    if ($this->normalize($drafts[$index]['zalo_name']) !== $this->normalize($match[2])) {
                        continue;
                    }
                    $draft = $drafts[$index];
                    $draft['note'] = trim($draft['note'] . "\n" . $body);
                    $draft['raw_text'] = trim($draft['raw_text'] . "\n" . $body);
                    $drafts[$index] = $draft;
                    break;
                }
                continue;
            }

            $drafts->push($this->parseOrder($match[1], trim($match[2]), $body));
        }

        return $drafts;
    }

    private function looksLikeOrder(string $body): bool
    {
        return preg_match('/(?:khách hàng|tên kh|\bkh\s*:|số lượng|\bsl\s*:|sản phẩm|\bsp\s*:|\b\d+\s*(?:con|c)\s+vịt)/iu', $body)
            && preg_match('/(?:\b0\d[\d\s.]{7,12}\b|\b\d+\s*(?:con|c)\b)/iu', $body);
    }

    private function parseOrder(string $messageDate, string $zaloName, string $body): array
    {
        $phone = $this->match('/(?:số\s*đt|sđt|đt|điện thoại)\s*[:\-]?\s*(0[\d\s.]{8,13})/iu', $body);
        $phone = preg_replace('/\D+/', '', (string) $phone);
        $customerName = $this->match('/(?:tên\s*kh|khách\s*hàng|\bkh)\s*[:\-]\s*([^\n]+)/iu', $body);
        if (!$customerName) {
            $firstLine = trim((string) preg_split('/\R/u', $body)[0], " \t\n\r\0\x0B-*");
            if (preg_match('/(?:lò quay|hộ kinh doanh|hkd|đắc nhân ký)/iu', $firstLine)) {
                $customerName = preg_replace('/\s*-\s*(?:sđt|số đt|đc|địa chỉ)\s*:?.*$/iu', '', $firstLine);
            }
        }
        $customerName = preg_replace('/\s*-\s*(?:sđt|số đt|đc|địa chỉ)\s*:?.*$/iu', '', (string) $customerName);
        $address = $this->match('/(?:địa\s*chỉ|đ\/c|\bđc)\s*[:\-]\s*([^\n]+)/iu', $body);
        $product = $this->match('/(?:sản\s*phẩm|\bsp)\s*[:\-]\s*([^\n]+)/iu', $body)
            ?: $this->match('/\b(vịt\s+(?:móc|nguyên con|lóc tỳ bà|quay lông|móc lòng)[^\n]*)/iu', $body);
        $quantity = $this->match('/(?:số\s*lượng|\bsl)\s*[:\-]?\s*(\d+)/iu', $body)
            ?: $this->match('/\b(\d+)\s*(?:con|c)\b/iu', $body);
        $size = $this->match('/\bsize\s*[:\-]?\s*(\d+(?:[.,]\d+|kg\d+)?)/iu', $body);
        $price = $this->match('/(?:giá(?:\s*tiền)?|giá)\s*[:\-]?\s*(\d+(?:[.,]\d+)?)(?:\s*(k|000))?/iu', $body, 1);
        $priceUnit = $this->match('/(?:giá(?:\s*tiền)?|giá)\s*[:\-]?\s*\d+(?:[.,]\d+)?\s*(k|000)/iu', $body);
        if (!$price) {
            $price = $this->match('/\b(\d{2,3})\s*k\s*\/\s*(?:1\s*)?kg\b/iu', $body);
            $priceUnit = $price ? 'k' : null;
        }
        $deliveryTime = $this->match('/(?:giờ\s*nhận|thời\s*gian(?:\s*giao)?|giao hàng)\s*[:\-]?\s*([^\n]+)/iu', $body);
        $truckBrandName = $this->match('/^\s*(?:nhà\s*xe)\s*[:\-]\s*([^\n]+)/imu', $body);
        $truckStationAddress = $this->match('/^\s*(?:địa\s*chỉ\s*nhà\s*xe|địa\s*chỉ\s*trạm\s*xe|trạm\s*xe)\s*[:\-]\s*([^\n]+)/imu', $body);

        $deliveryDate = Carbon::createFromFormat('d/m/Y', $messageDate)->addDay()->toDateString();
        if ($dateText = $this->match('/\b(\d{1,2}[\/\-]\d{1,2}(?:[\/\-]\d{2,4})?)\b/u', $body)) {
            try {
                $parts = preg_split('/[\/\-]/', $dateText);
                $year = isset($parts[2]) ? (int) $parts[2] : (int) Carbon::parse($deliveryDate)->year;
                $year = $year < 100 ? 2000 + $year : $year;
                $deliveryDate = Carbon::create($year, (int) $parts[1], (int) $parts[0])->toDateString();
            } catch (\Throwable) {
            }
        }

        $sale = $this->resolveSale($zaloName);
        $truckBrand = $this->resolveTruckBrand((string) $truckBrandName);
        $truckStation = $this->resolveTruckStation($truckBrand, (string) $truckStationAddress);
        $customer = $phone !== '' ? Customer::query()->whereRaw("REPLACE(REPLACE(REPLACE(phone, ' ', ''), '.', ''), '-', '') LIKE ?", ['%' . $phone . '%'])->first() : null;
        $normalizedSize = preg_replace('/kg(?=\d)/iu', '.', (string) $size);
        $variant = $this->resolveVariant((string) $product, (float) str_replace(',', '.', $normalizedSize));
        $numericPrice = $this->normalizePrice((string) $price);
        $items = $this->parseItems($body, (string) $product, (int) $quantity, (float) str_replace(',', '.', $normalizedSize), $numericPrice);
        $primaryItem = $items[0] ?? null;

        return [
            'sale_id' => $sale?->id,
            'customer_id' => $customer?->id,
            'truck_brand_id' => $truckBrand?->id,
            'truck_station_id' => $truckStation?->id,
            'product_variant_id' => $primaryItem['product_variant_id'] ?? $variant?->id,
            'zalo_name' => $zaloName,
            'customer_name' => trim((string) $customerName) ?: $customer?->name,
            'phone' => $phone ?: $customer?->phone,
            'address' => trim((string) $address) ?: $customer?->address,
            'truck_brand_name' => trim((string) $truckBrandName) ?: $truckBrand?->name,
            'truck_station_address' => trim((string) $truckStationAddress) ?: $truckStation?->address,
            'product_text' => trim((string) $product),
            'parsed_items' => $items,
            'quantity' => $primaryItem['quantity'] ?? ($quantity ? (int) $quantity : null),
            'size_kg' => $primaryItem['size_kg'] ?? ($size ? (float) str_replace(',', '.', $normalizedSize) : null),
            'unit_price' => $primaryItem['unit_price'] ?? ($numericPrice > 0 ? $numericPrice : null),
            'delivery_date' => $deliveryDate,
            'delivery_time' => trim((string) $deliveryTime),
            'note' => $body,
            'raw_text' => $body,
            'status' => 'draft',
        ];
    }

    private function parseItems(string $body, string $fallbackProduct, int $fallbackQuantity, float $fallbackSize, float $fallbackPrice): array
    {
        $items = [];
        foreach (preg_split('/\R/u', $body) as $line) {
            if (!preg_match('/(?:^|[+\-:\s])(\d+)\s*(?:con|c|bộ)\b\s*(.*)$/iu', trim($line), $match)) {
                continue;
            }
            $description = trim($match[2]);
            if ($description === '' || !preg_match('/(?:vịt|lòng|lóc|quay|móc)/iu', $description)) {
                continue;
            }
            $sizeText = $this->match('/\bsize\s*[:\-]?\s*(\d+(?:[.,]\d+|kg\d+)?)/iu', $description);
            $size = (float) str_replace(',', '.', preg_replace('/kg(?=\d)/iu', '.', (string) $sizeText));
            $priceText = $this->match('/\bgiá\s*[:\-]?\s*(\d+(?:[.,]\d+)?)/iu', $description);
            $price = $this->normalizePrice((string) $priceText);
            $variant = $this->resolveVariant($description, $size);
            $items[] = [
                'product_text' => $description,
                'product_variant_id' => $variant?->id,
                'quantity' => (int) $match[1],
                'size_kg' => $size > 0 ? $size : null,
                'unit_price' => $price > 0 ? $price : null,
            ];
        }

        if ($items === []) {
            $items[] = [
                'product_text' => $fallbackProduct,
                'product_variant_id' => $this->resolveVariant($fallbackProduct, $fallbackSize)?->id,
                'quantity' => $fallbackQuantity ?: null,
                'size_kg' => $fallbackSize > 0 ? $fallbackSize : null,
                'unit_price' => $fallbackPrice > 0 ? $fallbackPrice : null,
            ];
        }

        return $items;
    }

    private function resolveSale(string $zaloName): ?User
    {
        $needle = $this->normalize($zaloName);
        $this->sales ??= User::query()->whereHas('roles', fn ($query) => $query->where('name', 'sale'))->get();

        return $this->sales
            ->first(fn (User $user) => $this->normalize((string) $user->zalo_name) === $needle);
    }

    private function resolveVariant(string $productText, float $size): ?ProductVariant
    {
        $needle = $this->normalize($productText);
        $keywords = collect(preg_split('/\s+/', $needle))->filter(fn ($word) => mb_strlen($word) >= 3)->values();

        $this->variants ??= ProductVariant::query()->with('product')->get();

        return $this->variants
            ->sortByDesc(function (ProductVariant $variant) use ($keywords, $size) {
                $haystack = $this->normalize(($variant->product?->name ?? '') . ' ' . ($variant->name ?? '') . ' ' . ($variant->sku ?? ''));
                $score = $keywords->sum(fn ($word) => str_contains($haystack, $word) ? 10 : 0);
                if ($size > 0 && abs((float) $variant->size - $size) < 0.21) {
                    $score += 5;
                }
                return $score;
            })->first(fn (ProductVariant $variant) => $keywords->contains(fn ($word) => str_contains($this->normalize(($variant->product?->name ?? '') . ' ' . ($variant->name ?? '')), $word)));
    }

    private function resolveTruckBrand(string $name): ?TruckBrand
    {
        $needle = $this->normalize($name);
        if ($needle === '') {
            return null;
        }

        $this->truckBrands ??= TruckBrand::query()->where('is_active', true)->get();

        return $this->truckBrands->first(fn (TruckBrand $brand) => $this->normalize($brand->name) === $needle);
    }

    private function resolveTruckStation(?TruckBrand $brand, string $address): ?TruckStation
    {
        $needle = $this->normalize($address);
        if (!$brand || $needle === '') {
            return null;
        }

        $this->truckStations ??= TruckStation::query()->where('is_active', true)->get();

        return $this->truckStations->first(fn (TruckStation $station) =>
            (int) $station->brand_id === (int) $brand->id
            && ($this->normalize((string) $station->address) === $needle || $this->normalize($station->name) === $needle)
        );
    }

    private function match(string $pattern, string $subject, int $group = 1): ?string
    {
        return preg_match($pattern, $subject, $matches) ? trim((string) ($matches[$group] ?? '')) : null;
    }

    private function normalizePrice(string $value): float
    {
        $value = trim($value);
        if (preg_match('/^\d{1,3}(?:[.,]\d{3})+$/', $value)) {
            return (float) preg_replace('/[.,]/', '', $value);
        }

        $price = (float) str_replace(',', '.', $value);
        return $price > 0 && $price < 1000 ? $price * 1000 : $price;
    }

    private function normalize(string $value): string
    {
        return Str::of($value)->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', ' ')->squish()->toString();
    }
}
