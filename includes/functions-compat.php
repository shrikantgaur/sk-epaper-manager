<?php
/**
 * Backwards-compatible global functions.
 *
 * Every function that existed as a global in 1.2.x is kept here and simply
 * forwards to its class. Hooks are still registered under these names, so third
 * party code that calls them - or that unhooks them with remove_action() /
 * remove_filter() - keeps working exactly as before.
 *
 * @package SK_ePaper_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register the ePaper post type.
 *
 * @return void
 */
function sk_epaper_register_post_type() {
    SK_EPaper_CPT::register_post_type();
}

/**
 * Register the Editions and Languages taxonomies.
 *
 * @return void
 */
function sk_epaper_register_taxonomies() {
    SK_EPaper_CPT::register_taxonomies();
}

/**
 * One-time rewrite rule flush.
 *
 * @return void
 */
function sk_epaper_check_flush_rules() {
    SK_EPaper_CPT::check_flush_rules();
}

/**
 * Activation hook.
 *
 * @return void
 */
function sk_epaper_activate() {
    SK_EPaper_CPT::activate();
}

/**
 * Deactivation hook.
 *
 * @return void
 */
function sk_epaper_deactivate() {
    SK_EPaper_CPT::deactivate();
}

/**
 * Editions shown per archive page.
 *
 * @return int
 */
function sk_epaper_get_archive_per_page() {
    return SK_EPaper_CPT::get_archive_per_page();
}

/**
 * Paginate ePaper archives on the main query.
 *
 * @param WP_Query $query Query object.
 * @return void
 */
function sk_epaper_archive_pre_get_posts( $query ) {
    SK_EPaper_CPT::archive_pre_get_posts( $query );
}

/**
 * Template loader.
 *
 * @param string $template Template path.
 * @return string
 */
function sk_epaper_template_include( $template ) {
    return SK_EPaper_CPT::template_include( $template );
}

/**
 * Register admin sub-menu pages.
 *
 * @return void
 */
function sk_epaper_admin_menu_pages() {
    SK_EPaper_Admin::admin_menu_pages();
}

/**
 * Render the Getting Started screen.
 *
 * @return void
 */
function sk_epaper_render_guide_page() {
    SK_EPaper_Admin::render_admin_page( 'guide-page.php' );
}

/**
 * Render the Analytics screen.
 *
 * @return void
 */
function sk_epaper_render_analytics_page() {
    SK_EPaper_Admin::render_admin_page( 'analytics-page.php' );
}

/**
 * Render the Shortcodes helper screen.
 *
 * @return void
 */
function sk_epaper_render_shortcodes_page() {
    SK_EPaper_Admin::render_admin_page( 'shortcodes-page.php' );
}

/**
 * Render the Settings screen.
 *
 * @return void
 */
function sk_epaper_render_settings_page() {
    SK_EPaper_Admin::render_admin_page( 'settings-page.php' );
}

/**
 * Frontend styles.
 *
 * @return void
 */
function sk_epaper_enqueue_styles() {
    if ( ! SK_EPaper_Renderer::needs_assets() ) {
        return;
    }

    SK_EPaper_Renderer::enqueue_styles();
}

/**
 * Frontend scripts.
 *
 * @return void
 */
function sk_epaper_enqueue_scripts() {
    if ( ! SK_EPaper_Renderer::needs_assets() ) {
        return;
    }

    SK_EPaper_Renderer::enqueue_scripts();
}

/**
 * Admin styles.
 *
 * @return void
 */
function sk_epaper_enqueue_admin_styles() {
    SK_EPaper_Admin::enqueue_admin_styles();
}

/**
 * Admin scripts.
 *
 * @return void
 */
function sk_epaper_enqueue_admin_scripts() {
    SK_EPaper_Admin::enqueue_admin_scripts();
}

/**
 * Analytics query arguments.
 *
 * @param array $args Query options.
 * @return array
 */
function sk_epaper_get_analytics_query_args( $args = array() ) {
    return SK_EPaper_Admin::get_analytics_query_args( $args );
}

/**
 * Analytics AJAX handler.
 *
 * @return void
 */
function sk_epaper_ajax_filter_analytics() {
    SK_EPaper_Admin::ajax_filter_analytics();
}

/**
 * Register the pages meta box.
 *
 * @return void
 */
function sk_epaper_add_image_meta_box() {
    SK_EPaper_Admin::add_image_meta_box();
}

/**
 * Render the pages meta box.
 *
 * @param WP_Post $post Current post.
 * @return void
 */
function sk_epaper_render_image_meta_box( $post ) {
    SK_EPaper_Admin::render_image_meta_box( $post );
}

/**
 * Save the pages meta box.
 *
 * @param int $post_id Post ID.
 * @return void
 */
function sk_epaper_save_image_meta( $post_id ) {
    SK_EPaper_Admin::save_image_meta( $post_id );
}

/**
 * Seed the view counter on publish.
 *
 * @param string  $new_status New status.
 * @param string  $old_status Old status.
 * @param WP_Post $post       Post object.
 * @return void
 */
function sk_epaper_init_views_meta( $new_status, $old_status, $post ) {
    SK_EPaper_Views::init_meta( $new_status, $old_status, $post );
}

/**
 * List table columns.
 *
 * @param array $columns Columns.
 * @return array
 */
function sk_epaper_set_custom_columns( $columns ) {
    return SK_EPaper_Admin::set_custom_columns( $columns );
}

/**
 * Render list table columns.
 *
 * @param string $column  Column key.
 * @param int    $post_id Post ID.
 * @return void
 */
function sk_epaper_render_custom_columns( $column, $post_id ) {
    SK_EPaper_Admin::render_custom_columns( $column, $post_id );
}

/**
 * [sk_epaper] handler.
 *
 * @param array $atts Attributes.
 * @return string
 */
function sk_epaper_shortcode_handler( $atts ) {
    return SK_EPaper_Renderer::shortcode_handler( $atts );
}

/**
 * [sk_epaper_archive] handler.
 *
 * @param array $atts Attributes.
 * @return string
 */
function sk_epaper_archive_shortcode_handler( $atts ) {
    return SK_EPaper_Renderer::archive_shortcode_handler( $atts );
}

/**
 * Read an ePaper's page attachment IDs, handling both storage formats.
 *
 * @param int $post_id ePaper post ID.
 * @return array
 */
function sk_epaper_get_images( $post_id ) {
    return SK_EPaper_Renderer::get_images( $post_id );
}

/**
 * Render the ePaper viewer markup.
 *
 * @param int $post_id ePaper post ID.
 * @return string
 */
function sk_epaper_render_viewer( $post_id ) {
    return SK_EPaper_Renderer::render_viewer( $post_id );
}
