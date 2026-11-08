<?php
/**
 * Review / feedback prompt.
 *
 * A single admin notice asking whether the plugin is working out, shown only
 * once a site has genuinely used it for a while. Every option leads somewhere
 * useful: a happy publisher is pointed at the review form, an unhappy one at
 * the support forum, so problems reach the author instead of a one-star review.
 *
 * Nothing is sent anywhere by the plugin itself - the answers are plain links,
 * and the only thing stored is this site's own choice, so the notice never
 * comes back after a permanent dismissal.
 *
 * @package SK_ePaper_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Review notice and its dismissal handling.
 */
class SK_EPaper_Feedback {

    const INSTALLED_OPTION = 'sk_epaper_installed_at';
    const STATUS_OPTION    = 'sk_epaper_review_status';
    const SNOOZE_OPTION    = 'sk_epaper_review_snooze_until';
    const VIEWS_TRANSIENT  = 'sk_epaper_total_views';
    const NONCE_ACTION     = 'sk_epaper_review_notice';
    const AJAX_ACTION      = 'sk_epaper_review_notice';

    const MIN_AGE      = 7;   // Days since install.
    const MIN_EDITIONS = 5;   // Published editions, sample content excluded.
    const MIN_VIEWS    = 100; // Total counted views.
    const SNOOZE_DAYS  = 14;

    const REVIEW_URL  = 'https://wordpress.org/support/plugin/sk-epaper-manager/reviews/#new-post';
    const SUPPORT_URL = 'https://wordpress.org/support/plugin/sk-epaper-manager/';

    /**
     * Answers the notice accepts. Anything else is rejected.
     *
     * @var string[]
     */
    protected static $choices = array( 'rate', 'feedback', 'later', 'done' );

    /**
     * Admin page slug of the "Request a Feature" submenu item.
     */
    const FEATURE_PAGE = 'sk-epaper-feature-request';

    /**
     * External URL for one of the feedback links.
     *
     * Falls back to the support forum if a filter returns something unusable,
     * so the links can never point nowhere.
     *
     * @param string $which feature_request|early_access.
     * @return string
     */
    public static function get_url( $which ) {
        if ( 'early_access' === $which ) {
            /**
             * Filter the "Early Access to Pro" form URL.
             *
             * @param string $url Default from SK_EPAPER_EARLY_ACCESS_URL.
             */
            $url = apply_filters( 'sk_epaper_early_access_url', SK_EPAPER_EARLY_ACCESS_URL );
        } else {
            /**
             * Filter the "Request a Feature" form URL.
             *
             * @param string $url Default from SK_EPAPER_FEATURE_REQUEST_URL.
             */
            $url = apply_filters( 'sk_epaper_feature_request_url', SK_EPAPER_FEATURE_REQUEST_URL );
        }

        $url = esc_url_raw( (string) $url, array( 'http', 'https' ) );

        return '' !== $url ? $url : self::SUPPORT_URL;
    }

    /**
     * Without JavaScript the "Request a Feature" menu item lands on its own
     * admin page; send it straight on to the form.
     *
     * With JavaScript, epaper-admin.js points the menu link at the form
     * directly and opens it in a new tab, so this never runs.
     *
     * @return void
     */
    public static function redirect_feature_request() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        wp_redirect( self::get_url( 'feature_request' ) ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- operator-configured external form.
        exit;
    }

    /**
     * Fallback page body, only reached if the redirect above was skipped.
     *
     * @return void
     */
    public static function render_feature_request_page() {
        echo '<div class="wrap"><h1>' . esc_html__( 'Request a Feature', 'sk-epaper-manager' ) . '</h1><p><a class="button button-primary" href="' . esc_url( self::get_url( 'feature_request' ) ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Open the feature request form', 'sk-epaper-manager' ) . '</a></p></div>';
    }

    /**
     * Remember when the plugin was first installed.
     *
     * add_option() never overwrites, so reactivating keeps the original date.
     * Sites updating from 2.0.0 have no stamp yet and get one on their first
     * admin load after the update.
     *
     * @return void
     */
    public static function maybe_set_install_time() {
        if ( false === get_option( self::INSTALLED_OPTION, false ) ) {
            add_option( self::INSTALLED_OPTION, time() );
        }
    }

    /**
     * Whether the current admin screen is one the notice may appear on.
     *
     * The plugin's own list, taxonomy and submenu screens plus the main
     * Dashboard. Edit screens are left out: a prompt there interrupts someone
     * in the middle of publishing an edition.
     *
     * @return bool
     */
    public static function is_allowed_screen() {
        if ( ! function_exists( 'get_current_screen' ) ) {
            return false;
        }

        $screen = get_current_screen();

        if ( ! $screen ) {
            return false;
        }

        if ( 'dashboard' === $screen->id ) {
            return true;
        }

        if ( 0 === strpos( (string) $screen->id, 'epaper_page_' ) ) {
            return true;
        }

        if ( 'epaper' === $screen->post_type && in_array( $screen->base, array( 'edit', 'edit-tags', 'term' ), true ) ) {
            return true;
        }

        return false;
    }

    /**
     * Whether the notice should be shown on this request.
     *
     * Cheap checks run first; the database is only touched once the site is
     * old enough and the notice is not dismissed or snoozed.
     *
     * @return bool
     */
    public static function should_show() {
        if ( ! current_user_can( 'manage_options' ) || ! self::is_allowed_screen() ) {
            return false;
        }

        $show = self::is_eligible();

        /**
         * Filter whether the review notice is shown.
         *
         * Return false to suppress it, for example on a client site.
         *
         * @param bool $show Default decision.
         */
        return (bool) apply_filters( 'sk_epaper_show_review_notice', $show );
    }

    /**
     * Site-level eligibility, independent of user and screen.
     *
     * @return bool
     */
    protected static function is_eligible() {
        if ( '' !== (string) get_option( self::STATUS_OPTION, '' ) ) {
            return false;
        }

        if ( absint( get_option( self::SNOOZE_OPTION, 0 ) ) > time() ) {
            return false;
        }

        $installed = absint( get_option( self::INSTALLED_OPTION, 0 ) );

        if ( ! $installed || ( time() - $installed ) < self::MIN_AGE * DAY_IN_SECONDS ) {
            return false;
        }

        return self::count_real_editions() >= self::MIN_EDITIONS
            || self::get_total_views() >= self::MIN_VIEWS;
    }

    /**
     * Published editions, not counting the sample content.
     *
     * Importing the samples must not make a brand new site look established.
     *
     * @return int
     */
    protected static function count_real_editions() {
        $counts    = wp_count_posts( 'epaper' );
        $published = isset( $counts->publish ) ? absint( $counts->publish ) : 0;

        if ( $published < self::MIN_EDITIONS ) {
            return $published;
        }

        return max( 0, $published - SK_EPaper_Demo::count() );
    }

    /**
     * Total counted views across published editions, cached for 12 hours.
     *
     * @return int
     */
    public static function get_total_views() {
        $cached = get_transient( self::VIEWS_TRANSIENT );

        if ( false !== $cached ) {
            return absint( $cached );
        }

        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $total = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM( CAST( pm.meta_value AS UNSIGNED ) )
                 FROM {$wpdb->postmeta} pm
                 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
                 WHERE pm.meta_key = %s
                   AND p.post_type = 'epaper'
                   AND p.post_status = 'publish'",
                SK_EPaper_Views::META_KEY
            )
        );

        $total = absint( $total );

        set_transient( self::VIEWS_TRANSIENT, $total, 12 * HOUR_IN_SECONDS );

        return $total;
    }

    /**
     * Store an answer.
     *
     * @param string $choice rate|feedback|later|done.
     * @return bool Whether the choice was valid.
     */
    public static function apply_choice( $choice ) {
        if ( ! in_array( $choice, self::$choices, true ) ) {
            return false;
        }

        if ( 'later' === $choice ) {
            update_option( self::SNOOZE_OPTION, time() + self::SNOOZE_DAYS * DAY_IN_SECONDS, false );
            return true;
        }

        $status = array(
            'rate'     => 'rated',
            'feedback' => 'feedback',
            'done'     => 'done',
        );

        update_option( self::STATUS_OPTION, $status[ $choice ], false );
        delete_option( self::SNOOZE_OPTION );

        return true;
    }

    /**
     * AJAX: record the answer.
     *
     * @return void
     */
    public static function ajax_handle_choice() {
        check_ajax_referer( self::NONCE_ACTION, 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'sk-epaper-manager' ) ), 403 );
        }

        $choice = isset( $_POST['choice'] ) ? sanitize_key( wp_unslash( $_POST['choice'] ) ) : '';

        if ( ! self::apply_choice( $choice ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid request.', 'sk-epaper-manager' ) ), 400 );
        }

        wp_send_json_success();
    }

    /**
     * No-JavaScript fallback: the same answers as nonced links.
     *
     * "Yes" and "Not really" open this URL in a new tab, which records the
     * answer and then redirects that tab on to wordpress.org. With JavaScript
     * the links are swapped to point at wordpress.org directly and the answer
     * goes over AJAX instead.
     *
     * @return void
     */
    public static function handle_fallback() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- verified below.
        if ( ! isset( $_GET['sk_epaper_review'] ) ) {
            return;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        check_admin_referer( self::NONCE_ACTION );

        $choice = sanitize_key( wp_unslash( $_GET['sk_epaper_review'] ) );

        if ( ! self::apply_choice( $choice ) ) {
            return;
        }

        if ( 'rate' === $choice ) {
            wp_redirect( self::REVIEW_URL ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- fixed wordpress.org URL.
            exit;
        }

        if ( 'feedback' === $choice ) {
            wp_redirect( self::SUPPORT_URL ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- fixed wordpress.org URL.
            exit;
        }

        wp_safe_redirect( remove_query_arg( array( 'sk_epaper_review', '_wpnonce' ) ) );
        exit;
    }

    /**
     * Nonced fallback URL for one answer.
     *
     * @param string $choice Answer key.
     * @return string
     */
    protected static function fallback_url( $choice ) {
        return wp_nonce_url( add_query_arg( 'sk_epaper_review', $choice ), self::NONCE_ACTION );
    }

    /**
     * Load the notice script only on requests that actually show the notice.
     *
     * @return void
     */
    public static function enqueue_assets() {
        if ( ! self::should_show() ) {
            return;
        }

        wp_enqueue_script(
            SK_EPAPER_PREFIX . 'notice',
            SK_EPAPER_URL . 'assets/admin/epaper-notice.js',
            array( 'wp-a11y' ),
            SK_EPAPER_VERSION,
            true
        );

        wp_localize_script(
            SK_EPAPER_PREFIX . 'notice',
            'sk_epaper_review',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'action'   => self::AJAX_ACTION,
                'nonce'    => wp_create_nonce( self::NONCE_ACTION ),
            )
        );
    }

    /**
     * Print the notice.
     *
     * @return void
     */
    public static function render_notice() {
        if ( ! self::should_show() ) {
            return;
        }
        ?>
        <div class="notice notice-info is-dismissible sk-epaper-review-notice" id="sk-epaper-review-notice" role="region" aria-labelledby="sk-epaper-review-title">
            <div class="sk-review-notice-body">
                <p class="sk-review-notice-title" id="sk-epaper-review-title">
                    <strong><?php esc_html_e( 'Enjoying SK ePaper Manager?', 'sk-epaper-manager' ); ?></strong>
                </p>
                <p class="sk-review-notice-text">
                    <?php esc_html_e( 'You have been publishing editions for a while now. A quick word helps a lot: a review lets other publishers find the plugin, and if something is not right we want to hear about it.', 'sk-epaper-manager' ); ?>
                </p>
                <p class="sk-review-notice-actions">
                    <a class="button button-primary" data-sk-review="rate"
                        href="<?php echo esc_url( self::fallback_url( 'rate' ) ); ?>"
                        data-direct="<?php echo esc_url( self::REVIEW_URL ); ?>"
                        target="_blank" rel="noopener noreferrer">
                        <?php esc_html_e( 'Yes, I love it', 'sk-epaper-manager' ); ?>
                        <span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'sk-epaper-manager' ); ?></span>
                    </a>
                    <a class="button" data-sk-review="feedback"
                        href="<?php echo esc_url( self::fallback_url( 'feedback' ) ); ?>"
                        data-direct="<?php echo esc_url( self::SUPPORT_URL ); ?>"
                        target="_blank" rel="noopener noreferrer">
                        <?php esc_html_e( 'Not really', 'sk-epaper-manager' ); ?>
                        <span class="screen-reader-text"><?php esc_html_e( '(opens the support forum in a new tab)', 'sk-epaper-manager' ); ?></span>
                    </a>
                    <a class="button-link sk-review-secondary" data-sk-review="later" role="button"
                        href="<?php echo esc_url( self::fallback_url( 'later' ) ); ?>">
                        <?php esc_html_e( 'Maybe later', 'sk-epaper-manager' ); ?>
                    </a>
                    <a class="button-link sk-review-secondary" data-sk-review="done" role="button"
                        href="<?php echo esc_url( self::fallback_url( 'done' ) ); ?>">
                        <?php esc_html_e( 'Already did', 'sk-epaper-manager' ); ?>
                    </a>
                </p>
            </div>
            <div class="sk-review-notice-thanks" hidden>
                <p data-sk-thanks="rate"><?php esc_html_e( 'Thank you! Your review means a lot.', 'sk-epaper-manager' ); ?></p>
                <p data-sk-thanks="feedback">
                    <?php esc_html_e( 'Tell us what\'s wrong, we\'ll fix it.', 'sk-epaper-manager' ); ?>
                    <a href="<?php echo esc_url( self::SUPPORT_URL ); ?>" target="_blank" rel="noopener noreferrer">
                        <?php esc_html_e( 'Open the support forum', 'sk-epaper-manager' ); ?>
                        <span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'sk-epaper-manager' ); ?></span>
                    </a>
                </p>
            </div>
        </div>
        <?php
    }
}
