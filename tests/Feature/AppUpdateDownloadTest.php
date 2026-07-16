<?php

namespace Tests\Feature;

use Tests\TestCase;

class AppUpdateDownloadTest extends TestCase
{
    public function test_version_manifest_is_available_without_authentication(): void
    {
        $response = $this->get('/app-update/version.json');

        $response->assertOk()
            ->assertHeader('content-type', 'application/json; charset=UTF-8');
        $this->assertStringContainsString('no-cache', (string) $response->headers->get('cache-control'));
        $this->assertStringContainsString('no-store', (string) $response->headers->get('cache-control'));

        $manifest = json_decode(
            (string) file_get_contents($response->baseResponse->getFile()->getPathname()),
            true,
            flags: JSON_THROW_ON_ERROR
        );
        $this->assertArrayHasKey('version_code', $manifest);
        $this->assertArrayHasKey('apk_url', $manifest);
        $this->assertArrayHasKey('message', $manifest);
    }

    public function test_unknown_apk_is_not_exposed(): void
    {
        $this->get('/app-update/app-release-9.9.9.apk')->assertNotFound();
        $this->get('/app-update/version.json.apk')->assertNotFound();
    }
}
