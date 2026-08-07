=== SK ePaper Manager ===
Contributors: shrikantgaur
Tags: epaper, pdf, newspaper, magazine, shortcode
Requires at least: 5.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

SK ePaper Manager makes it easy to publish digital newspapers or magazines on your WordPress site using **images or PDF files**.

== Description ==

**SK ePaper Manager** lets you create and display digital ePapers by uploading multiple images or PDF files for each edition.

Visitors can browse all ePapers in an archive view or open single editions to read page by page — with support for **zoom**, **print**, **download**, and **shortcode embedding**.

Perfect for news portals, daily newspapers, schools, or local publications.

== Features ==

* Custom Post Type for ePapers
* Multi-Edition & Language Taxonomies
* **Drag-and-Drop** page reordering in Admin
* Dedicated Edition Date picker field
* **Shortcodes**: `[sk_epaper id="123"]` and `[sk_epaper_archive]`
* **View Counter & Admin Columns**: Track edition views and page count in WP Admin
* Upload multiple images **or** PDFs per edition
* Built-in archive page for all ePapers
* Single page view for each edition
* **Zoom in/out** for easier reading
* **Print edition** directly from the browser
* **Download** edition
* Modern, responsive layout

== Shortcodes ==

* `[sk_epaper id="123"]` - Embeds single ePaper reader inside any post or page.
* `[sk_epaper_archive count="6" edition="delhi"]` - Displays a grid of ePapers with optional edition filtering.

== Installation ==

1. Upload the plugin to `/wp-content/plugins/sk-epaper-manager` or install through the Plugins screen.
2. Activate **SK ePaper Manager** from the Plugins screen.
3. Use the **ePapers** menu in your dashboard to create and manage ePaper editions.

== Frequently Asked Questions ==

= Does this plugin support PDF files? =
Yes — you can upload either multiple images or PDF files for each ePaper edition.

= Can I embed an ePaper in Elementor or standard pages? =
Yes — use the shortcode `[sk_epaper id="YOUR_POST_ID"]`.

= Where are my ePapers displayed? =
By default:
* Archive: `/epapers/`
* Single Edition: `/epapers/{edition-name}/`
* Editions: `/epaper-edition/{edition-slug}/`

== Screenshots ==

1. Add New ePaper – Page upload, drag-and-drop sortable page order, and edition date selector.
2. City / Editions Management – Taxonomy management for regional and city editions.
3. Languages Management – Multi-language taxonomy configuration.
4. ePaper Analytics & View Statistics – Real-time reader engagement metrics, top rankings, live search, and AJAX pagination.
5. Shortcodes & Embed Documentation – Dynamic shortcode builder and integration guide for Gutenberg, Elementor, and PHP templates.
6. Viewer Settings – Controls toggle and frontend primary color customization options.

== Changelog ==

= 1.2.0 =
* Added Drag-and-Drop page reordering in Admin metabox via jQuery UI Sortable.
* Added "Editions" and "Languages" custom taxonomies for multi-city/language management.
* Added custom Edition Date picker meta field.
* Added shortcodes `[sk_epaper]` and `[sk_epaper_archive]` for page builder embedding.
* Added View Counter (`_sk_epaper_views`) and custom columns in Admin post list table.

= 1.1.6 =
* Fixed PDF upload visibility in admin edit screen metabox.
* Fixed frontend JavaScript error when displaying PDF attachments in ePaper viewer.

= 1.1.5 =
* Fixed direct file access protection in template files to prevent security issues.
* Updated "Tested up to" to 7.0 (current WordPress version).
* Updated PHP requirements to 7.4.

= 1.1.4 =
* Fixed Dashicons for zoom and print buttons on frontend.
* Updated template files for WordPress 6.9 compatibility.
* Minor UI improvements.

= 1.1.3 =
* Replaced all Font Awesome icons with WordPress Dashicons.
* Fixed minor UI issues for single ePaper template.
* Updated slider arrows to use Dashicons.
* General code and template improvements.

= 1.0 =
* Initial release — custom post type, image upload, archive and single templates.

== License ==

This plugin is licensed under the GPLv2 or later.  
See [https://www.gnu.org/licenses/gpl-2.0.html](https://www.gnu.org/licenses/gpl-2.0.html)