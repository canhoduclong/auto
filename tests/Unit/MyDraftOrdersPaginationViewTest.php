<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class MyDraftOrdersPaginationViewTest extends TestCase
{
    public function test_sale_drafts_use_real_pagination_and_a_sticky_footer(): void
    {
        $controller = file_get_contents(
            dirname(__DIR__, 2).'/app/Http/Controllers/Admin/TextOrderImportController.php'
        );
        $view = file_get_contents(
            dirname(__DIR__, 2).'/resources/views/site/my-draft-orders.blade.php'
        );

        $this->assertStringContainsString('$draftQuery->paginate($perPage)', $controller);
        $this->assertStringContainsString("[10, 20, 50]", $controller);
        $this->assertStringContainsString('$drafts->hasPages()', $view);
        $this->assertStringContainsString('class="draft-template-pagination"', $view);
        $this->assertStringContainsString('position: sticky;', $view);
        $this->assertStringContainsString('bottom: 0;', $view);
        $this->assertStringContainsString('$drafts->total()', $view);
    }
}
