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
        $config->set('HTML.Allowed', 'p[style],div[style],br,strong,b,em,i,u,s,sub,sup,span[style],h1[style],h2[style],h3[style],h4[style],h5[style],h6[style],ul[style],ol[start|type|style],li[value|style],dl,dt,dd,blockquote[style],pre[style],code,a[href|title],table[style],caption,thead,tbody,tfoot,tr[style],th[colspan|rowspan|style],td[colspan|rowspan|style],hr');
        $config->set('CSS.AllowedProperties', [
            'color', 'background-color', 'text-align', 'font-size', 'font-family', 'font-weight', 'font-style',
            'text-decoration', 'text-indent', 'line-height', 'vertical-align', 'white-space',
            'list-style-type', 'list-style-position', 'margin', 'margin-top', 'margin-right', 'margin-bottom', 'margin-left',
            'padding', 'padding-top', 'padding-right', 'padding-bottom', 'padding-left',
            'width', 'height', 'border', 'border-width', 'border-style', 'border-color', 'border-collapse',
        ]);
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true, 'tel' => true]);
        $definition = $config->getHTMLDefinition(true);
        $definition->addAttribute('ol', 'start', new \HTMLPurifier_AttrDef_Integer());
        $definition->addAttribute('ol', 'type', new \HTMLPurifier_AttrDef_Enum(['1', 'a', 'A', 'i', 'I'], true));
        $definition->addAttribute('li', 'value', new \HTMLPurifier_AttrDef_Integer());
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
        return trim(html_entity_decode(strip_tags(preg_replace('/<\/(p|div|li|dt|dd|blockquote|pre|h[1-6]|tr)>|<br\s*\/?\s*>/i', ' ', self::render($content))), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }
}
