=== SK ePaper Manager ===
Contributors: shrikantgaur
Tags: epaper, pdf, newspaper, magazine, shortcode
Requires at least: 5.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 2.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

SK ePaper Manager makes it easy to publish digital newspapers or magazines on your WordPress site using **images or PDF files**.

== Description ==

**SK ePaper Manager** lets you create and display digital ePapers by uploading multiple images or PDF files for each edition.

Visitors can browse all ePapers in an archive view or open single editions to read page by page — with support for **zoom**, **print**, **download**, and **shortcode embedding**.

Perfect for news portals, daily newspapers, schools, or local publications.

**Try it first:** use the *Live Preview* button on this page to open a demo site with sample editions already loaded. Nothing is installed on your own site.

== Features ==

**For your readers**

* Page reader with zoom, print (one page or all), download, keyboard arrows and touch swipe
* Thumbnail page rail, below the reader or as a side rail, with optional section names
* Pages load as the reader moves through them, so a long edition opens quickly
* Archive filters: search, Edition, Language, month and exact edition date
* A "More Editions" row under every edition
* Accessible controls, live page counter and reduced-motion support

**For you**

* Custom post type for editions, with Editions (city or regional) and Languages taxonomies
* Drag-and-drop page ordering, a publication date, and a name for each page
* Upload images or PDFs, mixed in the same edition
* Analytics with reader engagement, top editions and an edition date range
* Getting Started screen with a guide and one-click sample content
* Blocks and shortcodes: `[sk_epaper id="123"]` and `[sk_epaper_archive]`
* Brand colour, content width and reader controls, all configurable

**Built to last**

* View counting keeps working behind full page caching
* Templates can be overridden from your theme
* Uninstall removes nothing unless you ask it to

== Shortcodes ==

* `[sk_epaper id="123"]` - Embeds single ePaper reader inside any post or page.
* `[sk_epaper_archive count="6" edition="delhi"]` - Displays a grid of ePapers with optional edition filtering.

== Installation ==

1. Upload the plugin to `/wp-content/plugins/sk-epaper-manager` or install through the Plugins screen.
2. Activate **SK ePaper Manager** from the Plugins screen.
3. Use the **ePapers** menu in your dashboard to create and manage ePaper editions.

== Frequently Asked Questions ==

= Can I try it before installing? =

Yes. Use the *Live Preview* button on this page: it opens a temporary WordPress site with sample editions already loaded, and disappears when you close the tab.

Once installed, **ePapers > Getting Started** can create the same sample editions on your own site, and remove them again in one click.

= Does it support PDF files? =

Yes. Upload images, PDFs, or a mix of both in one edition.

Under **Settings > PDF Rendering** you can choose how PDFs are shown:

* **Browser embed** - the browser's own PDF viewer.
* **Page images** (recommended) - WordPress generates a page image for each PDF, so zoom, printing, swiping and lazy loading behave exactly like an image edition.

Page images need Imagick with Ghostscript on the server. Most hosts have both. If yours does not, the browser embed still works.

= How are view counts calculated? =

A view is counted when a reader opens an edition, not when it appears in the archive list.

* The same reader opening the same edition again within 24 hours counts once, so refreshing does not inflate the number.
* Known search engine crawlers and bots are ignored.
* Counting happens through a small background request, so it keeps working on sites with full page caching.
* Counts are per edition, not per page. Turning pages inside an edition adds nothing.

= My view counts dropped after updating. Why? =

They are more accurate now. Before 2.0.0 every page load counted, including bots, crawlers and the same reader refreshing. Your historical totals are untouched, but the numbers grow more slowly from here.

= Does turning off the Download button stop people downloading pages? =

No. That setting hides the toolbar button, but the uploaded images and PDFs still live in your normal WordPress uploads folder and stay reachable at their direct URLs.

Genuinely restricting access needs protected file delivery, which is planned for a future release. If you need it today, put access rules on your uploads directory at the web server level.

= Where are my ePapers displayed? =

* Archive: `/epapers/`
* Single edition: `/epapers/{edition-name}/`
* By city or region: `/epaper-edition/{edition-slug}/`
* By language: `/epaper-language/{language-slug}/`

= Can I embed an edition in Elementor, Divi or a normal page? =

Yes. Use `[sk_epaper id="123"]` for the reader or `[sk_epaper_archive count="6"]` for a grid. In the block editor, search for **ePaper** to insert the same thing as a block.

= Can I change the layout? =

Copy a template into your theme and the plugin will use yours instead:

* `yourtheme/sk-epaper/single-epaper.php`
* `yourtheme/sk-epaper/archive-epaper.php`

Editing the plugin's own files works too, but those changes are lost on the next update.

= The reader does not line up with the rest of my theme. =

Set **Settings > Content Width** to your theme's content width, or to 0 to remove the limit and let your theme's own container decide.

= Will it slow my site down? =

The plugin's CSS and JavaScript load only on pages that actually show an ePaper. Inside an edition, pages load as the reader reaches them rather than all at once, and archives are paginated.

= What happens to my editions if I delete the plugin? =

Nothing is removed unless you opt in first, under **Settings > On Uninstall**. Even then, only plugin settings and stored ePaper data are deleted - your ePaper posts and uploaded media are always left alone.

= I am upgrading from 1.x. Will anything break? =

No. Editions, settings, permalinks and shortcodes are unchanged and upgrade automatically. Two things change visibly: view counts grow more slowly because bots and repeat visits no longer count, and the archive is paginated at 24 editions per page, which you can change in Settings.

== Screenshots ==

1. Admin: add or edit an edition - title, publication date, Editions and Languages.
2. Admin: ePaper Details & Pages - drag and drop page order and per-page labels.
3. Frontend: the ePaper archive with search, Edition, Language, month and exact-date filters.
4. Frontend: the ePaper reader - zoom, print and download controls, with the page rail below.
5. Frontend: turning pages in the reader.
6. Frontend: a full edition page end to end, with the page rail and the More Editions row.
7. Admin: City and regional Editions.
8. Admin: Languages for multi-language publications.
9. Admin: Getting Started - a step-by-step guide, shortcode reference and one-click sample content.
10. Admin: Analytics - reader engagement, top editions, live filtering and an edition date range.
11. Admin: Shortcodes & Helper - build and copy shortcodes for any page builder.
12. Admin: Settings - reader controls, performance and lazy loading.
13. Admin: Settings - brand colour, content width, thumbnail position and uninstall behaviour.

== Changelog ==

= 2.0.0 =
**Performance**
* Pages now load on demand instead of all at once. A 24 page edition no longer pushes every full size image (or every PDF iframe) on first paint.
* Embedded viewers below the fold wait until they are approaching the viewport before loading anything.
* Plugin CSS and JavaScript now load only on pages that actually show an ePaper, instead of on every page of the site.

**Reader**
* Added a page thumbnail strip below the reader for jumping straight to a page.
* Added an optional PDF.js rendering mode for real zoom and text selection on PDF editions (requires the PDF.js library in assets/vendor/pdfjs/; falls back to the browser embed otherwise).

**View counting**
* Rewritten to run through a REST beacon with a noscript pixel fallback, so counts keep working behind full page caching, where the old template based counter silently stopped.
* Repeat views from the same visitor now collapse over a 24 hour window and known bots are filtered out. Existing totals are preserved, but the counter will grow more slowly from now on because the numbers are closer to reality.
* Counting no longer writes to the database on every single page view.

**Archive**
* Added filters on the ePaper archive: search, Edition, Language, month and an exact edition date.
* The date picker only offers days that actually have an edition, and never a future date.
* Search now also matches Edition and Language names and page labels, not just the title and content.
* Editions and Languages on a card are clickable badges that filter the archive.
* Cards show the whole page at A4 proportions and the entire card is one link.

**Related editions**
* Added a "More Editions" row under the reader, matched first by Edition, then Language, then most recent.

**Admin**
* Added a Getting Started screen with a step-by-step guide, a shortcode reference and an explanation of how view counts work.
* Added one-click sample content: three demo editions with labelled pages, drawn on your own server, removable in one click.
* Added an edition date range filter to Analytics.
* Added per-page labels, so pages can be named Front Page, City, Sports and so on.
* Settings are laid out in two columns with a sticky save bar.
* Added a Content Width setting so the reader and archive line up with your theme, plus a `sk_epaper_content_max_width` filter.
* Added a Thumbnail Position setting: below the reader, or a rail on the left or right.

**Editor and blocks**
* Added "ePaper Viewer" and "ePaper Archive" blocks. Both render through the same code as the shortcodes, and both shortcodes continue to work unchanged.
* ePapers are now exposed through the REST API for blocks and headless use.
* Existing sites keep the classic editor for ePapers. New sites start on the block editor, and either can switch under ePapers > Settings.

**Accessibility**
* Navigation arrows are real buttons with labels and keyboard focus instead of decorative divs.
* Added a live region for the page counter, visible focus outlines, and reduced motion support.

**Admin**
* Settings now run through the WordPress Settings API with per field sanitisation.
* Added settings for archive page size, lazy loading, thumbnails, PDF mode, editor choice, and uninstall behaviour.

**Housekeeping**
* Restructured the plugin into classes under includes/. Every function that existed as a global in 1.2.x is still defined and still hooked under the same name, so existing remove_action() and remove_filter() calls keep working.
* Themes can now override the plugin templates by copying them to yourtheme/sk-epaper/.
* Added uninstall.php. It deletes nothing unless you opt in first, and never touches ePaper posts or uploaded media.
* Completed internationalisation: post type labels, the viewer chrome, and admin JavaScript strings are all translatable, with a Domain Path header.
* Added a batched, resumable data migration so sites with thousands of editions upgrade without timing out.
* Added phpcs configuration and a GitHub Actions workflow covering PHP 7.4/8.1/8.3.

= 1.2.1 =
* Fixed: ePaper archive and taxonomy pages loaded every edition in a single query (`posts_per_page = -1`), which could exhaust memory on sites with many editions. Archives are now paginated through the main query (default 24 per page, configurable in Settings).
* Fixed: Editions with no recorded views were missing from the Analytics table because the query required the views meta key to exist.
* Added: View counter meta is now initialised when an ePaper is published.
* Fixed: Arrow-key navigation affected every viewer on pages with multiple `[sk_epaper]` shortcodes, and fired while typing in form fields.
* Fixed: "Print All Pages" could silently do nothing or print blank pages; printing now waits for images to finish loading.
* Fixed: Print iframes were left in the DOM after every print.
* Fixed: The meta box save routine now runs only for the ePaper post type, skips revisions, and validates that submitted IDs are real attachments.
* Changed: Deactivation no longer unregisters the post type and taxonomies; it only flushes rewrite rules.
* Housekeeping: Added the `SK_EPAPER_VERSION` constant for asset versioning.

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

= 1.1.2 =
* Added sidebar to single ePaper template
* UI improvements and minor fixes

= 1.1.1 =
* Updated plugin header and readme stable tag for WordPress.org compliance
* Added local Font Awesome to replace CDN
* Fixed escaping output issues
* Prefixed global variables in templates
* Other minor code improvements

= 1.0 =
* Initial release — custom post type, image upload, archive and single templates.

== License ==

This plugin is licensed under the GPLv2 or later.  
See [https://www.gnu.org/licenses/gpl-2.0.html](https://www.gnu.org/licenses/gpl-2.0.html)

== Upgrade Notice ==

= 2.0.0 =
Major release: rebuilt internals, archive filters, blocks and a faster reader. Editions, settings, permalinks and shortcodes are unchanged and upgrade automatically. Expect two changes: view counts now exclude bots and repeats, and the archive is paginated.

= 1.2.1 =
Archive pages are now paginated (24 editions per page by default) instead of rendering every edition at once. Adjust the number under ePapers > Settings. No content, settings, or permalinks change.
