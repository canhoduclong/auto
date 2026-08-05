<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ProductVariantCreatePriceInputTest extends TestCase
{
    public function test_create_form_accepts_single_dong_price(): void
    {
        $view = file_get_contents(
            dirname(__DIR__, 2).'/resources/views/product_variants/create.blade.php'
        );

        $this->assertStringContainsString('name="price"', $view);
        $this->assertStringContainsString('min="0" step="1"', $view);
        $this->assertStringContainsString("value=\"{{ old('price') }}\"", $view);
        $this->assertStringNotContainsString('name="price" class="form-control" min="0" step="1000"', $view);
    }
}
