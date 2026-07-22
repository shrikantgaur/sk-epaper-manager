# SK ePaper Manager

[![WordPress Plugin](https://img.shields.io/wordpress/v/sk-epaper-manager.svg)](https://wordpress.org/plugins/sk-epaper-manager/)
[![WordPress Tested Version](https://img.shields.io/wordpress/plugin/tested/sk-epaper-manager.svg)](https://wordpress.org/plugins/sk-epaper-manager/)
[![PHP Version Requirement](https://img.shields.io/wordpress/plugin/required-php/sk-epaper-manager.svg)](https://wordpress.org/plugins/sk-epaper-manager/)
[![Active Installations](https://img.shields.io/wordpress/plugin/installs/sk-epaper-manager.svg)](https://wordpress.org/plugins/sk-epaper-manager/)
[![License](https://img.shields.io/badge/License-GPL%20v2%20or%20later-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

**SK ePaper Manager** is a lightweight, responsive WordPress plugin that lets you upload, manage, and display beautiful digital ePapers (newspapers, magazines, or brochures) on your website using images or PDF files. 

Perfect for news portals, schools, local publications, and magazines.

---

## 🚀 Features

*   **Custom Post Type:** Dedicated interface to manage all your ePaper editions.
*   **PDF & Image Support:** Upload multiple page images or high-quality PDF files for each edition.
*   **Page-by-Page Reading:** Visitors can browse through digital publications smoothly with navigation arrows.
*   **Interactive Controls:**
    *   🔍 **Zoom In/Out** for effortless reading.
    *   🖨️ **Print Page** or **Print All Pages** directly from the browser.
    *   📥 **Download** page option for offline reading.
*   **Drag-to-Pan:** Smooth mouse dragging to pan when zoomed in.
*   **Modern Design:** Fully responsive layout that looks great on mobile, tablet, and desktop.
*   **Developer Friendly:** Easily override archive and single edition templates in your theme root.

---

## 📸 Screenshots

| 1. Admin: Manage ePaper Editions | 2. Frontend: Single ePaper View |
|:---:|:---:|
| ![Admin Meta Box](https://ps.w.org/sk-epaper-manager/assets/screenshot-2.png) | ![Frontend Slider](https://ps.w.org/sk-epaper-manager/assets/screenshot-5.png) |

---

## 🛠️ Installation

1. Upload the `sk-epaper-manager` folder to the `/wp-content/plugins/` directory, or install it directly via the WordPress Admin **Plugins > Add New** screen.
2. Activate the plugin.
3. A new **ePapers** menu will appear in your admin sidebar.

---

## 📖 How to Use

1. Go to **ePapers > Add New**.
2. Enter the title of your edition (e.g., "Daily Edition - 22 July 2026").
3. Upload your page images or PDF file using the **ePaper Images** meta box.
4. Set a Featured Image (which will act as the front page/cover thumbnail).
5. Click **Publish**.
6. By default, your ePapers will be accessible at:
    *   **Archive View:** `yourdomain.com/epapers/`
    *   **Single Edition:** `yourdomain.com/epapers/edition-slug/`

---

## 🧑‍💻 For Developers

You can easily customize the templates by copying them from the plugin's `templates` folder into your theme root:

*   `archive-epaper.php` - Controls the listing page for all ePapers.
*   `single-epaper.php` - Controls the individual ePaper slider and reader view.

---

## 📝 Changelog

### 1.1.5
*   Added direct file access protection check in template files for enhanced security.
*   Updated WordPress version compatibility check ("Tested up to: 7.0").
*   Updated PHP requirement to 7.4.
*   Removed unused FontAwesome files for a lighter package.

### 1.1.4
*   Fixed Dashicons for zoom and print buttons on frontend.
*   Updated template files for WordPress compatibility.
*   Minor UI improvements.

### 1.1.3
*   Replaced Font Awesome icons with WordPress Dashicons.
*   Fixed minor UI issues in single templates.

---

## 📄 License

This plugin is open-source and licensed under the [GPLv2 or later](https://www.gnu.org/licenses/gpl-2.0.html).
