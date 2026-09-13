<?php
/**
 * Uninstall routine.
 *
 * Nothing is deleted unless the operator explicitly opted in beforehand.
 * Deleting a plugin is a common troubleshooting step - "delete and reinstall"
 * must never cost somebody their archive.
 *
 * Even when opted in, ePaper posts and their uploaded media are left alone:
 * that is the site's content, not the plugin's data. Only plugin settings and
 * the meta this plugin wrote are removed.
 *
 * @package SK_ePaper_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

if ( ! get_option( 'sk_epaper_delete_data_on_uninstall' ) ) {
    return;
}

global $wpdb;

$sk_epaper_options = array(
    'sk_epaper_enable_download',
    'sk_epaper_enable_print',
    'sk_epaper_enable_zoom',
    'sk_epaper_lazy_load',
    'sk_epaper_show_thumbnails',
    'sk_epaper_thumb_position',
    'sk_epaper_show_related',
    'sk_epaper_related_count',
    'sk_epaper_content_width',
    'sk_epaper_use_block_editor',
    'sk_epaper_archive_per_page',
    'sk_epaper_header_bg',
    'sk_epaper_primary_color',
    'sk_epaper_pdf_mode',
    'sk_epaper_delete_data_on_uninstall',
    'sk_epaper_db_version',
    'sk_epaper_migration_progress',
    'sk_epaper_flush_rules_v120',
    'sk_epaper_flush_rules_v130',
);

foreach ( $sk_epaper_options as $sk_epaper_option ) {
    delete_option( $sk_epaper_option );
}

// Post meta written by this plugin. delete_post_meta_by_key() handles the
// cache invalidation that a raw DELETE would leave stale.
$sk_epaper_meta_keys = array(
    'sk_epaper_images',
    'sk_epaper_edition_date',
    'sk_epaper_page_labels',
    'sk_epaper_views',
    '_sk_epaper_demo',
);

foreach ( $sk_epaper_meta_keys as $sk_epaper_meta_key ) {
    delete_post_meta_by_key( $sk_epaper_meta_key );
}

// Cached archive lookups.
delete_transient( 'sk_epaper_archive_months' );
delete_transient( 'sk_epaper_available_dates' );

// View dedupe transients.
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
$wpdb->query(
    $wpdb->prepare(
        "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
        $wpdb->esc_like( '_transient_sk_ep_v_' ) . '%',
        $wpdb->esc_like( '_transient_timeout_sk_ep_v_' ) . '%'
    )
);
// phpcs:enable

// Any scheduled migration batch.
$sk_epaper_cron = wp_next_scheduled( 'sk_epaper_run_migration_batch' );

if ( $sk_epaper_cron ) {
    wp_unschedule_event( $sk_epaper_cron, 'sk_epaper_run_migration_batch' );
}
