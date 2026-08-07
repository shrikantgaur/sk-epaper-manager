<?php
/**
 * Template for displaying single ePaper - Reader with 10px Left & Right Side Spacing
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

// Enqueue Dashicons explicitly for frontend single template
wp_enqueue_style( 'dashicons' );

get_header();

$sk_post_id = get_the_ID();

// Increment View Count
if ( is_singular( 'epaper' ) ) {
    $sk_current_views = get_post_meta( $sk_post_id, SK_EPAPER_PREFIX . 'views', true );
    $sk_current_views = absint( $sk_current_views ) + 1;
    update_post_meta( $sk_post_id, SK_EPAPER_PREFIX . 'views', $sk_current_views );
}

// Get stored attachment IDs
$sk_epaper_images = get_post_meta( $sk_post_id, SK_EPAPER_PREFIX . 'images', true );
if ( ! is_array( $sk_epaper_images ) ) {
    $sk_epaper_images = array_filter( explode( ',', $sk_epaper_images ) );
}

// Settings
$sk_enable_download = get_option( 'sk_epaper_enable_download', 1 );
$sk_enable_print    = get_option( 'sk_epaper_enable_print', 1 );
$sk_enable_zoom     = get_option( 'sk_epaper_enable_zoom', 1 );
?>

<div id="primary" class="content-area epaper-single-area" style="width: 100%; padding: 0 10px; box-sizing: border-box;">
    <main id="main" class="site-main" role="main">
        <div class="epaper-wrapper wrapper">
            <!-- Epaper Top Header -->
            <div class="epaper-top-header">
                <div class="epaper-row">
                    <div class="epaper-col">
                        <div class="epaper-left">
                            <span class="current-date">ePaper: <?php single_post_title(); ?></span>
                        </div>
                        <div class="epaper-center">
                            <div class="epaper-controls-group">
                                <?php if ( $sk_enable_zoom ) : ?>
                                    <button id="zoom-in" class="sk-control-button" title="<?php esc_attr_e( 'Zoom In', 'sk-epaper-manager' ); ?>">
                                        <span class="dashicons dashicons-plus-alt2"></span>
                                    </button>
                                    <button id="zoom-out" class="sk-control-button" title="<?php esc_attr_e( 'Zoom Out', 'sk-epaper-manager' ); ?>">
                                        <span class="dashicons dashicons-minus"></span>
                                    </button>
                                <?php endif; ?>
                                <?php if ( $sk_enable_print ) : ?>
                                    <button id="print-btn" class="sk-control-button" title="<?php esc_attr_e( 'Print Page', 'sk-epaper-manager' ); ?>">
                                        <span class="dashicons dashicons-printer"></span>
                                    </button>
                                    <button id="print-all-btn" class="sk-control-button" title="<?php esc_attr_e( 'Print All Pages', 'sk-epaper-manager' ); ?>">
                                        <span class="dashicons dashicons-admin-page"></span>
                                    </button>
                                <?php endif; ?>
                                <?php if ( $sk_enable_download ) : ?>
                                    <a id="download-btn" href="#" download class="sk-control-button" title="<?php esc_attr_e( 'Download Page', 'sk-epaper-manager' ); ?>">
                                        <span class="dashicons dashicons-download"></span>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="epaper-right">
                            <span class="page-count">
                                Page: <span class="current-page">1</span> of <span class="total-page"><?php echo esc_html( count( $sk_epaper_images ) ); ?></span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Epaper Top Header End -->

            <!-- Epaper Content Slider -->
            <div class="epaper-content">
                <div class="epaper-row">
                    <div class="epaper-col">
                        <div class="slider-container">
                            <div class="arrow left-arrow" id="prev" aria-hidden="true">
                                <span class="dashicons dashicons-arrow-left-alt2"></span>
                            </div>
                            <div class="image-slider">
                                <?php
                                if ( ! empty( $sk_epaper_images ) ) {
                                    foreach ( $sk_epaper_images as $sk_epaper_attachment_id ) {
                                        $sk_epaper_attachment_id = absint( $sk_epaper_attachment_id );
                                        $sk_file_url = wp_get_attachment_url( $sk_epaper_attachment_id );
                                        $sk_is_image = wp_attachment_is( 'image', $sk_epaper_attachment_id );
                                        $sk_img_tag  = wp_get_attachment_image(
                                            $sk_epaper_attachment_id,
                                            'full',
                                            false,
                                            array( 'alt' => esc_attr( get_the_title( $sk_epaper_attachment_id ) ) )
                                        );

                                        echo '<div class="image-slide" data-download-url="' . esc_url( $sk_file_url ) . '" data-file-url="' . esc_url( $sk_file_url ) . '" data-is-image="' . ( $sk_is_image || ! empty( $sk_img_tag ) ? '1' : '0' ) . '">';
                                            echo '<div class="zoom-container">';
                                                if ( ! empty( $sk_img_tag ) ) {
                                                    echo wp_kses_post( $sk_img_tag );
                                                } else {
                                                    echo '<iframe src="' . esc_url( $sk_file_url ) . '" class="epaper-pdf-embed" width="100%" height="800px" style="border: none; min-height: 800px; width: 100%;"></iframe>';
                                                }
                                            echo '</div>';
                                        echo '</div>';
                                    }
                                }
                                ?>
                            </div>
                            <div class="arrow right-arrow" id="next" aria-hidden="true">
                                <span class="dashicons dashicons-arrow-right-alt2"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Epaper Content Slider End -->
        </div>
    </main>
</div>

<?php get_footer(); ?>