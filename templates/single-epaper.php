<?php
/**
 * Template for displaying single ePaper
 * Place this file in your theme root or in your plugin if overriding via `template_include`.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

// Get stored attachment IDs (prefixed variable)
$sk_epaper_images = get_post_meta( get_the_ID(), SK_EPAPER_PREFIX . 'images', true );
if ( ! is_array( $sk_epaper_images ) ) {
    $sk_epaper_images = array_filter( explode( ',', $sk_epaper_images ) );
}
?>

<div id="primary" class="content-area">
    <main id="main" class="site-main" role="main">
        <div class="epaper-wrapper wrapper">
            <!-- Epaper Top -->
            <div class="epaper-top-header">
                <div class="epaper-row">
                    <div class="epaper-col">
                        <div class="epaper-left">
                            <div class="date-box">
                                <span class="current-date">
                                    <?php single_post_title( 'ePaper: ' ); ?>
                                </span>
                            </div>
                        </div>
                        <div class="epaper-center">
                            <div>
                                <div>
                                    <!-- Updated Dashicons -->
                                    <button id="zoom-in" class="zoom-btn sk-control-button" title="<?php echo esc_attr__( 'Zoom In', 'sk-epaper-manager' ); ?>">
                                        <span class="dashicons dashicons-plus"></span>
                                    </button>
                                    <button id="zoom-out" class="zoom-btn sk-control-button" title="<?php echo esc_attr__( 'Zoom Out', 'sk-epaper-manager' ); ?>">
                                        <span class="dashicons dashicons-minus"></span>
                                    </button>
                                    <button id="print-btn" class="sk-control-button" title="<?php echo esc_attr__( 'Print', 'sk-epaper-manager' ); ?>">
                                        <span class="dashicons dashicons-media-default"></span>
                                    </button>
                                    <button id="print-all-btn" title="<?php echo esc_attr__( 'Print All Pages', 'sk-epaper-manager' ); ?>" class="sk-control-button">
                                        <span class="dashicons dashicons-media-default"></span>
                                    </button>
                                    <a id="download-btn" href="#" download class="sk-control-button" title="<?php echo esc_attr__( 'Download', 'sk-epaper-manager' ); ?>">
                                        <span class="dashicons dashicons-download"></span>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="epaper-right">
                            <div class="page-count-box">
                                <span class="page-count">
                                    Page:
                                    <span class="current-page">1</span> of
                                    <span class="total-page">
                                        <?php echo esc_html( count( $sk_epaper_images ) ); ?>
                                    </span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Epaper Top End -->

            <!-- Epaper Content -->
            <div class="epaper-content">
                <div class="epaper-row">
                    <div class="epaper-col">
                        <div class="slider-container">
                            <div class="arrow left-arrow" id="prev" aria-hidden="true">
                                <span class="dashicons dashicons-arrow-left"></span>
                            </div>
                            <div class="image-slider">
                                <?php
                                if ( ! empty( $sk_epaper_images ) ) {
                                    foreach ( $sk_epaper_images as $sk_epaper_attachment_id ) {
                                        $sk_epaper_attachment_id = absint( $sk_epaper_attachment_id );

                                        echo '<div class="image-slide">';
                                            echo '<div class="zoom-container">';
                                                echo wp_get_attachment_image(
                                                    $sk_epaper_attachment_id,
                                                    'full',
                                                    false,
                                                    array( 'alt' => esc_attr( get_the_title( $sk_epaper_attachment_id ) ) )
                                                );
                                            echo '</div>';
                                        echo '</div>';
                                    }
                                }
                                ?>
                            </div>
                            <div class="arrow right-arrow" id="next" aria-hidden="true">
                                <span class="dashicons dashicons-arrow-right"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Epaper Content End -->
        </div>
    </main>
</div>

<?php get_sidebar(); ?>
<?php get_footer(); ?>
