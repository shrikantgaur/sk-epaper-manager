<?php
/*
Plugin Name: SK ePaper Manager
Text Domain: sk-epaper-manager
Description: SK ePaper Manager lets you upload, manage, and display beautiful ePapers on your WordPress site.
Version: 1.1.6
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

// Activation hook
function sk_epaper_activate() {
    sk_epaper_register_post_type();
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, SK_EPAPER_PREFIX . 'activate' );

// Deactivation hook
function sk_epaper_deactivate() {
    unregister_post_type( 'epaper' );
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, SK_EPAPER_PREFIX . 'deactivate' );

// Template Hierarchy
function sk_epaper_template_include( $template ) {
    if ( is_post_type_archive( 'epaper' ) ) {
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
        '1.0.0',
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
        '1.0.0',
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
        '1.0.0',
        'all'
    );
}
add_action( 'admin_enqueue_scripts', SK_EPAPER_PREFIX . 'enqueue_admin_styles' );

function sk_epaper_enqueue_admin_scripts() {
    wp_enqueue_script(
        SK_EPAPER_PREFIX . 'admin_script',
        plugin_dir_url( __FILE__ ) . 'assets/admin/epaper-admin.js',
        array( 'jquery' ),
        '1.0.0',
        true
    );
}
add_action( 'admin_enqueue_scripts', SK_EPAPER_PREFIX . 'enqueue_admin_scripts' );

/*
 * ePaper Images Meta Box
 */
function sk_epaper_add_image_meta_box() {
    add_meta_box(
        SK_EPAPER_PREFIX . 'image_meta_box',
        'ePaper Images',
        SK_EPAPER_PREFIX . 'render_image_meta_box',
        'epaper',
        'normal',
        'default'
    );
}
add_action( 'add_meta_boxes', SK_EPAPER_PREFIX . 'add_image_meta_box' );

function sk_epaper_render_image_meta_box( $post ) {
    $image_ids = get_post_meta( $post->ID, SK_EPAPER_PREFIX . 'images', true );

    if ( ! is_array( $image_ids ) ) {
        $image_ids = array_filter( explode( ',', $image_ids ) );
    }

    wp_nonce_field( basename( __FILE__ ), SK_EPAPER_PREFIX . 'images_nonce' );

    ?>
    <div>
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
                    <?php echo $img_html; ?>
                    <?php if ( ! $is_image ) : ?>
                        <span class="file-title"><?php echo esc_html( $title ); ?></span>
                    <?php endif; ?>
                    <span class="remove-image"><span class="text-remove-btn hidden">Remove</span></span>
                </li>
            <?php endforeach; ?>
        </ul>
        <input type="button" class="button sk-epaper-upload-button" value="<?php echo esc_attr__( 'Add ePaper', 'sk-epaper-manager' ); ?>">
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
    }
}
add_action( 'save_post', SK_EPAPER_PREFIX . 'save_image_meta' );
