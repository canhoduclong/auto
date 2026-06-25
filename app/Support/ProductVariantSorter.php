<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class ProductVariantSorter
{
    public static function joinProductSort(Builder $query, ?int $userId = null): Builder
    {
        $query
            ->leftJoin('products as sort_products', 'sort_products.id', '=', 'product_variants.product_id')
            ->addSelect('product_variants.*');

        if ($userId && Schema::hasTable('user_product_variant_preferences')) {
            $query->leftJoin('user_product_variant_preferences as user_variant_prefs', function ($join) use ($userId): void {
                $join->on('user_variant_prefs.product_variant_id', '=', 'product_variants.id')
                    ->where('user_variant_prefs.user_id', '=', $userId);
            })->addSelect([
                'user_variant_prefs.is_pinned as is_pinned',
                'user_variant_prefs.sort_order as user_sort_order',
            ]);
        }

        return $query;
    }

    public static function applyDefault(Builder $query, ?int $userId = null): Builder
    {
        self::joinProductSort($query, $userId);
        self::applyUserPreferencePrefix($query, $userId);

        return $query
            ->orderByRaw('COALESCE(sort_products.sort_order, 0) ASC')
            ->orderByRaw('COALESCE(product_variants.sort_order, 0) ASC')
            ->orderByRaw("LOWER(COALESCE(sort_products.name, '')) ASC")
            ->orderByRaw("LOWER(COALESCE(NULLIF(product_variants.name, ''), product_variants.sku, '')) ASC")
            ->orderBy('product_variants.id');
    }

    public static function applyUserPreferencePrefix(Builder $query, ?int $userId = null): Builder
    {
        if ($userId && Schema::hasTable('user_product_variant_preferences')) {
            $query
                ->orderByRaw('COALESCE(user_variant_prefs.is_pinned, 0) DESC')
                ->orderByRaw('CASE WHEN user_variant_prefs.sort_order IS NULL THEN 1 ELSE 0 END')
                ->orderBy('user_variant_prefs.sort_order');
        }

        return $query;
    }

    public static function applyAdminFallback(Builder $query): Builder
    {
        return $query
            ->orderByRaw('COALESCE(sort_products.sort_order, 0) ASC')
            ->orderByRaw('COALESCE(product_variants.sort_order, 0) ASC');
    }
}
