<?php

defined('WP_UNINSTALL_PLUGIN') || exit;

delete_option('dzen_unified_rss_options');
delete_transient('dzen_unified_rss_needs_flush');

// ключ продублирован строкой (не через Pro\PerPostContentType::META_KEY) —
// на этапе uninstall автозагрузчик плагина уже не гарантированно доступен
delete_post_meta_by_key('_dzen_content_type');
