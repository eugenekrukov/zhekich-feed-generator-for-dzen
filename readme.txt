=== Unified RSS for Dzen ===
Contributors: e-krukov
Donate link:
Tags: rss, dzen, yandex news, feed, news
Requires at least: 5.8
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.2.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Self-contained unified-RSS feed generator for Dzen — a replacement for the abandoned Yandex.News Feed by Teplitsa.

== Description ==

Dzen moved partner sites to a single unified RSS format for News and channel content (see [dzen.ru/help/ru/news/seamless/rss.html](https://dzen.ru/help/ru/news/seamless/rss.html), rules effective 2026-07-13). The old separate feeds are being switched off automatically. Yandex.News Feed by Teplitsa, used by thousands of sites, is no longer supported and will not be updated.

Unified RSS for Dzen is an independent replacement that does not depend on any third-party RSS plugin.

= Three unified-RSS schemes =

Dzen accepts three schemes; this plugin supports all of them:

1. **Single feed, `<contentType>` per post.** The publication type (News / channel / both) is chosen individually for each post. *Pro.*
2. **Single feed, two URLs per post.** Each post has its regular site page plus a separate shadow mirror page (Dzen compares the title/text against the page content). *Pro.*
3. **Two separate feeds.** One feed entirely for News, another entirely for the channel — no per-post differences needed, the lowest barrier to entry. *Free.*

= Free =

* Ready-made feeds `/feed/dzen-news/` (News) and `/feed/dzen/` (channel).
* Post age limit, logo and square logo, category/term exclusion, hide author — full feature parity with Yandex.News Feed by Teplitsa.
* Its own HTML processor built for Dzen's requirements: allowed tags (`p, a, b, i, u, s, h1-h4, blockquote, ul/ol+li, img, figure, figcaption`), `em` → `i`, `strong` → `b`, `br` → paragraphs, `<enclosure>` cover image at least 700px wide, `media:rating`.

= Pro =

Variants 1 and 2 are provided by a separate paid add-on, **Unified RSS for Dzen Pro** — installed
on top of this plugin, not distributed through the WordPress.org directory (all the code in this
repository is free and fully functional on its own, without Pro).

* Per-post publication type control via a meta box on the post edit screen (variant 1).
* Dual-URL mode via automatic shadow pages (variant 2).
* Purchase and activation happen on the "Pro" tab in this plugin's settings.

= Disclaimer =

This plugin is not affiliated with Yandex/Dzen and is not an official product. "Dzen" and "Yandex.News" are trademarks of their respective owners, mentioned solely to describe compatibility.

== Installation ==

1. Upload the plugin folder to `wp-content/plugins/`.
2. Activate the plugin from the WordPress admin.
3. Go to Settings → Unified RSS for Dzen, "General" tab — set the age limit, logos, exclusions.
4. Add the `/feed/dzen-news/` and `/feed/dzen/` URLs in your Dzen media account.
5. If you were using Yandex.News Feed by Teplitsa, you can deactivate it — its functionality has been fully carried over.

== Frequently Asked Questions ==

= Do I need another RSS plugin as well? =

No. Unified RSS for Dzen is fully self-contained, generates its own XML and processes content without depending on other plugins.

= What should I do with Yandex.News Feed by Teplitsa? =

You can deactivate it — all of its functionality (age limit, logos, category exclusion, hiding the author) has been carried over into this plugin's free mode, updated for Dzen's new format.

= Which URLs do I add in the Dzen dashboard? =

In free mode, both addresses from the "General" tab (`/feed/dzen-news/` and `/feed/dzen/`). In Pro, one of the addresses on the "Pro" tab, depending on the chosen variant (1 or 2).

= How do I buy Pro? =

The "Buy license" button on the "Pro" tab leads to the payment page. After payment, you receive a download link for the Unified RSS for Dzen Pro add-on and an activation key by email — install the add-on on top of this plugin and enter the key on the same tab.

= Why do some tags disappear from my post content? =

Dzen renders a limited set of HTML tags in `content:encoded` (`p, a, b, i, u, s, h1-h4, blockquote, ul/ol+li, img, figure, figcaption`). Everything else is either converted (`em`→`i`, `strong`→`b`, `br`→paragraphs) or removed — this is a platform requirement, not a plugin bug.

== Screenshots ==

1. "General" tab — free mode settings (age limit, logos, exclusions).
2. "Pro" tab — overview of variants 1 and 2, license activation form.
3. "Dzen: publication type" meta box on the post edit screen (Pro, variant 1).

== Changelog ==

= 1.2.1 =
* Renamed the plugin from "Dzen Unified RSS" to "Unified RSS for Dzen" (slug: unified-rss-for-dzen) —
  required by the WordPress.org directory: a plugin name cannot start with a trademark without
  verified ownership of that trademark's email domain.

= 1.2.0 =
* Pro functionality (variants 1 and 2) and licensing moved to a separate add-on,
  Unified RSS for Dzen Pro — this plugin is now 100% free and fully functional on its own,
  without a single line of license-gated code (required by the WordPress.org directory).
* The "Pro" tab, when the add-on isn't installed, shows what it offers and a link to buy it.

= 1.1.0 =
* "General" / "Pro" tabs in settings, a clear explanation of variants 1/2/3.
* Pro licensing via an activation key instead of a checkbox.
* Built-in help (WP Help Tab) describing the unified-RSS schemes and FAQ.
* `uninstall.php` now clears the transient and post meta, not just the option.
* Fixed a variant 2 bug: the shadow page rewrite rule was never registered (add_action('init', ...) called from inside a callback already running on init).

= 1.0.0 =
* First release: free mode (variant 3), pro mode (variants 1 and 2).

== Upgrade Notice ==

= 1.2.1 =
Plugin renamed to Unified RSS for Dzen (slug changed). WordPress can't auto-update across a slug change — deactivate the old version and install this one manually. Your settings are preserved.

= 1.2.0 =
If you had Pro features enabled, install the separate Unified RSS for Dzen Pro add-on and reactivate your license on the Pro tab after updating this plugin.

= 1.1.0 =
If Pro was enabled via the old "Pro mode" checkbox, reactivate your license on the Pro tab after updating.
