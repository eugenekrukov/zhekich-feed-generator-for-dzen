<?php

namespace DzenUnifiedRss\Admin;

use DzenUnifiedRss\Support\Options;

defined('ABSPATH') || exit;

class SettingsPage
{
    private const PAGE_SLUG = 'dzen-unified-rss';
    private const SETTINGS_GROUP = 'dzen_unified_rss_group';
    public const PRO_TAB_ACTION = 'dzen_unified_rss_render_pro_tab';
    private const PURCHASE_URL = 'https://dzenrss.studio1008.com/';
    private const STYLE_HANDLE = 'dzen-unified-rss-admin';

    private string $pageHook = '';

    public function register(): void
    {
        add_action('admin_menu', [$this, 'addMenu']);
        add_action('admin_init', [$this, 'registerSettings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueStyles']);
    }

    public function addMenu(): void
    {
        $hook = add_options_page(
            __('Zhekich Feed Generator for Dzen', 'zhekich-feed-generator-for-dzen'),
            __('Zhekich Feed Generator for Dzen', 'zhekich-feed-generator-for-dzen'),
            'manage_options',
            self::PAGE_SLUG,
            [$this, 'renderPage']
        );

        if ($hook) {
            $this->pageHook = $hook;
            add_action("load-{$hook}", [$this, 'addHelpTab']);
        }
    }

    public function enqueueStyles(string $hookSuffix): void
    {
        if ($hookSuffix !== $this->pageHook) {
            return;
        }

        wp_register_style(self::STYLE_HANDLE, false, [], DZEN_UNIFIED_RSS_VERSION);
        wp_enqueue_style(self::STYLE_HANDLE);
        wp_add_inline_style(self::STYLE_HANDLE, '
            .dzen-unified-rss-settings .dzen-badge { display:inline-block; font-size:11px; line-height:1.6; padding:1px 8px; border-radius:10px; margin-left:6px; vertical-align:middle; font-weight:600; }
            .dzen-unified-rss-settings .dzen-badge-pro { background:#f3e8ff; color:#6b21a8; }
            .dzen-unified-rss-settings .dzen-badge-free { background:#e6f4ea; color:#1e7e34; }
            .dzen-unified-rss-settings .dzen-badge-active { background:#d1fae5; color:#065f46; }
            .dzen-unified-rss-settings .dzen-variants-overview ol { margin: 8px 0 8px 20px; }
            .dzen-unified-rss-settings .dzen-variants-overview li { margin-bottom: 6px; }
            .dzen-unified-rss-settings .postbox { margin-top: 16px; padding: 4px 16px 12px; }
            .dzen-unified-rss-settings .dzen-locked .postbox { background: #fafafa; }
            .dzen-unified-rss-settings .dzen-locked h3 { color: #50575e; }
            .dzen-unified-rss-settings .dzen-license-box code { padding: 3px 6px; }
        ');
    }

    public function addHelpTab(): void
    {
        $screen = get_current_screen();
        if (!$screen) {
            return;
        }

        $screen->add_help_tab([
            'id'      => 'dzen-variants',
            'title'   => __('Варианты unified-RSS', 'zhekich-feed-generator-for-dzen'),
            'content' => $this->helpVariantsHtml(),
        ]);

        $screen->add_help_tab([
            'id'      => 'dzen-faq',
            'title'   => __('Частые вопросы', 'zhekich-feed-generator-for-dzen'),
            'content' => $this->helpFaqHtml(),
        ]);

        $screen->set_help_sidebar(
            '<p><strong>' . esc_html__('Полезные ссылки', 'zhekich-feed-generator-for-dzen') . '</strong></p>' .
            '<p><a href="https://dzen.ru/help/ru/news/seamless/rss.html" target="_blank" rel="noopener">' . esc_html__('Требования Дзена к unified-RSS', 'zhekich-feed-generator-for-dzen') . '</a></p>'
        );
    }

    private function helpVariantsHtml(): string
    {
        return '<p>' . esc_html__('Дзен принимает три схемы unified-RSS — плагин поддерживает все три:', 'zhekich-feed-generator-for-dzen') . '</p>'
            . '<p><strong>' . esc_html__('Вариант 1 (Pro).', 'zhekich-feed-generator-for-dzen') . '</strong> '
            . esc_html__('Один поток, у каждой записи свой тег <contentType> («Новости и канал» / «Только Новости» / «Только канал») — выбирается на самой записи через meta-box. Подходит, если разные посты должны идти в разные места.', 'zhekich-feed-generator-for-dzen')
            . '</p>'
            . '<p><strong>' . esc_html__('Вариант 2 (Pro).', 'zhekich-feed-generator-for-dzen') . '</strong> '
            . esc_html__('Один поток, но у каждой записи два адреса — обычная страница сайта и отдельная теневая страница-зеркало. Нужен, потому что Дзен сверяет заголовок и текст публикации с содержимым страницы по ссылке, а оформление для Новостей и для сайта иногда должно отличаться.', 'zhekich-feed-generator-for-dzen')
            . '</p>'
            . '<p><strong>' . esc_html__('Вариант 3 (Free).', 'zhekich-feed-generator-for-dzen') . '</strong> '
            . esc_html__('Два отдельных, жёстко размеченных потока — один целиком для Новостей, другой целиком для канала. Не требует различий между постами — самый низкий порог входа, подходит почти всем сайтам, мигрирующим с Yandex.News Feed by Teplitsa.', 'zhekich-feed-generator-for-dzen')
            . '</p>';
    }

    private function helpFaqHtml(): string
    {
        return '<p><strong>' . esc_html__('Нужен ли ещё какой-то RSS-плагин для работы?', 'zhekich-feed-generator-for-dzen') . '</strong><br>'
            . esc_html__('Нет, Zhekich Feed Generator for Dzen полностью самостоятелен и не зависит от других плагинов.', 'zhekich-feed-generator-for-dzen') . '</p>'
            . '<p><strong>' . esc_html__('Что делать с Yandex.News Feed by Teplitsa?', 'zhekich-feed-generator-for-dzen') . '</strong><br>'
            . esc_html__('Его можно деактивировать — этот плагин полностью закрывает его функциональность (лимит возраста, логотипы, исключение рубрик, скрытие автора) в схеме unified-RSS.', 'zhekich-feed-generator-for-dzen') . '</p>'
            . '<p><strong>' . esc_html__('Какие URL добавлять в кабинет Дзена?', 'zhekich-feed-generator-for-dzen') . '</strong><br>'
            . esc_html__('В free-режиме — оба адреса вкладки «Основное». В Pro — один из адресов вкладки «Pro», в зависимости от выбранного варианта.', 'zhekich-feed-generator-for-dzen') . '</p>';
    }

    public function registerSettings(): void
    {
        register_setting(self::SETTINGS_GROUP, Options::OPTION_KEY, [
            'sanitize_callback' => [$this, 'sanitize'],
        ]);
    }

    public function sanitize($input): array
    {
        return [
            'max_age_days'     => max(0, (int) ($input['max_age_days'] ?? 0)),
            'logo_url'         => esc_url_raw($input['logo_url'] ?? ''),
            'logo_square_url'  => esc_url_raw($input['logo_square_url'] ?? ''),
            'exclude_taxonomy' => sanitize_key($input['exclude_taxonomy'] ?? ''),
            'exclude_terms'    => sanitize_text_field($input['exclude_terms'] ?? ''),
            'hide_author'      => !empty($input['hide_author']),
            'cover_in_content' => !empty($input['cover_in_content']),
        ];
    }

    public function renderPage(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Только переключение отображаемой вкладки, никаких изменений состояния — нонс не нужен.
        $tab = isset($_GET['tab']) && $_GET['tab'] === 'pro' ? 'pro' : 'general'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        ?>
        <div class="wrap dzen-unified-rss-settings">
            <h1><?php esc_html_e('Zhekich Feed Generator for Dzen', 'zhekich-feed-generator-for-dzen'); ?></h1>
            <?php $this->renderVariantsOverview(); ?>

            <h2 class="nav-tab-wrapper">
                <a href="<?php echo esc_url(add_query_arg(['page' => self::PAGE_SLUG, 'tab' => 'general'], admin_url('options-general.php'))); ?>"
                   class="nav-tab <?php echo $tab === 'general' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Основное (Free)', 'zhekich-feed-generator-for-dzen'); ?>
                </a>
                <a href="<?php echo esc_url(add_query_arg(['page' => self::PAGE_SLUG, 'tab' => 'pro'], admin_url('options-general.php'))); ?>"
                   class="nav-tab <?php echo $tab === 'pro' ? 'nav-tab-active' : ''; ?>">
                    Pro
                    <?php if (Options::isPro()): ?>
                        <span class="dzen-badge dzen-badge-active"><?php esc_html_e('активна', 'zhekich-feed-generator-for-dzen'); ?></span>
                    <?php else: ?>
                        <span class="dzen-badge dzen-badge-pro">🔒</span>
                    <?php endif; ?>
                </a>
            </h2>

            <?php if ($tab === 'general'): ?>
                <?php $this->renderGeneralTab(); ?>
            <?php else: ?>
                <?php $this->renderProTab(); ?>
            <?php endif; ?>
        </div>
        <?php
    }

    private function renderVariantsOverview(): void
    {
        ?>
        <div class="notice notice-info inline dzen-variants-overview">
            <p><strong><?php esc_html_e('Дзен принимает три схемы unified-RSS:', 'zhekich-feed-generator-for-dzen'); ?></strong></p>
            <ol>
                <li><strong><?php esc_html_e('Вариант 1', 'zhekich-feed-generator-for-dzen'); ?></strong> — <?php esc_html_e('один поток, <contentType> свой у каждой записи (meta-box на посте).', 'zhekich-feed-generator-for-dzen'); ?> <span class="dzen-badge dzen-badge-pro">Pro</span></li>
                <li><strong><?php esc_html_e('Вариант 2', 'zhekich-feed-generator-for-dzen'); ?></strong> — <?php esc_html_e('один поток, у каждой записи два адреса (страница сайта + теневая копия).', 'zhekich-feed-generator-for-dzen'); ?> <span class="dzen-badge dzen-badge-pro">Pro</span></li>
                <li><strong><?php esc_html_e('Вариант 3', 'zhekich-feed-generator-for-dzen'); ?></strong> — <?php esc_html_e('два отдельных потока: весь целиком в Новости, весь целиком в канал. Различий между постами не требует.', 'zhekich-feed-generator-for-dzen'); ?> <span class="dzen-badge dzen-badge-free">Free</span></li>
            </ol>
            <p><?php
                printf(
                    /* translators: %s: "Справка" (Help) tab label */
                    esc_html__('Подробнее — во вкладке %s в правом верхнем углу этой страницы.', 'zhekich-feed-generator-for-dzen'),
                    '<em>' . esc_html__('Справка', 'zhekich-feed-generator-for-dzen') . '</em>'
                );
            ?></p>
        </div>
        <?php
    }

    private function renderGeneralTab(): void
    {
        $options = Options::all();
        $key = Options::OPTION_KEY;
        ?>
        <div class="postbox">
            <div class="inside">
                <form method="post" action="options.php">
                    <?php settings_fields(self::SETTINGS_GROUP); ?>
                    <table class="form-table">
                        <tr>
                            <th><label for="max_age_days"><?php esc_html_e('Максимальный возраст записей (дни)', 'zhekich-feed-generator-for-dzen'); ?></label></th>
                            <td>
                                <input type="number" min="0" id="max_age_days"
                                       name="<?php echo esc_attr($key); ?>[max_age_days]"
                                       value="<?php echo esc_attr($options['max_age_days']); ?>">
                                <p class="description"><?php esc_html_e('Только для потока Новостей — фильтр по дате публикации.', 'zhekich-feed-generator-for-dzen'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="logo_url"><?php esc_html_e('Логотип (URL)', 'zhekich-feed-generator-for-dzen'); ?></label></th>
                            <td>
                                <input type="url" class="regular-text" id="logo_url"
                                       name="<?php echo esc_attr($key); ?>[logo_url]"
                                       value="<?php echo esc_attr($options['logo_url']); ?>">
                            </td>
                        </tr>
                        <tr>
                            <th><label for="logo_square_url"><?php esc_html_e('Квадратный логотип (URL)', 'zhekich-feed-generator-for-dzen'); ?></label></th>
                            <td>
                                <input type="url" class="regular-text" id="logo_square_url"
                                       name="<?php echo esc_attr($key); ?>[logo_square_url]"
                                       value="<?php echo esc_attr($options['logo_square_url']); ?>">
                            </td>
                        </tr>
                        <tr>
                            <th><label for="exclude_taxonomy"><?php esc_html_e('Таксономия для исключения', 'zhekich-feed-generator-for-dzen'); ?></label></th>
                            <td>
                                <select id="exclude_taxonomy" name="<?php echo esc_attr($key); ?>[exclude_taxonomy]">
                                    <option value=""><?php esc_html_e('— не исключать —', 'zhekich-feed-generator-for-dzen'); ?></option>
                                    <?php foreach (get_taxonomies(['public' => true], 'objects') as $taxonomy): ?>
                                        <option value="<?php echo esc_attr($taxonomy->name); ?>"
                                            <?php selected($options['exclude_taxonomy'], $taxonomy->name); ?>>
                                            <?php echo esc_html($taxonomy->labels->name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="exclude_terms"><?php esc_html_e('ID термов для исключения', 'zhekich-feed-generator-for-dzen'); ?></label></th>
                            <td>
                                <input type="text" class="regular-text" id="exclude_terms"
                                       name="<?php echo esc_attr($key); ?>[exclude_terms]"
                                       value="<?php echo esc_attr($options['exclude_terms']); ?>"
                                       placeholder="671, 812">
                                <p class="description"><?php esc_html_e('Через запятую, ID термов таксономии выше (например, «Новости компаний»).', 'zhekich-feed-generator-for-dzen'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('Скрыть автора', 'zhekich-feed-generator-for-dzen'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="<?php echo esc_attr($key); ?>[hide_author]"
                                        <?php checked($options['hide_author']); ?>>
                                    <?php esc_html_e('не выводить <author> в фиде', 'zhekich-feed-generator-for-dzen'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('Включать изображение превью в текст статьи', 'zhekich-feed-generator-for-dzen'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="<?php echo esc_attr($key); ?>[cover_in_content]"
                                        <?php checked($options['cover_in_content']); ?>>
                                    <?php esc_html_e('если в тексте статьи вообще нет картинок — добавить обложку первым элементом', 'zhekich-feed-generator-for-dzen'); ?>
                                </label>
                                <p class="description"><?php esc_html_e('Не дублирует, если в тексте уже есть хотя бы одна картинка.', 'zhekich-feed-generator-for-dzen'); ?></p>
                            </td>
                        </tr>
                    </table>
                    <?php submit_button(); ?>
                </form>

                <p class="description">
                    <?php esc_html_e('Free-режим (вариант 3, всегда активен):', 'zhekich-feed-generator-for-dzen'); ?><br>
                    <code><?php echo esc_html(home_url('/feed/dzen-news/')); ?></code> — <?php esc_html_e('Новости', 'zhekich-feed-generator-for-dzen'); ?>,
                    <code><?php echo esc_html(home_url('/feed/dzen/')); ?></code> — <?php esc_html_e('канал', 'zhekich-feed-generator-for-dzen'); ?>.
                </p>
            </div>
        </div>
        <?php
    }

    // Само содержимое Pro-вкладки рисует плагин-дополнение Zhekich Feed Generator for Dzen Pro
    // (не входит в этот репозиторий WP.org — весь код здесь бесплатный и полностью рабочий).
    // Если дополнение не установлено — просто рассказываем, что оно даёт, и куда за ним идти.
    private function renderProTab(): void
    {
        if (has_action(self::PRO_TAB_ACTION)) {
            do_action(self::PRO_TAB_ACTION); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound -- значение константы уже с префиксом dzen_unified_rss_

            return;
        }
        ?>
        <div class="postbox">
            <div class="inside">
                <p><?php
                    printf(
                        /* translators: %s: "Zhekich Feed Generator for Dzen Pro" (product name, not translated) */
                        esc_html__('Варианты 1 и 2 (по-постовый тип публикации и второй адрес на публикацию) не входят в этот плагин. Их даёт отдельное платное дополнение %s — устанавливается поверх этого плагина и не распространяется через каталог WordPress.org.', 'zhekich-feed-generator-for-dzen'),
                        '<strong>Zhekich Feed Generator for Dzen Pro</strong>'
                    );
                ?></p>
                <p><a class="button button-primary" href="<?php echo esc_url(self::PURCHASE_URL); ?>" target="_blank" rel="noopener"><?php esc_html_e('Подробнее и купить', 'zhekich-feed-generator-for-dzen'); ?></a></p>
            </div>
        </div>
        <?php
    }
}
