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
    public function test_list_numbering_indentation_and_basic_editor_formats_survive_round_trip(): void {
        $source = '<h1>Tiêu đề</h1><ol start="4" type="I" style="list-style-type: upper-roman; padding-left: 40px;"><li value="7"><strong>Mục 7</strong><ul style="list-style-type: square;"><li>Mục con</li></ul></li></ol><p style="text-align: center; margin-left: 40px; font-family: Arial; font-size: 18px; line-height: 1.8;"><span style="background-color: #ffff00; color: #ff0000;">Nội dung</span><sub>2</sub><sup>3</sup></p><blockquote>Trích dẫn</blockquote><pre>  giữ khoảng trắng</pre><table><caption>Bảng</caption><tbody><tr><td style="vertical-align: top;" colspan="2">Ô</td></tr></tbody></table>';
        $html = TaskDescription::render(TaskDescription::sanitize($source));
        foreach (['<h1>', 'start="4"', 'type="I"', 'value="7"', 'list-style-type:upper-roman', 'list-style-type:square', 'padding-left:40px', 'margin-left:40px', 'text-align:center', 'font-family:Arial', 'font-size:18px', 'line-height:1.8', '<sub>', '<sup>', '<blockquote>', '<pre>', '<caption>', 'colspan="2"', 'vertical-align:top'] as $expected) {
            self::assertStringContainsString($expected, str_replace(': ', ':', $html));
        }
    }

    public function test_additional_formatting_does_not_allow_scripts_or_unsafe_css(): void {
        $html = TaskDescription::sanitize('<div onclick="bad()" style="position:fixed; background-image:url(javascript:bad());"><p style="color:red; behavior:url(bad);">Text</p></div><ol onmouseover="bad()"><li>Item</li></ol>');
        foreach (['onclick', 'onmouseover', 'position:', 'background-image', 'javascript:', 'behavior:'] as $unsafe) self::assertStringNotContainsString($unsafe, $html);
        self::assertStringContainsString('Text', $html);
    }
}
