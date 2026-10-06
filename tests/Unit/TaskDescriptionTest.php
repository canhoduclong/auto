<?php
namespace Tests\Unit;
use App\Support\TaskDescription;
use PHPUnit\Framework\TestCase;
class TaskDescriptionTest extends TestCase
{
    public function test_rich_text_keeps_lists_tables_and_formatting(): void {
        $html=TaskDescription::sanitize('<h2>Mục tiêu</h2><p><strong>Đậm</strong> <em>Nghiêng</em></p><ul><li>Việc 1</li></ul><table><tbody><tr><td>20</td></tr></tbody></table>');
        foreach(['<h2>','<strong>','<em>','<ul>','<li>','<table>','<td>'] as $tag) self::assertStringContainsString($tag,$html);
    }
    public function test_unsafe_html_and_links_are_removed(): void {
        $html=TaskDescription::sanitize('<p onclick="alert(1)">Nội dung</p><script>alert(2)</script><iframe src="https://example.com"></iframe><a href="javascript:alert(3)">Link</a>');
        foreach(['onclick','<script','<iframe','javascript:'] as $unsafe) self::assertStringNotContainsString($unsafe,$html);
        self::assertStringContainsString('Nội dung',$html);
    }
    public function test_plain_text_keeps_newlines_and_escapes_entities(): void {
        $plain="Dòng 1 & 2\nDòng 3";
        self::assertSame($plain,TaskDescription::sanitize($plain));
        self::assertStringContainsString('&amp;',TaskDescription::render($plain));
        self::assertStringContainsString('<br',TaskDescription::render($plain));
    }
    public function test_preview_is_plain_text(): void {
        self::assertSame('Mục tiêu Việc 1',TaskDescription::text('<p>Mục tiêu</p><ul><li>Việc 1</li></ul>'));
    }
}
