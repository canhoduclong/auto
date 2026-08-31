<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ShipperAcceptWithoutConfirmationTest extends TestCase
{
    #[Test]
    public function accepting_an_available_order_does_not_show_a_confirmation_dialog(): void
    {
        $view = file_get_contents(dirname(__DIR__, 2).'/resources/views/shipper/available.blade.php');

        $this->assertStringContainsString("event.target.closest('.js-shipper-accept-form')", $view);
        $this->assertStringNotContainsString('Xác nhận nhận đơn #', $view);
    }
}
