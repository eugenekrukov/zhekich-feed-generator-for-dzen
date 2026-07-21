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
            'Zhekich Feed Generator for Dzen',
            'Zhekich Feed Generator for Dzen',
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
            'title'   => 'Варианты unified-RSS',
            'content' => $this->helpVariantsHtml(),
        ]);

        $screen->add_help_tab([
            'id'      => 'dzen-faq',
            'title'   => 'Частые вопросы',
            'content' => $this->helpFaqHtml(),
        ]);

        $screen->set_help_sidebar(
            '<p><strong>Полезные ссылки</strong></p>' .
            '<p><a href="https://dzen.ru/help/ru/news/seamless/rss.html" target="_blank" rel="noopener">Требования Дзена к unified-RSS</a></p>'
        );
    }

    private function helpVariantsHtml(): string
    {
        return '<p>Дзен принимает три схемы unified-RSS — плагин поддерживает все три:</p>'
            . '<p><strong>Вариант 1 (Pro).</strong> Один поток, у каждой записи свой тег &lt;contentType&gt; '
            . '(«Новости и канал» / «Только Новости» / «Только канал») — выбирается на самой записи через meta-box. '
            . 'Подходит, если разные посты должны идти в разные места.</p>'
            . '<p><strong>Вариант 2 (Pro).</strong> Один поток, но у каждой записи два адреса — обычная страница сайта '
            . 'и отдельная теневая страница-зеркало. Нужен, потому что Дзен сверяет заголовок и текст публикации '
            . 'с содержимым страницы по ссылке, а оформление для Новостей и для сайта иногда должно отличаться.</p>'
            . '<p><strong>Вариант 3 (Free).</strong> Два отдельных, жёстко размеченных потока — один целиком для Новостей, '
            . 'другой целиком для канала. Не требует различий между постами — самый низкий порог входа, подходит почти всем '
            . 'сайтам, мигрирующим с Yandex.News Feed by Teplitsa.</p>';
    }

    private function helpFaqHtml(): string
    {
        return '<p><strong>Нужен ли ещё какой-то RSS-плагин для работы?</strong><br>'
            . 'Нет, Zhekich Feed Generator for Dzen полностью самостоятелен и не зависит от других плагинов.</p>'
            . '<p><strong>Что делать с Yandex.News Feed by Teplitsa?</strong><br>'
            . 'Его можно деактивировать — этот плагин полностью закрывает его функциональность (лимит возраста, логотипы, '
            . 'исключение рубрик, скрытие автора) в схеме unified-RSS.</p>'
            . '<p><strong>Какие URL добавлять в кабинет Дзена?</strong><br>'
            . 'В free-режиме — оба адреса вкладки «Основное». В Pro — один из адресов вкладки «Pro», в зависимости '
            . 'от выбранного варианта.</p>';
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
            <h1>Zhekich Feed Generator for Dzen</h1>
            <?php $this->renderVariantsOverview(); ?>

            <h2 class="nav-tab-wrapper">
                <a href="<?php echo esc_url(add_query_arg(['page' => self::PAGE_SLUG, 'tab' => 'general'], admin_url('options-general.php'))); ?>"
                   class="nav-tab <?php echo $tab === 'general' ? 'nav-tab-active' : ''; ?>">
                    Основное (Free)
                </a>
                <a href="<?php echo esc_url(add_query_arg(['page' => self::PAGE_SLUG, 'tab' => 'pro'], admin_url('options-general.php'))); ?>"
                   class="nav-tab <?php echo $tab === 'pro' ? 'nav-tab-active' : ''; ?>">
                    Pro
                    <?php if (Options::isPro()): ?>
                        <span class="dzen-badge dzen-badge-active">активна</span>
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
            <p><strong>Дзен принимает три схемы unified-RSS:</strong></p>
            <ol>
                <li><strong>Вариант 1</strong> — один поток, &lt;contentType&gt; свой у каждой записи (meta-box на посте). <span class="dzen-badge dzen-badge-pro">Pro</span></li>
                <li><strong>Вариант 2</strong> — один поток, у каждой записи два адреса (страница сайта + теневая копия). <span class="dzen-badge dzen-badge-pro">Pro</span></li>
                <li><strong>Вариант 3</strong> — два отдельных потока: весь целиком в Новости, весь целиком в канал. Различий между постами не требует. <span class="dzen-badge dzen-badge-free">Free</span></li>
            </ol>
            <p>Подробнее — во вкладке <em>Справка</em> в правом верхнем углу этой страницы.</p>
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
                            <th><label for="max_age_days">Максимальный возраст записей (дни)</label></th>
                            <td>
                                <input type="number" min="0" id="max_age_days"
                                       name="<?php echo esc_attr($key); ?>[max_age_days]"
                                       value="<?php echo esc_attr($options['max_age_days']); ?>">
                                <p class="description">Только для потока Новостей — фильтр по дате публикации.</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="logo_url">Логотип (URL)</label></th>
                            <td>
                                <input type="url" class="regular-text" id="logo_url"
                                       name="<?php echo esc_attr($key); ?>[logo_url]"
                                       value="<?php echo esc_attr($options['logo_url']); ?>">
                            </td>
                        </tr>
                        <tr>
                            <th><label for="logo_square_url">Квадратный логотип (URL)</label></th>
                            <td>
                                <input type="url" class="regular-text" id="logo_square_url"
                                       name="<?php echo esc_attr($key); ?>[logo_square_url]"
                                       value="<?php echo esc_attr($options['logo_square_url']); ?>">
                            </td>
                        </tr>
                        <tr>
                            <th><label for="exclude_taxonomy">Таксономия для исключения</label></th>
                            <td>
                                <select id="exclude_taxonomy" name="<?php echo esc_attr($key); ?>[exclude_taxonomy]">
                                    <option value="">— не исключать —</option>
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
                            <th><label for="exclude_terms">ID термов для исключения</label></th>
                            <td>
                                <input type="text" class="regular-text" id="exclude_terms"
                                       name="<?php echo esc_attr($key); ?>[exclude_terms]"
                                       value="<?php echo esc_attr($options['exclude_terms']); ?>"
                                       placeholder="671, 812">
                                <p class="description">Через запятую, ID термов таксономии выше (например, «Новости компаний»).</p>
                            </td>
                        </tr>
                        <tr>
                            <th>Скрыть автора</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="<?php echo esc_attr($key); ?>[hide_author]"
                                        <?php checked($options['hide_author']); ?>>
                                    не выводить &lt;author&gt; в фиде
                                </label>
                            </td>
                        </tr>
                    </table>
                    <?php submit_button(); ?>
                </form>

                <p class="description">
                    Free-режим (вариант 3, всегда активен):<br>
                    <code><?php echo esc_html(home_url('/feed/dzen-news/')); ?></code> — Новости,
                    <code><?php echo esc_html(home_url('/feed/dzen/')); ?></code> — канал.
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
                <p>Варианты 1 и 2 (по-постовый тип публикации и второй адрес на публикацию) не входят
                    в этот плагин. Их даёт отдельное платное дополнение
                    <strong>Zhekich Feed Generator for Dzen Pro</strong> — устанавливается поверх этого
                    плагина и не распространяется через каталог WordPress.org.</p>
                <p><a class="button button-primary" href="<?php echo esc_url(self::PURCHASE_URL); ?>" target="_blank" rel="noopener">Подробнее и купить</a></p>
            </div>
        </div>
        <?php
    }
}
