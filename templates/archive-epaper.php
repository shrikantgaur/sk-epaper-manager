<?php
/**
 * ePaper archive, City Edition and Language listings.
 *
 * Themes may override this file by copying it to:
 *   yourtheme/sk-epaper/archive-epaper.php
 *
 * @package SK_ePaper_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

get_header();

global $wp_query;

$sk_epaper_archive_title = __( 'ePapers', 'sk-epaper-manager' );
$sk_epaper_archive_icon  = 'dashicons-welcome-widgets-menus';

if ( is_tax( 'epaper_edition' ) ) {
    $sk_epaper_term = get_queried_object();
    /* translators: %s: Taxonomy term name */
    $sk_epaper_archive_title = sprintf( __( 'Edition: %s', 'sk-epaper-manager' ), $sk_epaper_term->name );
    $sk_epaper_archive_icon  = 'dashicons-location-alt';
} elseif ( is_tax( 'epaper_language' ) ) {
    $sk_epaper_term = get_queried_object();
    /* translators: %s: Taxonomy term name */
    $sk_epaper_archive_title = sprintf( __( 'Language: %s', 'sk-epaper-manager' ), $sk_epaper_term->name );
    $sk_epaper_archive_icon  = 'dashicons-translation';
}

// The main query is already scoped to the ePaper archive / taxonomy term and is
// paginated via sk_epaper_archive_pre_get_posts(), so no secondary query here.
$sk_epaper_found_count = (int) $wp_query->found_posts;

$sk_epaper_filters    = SK_EPaper_CPT::get_archive_filters();
$sk_epaper_has_filter = SK_EPaper_CPT::has_archive_filters();
$sk_epaper_all_editions  = get_terms( array( 'taxonomy' => 'epaper_edition', 'hide_empty' => true ) );
$sk_epaper_all_languages = get_terms( array( 'taxonomy' => 'epaper_language', 'hide_empty' => true ) );

$sk_epaper_form_action = is_tax() ? get_term_link( get_queried_object() ) : get_post_type_archive_link( 'epaper' );

if ( is_wp_error( $sk_epaper_form_action ) ) {
    $sk_epaper_form_action = get_post_type_archive_link( 'epaper' );
}

$sk_epaper_months = SK_EPaper_CPT::get_archive_months();
?>

<div id="primary" class="content-area epaper-archive-wrapper">
    <main id="main" class="site-main epaper-archive-container">
        
        <!-- Header Banner -->
        <header class="epaper-archive-hero-header">
            <div class="hero-header-content">
                <h1 class="page-title">
                    <span class="dashicons <?php echo esc_attr( $sk_epaper_archive_icon ); ?>"></span>
                    <?php echo esc_html( $sk_epaper_archive_title ); ?>
                </h1>
                <span class="epaper-count-badge">
                    <span class="dashicons dashicons-images-alt2"></span>
                    <?php
                    /* translators: %d: Total number of available ePaper editions */
                    echo esc_html( sprintf( _n( '%d Edition Available', '%d Editions Available', $sk_epaper_found_count, 'sk-epaper-manager' ), $sk_epaper_found_count ) );
                    ?>
                </span>
            </div>
        </header>

        <!-- Filters -->
        <form class="epaper-filter-bar" method="get" action="<?php echo esc_url( $sk_epaper_form_action ); ?>">
            <div class="epaper-filter-field epaper-filter-search">
                <label class="screen-reader-text" for="sk-epaper-q"><?php esc_html_e( 'Search editions', 'sk-epaper-manager' ); ?></label>
                <span class="dashicons dashicons-search" aria-hidden="true"></span>
                <input type="search" id="sk-epaper-q" name="sk_q" value="<?php echo esc_attr( $sk_epaper_filters['search'] ); ?>"
                    placeholder="<?php esc_attr_e( 'Search editions...', 'sk-epaper-manager' ); ?>">
            </div>

            <?php if ( ! empty( $sk_epaper_all_editions ) && ! is_wp_error( $sk_epaper_all_editions ) && ! is_tax( 'epaper_edition' ) ) : ?>
                <div class="epaper-filter-field">
                    <label class="screen-reader-text" for="sk-epaper-edition"><?php esc_html_e( 'Edition', 'sk-epaper-manager' ); ?></label>
                    <select id="sk-epaper-edition" name="sk_edition">
                        <option value=""><?php esc_html_e( 'All Editions', 'sk-epaper-manager' ); ?></option>
                        <?php foreach ( $sk_epaper_all_editions as $sk_epaper_term_option ) : ?>
                            <option value="<?php echo esc_attr( $sk_epaper_term_option->slug ); ?>" <?php selected( $sk_epaper_filters['edition'], $sk_epaper_term_option->slug ); ?>>
                                <?php echo esc_html( $sk_epaper_term_option->name ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <?php if ( ! empty( $sk_epaper_all_languages ) && ! is_wp_error( $sk_epaper_all_languages ) && ! is_tax( 'epaper_language' ) ) : ?>
                <div class="epaper-filter-field">
                    <label class="screen-reader-text" for="sk-epaper-language"><?php esc_html_e( 'Language', 'sk-epaper-manager' ); ?></label>
                    <select id="sk-epaper-language" name="sk_language">
                        <option value=""><?php esc_html_e( 'All Languages', 'sk-epaper-manager' ); ?></option>
                        <?php foreach ( $sk_epaper_all_languages as $sk_epaper_term_option ) : ?>
                            <option value="<?php echo esc_attr( $sk_epaper_term_option->slug ); ?>" <?php selected( $sk_epaper_filters['language'], $sk_epaper_term_option->slug ); ?>>
                                <?php echo esc_html( $sk_epaper_term_option->name ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <?php if ( ! empty( $sk_epaper_months ) ) : ?>
                <div class="epaper-filter-field">
                    <label class="screen-reader-text" for="sk-epaper-month"><?php esc_html_e( 'Month', 'sk-epaper-manager' ); ?></label>
                    <select id="sk-epaper-month" name="sk_month">
                        <option value=""><?php esc_html_e( 'Any Month', 'sk-epaper-manager' ); ?></option>
                        <?php foreach ( $sk_epaper_months as $sk_epaper_month_key => $sk_epaper_month_label ) : ?>
                            <option value="<?php echo esc_attr( $sk_epaper_month_key ); ?>" <?php selected( $sk_epaper_filters['month'], $sk_epaper_month_key ); ?>>
                                <?php echo esc_html( $sk_epaper_month_label ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div class="epaper-filter-field">
                <label class="screen-reader-text" for="sk-epaper-date"><?php esc_html_e( 'Edition date', 'sk-epaper-manager' ); ?></label>
                <?php
                $sk_epaper_dates    = SK_EPaper_CPT::get_available_dates();
                $sk_epaper_date_max = $sk_epaper_dates ? reset( $sk_epaper_dates ) : current_time( 'Y-m-d' );
                $sk_epaper_date_min = $sk_epaper_dates ? end( $sk_epaper_dates ) : '';
                ?>
                <input type="date" id="sk-epaper-date" name="sk_date" class="sk-epaper-date-field"
                    value="<?php echo esc_attr( $sk_epaper_filters['date'] ); ?>"
                    max="<?php echo esc_attr( $sk_epaper_date_max ); ?>"
                    <?php if ( $sk_epaper_date_min ) : ?>min="<?php echo esc_attr( $sk_epaper_date_min ); ?>"<?php endif; ?>
                    placeholder="<?php esc_attr_e( 'Any date', 'sk-epaper-manager' ); ?>"
                    aria-label="<?php esc_attr_e( 'Edition date', 'sk-epaper-manager' ); ?>">
            </div>

            <button type="submit" class="epaper-filter-submit">
                <span class="dashicons dashicons-filter" aria-hidden="true"></span>
                <?php esc_html_e( 'Filter', 'sk-epaper-manager' ); ?>
            </button>

            <?php if ( $sk_epaper_has_filter ) : ?>
                <a class="epaper-filter-reset" href="<?php echo esc_url( $sk_epaper_form_action ); ?>">
                    <span class="dashicons dashicons-no-alt" aria-hidden="true"></span>
                    <?php esc_html_e( 'Clear', 'sk-epaper-manager' ); ?>
                </a>
            <?php endif; ?>
        </form>

        <!-- ePaper Grid -->
        <section class="epaper-grid-container">
            <?php if ( have_posts() ) : ?>
                <?php while ( have_posts() ) : the_post(); ?>
                    <?php echo SK_EPaper_Renderer::render_card( get_the_ID() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <?php endwhile; ?>
            <?php else : ?>
                <div class="epaper-no-results">
                    <span class="dashicons dashicons-info-outline"></span>
                    <p>
                        <?php
                        if ( $sk_epaper_has_filter ) {
                            esc_html_e( 'No editions match these filters.', 'sk-epaper-manager' );
                        } else {
                            esc_html_e( 'No ePaper editions found matching this category.', 'sk-epaper-manager' );
                        }
                        ?>
                    </p>
                    <?php if ( $sk_epaper_has_filter ) : ?>
                        <p><a class="epaper-empty-cta" href="<?php echo esc_url( $sk_epaper_form_action ); ?>">
                            <span class="dashicons dashicons-image-rotate" aria-hidden="true"></span>
                            <?php esc_html_e( 'Clear filters', 'sk-epaper-manager' ); ?>
                        </a></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>

        <?php
        the_posts_pagination(
            array(
                'mid_size'           => 2,
                'class'              => 'epaper-archive-pagination',
                'prev_text'          => '<span class="dashicons dashicons-arrow-left-alt2"></span> ' . esc_html__( 'Previous', 'sk-epaper-manager' ),
                'next_text'          => esc_html__( 'Next', 'sk-epaper-manager' ) . ' <span class="dashicons dashicons-arrow-right-alt2"></span>',
                'screen_reader_text' => esc_html__( 'ePaper archive navigation', 'sk-epaper-manager' ),
            )
        );
        ?>

    </main>
</div>

<?php
get_footer();
?>