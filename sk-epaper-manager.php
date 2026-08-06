<?php
/*
Plugin Name: SK ePaper Manager
Text Domain: sk-epaper-manager
Description: SK ePaper Manager lets you upload, manage, and display beautiful ePapers on your WordPress site.
Version: 1.2.0
Author: Shri Kant Gaur
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Requires at least: 5.0
Requires PHP: 7.4
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'SK_EPAPER_PREFIX' ) ) {
    define( 'SK_EPAPER_PREFIX', 'sk_epaper_' );
}

// Register ePaper Custom Post Type
function sk_epaper_register_post_type() {
    $labels = array(
        'name'               => 'ePapers',
        'singular_name'      => 'ePaper',
        'menu_name'          => 'ePapers',
        'add_new'            => 'Add New',
        'add_new_item'       => 'Add New ePaper',
        'edit_item'          => 'Edit ePaper',
        'new_item'           => 'New ePaper',
        'view_item'          => 'View ePaper',
        'search_items'       => 'Search ePapers',
        'not_found'          => 'No ePapers found',
        'not_found_in_trash' => 'No ePapers found in Trash',
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'has_archive'        => true,
        'rewrite'            => array( 'slug' => 'epapers', 'with_front' => false ),
        'hierarchical'       => true,
        'supports'           => array( 'title', 'editor', 'thumbnail' ),
    );

    register_post_type( 'epaper', $args );
}
add_action( 'init', SK_EPAPER_PREFIX . 'register_post_type' );

// Register Custom Taxonomies (Stations / Editions)
function sk_epaper_register_taxonomies() {
    $labels = array(
        'name'              => _x( 'Editions / Stations', 'taxonomy general name', 'sk-epaper-manager' ),
        'singular_name'     => _x( 'Edition / Station', 'taxonomy singular name', 'sk-epaper-manager' ),
        'search_items'      => __( 'Search Stations', 'sk-epaper-manager' ),
        'all_items'         => __( 'All Stations', 'sk-epaper-manager' ),
        'parent_item'       => __( 'Parent Station', 'sk-epaper-manager' ),
        'parent_item_colon' => __( 'Parent Station:', 'sk-epaper-manager' ),
        'edit_item'         => __( 'Edit Station', 'sk-epaper-manager' ),
        'update_item'       => __( 'Update Station', 'sk-epaper-manager' ),
        'add_new_item'      => __( 'Add New Station', 'sk-epaper-manager' ),
        'new_item_name'     => __( 'New Station Name', 'sk-epaper-manager' ),
        'menu_name'         => __( 'Stations / Editions', 'sk-epaper-manager' ),
    );

    $args = array(
        'hierarchical'      => true,
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'epaper-station' ),
        'show_in_rest'      => true,
    );

    register_taxonomy( 'epaper_station', array( 'epaper' ), $args );
}
add_action( 'init', SK_EPAPER_PREFIX . 'register_taxonomies' );

// Activation hook
function sk_epaper_activate() {
    sk_epaper_register_post_type();
    sk_epaper_register_taxonomies();
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, SK_EPAPER_PREFIX . 'activate' );

// Deactivation hook
function sk_epaper_deactivate() {
    unregister_post_type( 'epaper' );
    unregister_taxonomy( 'epaper_station' );
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, SK_EPAPER_PREFIX . 'deactivate' );

// Template Hierarchy
function sk_epaper_template_include( $template ) {
    if ( is_post_type_archive( 'epaper' ) || is_tax( 'epaper_station' ) ) {
        $template = plugin_dir_path( __FILE__ ) . 'templates/archive-epaper.php';
    } elseif ( is_singular( 'epaper' ) ) {
        $template = plugin_dir_path( __FILE__ ) . 'templates/single-epaper.php';
    }
    return $template;
}
add_filter( 'template_include', SK_EPAPER_PREFIX . 'template_include' );

/*
 * Frontend CSS and JS
 */
function sk_epaper_enqueue_styles() {
    wp_enqueue_style(
        SK_EPAPER_PREFIX . 'style',
        plugin_dir_url( __FILE__ ) . 'assets/epaper-style.css',
        array(),
        '1.2.0',
        'all'
    );
}
add_action( 'wp_enqueue_scripts', SK_EPAPER_PREFIX . 'enqueue_styles' );

function sk_epaper_enqueue_dashicons() {
    wp_enqueue_style( 'dashicons' );
}
add_action( 'wp_enqueue_scripts', SK_EPAPER_PREFIX . 'enqueue_dashicons' );

function sk_epaper_enqueue_scripts() {
    wp_enqueue_script(
        SK_EPAPER_PREFIX . 'script',
        plugin_dir_url( __FILE__ ) . 'assets/epaper-script.js',
        array( 'jquery' ),
        '1.2.0',
        true
    );
}
add_action( 'wp_enqueue_scripts', SK_EPAPER_PREFIX . 'enqueue_scripts' );

/*
 * Admin CSS and JS
 */
function sk_epaper_enqueue_admin_styles() {
    wp_enqueue_style(
        SK_EPAPER_PREFIX . 'admin_style',
        plugin_dir_url( __FILE__ ) . 'assets/admin/epaper-admin.css',
        array(),
        '1.2.0',
        'all'
    );
}
add_action( 'admin_enqueue_scripts', SK_EPAPER_PREFIX . 'enqueue_admin_styles' );

function sk_epaper_enqueue_admin_scripts() {
    wp_enqueue_script( 'jquery-ui-sortable' );
    wp_enqueue_script(
        SK_EPAPER_PREFIX . 'admin_script',
        plugin_dir_url( __FILE__ ) . 'assets/admin/epaper-admin.js',
        array( 'jquery', 'jquery-ui-sortable' ),
        '1.2.0',
        true
    );
}
add_action( 'admin_enqueue_scripts', SK_EPAPER_PREFIX . 'enqueue_admin_scripts' );

/*
 * ePaper Images & Settings Meta Box
 */
function sk_epaper_add_image_meta_box() {
    add_meta_box(
        SK_EPAPER_PREFIX . 'image_meta_box',
        'ePaper Details & Pages',
        SK_EPAPER_PREFIX . 'render_image_meta_box',
        'epaper',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', SK_EPAPER_PREFIX . 'add_image_meta_box' );

function sk_epaper_render_image_meta_box( $post ) {
    $image_ids    = get_post_meta( $post->ID, SK_EPAPER_PREFIX . 'images', true );
    $edition_date = get_post_meta( $post->ID, SK_EPAPER_PREFIX . 'edition_date', true );

    if ( empty( $edition_date ) ) {
        $edition_date = get_the_date( 'Y-m-d', $post->ID );
    }

    if ( ! is_array( $image_ids ) ) {
        $image_ids = array_filter( explode( ',', $image_ids ) );
    }

    wp_nonce_field( basename( __FILE__ ), SK_EPAPER_PREFIX . 'images_nonce' );

    ?>
    <div class="sk-epaper-admin-meta-container">
        <div class="sk-epaper-meta-field" style="margin-bottom: 20px;">
            <label for="sk_epaper_edition_date" style="font-weight: 600; display: block; margin-bottom: 5px;">
                <?php esc_html_e( 'ePaper Publication Date:', 'sk-epaper-manager' ); ?>
            </label>
            <input type="date" name="sk_epaper_edition_date" id="sk_epaper_edition_date" value="<?php echo esc_attr( $edition_date ); ?>" class="regular-text">
            <p class="description"><?php esc_html_e( 'Select the date of this newspaper/magazine edition.', 'sk-epaper-manager' ); ?></p>
        </div>

        <hr style="margin: 20px 0; border: none; border-top: 1px solid #ddd;">

        <label style="font-weight: 600; display: block; margin-bottom: 10px;">
            <?php esc_html_e( 'ePaper Pages / Images (Drag and drop to reorder):', 'sk-epaper-manager' ); ?>
        </label>
        <ul class="sk-epaper-image-list">
            <?php foreach ( $image_ids as $id ) :
                $id = absint( $id );
                $is_image = wp_attachment_is( 'image', $id );
                $img_html = wp_get_attachment_image( $id, 'thumbnail', true );
                $title    = get_the_title( $id );
                if ( ! $img_html ) {
                    $file_url = wp_get_attachment_url( $id );
                    $filename = wp_basename( $file_url );
                    $img_html = '<span class="dashicons dashicons-pdf"></span><span class="filename">' . esc_html( $filename ) . '</span>';
                }
                ?>
                <li data-id="<?php echo esc_attr( $id ); ?>" class="<?php echo $is_image ? 'is-image' : 'is-file'; ?>">
                    <span class="drag-handle" title="<?php esc_attr_e( 'Drag to reorder', 'sk-epaper-manager' ); ?>"><span class="dashicons dashicons-menu"></span></span>
                    <?php echo $img_html; ?>
                    <?php if ( ! $is_image ) : ?>
                        <span class="file-title"><?php echo esc_html( $title ); ?></span>
                    <?php endif; ?>
                    <span class="remove-image"><span class="text-remove-btn hidden">Remove</span></span>
                </li>
            <?php endforeach; ?>
        </ul>
        <input type="button" class="button button-primary sk-epaper-upload-button" value="<?php echo esc_attr__( 'Add ePaper Pages', 'sk-epaper-manager' ); ?>">
        <input type="hidden" name="<?php echo esc_attr( SK_EPAPER_PREFIX . 'images' ); ?>" id="<?php echo esc_attr( SK_EPAPER_PREFIX . 'images' ); ?>" value="<?php echo esc_attr( implode( ',', $image_ids ) ); ?>">
    </div>
    <?php
}

function sk_epaper_save_image_meta( $post_id ) {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    if (
        isset( $_POST[ SK_EPAPER_PREFIX . 'images_nonce' ] ) &&
        wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ SK_EPAPER_PREFIX . 'images_nonce' ] ) ), basename( __FILE__ ) )
    ) {
        if ( isset( $_POST[ SK_EPAPER_PREFIX . 'images' ] ) ) {
            $raw_input = sanitize_text_field( wp_unslash( $_POST[ SK_EPAPER_PREFIX . 'images' ] ) );
            $image_ids = array_filter( array_map( 'absint', explode( ',', $raw_input ) ) );
            update_post_meta( $post_id, SK_EPAPER_PREFIX . 'images', $image_ids );
        }

        if ( isset( $_POST['sk_epaper_edition_date'] ) ) {
            $edition_date = sanitize_text_field( wp_unslash( $_POST['sk_epaper_edition_date'] ) );
            update_post_meta( $post_id, SK_EPAPER_PREFIX . 'edition_date', $edition_date );
        }
    }
}
add_action( 'save_post', SK_EPAPER_PREFIX . 'save_image_meta' );

/*
 * Custom Columns for Admin Post List Table
 */
function sk_epaper_set_custom_columns( $columns ) {
    $new_columns = array();
    foreach ( $columns as $key => $value ) {
        $new_columns[ $key ] = $value;
        if ( 'title' === $key ) {
            $new_columns['epaper_date']  = __( 'Edition Date', 'sk-epaper-manager' );
            $new_columns['epaper_pages'] = __( 'Pages', 'sk-epaper-manager' );
            $new_columns['epaper_views'] = __( 'Views', 'sk-epaper-manager' );
        }
    }
    return $new_columns;
}
add_filter( 'manage_epaper_posts_columns', SK_EPAPER_PREFIX . 'set_custom_columns' );

function sk_epaper_render_custom_columns( $column, $post_id ) {
    switch ( $column ) {
        case 'epaper_date':
            $date = get_post_meta( $post_id, SK_EPAPER_PREFIX . 'edition_date', true );
            echo $date ? esc_html( date_i18n( get_option( 'date_format' ), strtotime( $date ) ) ) : '—';
            break;
        case 'epaper_pages':
            $images = get_post_meta( $post_id, SK_EPAPER_PREFIX . 'images', true );
            if ( ! is_array( $images ) ) {
                $images = array_filter( explode( ',', $images ) );
            }
            echo esc_html( count( $images ) );
            break;
        case 'epaper_views':
            $views = get_post_meta( $post_id, SK_EPAPER_PREFIX . 'views', true );
            echo esc_html( absint( $views ) );
            break;
    }
}
add_action( 'manage_epaper_posts_custom_column', SK_EPAPER_PREFIX . 'render_custom_columns', 10, 2 );

/*
 * Shortcodes
 */
function sk_epaper_shortcode_handler( $atts ) {
    $atts = shortcode_atts( array(
        'id' => 0,
    ), $atts, 'sk_epaper' );

    $post_id = absint( $atts['id'] );
    if ( ! $post_id || 'epaper' !== get_post_type( $post_id ) ) {
        return '<p class="sk-epaper-error">' . esc_html__( 'Invalid ePaper ID.', 'sk-epaper-manager' ) . '</p>';
    }

    $sk_epaper_images = get_post_meta( $post_id, SK_EPAPER_PREFIX . 'images', true );
    if ( ! is_array( $sk_epaper_images ) ) {
        $sk_epaper_images = array_filter( explode( ',', $sk_epaper_images ) );
    }

    // Enqueue scripts & styles for shortcode rendering
    wp_enqueue_style( SK_EPAPER_PREFIX . 'style' );
    wp_enqueue_style( 'dashicons' );
    wp_enqueue_script( SK_EPAPER_PREFIX . 'script' );

    ob_start();
    ?>
    <div class="epaper-wrapper wrapper">
        <div class="epaper-top-header">
            <div class="epaper-row">
                <div class="epaper-col">
                    <div class="epaper-left">
                        <div class="date-box">
                            <span class="current-date"><?php echo esc_html( get_the_title( $post_id ) ); ?></span>
                        </div>
                    </div>
                    <div class="epaper-center">
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
                    <div class="epaper-right">
                        <div class="page-count-box">
                            <span class="page-count">
                                Page: <span class="current-page">1</span> of <span class="total-page"><?php echo esc_html( count( $sk_epaper_images ) ); ?></span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

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
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'sk_epaper', SK_EPAPER_PREFIX . 'shortcode_handler' );

function sk_epaper_archive_shortcode_handler( $atts ) {
    $atts = shortcode_atts( array(
        'count'   => 6,
        'station' => '',
    ), $atts, 'sk_epaper_archive' );

    $args = array(
        'post_type'      => 'epaper',
        'posts_per_page' => intval( $atts['count'] ),
    );

    if ( ! empty( $atts['station'] ) ) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'epaper_station',
                'field'    => 'slug',
                'terms'    => sanitize_text_field( $atts['station'] ),
            ),
        );
    }

    $query = new WP_Query( $args );
    wp_enqueue_style( SK_EPAPER_PREFIX . 'style' );

    ob_start();
    ?>
    <section class="epaper-listing">
        <?php if ( $query->have_posts() ) : ?>
            <?php while ( $query->have_posts() ) : $query->the_post(); ?>
                <article class="epaper-item">
                    <div class="epaper-thumbnail">
                        <a href="<?php echo esc_url( get_permalink() ); ?>">
                            <?php the_post_thumbnail( 'medium' ); ?>
                        </a>
                    </div>
                    <div class="epaper-content">
                        <h2><a href="<?php echo esc_url( get_permalink() ); ?>"><?php the_title(); ?></a></h2>
                        <?php
                        $edition_date = get_post_meta( get_the_ID(), SK_EPAPER_PREFIX . 'edition_date', true );
                        if ( $edition_date ) {
                            echo '<p class="epaper-meta-date"><span class="dashicons dashicons-calendar-alt"></span> ' . esc_html( date_i18n( get_option( 'date_format' ), strtotime( $edition_date ) ) ) . '</p>';
                        }
                        ?>
                    </div>
                </article>
            <?php endwhile; ?>
        <?php else : ?>
            <p><?php esc_html_e( 'No ePapers found.', 'sk-epaper-manager' ); ?></p>
        <?php endif; ?>
    </section>
    <?php
    wp_reset_postdata();
    return ob_get_clean();
}
add_shortcode( 'sk_epaper_archive', SK_EPAPER_PREFIX . 'archive_shortcode_handler' );
