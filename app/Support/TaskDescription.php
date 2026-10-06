<?php

namespace App\Support;

class TaskDescription
{
    public static function sanitize(?string $content): ?string
    {
        if ($content === null || $content === '') return $content;
        if (!self::isHtml($content)) return $content;
        $config = \HTMLPurifier_Config::createDefault();
        $config->set('Cache.DefinitionImpl', null);
        $config->set('HTML.Allowed', 'p[style],br,strong,b,em,i,u,s,span[style],h2[style],h3[style],h4[style],ul,ol[start],li,blockquote,a[href|title],table[style],thead,tbody,tr,th[colspan|rowspan|style],td[colspan|rowspan|style],hr');
        $config->set('CSS.AllowedProperties', ['color', 'background-color', 'text-align', 'font-size', 'font-weight', 'text-decoration', 'width', 'border', 'border-collapse', 'padding']);
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true, 'tel' => true]);
        return (new \HTMLPurifier($config))->purify($content);
    }

    private static function isHtml(string $content): bool
    {
        return preg_match('/<\/?[a-z][a-z0-9]*\b[^>]*>/i', $content) === 1;
    }

    public static function render(?string $content): string
    {
        $content = $content ?? '';
        return self::isHtml($content) ? (string) self::sanitize($content) : nl2br(e($content));
    }

    public static function text(?string $content): string
    {
        return trim(html_entity_decode(strip_tags(preg_replace('/<\/(p|li|h[2-4]|tr)>|<br\s*\/?\s*>/i', ' ', self::render($content))), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }
}
