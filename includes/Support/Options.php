<?php

namespace DzenUnifiedRss\Support;

defined('ABSPATH') || exit;

class Options
{
    public const OPTION_KEY = 'dzen_unified_rss_options';

    public static function defaults(): array
    {
        return [
            'max_age_days'     => 3,
            'logo_url'         => '',
            'logo_square_url'  => '',
            'exclude_taxonomy' => '',
            'exclude_terms'    => '',
            'hide_author'      => true,
            'cover_in_content' => false,
        ];
    }

    public static function all(): array
    {
        return wp_parse_args(get_option(self::OPTION_KEY, []), self::defaults());
    }

    /**
     * @return mixed
     */
    public static function get(string $key)
    {
        $options = self::all();

        return $options[$key] ?? null;
    }

    public static function update(array $values): void
    {
        update_option(self::OPTION_KEY, wp_parse_args($values, self::all()));
    }

    // Free-плагин сам ничего не знает про лицензии — Pro-дополнение (отдельный плагин,
    // не в этом репозитории WP.org) навешивает этот фильтр, если оно установлено и активно.
    public static function isPro(): bool
    {
        return (bool) apply_filters('dzen_unified_rss_is_pro', false);
    }
}
