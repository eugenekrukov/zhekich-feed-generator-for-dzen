<?php
/**
 * Plugin Name: Dzen Unified RSS
 * Plugin URI: https://github.com/eugenekrukov/dzen-unified-rss
 * Description: Самостоятельный генератор unified-RSS фида для Дзена (Новости + канал) по правилам от 13.07.2026 (dzen.ru/help/ru/news/seamless/rss.html). Замена для заброшенного Yandex.News Feed by Teplitsa.
 * Version: 1.2.0
 * Requires PHP: 7.4
 * Author: Eugene Krukov
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: dzen-unified-rss
 */

defined('ABSPATH') || exit;

define('DZEN_UNIFIED_RSS_DIR', plugin_dir_path(__FILE__));
define('DZEN_UNIFIED_RSS_URL', plugin_dir_url(__FILE__));
define('DZEN_UNIFIED_RSS_VERSION', '1.2.0');

spl_autoload_register(static function (string $class): void {
    $prefix = 'DzenUnifiedRss\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $path = DZEN_UNIFIED_RSS_DIR . 'includes/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($path)) {
        require $path;
    }
});

register_activation_hook(__FILE__, static function (): void {
    \DzenUnifiedRss\Plugin::instance()->registerFeeds();
    flush_rewrite_rules();
});

register_deactivation_hook(__FILE__, 'flush_rewrite_rules');

add_action('plugins_loaded', static function (): void {
    \DzenUnifiedRss\Plugin::instance()->init();
});
