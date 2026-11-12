<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;

final class LessonRichText
{
    private const ELEMENTS = [
        'p', 'br', 'hr', 'h2', 'h3', 'h4', 'strong', 'b', 'em', 'i', 'u',
        'blockquote', 'ul', 'ol', 'li', 'pre', 'code', 'a', 'img', 'figure',
        'figcaption', 'table', 'thead', 'tbody', 'tr', 'th', 'td', 'span', 'div', 'bdi',
    ];

    private const DISCARD = [
        'script', 'style', 'iframe', 'object', 'embed', 'template', 'svg', 'math',
    ];

    public static function clean(string $html): string
    {
        $document = new DOMDocument;
        $document->loadHTML(
            '<!doctype html><html><head><meta charset="utf-8"></head><body>'.$html.'</body></html>',
            LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING,
        );

        $body = $document->getElementsByTagName('body')->item(0);

        return $body ? self::children($body) : '';
    }

    private static function children(DOMNode $node): string
    {
        $html = '';

        foreach ($node->childNodes as $child) {
            if ($child instanceof DOMText) {
                $html .= self::escape($child->nodeValue);
            } elseif ($child instanceof DOMElement) {
                $html .= self::element($child);
            }
        }

        return $html;
    }

    private static function element(DOMElement $element): string
    {
        $tag = $element->tagName;
        if (in_array($tag, self::DISCARD, true)) {
            return '';
        }

        if ($tag === 'img') {
            return self::image($element);
        }

        if (in_array($tag, ['br', 'hr'], true)) {
            return "<{$tag}>";
        }

        $content = self::children($element);
        if (! in_array($tag, self::ELEMENTS, true)) {
            return $content;
        }

        $attributes = '';
        if ($tag === 'a') {
            $href = $element->getAttribute('href');
            if (! self::safeLink($href)) {
                return $content;
            }

            $attributes = ' href="'.self::escape($href).'"';
        }

        $lang = $element->getAttribute('lang');
        if (preg_match('/^[a-z]{2,3}(?:-[a-z0-9]{2,8})*$/iD', $lang)) {
            $attributes .= ' lang="'.$lang.'"';
        }

        $direction = $element->getAttribute('dir');
        if (in_array($direction, ['ltr', 'rtl', 'auto'], true)) {
            $attributes .= ' dir="'.$direction.'"';
        }

        return "<{$tag}{$attributes}>{$content}</{$tag}>";
    }

    private static function safeLink(string $href): bool
    {
        if (str_contains($href, '\\') || preg_match('/[\x00-\x20\x7f]/', $href)) {
            return false;
        }

        return str_starts_with($href, '#')
            || (str_starts_with($href, '/') && ! str_starts_with($href, '//'))
            || (str_starts_with(strtolower($href), 'https://') && filter_var($href, FILTER_VALIDATE_URL) !== false);
    }

    private static function image(DOMElement $element): string
    {
        $source = $element->getAttribute('src');
        if (strlen($source) > 350000 || ! preg_match('~^data:(image/(?:png|jpeg|webp));base64,([a-zA-Z0-9+/=]+)$~D', $source, $parts)) {
            return '';
        }

        $bytes = base64_decode($parts[2], true);
        if ($bytes === false || strlen($bytes) > 256 * 1024) {
            return '';
        }

        $size = @getimagesizefromstring($bytes);
        if ($size === false || $size['mime'] !== $parts[1] || min($size[0], $size[1]) < 1 || max($size[0], $size[1]) > 4096) {
            return '';
        }

        $alt = self::escape($element->getAttribute('alt'));

        return '<img src="data:'.$parts[1].';base64,'.base64_encode($bytes).'" alt="'.$alt.'" loading="lazy">';
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
