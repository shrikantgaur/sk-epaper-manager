<?php
/**
 * View counting.
 *
 * Until 1.2.x the counter incremented straight from templates/single-epaper.php
 * on every request. That wrote to the database on every page view, counted every
 * bot and refresh, and stopped working entirely behind full page caching, since
 * cached HTML never reaches PHP.
 *
 * Counting now happens out of band: a small beacon hits a REST endpoint (with a
 * noscript pixel fallback), so it survives page caching, and a per-visitor
 * transient collapses repeat views inside a 24 hour window.
 *
 * The meta key is unchanged, so historical totals carry over untouched.
 *
 * @package SK_ePaper_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Records and reads ePaper view counts.
 */
class SK_EPaper_Views {

    const META_KEY  = 'sk_epaper_views';
    const REST_NS   = 'sk-epaper/v1';
    const DEDUPE_TTL = DAY_IN_SECONDS;

    /**
     * Register the REST route used by the beacon.
     *
     * @return void
     */
    public static function register_rest_routes() {
        register_rest_route(
            self::REST_NS,
            '/view/(?P<id>\d+)',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( __CLASS__, 'handle_rest_view' ),
                'permission_callback' => '__return_true',
                'args'                => array(
                    'id' => array(
                        'required'          => true,
                        'sanitize_callback' => 'absint',
                        'validate_callback' => static function ( $value ) {
                            return absint( $value ) > 0;
                        },
                    ),
                ),
            )
        );
    }

    /**
     * REST callback: count a view.
     *
     * @param WP_REST_Request $request Request.
     * @return WP_REST_Response
     */
    public static function handle_rest_view( $request ) {
        $post_id = absint( $request['id'] );
        $counted = self::record( $post_id );

        return rest_ensure_response(
            array(
                'counted' => $counted,
                'views'   => self::get( $post_id ),
            )
        );
    }

    /**
     * admin-ajax callback serving the noscript tracking pixel.
     *
     * @return void
     */
    public static function handle_pixel() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $post_id = isset( $_GET['epaper_id'] ) ? absint( $_GET['epaper_id'] ) : 0;

        if ( $post_id ) {
            self::record( $post_id );
        }

        nocache_headers();
        header( 'Content-Type: image/gif' );

        // 1x1 transparent GIF.
        echo base64_decode( 'R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
        exit;
    }

    /**
     * Count a view if it passes the bot and dedupe checks.
     *
     * @param int $post_id ePaper post ID.
     * @return bool Whether the view was counted.
     */
    public static function record( $post_id ) {
        $post_id = absint( $post_id );

        if ( ! $post_id || 'epaper' !== get_post_type( $post_id ) ) {
            return false;
        }

        if ( 'publish' !== get_post_status( $post_id ) ) {
            return false;
        }

        if ( ! self::should_count() ) {
            return false;
        }

        $key = self::dedupe_key( $post_id );

        if ( get_transient( $key ) ) {
            return false;
        }

        set_transient( $key, 1, self::DEDUPE_TTL );
        self::increment( $post_id );

        /**
         * Fires after an ePaper view has been counted.
         *
         * @param int $post_id ePaper post ID.
         */
        do_action( 'sk_epaper_view_counted', $post_id );

        return true;
    }

    /**
     * Whether the current request looks like a countable human visit.
     *
     * @return bool
     */
    protected static function should_count() {
        if ( wp_doing_cron() ) {
            return false;
        }

        $user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';

        if ( '' === $user_agent ) {
            return false;
        }

        $bots = array(
            'bot', 'crawl', 'spider', 'slurp', 'archiver', 'facebookexternalhit',
            'preview', 'headless', 'lighthouse', 'pingdom', 'uptime', 'monitor',
            'curl', 'wget', 'python-requests', 'go-http-client', 'okhttp',
            'phantomjs', 'ahrefs', 'semrush', 'mj12', 'dotbot', 'petalbot',
        );

        $needle = strtolower( $user_agent );

        foreach ( $bots as $bot ) {
            if ( false !== strpos( $needle, $bot ) ) {
                return false;
            }
        }

        /**
         * Filter whether the current request should count as a view.
         *
         * @param bool   $should_count Default decision.
         * @param string $user_agent   Request user agent.
         */
        return (bool) apply_filters( 'sk_epaper_should_count_view', true, $user_agent );
    }

    /**
     * Per-visitor dedupe key. No raw IP is stored - only a salted hash.
     *
     * @param int $post_id ePaper post ID.
     * @return string
     */
    protected static function dedupe_key( $post_id ) {
        $ip         = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
        $user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
        $user_id    = get_current_user_id();

        return 'sk_ep_v_' . md5( $post_id . '|' . $user_id . '|' . $ip . '|' . $user_agent . '|' . wp_salt() );
    }

    /**
     * Atomically bump the stored counter.
     *
     * @param int $post_id ePaper post ID.
     * @return void
     */
    protected static function increment( $post_id ) {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $updated = $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$wpdb->postmeta} SET meta_value = meta_value + 1 WHERE post_id = %d AND meta_key = %s",
                $post_id,
                self::META_KEY
            )
        );

        if ( ! $updated ) {
            // No row yet (edition published before the counter existed).
            add_post_meta( $post_id, self::META_KEY, 1, true );
        }

        wp_cache_delete( $post_id, 'post_meta' );
    }

    /**
     * Current view count.
     *
     * @param int $post_id ePaper post ID.
     * @return int
     */
    public static function get( $post_id ) {
        return absint( get_post_meta( $post_id, self::META_KEY, true ) );
    }

    /**
     * Seed the counter when an ePaper is published so it always sorts and
     * reports predictably.
     *
     * @param string  $new_status New status.
     * @param string  $old_status Previous status.
     * @param WP_Post $post       Post object.
     * @return void
     */
    public static function init_meta( $new_status, $old_status, $post ) {
        if ( 'publish' !== $new_status || ! $post instanceof WP_Post || 'epaper' !== $post->post_type ) {
            return;
        }

        if ( '' === get_post_meta( $post->ID, self::META_KEY, true ) ) {
            add_post_meta( $post->ID, self::META_KEY, 0, true );
        }
    }

    /**
     * Hand the beacon endpoint to the frontend script.
     *
     * @param string $handle Script handle to attach the data to.
     * @return void
     */
    public static function localize( $handle ) {
        wp_localize_script(
            $handle,
            'sk_epaper_views',
            array(
                'endpoint' => esc_url_raw( rest_url( self::REST_NS . '/view/' ) ),
                'enabled'  => (bool) apply_filters( 'sk_epaper_enable_view_beacon', true ),
            )
        );
    }

    /**
     * Fallback pixel for visitors without JavaScript.
     *
     * @param int $post_id ePaper post ID.
     * @return string
     */
    public static function get_noscript_pixel( $post_id ) {
        $url = add_query_arg(
            array(
                'action'    => 'sk_epaper_view_pixel',
                'epaper_id' => absint( $post_id ),
            ),
            admin_url( 'admin-ajax.php' )
        );

        return '<noscript><img src="' . esc_url( $url ) . '" alt="" width="1" height="1" style="position:absolute;left:-9999px;" /></noscript>';
    }
}
