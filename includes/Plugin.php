<?php

namespace DzenUnifiedRss;

use DzenUnifiedRss\Admin\SettingsPage;
use DzenUnifiedRss\Free\DualStreamMode;

defined('ABSPATH') || exit;

class Plugin
{
    private static ?Plugin $instance = null;

    public static function instance(): Plugin
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
    }

    public function init(): void
    {
        add_action('init', [$this, 'registerFeeds']);
        add_action('init', [$this, 'maybeFlushRewriteRules'], 20);

        if (is_admin()) {
            (new SettingsPage())->register();
        }
    }

    public function registerFeeds(): void
    {
        // Pro-функциональность (варианты 1 и 2) регистрирует себя сама из отдельного
        // плагина-дополнения на своём собственном 'init' — здесь про неё ничего не знаем.
        (new DualStreamMode())->register();
    }

    public function maybeFlushRewriteRules(): void
    {
        if (get_transient('dzen_unified_rss_needs_flush')) {
            delete_transient('dzen_unified_rss_needs_flush');
            flush_rewrite_rules();
        }
    }
}
