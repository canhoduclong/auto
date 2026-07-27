<?php

namespace Tests\Unit;

use Tests\TestCase;

class MyCustomerTakeoverViewTest extends TestCase
{
    public function test_takeover_action_uses_named_route_and_handles_failed_responses(): void
    {
        $template = file_get_contents(resource_path('views/site/my_customer/index.blade.php'));

        $this->assertStringContainsString("takeover:     \"{{ route('my_customer.takeover', ':id') }}\"", $template);
        $this->assertStringContainsString("fetch(mcUrl('takeover', id), {", $template);
        $this->assertStringContainsString('if (!res.ok || data.success === false)', $template);
        $this->assertStringContainsString('} finally {', $template);
    }
}
