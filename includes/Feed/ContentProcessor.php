<?php

namespace DzenUnifiedRss\Feed;

defined('ABSPATH') || exit;

/**
 * Приводит произвольный HTML контента поста к тегам, которые Дзен реально рендерит
 * в content:encoded (dzen.ru/help/ru/news/seamless/rss.html). Чистый PHP без вызовов
 * WordPress API — тестируется голым php-скриптом, см. tests/test-content-processor.php.
 */
class ContentProcessor
{
    private const ALLOWED_TAGS = [
        'p', 'a', 'b', 'i', 'u', 's',
        'h1', 'h2', 'h3', 'h4',
        'blockquote', 'ul', 'ol', 'li',
        'img', 'figure', 'figcaption',
    ];

    private const ALLOWED_ATTRIBUTES = [
        'a'   => ['href'],
        'img' => ['src', 'alt', 'width', 'height'],
    ];

    // Дзен не поддерживает em/strong — использовать i/b (см. plugin.md)
    private const RENAME_MAP = [
        'em'     => 'i',
        'strong' => 'b',
    ];

    // теги, которые площадка молча не отрендерит — убираем вместе с содержимым, не только обёртку
    private const STRIP_WITH_CONTENT = [
        'script', 'style', 'iframe', 'noscript', 'form', 'button', 'svg', 'video', 'audio', 'object', 'embed',
    ];

    public static function process(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        $dom = self::parse($html);
        $body = $dom->getElementsByTagName('body')->item(0);

        self::removeWithContent($dom, self::STRIP_WITH_CONTENT);
        self::renameTags($dom, self::RENAME_MAP);
        self::splitOnBreaks($dom);
        self::unwrapDisallowed($body);
        self::useOriginalImageFiles($body);
        self::sanitizeAttributes($body);
        self::removeEmptyElements($body);

        return self::renderInner($body);
    }

    public static function plainTextLength(string $html): int
    {
        return mb_strlen(trim(html_entity_decode(wp_strip_all_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    }

    public static function meetsMinLength(string $html, int $minChars = 300): bool
    {
        return self::plainTextLength($html) >= $minChars;
    }

    private static function parse(string $html): \DOMDocument
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $dom->loadHTML(
            '<?xml encoding="UTF-8"?><html><body>' . $html . '</body></html>',
            LIBXML_NOERROR | LIBXML_NOWARNING
        );
        libxml_clear_errors();

        return $dom;
    }

    private static function removeWithContent(\DOMDocument $dom, array $tags): void
    {
        foreach ($tags as $tag) {
            foreach (iterator_to_array($dom->getElementsByTagName($tag)) as $node) {
                if ($node->parentNode) {
                    $node->parentNode->removeChild($node);
                }
            }
        }
    }

    private static function renameTags(\DOMDocument $dom, array $map): void
    {
        foreach ($map as $from => $to) {
            foreach (iterator_to_array($dom->getElementsByTagName($from)) as $node) {
                $new = $dom->createElement($to);
                while ($node->firstChild) {
                    $new->appendChild($node->firstChild);
                }
                foreach ($node->attributes as $attr) {
                    $new->setAttribute($attr->nodeName, $attr->nodeValue);
                }
                $node->parentNode->replaceChild($new, $node);
            }
        }
    }

    /**
     * ponytail: одиночный <br> -> пробел, два и более подряд -> разрыв абзаца.
     * Дзен не поддерживает <br> (только <p>); точная реконструкция авторской вёрстки
     * не нужна для этой задачи — апгрейд, если реальный контент потребует точнее.
     */
    private static function splitOnBreaks(\DOMDocument $dom): void
    {
        foreach (iterator_to_array($dom->getElementsByTagName('br')) as $br) {
            $parent = $br->parentNode;
            if (!$parent) {
                continue;
            }

            $next = $br->nextSibling;
            $isDouble = $next instanceof \DOMElement && $next->tagName === 'br';

            if ($isDouble && $parent->nodeName === 'p') {
                self::splitParagraphAt($parent, $br);
                if ($next->parentNode) {
                    $next->parentNode->removeChild($next);
                }
            } else {
                $parent->replaceChild($dom->createTextNode(' '), $br);
            }
        }
    }

    private static function splitParagraphAt(\DOMElement $p, \DOMNode $marker): void
    {
        $tail = $p->ownerDocument->createElement('p');
        $moving = false;

        foreach (iterator_to_array($p->childNodes) as $child) {
            if ($child === $marker) {
                $moving = true;
                $p->removeChild($child);
                continue;
            }
            if ($moving) {
                $tail->appendChild($child);
            }
        }

        $p->parentNode->insertBefore($tail, $p->nextSibling);
    }

    private static function unwrapDisallowed(\DOMElement $context): void
    {
        foreach (iterator_to_array($context->getElementsByTagName('*')) as $node) {
            if (!$node->parentNode || in_array($node->tagName, self::ALLOWED_TAGS, true)) {
                continue;
            }
            while ($node->firstChild) {
                $node->parentNode->insertBefore($node->firstChild, $node);
            }
            $node->parentNode->removeChild($node);
        }
    }

    /**
     * WP-галереи и CDN-прокси (напр. Jetpack Photon, i0.wp.com) вставляют в текст маленькие
     * миниатюры не с домена сайта — Дзен требует минимум 480×320 в content:encoded и, вероятно,
     * не рендерит картинки со стороннего домена. WordPress сам кладёт прямую ссылку на оригинал
     * в data-orig-file — используем её вместо текущего src, пока data-* ещё не вырезаны sanitizeAttributes().
     */
    private static function useOriginalImageFiles(\DOMElement $context): void
    {
        foreach (iterator_to_array($context->getElementsByTagName('img')) as $img) {
            $origFile = $img->getAttribute('data-orig-file');
            if ($origFile === '') {
                continue;
            }

            $img->setAttribute('src', $origFile);

            if (preg_match('/^(\d+),(\d+)$/', $img->getAttribute('data-orig-size'), $m)) {
                $img->setAttribute('width', $m[1]);
                $img->setAttribute('height', $m[2]);
            } else {
                $img->removeAttribute('width');
                $img->removeAttribute('height');
            }
        }
    }

    private static function sanitizeAttributes(\DOMElement $context): void
    {
        foreach (iterator_to_array($context->getElementsByTagName('*')) as $node) {
            $allowed = self::ALLOWED_ATTRIBUTES[$node->tagName] ?? [];
            if (!$node->hasAttributes()) {
                continue;
            }
            $toRemove = [];
            foreach ($node->attributes as $attr) {
                if (!in_array($attr->nodeName, $allowed, true)) {
                    $toRemove[] = $attr->nodeName;
                }
            }
            foreach ($toRemove as $name) {
                $node->removeAttribute($name);
            }
        }
    }

    private static function removeEmptyElements(\DOMElement $context): void
    {
        $tags = ['p', 'b', 'i', 'u', 's', 'blockquote', 'li', 'h1', 'h2', 'h3', 'h4'];
        foreach ($tags as $tag) {
            foreach (iterator_to_array($context->getElementsByTagName($tag)) as $node) {
                if (trim($node->textContent) === '' && $node->getElementsByTagName('img')->length === 0 && $node->parentNode) {
                    $node->parentNode->removeChild($node);
                }
            }
        }
    }

    private static function renderInner(\DOMElement $body): string
    {
        $html = '';
        foreach (iterator_to_array($body->childNodes) as $child) {
            $html .= $body->ownerDocument->saveHTML($child);
        }

        return trim($html);
    }
}
