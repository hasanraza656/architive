<?php

namespace App\Services\Blog;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Illuminate\Support\Str;

/**
 * Keeps blog HTML clean and safe, and prepares it for reading:
 *  - sanitize(): whitelist of tags/attributes when an article is saved (no scripts, no inline handlers, no javascript: links)
 *  - render(): adds heading anchors + a table of contents, lazy-loads images, makes outside links safe, wraps wide tables
 */
class ContentProcessor
{
    /** tag => allowed attributes */
    private const ALLOWED = [
        'p' => ['class'], 'br' => [], 'hr' => [],
        'h2' => ['id', 'class'], 'h3' => ['id', 'class'], 'h4' => ['id', 'class'],
        'ul' => [], 'ol' => [], 'li' => ['class'],
        'strong' => [], 'b' => [], 'em' => [], 'i' => [], 'u' => [], 's' => [], 'sub' => [], 'sup' => [], 'mark' => [],
        'blockquote' => [], 'pre' => ['class'], 'code' => [],
        'a' => ['href', 'title', 'target', 'rel'],
        'img' => ['src', 'alt', 'title', 'width', 'height', 'loading', 'decoding'],
        'figure' => ['class'], 'figcaption' => [],
        'table' => [], 'thead' => [], 'tbody' => [], 'tr' => [], 'th' => ['colspan', 'rowspan'], 'td' => ['colspan', 'rowspan'],
    ];

    /** Removed together with everything inside. */
    private const DROP = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'textarea', 'select', 'svg', 'math', 'link', 'meta', 'noscript', 'head', 'title'];

    /* ------------------------------------------------------------ Save-time cleaning */

    public function sanitize(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }

        $doc = $this->load($html);
        $this->clean($doc->getElementsByTagName('body')->item(0));
        $out = $this->inner($doc);

        // Quill leaves empty paragraphs behind
        $out = preg_replace('#<p>(\s|&nbsp;|<br\s*/?>)*</p>#i', '', $out);

        return trim($out);
    }

    private function clean(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if (! $child instanceof DOMElement) {
                continue;
            }
            $tag = strtolower($child->tagName);

            if (in_array($tag, self::DROP, true)) {
                $node->removeChild($child);
                continue;
            }

            $this->clean($child);

            if ($tag === 'div' || $tag === 'span' || ! isset(self::ALLOWED[$tag])) {
                // unknown wrapper: keep the text and children, drop the tag itself
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);
                continue;
            }

            $this->cleanAttributes($child, $tag);
        }
    }

    private function cleanAttributes(DOMElement $el, string $tag): void
    {
        $allowed = self::ALLOWED[$tag];
        foreach (iterator_to_array($el->attributes) as $attr) {
            $name = strtolower($attr->name);
            $value = trim($attr->value);

            if (! in_array($name, $allowed, true)) {
                $el->removeAttribute($attr->name);
                continue;
            }
            if ($name === 'class') {
                // only our own alignment helpers survive
                $keep = array_filter(preg_split('/\s+/', $value), fn ($c) => preg_match('/^(ql-align-(center|right|justify)|post-lead|callout|ql-syntax)$/', $c));
                $keep ? $el->setAttribute('class', implode(' ', $keep)) : $el->removeAttribute('class');
                continue;
            }
            if (in_array($name, ['href', 'src'], true) && ! $this->safeUrl($value, $tag === 'img')) {
                $el->removeAttribute($attr->name);
            }
        }

        if ($tag === 'a') {
            if (! $el->hasAttribute('href')) {
                // a link without a destination is just text
                while ($el->firstChild) {
                    $el->parentNode->insertBefore($el->firstChild, $el);
                }
                $el->parentNode->removeChild($el);

                return;
            }
            if ($el->getAttribute('target') === '_blank') {
                $el->setAttribute('rel', 'noopener noreferrer');
            } else {
                $el->removeAttribute('target');
            }
        }
        if ($tag === 'img' && ! $el->hasAttribute('alt')) {
            $el->setAttribute('alt', '');
        }
    }

    private function safeUrl(string $url, bool $image): bool
    {
        if ($url === '') {
            return false;
        }
        $compact = strtolower(preg_replace('/[\x00-\x20]+/', '', $url));
        if (preg_match('#^(https?:|mailto:|tel:|/|\#|\.\.?/)#', $compact)) {
            return true;
        }
        if ($image && preg_match('#^data:image/(png|jpe?g|gif|webp);base64,#', $compact)) {
            return true;
        }

        // relative path such as uploads/blog/x.webp
        return ! str_contains($compact, ':');
    }

    /* ------------------------------------------------------------ Reading-time preparation */

    /** @return array{html:string, toc:array<int,array{id:string,text:string,level:int}>} */
    public function render(string $html): array
    {
        if (trim($html) === '') {
            return ['html' => '', 'toc' => []];
        }

        $doc = $this->load($html);
        $xp = new DOMXPath($doc);
        $toc = [];
        $used = [];

        foreach ($xp->query('//h2 | //h3') as $h) {
            $text = trim(preg_replace('/\s+/', ' ', $h->textContent));
            if ($text === '') {
                continue;
            }
            $base = Str::slug(Str::limit($text, 60, '')) ?: 'section';
            $id = $base;
            for ($i = 2; isset($used[$id]); $i++) {
                $id = $base . '-' . $i;
            }
            $used[$id] = true;
            $h->setAttribute('id', $id);
            $toc[] = ['id' => $id, 'text' => $text, 'level' => (int) substr($h->tagName, 1)];
        }

        foreach ($xp->query('//img') as $i => $img) {
            $img->setAttribute('loading', 'lazy');
            $img->setAttribute('decoding', 'async');
        }

        $host = parse_url(url('/'), PHP_URL_HOST);
        foreach ($xp->query('//a[@href]') as $a) {
            $h = parse_url($a->getAttribute('href'), PHP_URL_HOST);
            if ($h && $h !== $host) {
                $a->setAttribute('target', '_blank');
                $a->setAttribute('rel', 'noopener noreferrer');
            }
        }

        foreach (iterator_to_array($xp->query('//table')) as $table) {
            $wrap = $doc->createElement('div');
            $wrap->setAttribute('class', 'post-table');
            $table->parentNode->replaceChild($wrap, $table);
            $wrap->appendChild($table);
        }

        return ['html' => $this->inner($doc), 'toc' => $toc];
    }

    public function wordCount(string $html): int
    {
        return str_word_count(strip_tags(html_entity_decode($html)));
    }

    public function readingMinutes(string $html): int
    {
        return max(1, (int) ceil($this->wordCount($html) / 220));
    }

    /* ------------------------------------------------------------ DOM helpers */

    private function load(string $html): DOMDocument
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $prev = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8"><html><body>' . $html . '</body></html>', LIBXML_HTML_NODEFDTD | LIBXML_HTML_NOIMPLIED);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        return $doc;
    }

    private function inner(DOMDocument $doc): string
    {
        $body = $doc->getElementsByTagName('body')->item(0);
        $out = '';
        foreach ($body->childNodes as $child) {
            $out .= $doc->saveHTML($child);
        }

        return $out;
    }
}
