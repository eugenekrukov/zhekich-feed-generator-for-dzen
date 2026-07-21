<?php

namespace DzenUnifiedRss\Feed;

use DzenUnifiedRss\Support\Options;

defined('ABSPATH') || exit;

/**
 * Строит unified-RSS фид под требования Дзена (dzen.ru/help/ru/news/seamless/rss.html).
 */
class FeedGenerator
{
    /**
     * Вариант 3: фиксированный <contentType> на весь поток.
     */
    public static function output(string $contentType, bool $isNewsStream): void
    {
        self::renderMulti(QueryHelper::args($isNewsStream), $isNewsStream, static function (\WP_Post $post) use ($contentType) {
            return [[$contentType, null]];
        });
    }

    /**
     * Варианты 1 и 2 (per-post contentType, dual-link) НЕ реализованы в этом (бесплатном,
     * хостящемся на WP.org) плагине — даже в виде неиспользуемого кода. Их рендерит
     * DzenUnifiedRss\Pro\ProFeedGenerator в отдельном платном дополнении, которое на
     * WP.org не публикуется. WP.org прямо запрещает держать в бесплатном плагине готовую,
     * но не задействованную реализацию премиум-функций «на случай, если купят Pro» —
     * см. review R❗TRM unified-rss-for-dzen/e-krukov/16Jul26/T2 (Trialware/Guideline 5).
     */

    /**
     * @param callable $resolveVariants function(\WP_Post $post): array<array{0:string,1:?string}>
     */
    private static function renderMulti(array $queryArgs, bool $isNewsStream, callable $resolveVariants): void
    {
        header('Content-Type: ' . feed_content_type('rss2') . '; charset=' . get_option('blog_charset'), true);
        nocache_headers();

        $query = new \WP_Query($queryArgs);

        $dom = new \DOMDocument('1.0', get_option('blog_charset'));
        $dom->formatOutput = true;

        $rss = $dom->createElement('rss');
        $rss->setAttribute('version', '2.0');
        $rss->setAttribute('xmlns:yandex', 'http://news.yandex.ru');
        $rss->setAttribute('xmlns:media', 'http://search.yahoo.com/mrss/');
        $rss->setAttribute('xmlns:content', 'http://purl.org/rss/1.0/modules/content/');
        $dom->appendChild($rss);

        $channel = $dom->createElement('channel');
        $rss->appendChild($channel);

        self::appendChannelMeta($dom, $channel, $isNewsStream);

        foreach ($query->posts as $post) {
            foreach ($resolveVariants($post) as [$contentType, $link]) {
                self::appendItem($dom, $channel, $post, $contentType, $link);
            }
        }

        // Весь документ уже собран из escaped/CDATA-обёрнутых узлов DOMDocument выше —
        // saveXML() отдаёт готовый well-formed XML, а не сырой пользовательский ввод.
        echo $dom->saveXML(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        exit;
    }

    private static function appendChannelMeta(\DOMDocument $dom, \DOMElement $channel, bool $isNewsStream): void
    {
        $channel->appendChild($dom->createElement('title', htmlspecialchars(get_bloginfo('name'))));
        $channel->appendChild($dom->createElement('link', get_bloginfo('url')));
        $channel->appendChild($dom->createElement('description', htmlspecialchars(get_bloginfo('description'))));
        $channel->appendChild($dom->createElement('language', get_bloginfo('language')));

        if (!$isNewsStream) {
            return;
        }

        $logo = (string) Options::get('logo_url');
        if ($logo !== '') {
            $channel->appendChild($dom->createElement('yandex:logo', $logo));
        }

        $logoSquare = (string) Options::get('logo_square_url');
        if ($logoSquare !== '') {
            $node = $dom->createElement('yandex:logo', $logoSquare);
            $node->setAttribute('type', 'square');
            $channel->appendChild($node);
        }
    }

    private static function appendItem(\DOMDocument $dom, \DOMElement $channel, \WP_Post $post, string $contentType, ?string $link): void
    {
        // 'the_content' — стандартный хук ядра WordPress (шорткоды, embeds), а не наш собственный.
        $processed = ContentProcessor::process(apply_filters('the_content', $post->post_content)); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
        if (!ContentProcessor::meetsMinLength($processed)) {
            return;
        }

        $link ??= get_permalink($post);

        $item = $dom->createElement('item');
        $item->appendChild(self::cdataElement($dom, 'title', get_the_title($post)));
        $item->appendChild($dom->createElement('link', $link));
        $item->appendChild($dom->createElement('guid', $link));
        $item->appendChild($dom->createElement('pubDate', get_post_time('D, d M Y H:i:s O', true, $post)));
        $item->appendChild($dom->createElement('contentType', $contentType));

        foreach (wp_get_post_categories($post->ID, ['fields' => 'names']) as $category) {
            $item->appendChild(self::cdataElement($dom, 'category', $category));
        }

        if (!Options::get('hide_author')) {
            $author = get_the_author_meta('display_name', $post->post_author);
            if ($author) {
                $item->appendChild(self::cdataElement($dom, 'author', $author));
            }
        }

        $image = ImageResolver::resolve($post->ID);
        if ($image) {
            $enclosure = $dom->createElement('enclosure');
            $enclosure->setAttribute('url', $image['url']);
            $enclosure->setAttribute('type', $image['type']);
            $item->appendChild($enclosure);
        }

        $rating = $dom->createElement('media:rating', 'nonadult');
        $rating->setAttribute('scheme', 'urn:simple');
        $item->appendChild($rating);

        $item->appendChild(self::cdataElement($dom, 'content:encoded', $processed));

        $channel->appendChild($item);
    }

    private static function cdataElement(\DOMDocument $dom, string $tagName, string $text): \DOMElement
    {
        $el = $dom->createElement($tagName);
        $el->appendChild($dom->createCDATASection($text));

        return $el;
    }
}
