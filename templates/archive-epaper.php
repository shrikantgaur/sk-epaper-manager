<?php
/**
 * Template for displaying all ePapers (Archive)
 * Place this file in your theme root or in your plugin if overriding via `template_include`.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

// Use prefixed variable names
$sk_epaper_args = array(
    'post_type'      => 'epaper',
    'posts_per_page' => -1,
);

$sk_epaper_query = new WP_Query( $sk_epaper_args );
?>

<div id="primary" class="content-area">
    <main id="main" class="site-main" role="main">
        <section class="epaper-listing">
            <?php if ( $sk_epaper_query->have_posts() ) : ?>
                <?php while ( $sk_epaper_query->have_posts() ) : $sk_epaper_query->the_post(); ?>
                    <article class="epaper-item">
                        <div class="epaper-thumbnail">
                            <a href="<?php echo esc_url( get_permalink() ); ?>">
                                <?php the_post_thumbnail( 'medium' ); ?>
                            </a>
                        </div>
                        <div class="epaper-content">
                            <h2><a href="<?php echo esc_url( get_permalink() ); ?>"><?php the_title(); ?></a></h2>
                            <?php the_excerpt(); ?>
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
