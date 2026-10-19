<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\LoginNoticeController;
use App\Http\Controllers\Api\Mobile\AuthApiController;
use App\Services\LoginNoticeService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Schema};
use PHPUnit\Framework\TestCase;

class LoginNoticeTest extends TestCase
{
    private $app;

    protected function setUp(): void
    {
        $this->app = require dirname(__DIR__, 2).'/bootstrap/app.php';
        $this->app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        config(['cache.default' => 'array', 'session.driver' => 'array']);
        Schema::create('settings', function (Blueprint $table) {
            $table->temporary();
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        DB::statement('DROP TEMPORARY TABLE IF EXISTS settings');
        $this->app->flush();
        restore_error_handler();
        restore_exception_handler();
        parent::tearDown();
    }

    public function test_defaults_keep_demo_period_and_footer(): void
    {
        $notice = (new LoginNoticeService)->configuration();
        self::assertTrue($notice['enabled']);
        self::assertStringContainsString('15/09/2026', $notice['content']);
        self::assertStringContainsString('30/11/2026', $notice['content']);
        self::assertNotEmpty($notice['footer']);
    }

    public function test_admin_update_is_shared_with_mobile_including_off_switch(): void
    {
        $data = ['enabled' => '0', 'title' => 'Thông báo demo', 'content' => "Dòng một\nDòng hai", 'footer' => 'Số liệu tham khảo'];
        $request = Request::create('/', 'PUT', $data);
        (new LoginNoticeController)->update($request);
        $notice = (new AuthApiController)->loginNotice()->getData(true)['data'];
        self::assertFalse($notice['enabled']);
        self::assertSame($data['title'], $notice['title']);
        self::assertSame($data['content'], $notice['content']);
        self::assertSame($data['footer'], $notice['footer']);
    }

    public function test_admin_routes_are_restricted_to_admin(): void
    {
        $routes = $this->app['router']->getRoutes();
        foreach (['admin.login-notice.edit', 'admin.login-notice.update'] as $name) {
            self::assertContains('role:admin', $routes->getByName($name)->gatherMiddleware());
        }
    }

    public function test_web_displays_notice_once_per_login_and_escapes_content(): void
    {
        \App\Models\Setting::set('system_login_notice', json_encode(['enabled' => true, 'title' => '<script>demo</script>']));
        session()->put('show_system_testing_notice', true);
        $html = view('layouts.partials.system_testing_notice')->render();
        self::assertStringContainsString('&lt;script&gt;demo&lt;/script&gt;', $html);
        self::assertStringContainsString('<dialog', $html);
        self::assertStringNotContainsString('<dialog', view('layouts.partials.system_testing_notice')->render());
    }

    public function test_web_respects_disabled_notice(): void
    {
        \App\Models\Setting::set('system_login_notice', json_encode(['enabled' => false]));
        session()->put('show_system_testing_notice', true);
        self::assertStringNotContainsString('<dialog', view('layouts.partials.system_testing_notice')->render());
    }

    public function test_invalid_content_is_not_saved(): void
    {
        try {
            (new LoginNoticeController)->update(Request::create('/', 'PUT', ['enabled' => '1', 'title' => '', 'content' => '']));
            self::fail('Missing required fields were accepted');
        } catch (\Illuminate\Validation\ValidationException $exception) {
            self::assertArrayHasKey('title', $exception->errors());
        }
        self::assertSame(0, DB::table('settings')->count());
    }
}
