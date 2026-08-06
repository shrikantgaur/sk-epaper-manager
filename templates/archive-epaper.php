<?php
/**
 * Template for displaying all ePapers (Archive)
 * Place this file in your theme root or in your plugin if overriding via `template_include`.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

$sk_epaper_args = array(
    'post_type'      => 'epaper',
    'posts_per_page' => -1,
);

if ( is_tax( 'epaper_station' ) ) {
    $queried_object = get_queried_object();
    $sk_epaper_args['tax_query'] = array(
        array(
            'taxonomy' => 'epaper_station',
            'field'    => 'term_id',
            'terms'    => $queried_object->term_id,
        ),
    );
}

$sk_epaper_query = new WP_Query( $sk_epaper_args );
?>

<div id="primary" class="content-area">
    <main id="main" class="site-main" role="main">
        <header class="epaper-archive-header" style="margin-bottom: 20px;">
            <h1 class="page-title">
                <?php
                if ( is_tax( 'epaper_station' ) ) {
                    single_term_title( 'Station: ' );
                } else {
                    esc_html_e( 'ePapers Archive', 'sk-epaper-manager' );
                }
                ?>
            </h1>
        </header>

        <section class="epaper-listing">
            <?php if ( $sk_epaper_query->have_posts() ) : ?>
                <?php while ( $sk_epaper_query->have_posts() ) : $sk_epaper_query->the_post(); ?>
                    <?php
                    $post_id         = get_the_ID();
                    $sk_edition_date = get_post_meta( $post_id, SK_EPAPER_PREFIX . 'edition_date', true );
                    $sk_stations     = get_the_terms( $post_id, 'epaper_station' );
                    ?>
                    <article class="epaper-item">
                        <div class="epaper-thumbnail">
                            <a href="<?php echo esc_url( get_permalink() ); ?>">
                                <?php if ( has_post_thumbnail() ) : ?>
                                    <?php the_post_thumbnail( 'medium' ); ?>
                                <?php else : ?>
                                    <div class="epaper-placeholder-thumb" style="background:#eee; height:200px; display:flex; align-items:center; justify-content:center;">
                                        <span class="dashicons dashicons-pdf" style="font-size:48px; width:48px; height:48px; color:#666;"></span>
                                    </div>
                                <?php endif; ?>
                            </a>
                        </div>
                        <div class="epaper-content">
                            <h2><a href="<?php echo esc_url( get_permalink() ); ?>"><?php the_title(); ?></a></h2>
                            <?php if ( $sk_edition_date ) : ?>
                                <p class="epaper-meta-date" style="font-size: 13px; color: #666; margin: 5px 0;">
                                    <span class="dashicons dashicons-calendar-alt" style="font-size:16px; vertical-align:middle;"></span>
                                    <?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $sk_edition_date ) ) ); ?>
                                </p>
                            <?php endif; ?>
                            <?php if ( ! empty( $sk_stations ) && ! is_wp_error( $sk_stations ) ) : ?>
                                <p class="epaper-meta-station" style="font-size: 12px; margin: 3px 0;">
                                    <span class="dashicons dashicons-location-alt" style="font-size:14px; vertical-align:middle;"></span>
                                    <?php echo esc_html( implode( ', ', wp_list_pluck( $sk_stations, 'name' ) ) ); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endwhile; ?>
            <?php else : ?>
                <p><?php esc_html_e( 'No ePapers found.', 'sk-epaper-manager' ); ?></p>
            <?php endif; ?>
        </section>
    </main>
</div>

<?php
wp_reset_postdata();
get_footer();
?>
