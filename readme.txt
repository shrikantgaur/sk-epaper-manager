=== SK ePaper Manager ===
Contributors: shrikantgaur
Tags: epaper, pdf, newspaper, magazine, image gallery
Requires at least: 5.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.1.6
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

SK ePaper Manager makes it easy to publish digital newspapers or magazines on your WordPress site using **images or PDF files**.

== Description ==

**SK ePaper Manager** lets you create and display digital ePapers by uploading multiple images or PDF files for each edition.

Visitors can browse all ePapers in an archive view or open single editions to read page by page — with support for **zoom**, **print**, and **download** options.

Perfect for news portals, schools, or local publications.

== Features ==

* Custom Post Type for ePapers
* Upload multiple images **or** PDFs edition
* Built-in archive page for all ePapers
* Single page view for each edition
* **Zoom in/out** for easier reading
* **Print edition** directly from the browser
* **Download** edition
* Modern, responsive layout
* Developer-friendly templates you can override
* Lightweight and flexible

== Installation ==

1. Upload the plugin to `/wp-content/plugins/sk-epaper-manager` or install through the Plugins screen.
2. Activate **SK ePaper Manager** from the Plugins screen.
3. Use the **ePapers** menu in your dashboard to create and manage ePaper editions.

== Usage ==

1. Go to **ePapers > Add New**.
2. Enter a title (e.g. edition date or name).
3. Upload your ePaper images or PDF files.
4. Publish the edition.
5. Visit your archive and single edition pages.

== Frequently Asked Questions ==

= Does this plugin support PDF files? =
Yes — you can upload either multiple images or PDF files for each ePaper edition.

= Where are my ePapers displayed? =
By default:
* Archive: `/epapers/`
* Single Edition: `/epapers/{edition-name}/`

= Can I customize the design? =
Yes — the plugin uses template files you can override in your theme for full control.

== Screenshots ==

1. Admin: SK ePaper Manager plugin installed and activated.
2. Admin: Add New ePaper – upload multiple images or a PDF file.
3. Admin: Add/Edit ePaper – set title, featured image, and upload files.
4. Frontend: Archive page showing all ePaper editions.
5. Frontend: Single ePaper view with zoom, print, and download features.

== Changelog ==

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

= 1.1.0 =
* Added support for PDF uploads.
* Added zoom in/out functionality for single edition view.
* Added print button to print the current edition.
* Added download button for downloading ePaper pages.
* Improved CSS and layout responsiveness.

= 1.0 =
* Initial release — custom post type, image upload, archive and single templates.

== Upgrade Notice ==

= 1.1.1 =
Fixes WordPress.org plugin check errors, escapes output, prefixes variables, and replaces external CDN with local Font Awesome.

= 1.1.0 =
Adds support for PDF uploads, zoom, print, and download buttons. Recommended update for better user experience.

== License ==

This plugin is licensed under the GPLv2 or later.  
See [https://www.gnu.org/licenses/gpl-2.0.html](https://www.gnu.org/licenses/gpl-2.0.html)