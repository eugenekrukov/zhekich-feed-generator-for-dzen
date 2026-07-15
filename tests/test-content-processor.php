<?php

/**
 * Голый php-скрипт без WordPress/фреймворков — ContentProcessor не зависит от WP API,
 * поэтому проверяется напрямую. Запуск: php tests/test-content-processor.php
 */

// ContentProcessor теперь несёт стандартный guard defined('ABSPATH') || exit —
// этот скрипт не грузит WordPress, поэтому подставляем константу сами.
defined('ABSPATH') || define('ABSPATH', __DIR__);

require __DIR__ . '/../includes/Feed/ContentProcessor.php';

use DzenUnifiedRss\Feed\ContentProcessor;

// str_contains() требует PHP 8.0+, целевая среда (Beget, r-berega.info) — PHP 7.4
if (!function_exists('str_contains')) {
    function str_contains(string $haystack, string $needle): bool
    {
        return $needle === '' || strpos($haystack, $needle) !== false;
    }
}

$failures = 0;

function check(string $label, bool $condition): void
{
    global $failures;
    if ($condition) {
        echo "OK: {$label}\n";
        return;
    }
    $failures++;
    echo "FAIL: {$label}\n";
}

$out = ContentProcessor::process('<p>Текст <em>курсив</em> и <strong>жирный</strong>.</p>');
check('em -> i', str_contains($out, '<i>курсив</i>'));
check('strong -> b', str_contains($out, '<b>жирный</b>'));

$out = ContentProcessor::process('<p>Первая строка<br>вторая строка</p><p>Абзац1<br><br>Абзац2</p>');
check('single br -> space, no <br> left', !str_contains($out, '<br'));
check('double br splits paragraph', substr_count($out, '<p>') >= 3);
check('paragraph split kept both halves', str_contains($out, 'Абзац1') && str_contains($out, 'Абзац2'));

$out = ContentProcessor::process('<div class="wrap"><p>Текст внутри div</p></div>');
check('div unwrapped', !str_contains($out, '<div'));
check('content inside div preserved', str_contains($out, 'Текст внутри div'));

$out = ContentProcessor::process('<p>До</p><script>alert(1)</script><iframe src="x"></iframe><p>После</p>');
check('script removed with content', !str_contains($out, 'alert'));
check('iframe removed', !str_contains($out, '<iframe'));

$out = ContentProcessor::process('<p onclick="x()">Текст</p><a href="https://a.ru" onclick="y()">ссылка</a>');
check('onclick stripped from p', !str_contains($out, 'onclick'));
check('href kept on a', str_contains($out, 'href="https://a.ru"'));

check('min length: short text fails', !ContentProcessor::meetsMinLength('<p>Коротко.</p>', 300));
check('min length: long text passes', ContentProcessor::meetsMinLength('<p>' . str_repeat('Текст ', 100) . '</p>', 300));

echo "\n";
if ($failures > 0) {
    echo "{$failures} check(s) failed.\n";
    exit(1);
}
echo "All ContentProcessor checks passed.\n";
