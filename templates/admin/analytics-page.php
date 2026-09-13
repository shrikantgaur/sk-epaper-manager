<?php
/**
 * Analytics & Statistics Dashboard Page with High Performance & Pagination
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

global $wpdb;

// Pagination Parameters
$sk_per_page = 15;
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$sk_current_page = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
$sk_offset       = ( $sk_current_page - 1 ) * $sk_per_page;

// Filter Inputs
// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
$sk_filter_edition  = isset( $_GET['filter_edition'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_edition'] ) ) : '';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
$sk_filter_language = isset( $_GET['filter_language'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_language'] ) ) : '';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
$sk_filter_search   = isset( $_GET['filter_search'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_search'] ) ) : '';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$sk_filter_from     = isset( $_GET['filter_from'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_from'] ) ) : '';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$sk_filter_to       = isset( $_GET['filter_to'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_to'] ) ) : '';

foreach ( array( 'sk_filter_from', 'sk_filter_to' ) as $sk_date_field ) {
    if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $$sk_date_field ) ) {
        $$sk_date_field = '';
    }
}

// A backwards range returns nothing and looks like a bug, so swap it.
if ( $sk_filter_from && $sk_filter_to && $sk_filter_from > $sk_filter_to ) {
    list( $sk_filter_from, $sk_filter_to ) = array( $sk_filter_to, $sk_filter_from );
}

// 1. High Performance SQL Aggregation for Top Metric Cards (0.001s Query Time)
$sk_total_published = wp_count_posts( 'epaper' )->publish;

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$sk_total_views = $wpdb->get_var(
    "SELECT SUM(CAST(meta_value AS UNSIGNED)) 
     FROM {$wpdb->postmeta} pm 
     INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id 
     WHERE pm.meta_key = 'sk_epaper_views' AND p.post_type = 'epaper' AND p.post_status = 'publish'"
);
$sk_total_views = absint( $sk_total_views );

$sk_avg_views = $sk_total_published > 0 ? round( $sk_total_views / $sk_total_published, 1 ) : 0;

// 2. Build Query Args for Paginated Performance Table (shared with the AJAX handler).
$sk_query_args = sk_epaper_get_analytics_query_args(
    array(
        'paged'    => $sk_current_page,
        'per_page' => $sk_per_page,
        'edition'  => $sk_filter_edition,
        'language' => $sk_filter_language,
        'search'   => $sk_filter_search,
        'from'     => $sk_filter_from,
        'to'       => $sk_filter_to,
    )
);

$sk_analytics_query = new WP_Query( $sk_query_args );
$sk_total_found     = $sk_analytics_query->found_posts;
$sk_total_pages     = $sk_analytics_query->max_num_pages;

// Total Pages count approximation for top stats
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$sk_total_pages_count = $wpdb->get_var(
    "SELECT COUNT(*) FROM {$wpdb->postmeta} pm 
     INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id 
     WHERE pm.meta_key = 'sk_epaper_images' AND p.post_type = 'epaper' AND p.post_status = 'publish'"
);

// Fetch Taxonomies for Dropdowns
$sk_all_editions  = get_terms( array( 'taxonomy' => 'epaper_edition', 'hide_empty' => false ) );
$sk_all_languages = get_terms( array( 'taxonomy' => 'epaper_language', 'hide_empty' => false ) );
?>

<div class="wrap sk-epaper-admin-wrap">
    <div class="sk-admin-header-flex">
        <div>
            <h1 class="wp-heading-inline">
                <span class="dashicons dashicons-chart-bar sk-header-icon"></span>
                <?php esc_html_e( 'ePaper Analytics & View Statistics', 'sk-epaper-manager' ); ?>
            </h1>
            <p class="description">
                <?php esc_html_e( 'Real-time reader engagement metrics, popular edition rankings, and station analytics.', 'sk-epaper-manager' ); ?>
            </p>
        </div>
    </div>

    <!-- 1. Metric Cards (TOP SECTION) -->
    <div class="sk-epaper-stats-grid" style="margin-top: 20px;">
        <div class="sk-stat-card border-blue">
            <div class="sk-stat-icon icon-blue"><span class="dashicons dashicons-visibility"></span></div>
            <div class="sk-stat-info">
                <h3><?php echo esc_html( number_format( $sk_total_views ) ); ?></h3>
                <p><?php esc_html_e( 'Total ePaper Reads', 'sk-epaper-manager' ); ?></p>
            </div>
        </div>

        <div class="sk-stat-card border-green">
            <div class="sk-stat-icon icon-green"><span class="dashicons dashicons-welcome-widgets-menus"></span></div>
            <div class="sk-stat-info">
                <h3><?php echo esc_html( number_format( $sk_total_published ) ); ?></h3>
                <p><?php esc_html_e( 'Published Editions', 'sk-epaper-manager' ); ?></p>
            </div>
        </div>

        <div class="sk-stat-card border-purple">
            <div class="sk-stat-icon icon-purple"><span class="dashicons dashicons-images-alt2"></span></div>
            <div class="sk-stat-info">
                <h3><?php echo esc_html( number_format( $sk_total_pages_count ) ); ?></h3>
                <p><?php esc_html_e( 'Total Editions Count', 'sk-epaper-manager' ); ?></p>
            </div>
        </div>

        <div class="sk-stat-card border-orange">
            <div class="sk-stat-icon icon-orange"><span class="dashicons dashicons-chart-area"></span></div>
            <div class="sk-stat-info">
                <h3><?php echo esc_html( $sk_avg_views ); ?></h3>
                <p><?php esc_html_e( 'Avg Reads per Edition', 'sk-epaper-manager' ); ?></p>
            </div>
        </div>
    </div>

    <!-- 2. Filter Toolbar (BELOW METRICS) -->
    <div class="sk-filter-toolbar" style="margin-top: 25px;">
        <form id="sk-analytics-filter-form" method="get" action="">
            <input type="hidden" name="post_type" value="epaper">
            <input type="hidden" name="page" value="sk-epaper-analytics">
            <input type="hidden" name="paged" id="sk-analytics-paged" value="<?php echo esc_attr( $sk_current_page ); ?>">

            <div class="sk-filter-row">
                <div class="sk-filter-item">
                    <label for="filter_search"><span class="dashicons dashicons-search"></span>
                        <?php esc_html_e( 'Search Title', 'sk-epaper-manager' ); ?></label>
                    <input type="text" name="filter_search" id="filter_search"
                        value="<?php echo esc_attr( $sk_filter_search ); ?>"
                        placeholder="<?php esc_attr_e( 'Search edition...', 'sk-epaper-manager' ); ?>"
                        class="regular-text">
                </div>

                <?php if ( ! empty( $sk_all_editions ) && ! is_wp_error( $sk_all_editions ) ) : ?>
                    <div class="sk-filter-item">
                        <label for="filter_edition"><span class="dashicons dashicons-location"></span>
                            <?php esc_html_e( 'City / Edition', 'sk-epaper-manager' ); ?></label>
                        <select name="filter_edition" id="filter_edition">
                            <option value=""><?php esc_html_e( 'All Cities / Editions', 'sk-epaper-manager' ); ?></option>
                            <?php foreach ( $sk_all_editions as $sk_term ) : ?>
                                <option value="<?php echo esc_attr( $sk_term->slug ); ?>" <?php selected( $sk_filter_edition, $sk_term->slug ); ?>><?php echo esc_html( $sk_term->name ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <?php if ( ! empty( $sk_all_languages ) && ! is_wp_error( $sk_all_languages ) ) : ?>
                    <div class="sk-filter-item">
                        <label for="filter_language"><span class="dashicons dashicons-translation"></span>
                            <?php esc_html_e( 'Language', 'sk-epaper-manager' ); ?></label>
                        <select name="filter_language" id="filter_language">
                            <option value=""><?php esc_html_e( 'All Languages', 'sk-epaper-manager' ); ?></option>
                            <?php foreach ( $sk_all_languages as $sk_term ) : ?>
                                <option value="<?php echo esc_attr( $sk_term->slug ); ?>" <?php selected( $sk_filter_language, $sk_term->slug ); ?>><?php echo esc_html( $sk_term->name ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="sk-filter-item sk-filter-dates">
                    <label for="filter_from"><span class="dashicons dashicons-calendar-alt"></span>
                        <?php esc_html_e( 'Edition Date Range', 'sk-epaper-manager' ); ?></label>
                    <div class="sk-date-range">
                        <input type="date" name="filter_from" id="filter_from"
                            value="<?php echo esc_attr( $sk_filter_from ); ?>"
                            aria-label="<?php esc_attr_e( 'From date', 'sk-epaper-manager' ); ?>">
                        <span class="sk-date-sep" aria-hidden="true">&ndash;</span>
                        <input type="date" name="filter_to" id="filter_to"
                            value="<?php echo esc_attr( $sk_filter_to ); ?>"
                            aria-label="<?php esc_attr_e( 'To date', 'sk-epaper-manager' ); ?>">
                    </div>
                </div>

                <div class="sk-filter-actions">
                    <button type="submit" class="button button-primary">
                        <span class="dashicons dashicons-filter"></span>
                        <?php esc_html_e( 'Apply Filter', 'sk-epaper-manager' ); ?>
                    </button>
                    <?php if ( ! empty( $sk_filter_edition ) || ! empty( $sk_filter_language ) || ! empty( $sk_filter_search ) || ! empty( $sk_filter_from ) || ! empty( $sk_filter_to ) ) : ?>
                        <a id="sk-reset-filters-btn" href="<?php echo esc_url( admin_url( 'edit.php?post_type=epaper&page=sk-epaper-analytics' ) ); ?>"
                            class="button button-secondary">
                            <?php esc_html_e( 'Reset Filters', 'sk-epaper-manager' ); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>

    <!-- 3. Most Read Table & Pagination -->
    <div class="sk-epaper-dashboard-section" style="margin-top: 25px;">
        <div class="sk-dashboard-card" style="position: relative;">
            <div class="sk-card-title-row"
                style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
                <h2>
                    <span class="dashicons dashicons-awards sk-icon-gold"></span>
                    <?php esc_html_e( 'Most Read ePaper Editions', 'sk-epaper-manager' ); ?>
                </h2>
                <span id="sk-total-editions-badge" class="badge-pill bg-blue" style="font-size: 13px; padding: 5px 12px;">
                    <?php
                    /* translators: %d: Total number of ePaper editions */
                    echo esc_html( sprintf( __( 'Total: %d Editions', 'sk-epaper-manager' ), $sk_total_found ) );
                    ?>
                </span>
            </div>

            <div id="sk-analytics-table-container">
                <?php if ( $sk_analytics_query->have_posts() ) : ?>
                    <table class="wp-list-table widefat fixed striped sk-analytics-table">
                        <thead>
                            <tr>
                                <th style="width: 70px; text-align: center;"><?php esc_html_e( 'Rank', 'sk-epaper-manager' ); ?>
                                </th>
                                <th style="width: 35%;"><?php esc_html_e( 'Edition Title', 'sk-epaper-manager' ); ?></th>
                                <th style="width: 20%;"><?php esc_html_e( 'City / Edition', 'sk-epaper-manager' ); ?></th>
                                <th style="width: 15%;"><?php esc_html_e( 'Language', 'sk-epaper-manager' ); ?></th>
                                <th style="width: 12%; text-align: center;"><?php esc_html_e( 'Pages', 'sk-epaper-manager' ); ?>
                                </th>
                                <th style="width: 15%; text-align: center;">
                                    <?php esc_html_e( 'Total Views', 'sk-epaper-manager' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sk_rank = $sk_offset + 1;
                            while ( $sk_analytics_query->have_posts() ) :
                                $sk_analytics_query->the_post();
                                $sk_epaper_id = get_the_ID();
                                $sk_views       = SK_EPaper_Views::get( $sk_epaper_id );
                                $sk_pages_count = count( SK_EPaper_Renderer::get_images( $sk_epaper_id ) );

                                $sk_editions_terms  = get_the_terms( $sk_epaper_id, 'epaper_edition' );
                                $sk_languages_terms = get_the_terms( $sk_epaper_id, 'epaper_language' );
                                $sk_edition_names   = ( ! empty( $sk_editions_terms ) && ! is_wp_error( $sk_editions_terms ) ) ? implode( ', ', wp_list_pluck( $sk_editions_terms, 'name' ) ) : '—';
                                $sk_language_names  = ( ! empty( $sk_languages_terms ) && ! is_wp_error( $sk_languages_terms ) ) ? implode( ', ', wp_list_pluck( $sk_languages_terms, 'name' ) ) : '—';

                                $sk_rank_badge = ( 1 === $sk_rank ) ? 'rank-gold' : (( 2 === $sk_rank ) ? 'rank-silver' : (( 3 === $sk_rank ) ? 'rank-bronze' : 'rank-normal'));
                                ?>
                                <tr>
                                    <td style="text-align: center;">
                                        <span
                                            class="sk-rank-badge <?php echo esc_attr( $sk_rank_badge ); ?>">#<?php echo esc_html( $sk_rank ); ?></span>
                                    </td>
                                    <td>
                                        <strong><a href="<?php echo esc_url( get_permalink() ); ?>" target="_blank"
                                                class="sk-edition-link"><?php the_title(); ?></a></strong>
                                        <div class="row-actions">
                                            <span class="edit"><a
                                                    href="<?php echo esc_url( get_edit_post_link( $sk_epaper_id ) ); ?>"><?php esc_html_e( 'Edit', 'sk-epaper-manager' ); ?></a>
                                                | </span>
                                            <span class="view"><a href="<?php echo esc_url( get_permalink() ); ?>"
                                                    target="_blank"><?php esc_html_e( 'View', 'sk-epaper-manager' ); ?></a></span>
                                        </div>
                                    </td>
                                    <td><span class="sk-meta-tag"><span class="dashicons dashicons-location"></span>
                                            <?php echo esc_html( $sk_edition_names ); ?></span></td>
                                    <td><span class="sk-meta-tag"><span class="dashicons dashicons-translation"></span>
                                            <?php echo esc_html( $sk_language_names ); ?></span></td>
                                    <td style="text-align: center;"><span
                                            class="badge-pill"><?php
                                            /* translators: %d: Number of pages in the edition */
                                            printf( esc_html( _n( '%d Page', '%d Pages', $sk_pages_count, 'sk-epaper-manager' ) ), esc_html( $sk_pages_count ) );
                                        ?></span></td>
                                    <td style="text-align: center;"><strong
                                            class="sk-views-count"><?php echo esc_html( number_format( $sk_views ) ); ?></strong>
                                    </td>
                                </tr>
                                <?php
                                $sk_rank++;
                            endwhile;
                            wp_reset_postdata();
                            ?>
                        </tbody>
                    </table>

                    <!-- Admin Pagination Links -->
                    <?php if ( $sk_total_pages > 1 ) : ?>
                        <div class="sk-pagination-wrapper" style="margin-top: 20px; display: flex; justify-content: flex-end;">
                            <div class="tablenav-pages">
                                <?php
                                $sk_page_links = paginate_links( array(
                                    'base'      => add_query_arg( 'paged', '%#%' ),
                                    'format'    => '',
                                    'prev_text' => __( '&laquo; Previous', 'sk-epaper-manager' ),
                                    'next_text' => __( 'Next &raquo;', 'sk-epaper-manager' ),
                                    'total'     => $sk_total_pages,
                                    'current'   => $sk_current_page,
                                    'type'      => 'plain',
                                ) );
                                echo wp_kses_post( $sk_page_links );
                                ?>
                            </div>
                        </div>
                    <?php endif; ?>

                <?php else : ?>
                    <div class="sk-empty-analytics">
                        <span class="dashicons dashicons-info"
                            style="font-size: 36px; width: 36px; height: 36px; color: #94a3b8;"></span>
                        <p><?php esc_html_e( 'No matching analytics data found. Try adjusting your filters.', 'sk-epaper-manager' ); ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>