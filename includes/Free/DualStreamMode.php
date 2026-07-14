<?php

namespace DzenUnifiedRss\Free;

use DzenUnifiedRss\Feed\FeedGenerator;

defined('ABSPATH') || exit;

/**
 * Вариант 3 из требований Дзена: два независимых потока с разными URL,
 * каждый жёстко размечен одним типом на все записи. Не требует различий
 * между постами — самый низкий порог для замены Teplitsa, поэтому free.
 */
class DualStreamMode
{
    public const NEWS_SLUG = 'dzen-news';
    public const CHANNEL_SLUG = 'dzen';

    public function register(): void
    {
        add_feed(self::NEWS_SLUG, [$this, 'renderNewsFeed']);
        add_feed(self::CHANNEL_SLUG, [$this, 'renderChannelFeed']);
    }

    public function renderNewsFeed(): void
    {
        FeedGenerator::output('news_only', true);
    }

    public function renderChannelFeed(): void
    {
        FeedGenerator::output('blogs_only', false);
    }
}
