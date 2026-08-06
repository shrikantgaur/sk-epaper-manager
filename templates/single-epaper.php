<?php
/**
 * Template for displaying single ePaper
 * Place this file in your theme root or in your plugin if overriding via `template_include`.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

$post_id = get_the_ID();

// Increment View Count
if ( is_singular( 'epaper' ) ) {
    $current_views = get_post_meta( $post_id, SK_EPAPER_PREFIX . 'views', true );
    $current_views = absint( $current_views ) + 1;
    update_post_meta( $post_id, SK_EPAPER_PREFIX . 'views', $current_views );
}

// Get stored attachment IDs (prefixed variable)
$sk_epaper_images = get_post_meta( $post_id, SK_EPAPER_PREFIX . 'images', true );
if ( ! is_array( $sk_epaper_images ) ) {
    $sk_epaper_images = array_filter( explode( ',', $sk_epaper_images ) );
}

// Get Edition Date
$sk_edition_date = get_post_meta( $post_id, SK_EPAPER_PREFIX . 'edition_date', true );
if ( $sk_edition_date ) {
    $sk_display_date = date_i18n( get_option( 'date_format' ), strtotime( $sk_edition_date ) );
} else {
    $sk_display_date = get_the_date();
}

// Get Stations / Editions Taxonomy terms
$sk_stations = get_the_terms( $post_id, 'epaper_station' );
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
                                    <?php echo esc_html( $sk_display_date ); ?> &mdash; <?php single_post_title(); ?>
                                    <?php if ( ! empty( $sk_stations ) && ! is_wp_error( $sk_stations ) ) : ?>
                                        <span class="epaper-station-badge">
                                            (<?php echo esc_html( implode( ', ', wp_list_pluck( $sk_stations, 'name' ) ) ); ?>)
                                        </span>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                        <div class="epaper-center">
                            <div>
                                <div>
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
                                        $file_url = wp_get_attachment_url( $sk_epaper_attachment_id );
                                        $is_image = wp_attachment_is( 'image', $sk_epaper_attachment_id );
                                        $img_tag  = wp_get_attachment_image(
                                            $sk_epaper_attachment_id,
                                            'full',
                                            false,
                                            array( 'alt' => esc_attr( get_the_title( $sk_epaper_attachment_id ) ) )
                                        );

                                        echo '<div class="image-slide" data-download-url="' . esc_url( $file_url ) . '" data-file-url="' . esc_url( $file_url ) . '" data-is-image="' . ( $is_image || ! empty( $img_tag ) ? '1' : '0' ) . '">';
                                            echo '<div class="zoom-container">';
                                                if ( ! empty( $img_tag ) ) {
                                                    echo $img_tag;
                                                } else {
                                                    echo '<iframe src="' . esc_url( $file_url ) . '" class="epaper-pdf-embed" width="100%" height="700px" style="border: none; min-height: 700px; width: 100%;"></iframe>';
                                                }
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
