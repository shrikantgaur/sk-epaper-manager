<?php
/**
 * Premium Template for displaying ePaper Archives, City Editions & Languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

get_header();

$sk_epaper_args = array(
    'post_type'      => 'epaper',
    'posts_per_page' => -1,
    'post_status'    => 'publish',
);

$sk_archive_title = __( 'ePapers', 'sk-epaper-manager' );
$sk_archive_icon  = 'dashicons-welcome-widgets-menus';

if ( is_tax( 'epaper_edition' ) ) {
    $sk_term = get_queried_object();
    /* translators: %s: Taxonomy term name */
    $sk_archive_title = sprintf( __( 'Edition: %s', 'sk-epaper-manager' ), $sk_term->name );
    $sk_archive_icon  = 'dashicons-location-alt';
    // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
    $sk_epaper_args['tax_query'] = array(
        array(
            'taxonomy' => 'epaper_edition',
            'field'    => 'term_id',
            'terms'    => $sk_term->term_id,
        ),
    );
} elseif ( is_tax( 'epaper_language' ) ) {
    $sk_term = get_queried_object();
    /* translators: %s: Taxonomy term name */
    $sk_archive_title = sprintf( __( 'Language: %s', 'sk-epaper-manager' ), $sk_term->name );
    $sk_archive_icon  = 'dashicons-translation';
    // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
    $sk_epaper_args['tax_query'] = array(
        array(
            'taxonomy' => 'epaper_language',
            'field'    => 'term_id',
            'terms'    => $sk_term->term_id,
        ),
    );
}

$sk_epaper_query = new WP_Query( $sk_epaper_args );
$sk_found_count  = $sk_epaper_query->found_posts;
?>

<div id="primary" class="content-area epaper-archive-wrapper">
    <main id="main" class="site-main epaper-archive-container">
        
        <!-- Header Banner -->
        <header class="epaper-archive-hero-header">
            <div class="hero-header-content">
                <h1 class="page-title">
                    <span class="dashicons <?php echo esc_attr( $sk_archive_icon ); ?>"></span>
                    <?php echo esc_html( $sk_archive_title ); ?>
                </h1>
                <span class="epaper-count-badge">
                    <span class="dashicons dashicons-images-alt2"></span>
                    <?php
                    /* translators: %d: Total number of available ePaper editions */
                    echo esc_html( sprintf( _n( '%d Edition Available', '%d Editions Available', $sk_found_count, 'sk-epaper-manager' ), $sk_found_count ) );
                    ?>
                </span>
            </div>
        </header>

        <!-- ePaper Grid -->
        <section class="epaper-grid-container">
            <?php if ( $sk_epaper_query->have_posts() ) : ?>
                <?php while ( $sk_epaper_query->have_posts() ) : $sk_epaper_query->the_post(); ?>
                    <?php
                    $sk_post_id      = get_the_ID();
                    $sk_edition_date = get_post_meta( $sk_post_id, SK_EPAPER_PREFIX . 'edition_date', true );
                    $sk_images       = get_post_meta( $sk_post_id, SK_EPAPER_PREFIX . 'images', true );
                    if ( ! is_array( $sk_images ) ) {
                        $sk_images = array_filter( explode( ',', $sk_images ) );
                    }
                    $sk_page_count = count( $sk_images );
                    $sk_editions   = get_the_terms( $sk_post_id, 'epaper_edition' );
                    $sk_languages  = get_the_terms( $sk_post_id, 'epaper_language' );
                    ?>
                    <article class="epaper-card-item">
                        <div class="epaper-card-thumb-wrap">
                            <a href="<?php echo esc_url( get_permalink() ); ?>" class="epaper-thumb-link">
                                <?php if ( has_post_thumbnail() ) : ?>
                                    <?php the_post_thumbnail( 'large', array( 'class' => 'epaper-card-img' ) ); ?>
                                <?php else : ?>
                                    <div class="epaper-placeholder-card">
                                        <span class="dashicons dashicons-media-document"></span>
                                    </div>
                                <?php endif; ?>
                            </a>

                            <!-- Overlay Page Counter Badge -->
                            <span class="epaper-card-page-badge">
                                <span class="dashicons dashicons-images-alt2"></span>
                                <?php
                                /* translators: %d: Total number of pages */
                                echo esc_html( sprintf( __( '%d Pages', 'sk-epaper-manager' ), $sk_page_count ) );
                                ?>
                            </span>

                            <!-- Hover Overlay Button -->
                            <div class="epaper-card-hover-overlay">
                                <a href="<?php echo esc_url( get_permalink() ); ?>" class="epaper-hover-read-btn">
                                    <span class="dashicons dashicons-visibility"></span> <?php esc_html_e( 'Read ePaper', 'sk-epaper-manager' ); ?>
                                </a>
                            </div>
                        </div>

                        <div class="epaper-card-body">
                            <?php if ( $sk_edition_date ) : ?>
                                <div class="epaper-card-date">
                                    <span class="dashicons dashicons-calendar-alt"></span>
                                    <?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $sk_edition_date ) ) ); ?>
                                </div>
                            <?php endif; ?>

                            <h3 class="epaper-card-title">
                                <a href="<?php echo esc_url( get_permalink() ); ?>"><?php the_title(); ?></a>
                            </h3>

                            <div class="epaper-card-tax-meta">
                                <?php if ( ! empty( $sk_editions ) && ! is_wp_error( $sk_editions ) ) : ?>
                                    <span class="epaper-tax-pill pill-edition">
                                        <span class="dashicons dashicons-location"></span>
                                        <?php echo esc_html( implode( ', ', wp_list_pluck( $sk_editions, 'name' ) ) ); ?>
                                    </span>
                                <?php endif; ?>

                                <?php if ( ! empty( $sk_languages ) && ! is_wp_error( $sk_languages ) ) : ?>
                                    <span class="epaper-tax-pill pill-language">
                                        <span class="dashicons dashicons-translation"></span>
                                        <?php echo esc_html( implode( ', ', wp_list_pluck( $sk_languages, 'name' ) ) ); ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <a href="<?php echo esc_url( get_permalink() ); ?>" class="epaper-card-cta-btn">
                                <span><?php esc_html_e( 'Read Now', 'sk-epaper-manager' ); ?></span>
                                <span class="dashicons dashicons-arrow-right-alt2"></span>
                            </a>
                        </div>
                    </article>
                <?php endwhile; ?>
            <?php else : ?>
                <div class="epaper-no-results">
                    <span class="dashicons dashicons-info-outline"></span>
                    <p><?php esc_html_e( 'No ePaper editions found matching this category.', 'sk-epaper-manager' ); ?></p>
                </div>
            <?php endif; ?>
        </section>

    </main>
</div>

<?php
wp_reset_postdata();
get_footer();
?>