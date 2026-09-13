<?php
/**
 * Schema/data upgrade routines.
 *
 * Runs off a stored sk_epaper_db_version option so upgrades are ordered and
 * idempotent. Anything that touches every ePaper runs in cron batches: sites
 * with thousands of editions must not do that work inside a page load.
 *
 * @package SK_ePaper_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Versioned data migrations.
 */
class SK_EPaper_Migrations {

    const VERSION_OPTION  = 'sk_epaper_db_version';
    const PROGRESS_OPTION = 'sk_epaper_migration_progress';
    const CRON_HOOK       = 'sk_epaper_run_migration_batch';
    const BATCH_SIZE      = 200;

    /**
     * Run any migrations the stored version has not seen yet.
     *
     * @return void
     */
    public static function maybe_upgrade() {
        $stored = get_option( self::VERSION_OPTION, '' );

        if ( SK_EPAPER_DB_VERSION === $stored ) {
            return;
        }

        // Fresh install: nothing to migrate, just stamp the version. New sites
        // start on the block editor; upgrades keep the classic editor they know.
        if ( '' === $stored && ! self::has_epapers() ) {
            add_option( 'sk_epaper_use_block_editor', 1 );
            update_option( self::VERSION_OPTION, SK_EPAPER_DB_VERSION );
            return;
        }

        add_option( 'sk_epaper_use_block_editor', 0 );

        if ( version_compare( $stored, '1.3.0', '<' ) ) {
            self::start_batch_job( '1.3.0' );
            return;
        }

        update_option( self::VERSION_OPTION, SK_EPAPER_DB_VERSION );
    }

    /**
     * Whether the site has any ePapers at all.
     *
     * @return bool
     */
    protected static function has_epapers() {
        $existing = get_posts(
            array(
                'post_type'        => 'epaper',
                'post_status'      => 'any',
                'posts_per_page'   => 1,
                'fields'           => 'ids',
                'no_found_rows'    => true,
                'suppress_filters' => false,
            )
        );

        return ! empty( $existing );
    }

    /**
     * Queue the batched migration for a target version.
     *
     * @param string $version Target version.
     * @return void
     */
    protected static function start_batch_job( $version ) {
        $progress = get_option( self::PROGRESS_OPTION, array() );

        if ( empty( $progress ) || ! isset( $progress['version'] ) || $version !== $progress['version'] ) {
            update_option(
                self::PROGRESS_OPTION,
                array(
                    'version' => $version,
                    'offset'  => 0,
                )
            );
        }

        self::schedule_next();
    }

    /**
     * Schedule the next batch if one is not already pending.
     *
     * @return void
     */
    public static function schedule_next() {
        if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
            wp_schedule_single_event( time() + 10, self::CRON_HOOK );
        }
    }

    /**
     * Clear any pending batch (used on deactivation).
     *
     * @return void
     */
    public static function unschedule() {
        $timestamp = wp_next_scheduled( self::CRON_HOOK );

        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, self::CRON_HOOK );
        }
    }

    /**
     * Process one batch, then reschedule or finish.
     *
     * Safe to run repeatedly: every operation checks before it writes.
     *
     * @return void
     */
    public static function run_batch() {
        $progress = get_option( self::PROGRESS_OPTION, array() );

        if ( empty( $progress['version'] ) ) {
            update_option( self::VERSION_OPTION, SK_EPAPER_DB_VERSION );
            return;
        }

        $offset = isset( $progress['offset'] ) ? absint( $progress['offset'] ) : 0;

        $post_ids = get_posts(
            array(
                'post_type'        => 'epaper',
                'post_status'      => 'any',
                'posts_per_page'   => self::BATCH_SIZE,
                'offset'           => $offset,
                'fields'           => 'ids',
                'orderby'          => 'ID',
                'order'            => 'ASC',
                'no_found_rows'    => true,
                'suppress_filters' => false,
            )
        );

        if ( empty( $post_ids ) ) {
            delete_option( self::PROGRESS_OPTION );
            update_option( self::VERSION_OPTION, SK_EPAPER_DB_VERSION );
            return;
        }

        foreach ( $post_ids as $post_id ) {
            self::migrate_post( $post_id );
        }

        // A short batch means there is nothing left, so finish here rather than
        // waiting on another cron run just to discover an empty page.
        if ( count( $post_ids ) < self::BATCH_SIZE ) {
            delete_option( self::PROGRESS_OPTION );
            update_option( self::VERSION_OPTION, SK_EPAPER_DB_VERSION );
            return;
        }

        $progress['offset'] = $offset + count( $post_ids );
        update_option( self::PROGRESS_OPTION, $progress );

        self::schedule_next();
    }

    /**
     * Normalise a single ePaper's stored data.
     *
     * @param int $post_id ePaper post ID.
     * @return void
     */
    protected static function migrate_post( $post_id ) {
        // 1. Normalise the legacy comma-separated page list into an array.
        //    The read-side fallback in SK_EPaper_Renderer::get_images() stays in
        //    place regardless - this only saves it from doing the work.
        $raw = get_post_meta( $post_id, SK_EPAPER_PREFIX . 'images', true );

        if ( ! is_array( $raw ) && '' !== $raw && null !== $raw ) {
            $normalised = array_values( array_filter( array_map( 'absint', explode( ',', (string) $raw ) ) ) );
            update_post_meta( $post_id, SK_EPAPER_PREFIX . 'images', $normalised );
        }

        // 2. Seed the view counter so zero-view editions sort and report properly.
        if ( '' === get_post_meta( $post_id, SK_EPaper_Views::META_KEY, true ) ) {
            add_post_meta( $post_id, SK_EPaper_Views::META_KEY, 0, true );
        }
    }

    /**
     * Whether a migration is still working through the queue.
     *
     * @return bool
     */
    public static function is_running() {
        $progress = get_option( self::PROGRESS_OPTION, array() );

        return ! empty( $progress['version'] );
    }
}
