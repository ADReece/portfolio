<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Allow-list HTML sanitiser for short rich text (e.g. the portfolio bio).
 * Keeps basic formatting; links keep only an http(s)/mailto href. Every
 * other tag is unwrapped and every other attribute (onclick, style…) dropped.
 */
class HtmlSanitizer
{
    private const ALLOWED_TAGS = ['p', 'br', 'strong', 'b', 'em', 'i', 'u', 'ol', 'ul', 'li', 'a'];

    public static function clean(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        $doc = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8"?><div id="__root">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $doc->getElementById('__root');
        if (! $root) {
            return e(strip_tags($html));
        }

        self::cleanChildren($root);

        $out = '';
        foreach (iterator_to_array($root->childNodes) as $child) {
            $out .= $doc->saveHTML($child);
        }

        // Non-breaking spaces stop text wrapping (some editors insert them for every space).
        return trim(str_replace(['&nbsp;', "\u{00A0}"], ' ', $out));
    }

    private static function cleanChildren(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMElement) {
                $tag = strtolower($child->tagName);

                if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed', 'template'], true)) {
                    $node->removeChild($child);
                    continue;
                }

                self::cleanChildren($child);

                if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                    // Unwrap: keep the text, drop the tag.
                    while ($child->firstChild) {
                        $node->insertBefore($child->firstChild, $child);
                    }
                    $node->removeChild($child);
                    continue;
                }

                $href = $tag === 'a' ? trim($child->getAttribute('href')) : null;
                foreach (iterator_to_array($child->attributes) as $attr) {
                    $child->removeAttribute($attr->nodeName);
                }
                if ($tag === 'a') {
                    if ($href && preg_match('#^(https?://|mailto:)#i', $href)) {
                        $child->setAttribute('href', $href);
                        $child->setAttribute('rel', 'nofollow noopener');
                        $child->setAttribute('target', '_blank');
                    }
                }
            } elseif ($child->nodeType === XML_COMMENT_NODE) {
                $node->removeChild($child);
            }
        }
    }
}
