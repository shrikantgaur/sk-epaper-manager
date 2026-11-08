<?php
/*
Plugin Name: SK ePaper Manager
Text Domain: sk-epaper-manager
Domain Path: /languages
Description: SK ePaper Manager lets you upload, manage, and display beautiful ePapers on your WordPress site.
Version: 2.1.0
Author: Shri Kant Gaur
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Requires at least: 5.0
Requires PHP: 7.4
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'SK_EPAPER_PREFIX' ) ) {
    define( 'SK_EPAPER_PREFIX', 'sk_epaper_' );
}

if ( ! defined( 'SK_EPAPER_VERSION' ) ) {
    define( 'SK_EPAPER_VERSION', '2.1.0' );
}

/*
 * Data schema version, deliberately independent of the plugin version above.
 * It only moves when stored data needs migrating, so bumping the plugin to
 * 2.0.0 must not re-run a migration sites have already completed.
 */
if ( ! defined( 'SK_EPAPER_DB_VERSION' ) ) {
    define( 'SK_EPAPER_DB_VERSION', '1.3.0' );
}

/*
 * Where "Request a Feature" and "Early Access to Pro" point. Define either in
 * wp-config.php to override, or use the sk_epaper_feature_request_url /
 * sk_epaper_early_access_url filters. The plugin only links to these; it never
 * sends anything to them.
 */
if ( ! defined( 'SK_EPAPER_FEATURE_REQUEST_URL' ) ) {
    define( 'SK_EPAPER_FEATURE_REQUEST_URL', 'https://wordpress.org/support/plugin/sk-epaper-manager/' );
}

if ( ! defined( 'SK_EPAPER_EARLY_ACCESS_URL' ) ) {
    define( 'SK_EPAPER_EARLY_ACCESS_URL', 'https://docs.google.com/forms/d/e/1FAIpQLScmYQu2_Q_5mc7QwuWCj4uB-eUxQHyIl4wY5Ry8rFmu14alsQ/viewform' );
}

define( 'SK_EPAPER_FILE', __FILE__ );
define( 'SK_EPAPER_PATH', plugin_dir_path( __FILE__ ) );
define( 'SK_EPAPER_URL', plugin_dir_url( __FILE__ ) );

require_once SK_EPAPER_PATH . 'includes/class-sk-epaper-settings.php';
require_once SK_EPAPER_PATH . 'includes/class-sk-epaper-views.php';
require_once SK_EPAPER_PATH . 'includes/class-sk-epaper-migrations.php';
require_once SK_EPAPER_PATH . 'includes/class-sk-epaper-cpt.php';
require_once SK_EPAPER_PATH . 'includes/class-sk-epaper-renderer.php';
require_once SK_EPAPER_PATH . 'includes/class-sk-epaper-admin.php';
require_once SK_EPAPER_PATH . 'includes/class-sk-epaper-blocks.php';
require_once SK_EPAPER_PATH . 'includes/class-sk-epaper-demo.php';
require_once SK_EPAPER_PATH . 'includes/class-sk-epaper-feedback.php';
require_once SK_EPAPER_PATH . 'includes/class-sk-epaper-share.php';
require_once SK_EPAPER_PATH . 'includes/functions-compat.php';

/*
 * Hooks are registered against the global function names from includes/functions-compat.php
 * rather than class callbacks, so existing remove_action() / remove_filter()
 * calls in themes and other plugins keep working.
 */

// Content model.
add_action( 'init', SK_EPAPER_PREFIX . 'register_post_type' );
add_action( 'init', SK_EPAPER_PREFIX . 'register_taxonomies', 0 );
add_action( 'admin_init', SK_EPAPER_PREFIX . 'check_flush_rules' );
add_action( 'pre_get_posts', SK_EPAPER_PREFIX . 'archive_pre_get_posts' );
add_filter( 'posts_join', array( 'SK_EPaper_CPT', 'search_join' ), 10, 2 );
add_filter( 'posts_search', array( 'SK_EPaper_CPT', 'search_where' ), 10, 2 );
add_filter( 'posts_distinct', array( 'SK_EPaper_CPT', 'search_distinct' ), 10, 2 );
add_filter( 'template_include', SK_EPAPER_PREFIX . 'template_include' );
add_filter( 'use_block_editor_for_post_type', array( 'SK_EPaper_CPT', 'use_block_editor' ), 10, 2 );

register_activation_hook( __FILE__, SK_EPAPER_PREFIX . 'activate' );
register_deactivation_hook( __FILE__, SK_EPAPER_PREFIX . 'deactivate' );

// Frontend.
add_action( 'wp_enqueue_scripts', SK_EPAPER_PREFIX . 'enqueue_styles' );
add_action( 'wp_enqueue_scripts', SK_EPAPER_PREFIX . 'enqueue_scripts' );
add_shortcode( 'sk_epaper', SK_EPAPER_PREFIX . 'shortcode_handler' );
add_shortcode( 'sk_epaper_archive', SK_EPAPER_PREFIX . 'archive_shortcode_handler' );

// Admin.
add_action( 'admin_init', array( 'SK_EPaper_Settings', 'register' ) );
add_action( 'admin_init', array( 'SK_EPaper_Demo', 'handle_actions' ) );
add_action( 'admin_menu', SK_EPAPER_PREFIX . 'admin_menu_pages' );
add_action( 'admin_enqueue_scripts', SK_EPAPER_PREFIX . 'enqueue_admin_styles' );
add_action( 'admin_enqueue_scripts', SK_EPAPER_PREFIX . 'enqueue_admin_scripts' );
add_action( 'add_meta_boxes', SK_EPAPER_PREFIX . 'add_image_meta_box' );
add_action( 'save_post', SK_EPAPER_PREFIX . 'save_image_meta' );
add_filter( 'manage_epaper_posts_columns', SK_EPAPER_PREFIX . 'set_custom_columns' );
add_action( 'manage_epaper_posts_custom_column', SK_EPAPER_PREFIX . 'render_custom_columns', 10, 2 );
add_action( 'wp_ajax_sk_epaper_filter_analytics', SK_EPAPER_PREFIX . 'ajax_filter_analytics' );

// Blocks.
add_action( 'init', array( 'SK_EPaper_Blocks', 'register' ) );

// View counting.
add_action( 'transition_post_status', SK_EPAPER_PREFIX . 'init_views_meta', 10, 3 );
add_action( 'save_post_epaper', array( 'SK_EPaper_CPT', 'flush_month_cache' ) );
add_action( 'deleted_post', array( 'SK_EPaper_CPT', 'flush_month_cache' ) );
add_action( 'rest_api_init', array( 'SK_EPaper_Views', 'register_rest_routes' ) );
add_action( 'wp_ajax_sk_epaper_view_pixel', array( 'SK_EPaper_Views', 'handle_pixel' ) );
add_action( 'wp_ajax_nopriv_sk_epaper_view_pixel', array( 'SK_EPaper_Views', 'handle_pixel' ) );

// Review / feedback notice.
add_action( 'admin_init', array( 'SK_EPaper_Feedback', 'maybe_set_install_time' ) );
add_action( 'admin_init', array( 'SK_EPaper_Feedback', 'handle_fallback' ) );
add_action( 'admin_enqueue_scripts', array( 'SK_EPaper_Feedback', 'enqueue_assets' ) );
add_action( 'admin_notices', array( 'SK_EPaper_Feedback', 'render_notice' ) );
add_action( 'wp_ajax_sk_epaper_review_notice', array( 'SK_EPaper_Feedback', 'ajax_handle_choice' ) );

// Page deep links, share bar and Open Graph.
add_action( 'init', array( 'SK_EPaper_Share', 'add_rewrite_rule' ) );
add_filter( 'query_vars', array( 'SK_EPaper_Share', 'register_query_var' ) );
add_action( 'wp_head', array( 'SK_EPaper_Share', 'output_og_tags' ), 5 );
add_action( 'admin_init', array( 'SK_EPaper_Share', 'maybe_set_defaults' ), 5 );
add_action( 'admin_init', array( 'SK_EPaper_Share', 'handle_notice_fallback' ) );
add_action( 'admin_enqueue_scripts', array( 'SK_EPaper_Share', 'enqueue_notice_assets' ) );
add_action( 'admin_notices', array( 'SK_EPaper_Share', 'render_notice' ) );
add_action( 'wp_ajax_sk_epaper_share_notice', array( 'SK_EPaper_Share', 'ajax_notice_choice' ) );
add_filter( 'sk_epaper_show_review_notice', array( 'SK_EPaper_Share', 'suppress_review_notice' ) );

// Data migrations.
add_action( 'admin_init', array( 'SK_EPaper_Migrations', 'maybe_upgrade' ) );
add_action( 'sk_epaper_run_migration_batch', array( 'SK_EPaper_Migrations', 'run_batch' ) );
