<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class AdminNotificationMenuTest extends TestCase
{
    public function test_admin_sidebar_exposes_notification_management_entry(): void
    {
        $sidebar = file_get_contents(dirname(__DIR__, 2) . '/resources/views/layouts/sidebar.blade.php');

        $this->assertStringContainsString("route('admin.notifications.index')", $sidebar);
        $this->assertStringContainsString('Quản trị thông báo', $sidebar);
        $this->assertStringContainsString('ph-bell-ringing', $sidebar);
    }
}
