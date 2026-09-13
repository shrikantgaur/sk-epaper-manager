<?php
/**
 * Single ePaper reader.
 *
 * Themes may override this file by copying it to:
 *   yourtheme/sk-epaper/single-epaper.php
 *
 * The viewer itself is rendered by SK_EPaper_Renderer so the shortcode and this
 * template can never drift apart.
 *
 * @package SK_ePaper_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

wp_enqueue_style( 'dashicons' );

get_header();

$sk_epaper_post_id = get_the_ID();
?>

<div id="primary" class="content-area epaper-single-area">
    <main id="main" class="site-main" role="main">
        <?php
        // View counting happens through the REST beacon in assets/epaper-script.js
        // (with a noscript pixel fallback), so it keeps working behind page caching.
        echo SK_EPaper_Renderer::render_viewer( $sk_epaper_post_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo SK_EPaper_Renderer::render_related( $sk_epaper_post_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        ?>
    </main>
</div>

<?php get_footer(); ?>
