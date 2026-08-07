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
        'all_items'          => 'All ePapers',
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
        'hierarchical'       => false,
        'supports'           => array( 'title', 'editor', 'thumbnail' ),
    );

    register_post_type( 'epaper', $args );
}
add_action( 'init', SK_EPAPER_PREFIX . 'register_post_type' );

// Register Taxonomies (Editions & Languages)
function sk_epaper_register_taxonomies() {
    // 1. Editions Taxonomy (City/Regional)
    $edition_labels = array(
        'name'              => _x( 'Editions', 'taxonomy general name', 'sk-epaper-manager' ),
        'singular_name'     => _x( 'Edition', 'taxonomy singular name', 'sk-epaper-manager' ),
        'search_items'      => __( 'Search Editions', 'sk-epaper-manager' ),
        'all_items'         => __( 'All Editions', 'sk-epaper-manager' ),
        'parent_item'       => __( 'Parent Edition', 'sk-epaper-manager' ),
        'parent_item_colon' => __( 'Parent Edition:', 'sk-epaper-manager' ),
        'edit_item'         => __( 'Edit Edition', 'sk-epaper-manager' ),
        'update_item'       => __( 'Update Edition', 'sk-epaper-manager' ),
        'add_new_item'      => __( 'Add New Edition', 'sk-epaper-manager' ),
        'new_item_name'     => __( 'New Edition Name', 'sk-epaper-manager' ),
        'menu_name'         => __( 'Editions', 'sk-epaper-manager' ),
    );

    register_taxonomy( 'epaper_edition', array( 'epaper' ), array(
        'hierarchical'      => true,
        'labels'            => $edition_labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'epaper-edition', 'with_front' => false ),
        'show_in_rest'      => true,
    ) );

    // 2. Languages Taxonomy (English, Hindi, Punjabi, Urdu, etc.)
    $lang_labels = array(
        'name'              => _x( 'Languages', 'taxonomy general name', 'sk-epaper-manager' ),
        'singular_name'     => _x( 'Language', 'taxonomy singular name', 'sk-epaper-manager' ),
        'search_items'      => __( 'Search Languages', 'sk-epaper-manager' ),
        'all_items'         => __( 'All Languages', 'sk-epaper-manager' ),
        'edit_item'         => __( 'Edit Language', 'sk-epaper-manager' ),
        'update_item'       => __( 'Update Language', 'sk-epaper-manager' ),
        'add_new_item'      => __( 'Add New Language', 'sk-epaper-manager' ),
        'new_item_name'     => __( 'New Language Name', 'sk-epaper-manager' ),
        'menu_name'         => __( 'Languages', 'sk-epaper-manager' ),
    );

    register_taxonomy( 'epaper_language', array( 'epaper' ), array(
        'hierarchical'      => true,
        'labels'            => $lang_labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'epaper-language', 'with_front' => false ),
        'show_in_rest'      => true,
    ) );
}
add_action( 'init', SK_EPAPER_PREFIX . 'register_taxonomies', 0 );

// Flush rewrite rules check
function sk_epaper_check_flush_rules() {
    if ( get_option( 'sk_epaper_flush_rules_v120' ) !== '1' ) {
        sk_epaper_register_post_type();
        sk_epaper_register_taxonomies();
        flush_rewrite_rules();
        update_option( 'sk_epaper_flush_rules_v120', '1' );
    }
}
add_action( 'admin_init', SK_EPAPER_PREFIX . 'check_flush_rules' );

// Activation & Deactivation Hooks
function sk_epaper_activate() {
    sk_epaper_register_post_type();
    sk_epaper_register_taxonomies();
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, SK_EPAPER_PREFIX . 'activate' );

function sk_epaper_deactivate() {
    unregister_post_type( 'epaper' );
    unregister_taxonomy( 'epaper_edition' );
    unregister_taxonomy( 'epaper_language' );
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, SK_EPAPER_PREFIX . 'deactivate' );

// Template Hierarchy
function sk_epaper_template_include( $template ) {
    if ( is_post_type_archive( 'epaper' ) || is_tax( 'epaper_edition' ) || is_tax( 'epaper_language' ) ) {
        $template = plugin_dir_path( __FILE__ ) . 'templates/archive-epaper.php';
    } elseif ( is_singular( 'epaper' ) ) {
        $template = plugin_dir_path( __FILE__ ) . 'templates/single-epaper.php';
    }
    return $template;
}
add_filter( 'template_include', SK_EPAPER_PREFIX . 'template_include' );

/*
 * Admin Sub-Menu Pages
 */
function sk_epaper_admin_menu_pages() {
    add_submenu_page(
        'edit.php?post_type=epaper',
        __( 'ePaper Analytics', 'sk-epaper-manager' ),
        __( 'Analytics & Stats', 'sk-epaper-manager' ),
        'manage_options',
        'sk-epaper-analytics',
        'sk_epaper_render_analytics_page'
    );

    add_submenu_page(
        'edit.php?post_type=epaper',
        __( 'ePaper Shortcodes', 'sk-epaper-manager' ),
        __( 'Shortcodes & Helper', 'sk-epaper-manager' ),
        'manage_options',
        'sk-epaper-shortcodes',
        'sk_epaper_render_shortcodes_page'
    );

    add_submenu_page(
        'edit.php?post_type=epaper',
        __( 'ePaper Settings', 'sk-epaper-manager' ),
        __( 'Settings', 'sk-epaper-manager' ),
        'manage_options',
        'sk-epaper-settings',
        'sk_epaper_render_settings_page'
    );
}
add_action( 'admin_menu', SK_EPAPER_PREFIX . 'admin_menu_pages' );

function sk_epaper_render_analytics_page() {
    include plugin_dir_path( __FILE__ ) . 'templates/admin/analytics-page.php';
}

function sk_epaper_render_shortcodes_page() {
    include plugin_dir_path( __FILE__ ) . 'templates/admin/shortcodes-page.php';
}

function sk_epaper_render_settings_page() {
    include plugin_dir_path( __FILE__ ) . 'templates/admin/settings-page.php';
}

/*
 * Frontend CSS and JS with Dynamic Color Theme Injection (Default Primary Color: #000000)
 */
function sk_epaper_enqueue_styles() {
    wp_enqueue_style( 'dashicons' );
    wp_enqueue_style(
        SK_EPAPER_PREFIX . 'style',
        plugin_dir_url( __FILE__ ) . 'assets/epaper-style.css',
        array( 'dashicons' ),
        '1.2.0',
        'all'
    );

    $header_bg     = get_option( 'sk_epaper_header_bg', '#000000' );
    $primary_color = get_option( 'sk_epaper_primary_color', '#000000' );
    
    $custom_css = "";
    if ( $header_bg ) {
        $custom_css .= ".epaper-top-header .epaper-row { background: " . esc_attr( $header_bg ) . " !important; }\n";
    }
    if ( $primary_color ) {
        $custom_css .= "
            .epaper-hover-read-btn, 
            .pill-edition, 
            .sk-control-button:hover, 
            .arrow:hover { 
                background: " . esc_attr( $primary_color ) . " !important; 
                color: #ffffff !important; 
                border-color: " . esc_attr( $primary_color ) . " !important; 
            }
            .epaper-card-item:hover {
                border-color: " . esc_attr( $primary_color ) . " !important;
            }
            .epaper-card-cta-btn {
                background: " . esc_attr( $primary_color ) . " !important;
                color: #ffffff !important;
                border-color: " . esc_attr( $primary_color ) . " !important;
            }
            .epaper-card-cta-btn:hover,
            .epaper-card-item:hover .epaper-card-cta-btn {
                background: #000000 !important;
                color: #ffffff !important;
                border-color: #000000 !important;
                box-shadow: 0 6px 20px rgba(0, 0, 0, 0.35) !important;
            }
            .epaper-card-date .dashicons,
            .epaper-archive-hero-header h1.page-title .dashicons,
            .epaper-count-badge .dashicons,
            .epaper-card-title a:hover {
                color: " . esc_attr( $primary_color ) . " !important;
            }
        ";
    }
    if ( ! empty( $custom_css ) ) {
        wp_add_inline_style( SK_EPAPER_PREFIX . 'style', $custom_css );
    }
}
add_action( 'wp_enqueue_scripts', SK_EPAPER_PREFIX . 'enqueue_styles' );

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

    wp_localize_script(
        SK_EPAPER_PREFIX . 'admin_script',
        'sk_epaper_admin_ajax',
        array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'sk_epaper_analytics_nonce' ),
        )
    );
}
add_action( 'admin_enqueue_scripts', SK_EPAPER_PREFIX . 'enqueue_admin_scripts' );

/*
 * AJAX Handler for Admin Analytics Filtering & Pagination without page reload
 */
function sk_epaper_ajax_filter_analytics() {
    check_ajax_referer( 'sk_epaper_analytics_nonce', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => __( 'Permission denied.', 'sk-epaper-manager' ) ) );
    }

    $paged           = isset( $_POST['paged'] ) ? max( 1, absint( $_POST['paged'] ) ) : 1;
    $filter_edition  = isset( $_POST['filter_edition'] ) ? sanitize_text_field( wp_unslash( $_POST['filter_edition'] ) ) : '';
    $filter_language = isset( $_POST['filter_language'] ) ? sanitize_text_field( wp_unslash( $_POST['filter_language'] ) ) : '';
    $filter_search   = isset( $_POST['filter_search'] ) ? sanitize_text_field( wp_unslash( $_POST['filter_search'] ) ) : '';

    $per_page = 15;
    $offset   = ( $paged - 1 ) * $per_page;

    $query_args = array(
        'post_type'      => 'epaper',
        'posts_per_page' => $per_page,
        'paged'          => $paged,
        'post_status'    => 'publish',
        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
        'meta_key'       => SK_EPAPER_PREFIX . 'views',
        'orderby'        => 'meta_value_num',
        'order'          => 'DESC',
        's'              => $filter_search,
    );

    $tax_query = array();
    if ( ! empty( $filter_edition ) ) {
        $tax_query[] = array(
            'taxonomy' => 'epaper_edition',
            'field'    => 'slug',
            'terms'    => $filter_edition,
        );
    }

    if ( ! empty( $filter_language ) ) {
        $tax_query[] = array(
            'taxonomy' => 'epaper_language',
            'field'    => 'slug',
            'terms'    => $filter_language,
        );
    }

    if ( count( $tax_query ) > 1 ) {
        $tax_query['relation'] = 'AND';
    }

    if ( ! empty( $tax_query ) ) {
        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
        $query_args['tax_query'] = $tax_query;
    }

    $analytics_query = new WP_Query( $query_args );
    $total_found     = $analytics_query->found_posts;
    $total_pages     = $analytics_query->max_num_pages;

    ob_start();
    if ( $analytics_query->have_posts() ) : ?>
        <table class="wp-list-table widefat fixed striped sk-analytics-table">
            <thead>
                <tr>
                    <th style="width: 70px; text-align: center;"><?php esc_html_e( 'Rank', 'sk-epaper-manager' ); ?></th>
                    <th style="width: 35%;"><?php esc_html_e( 'Edition Title', 'sk-epaper-manager' ); ?></th>
                    <th style="width: 20%;"><?php esc_html_e( 'City / Edition', 'sk-epaper-manager' ); ?></th>
                    <th style="width: 15%;"><?php esc_html_e( 'Language', 'sk-epaper-manager' ); ?></th>
                    <th style="width: 12%; text-align: center;"><?php esc_html_e( 'Pages', 'sk-epaper-manager' ); ?></th>
                    <th style="width: 15%; text-align: center;"><?php esc_html_e( 'Total Views', 'sk-epaper-manager' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $rank = $offset + 1;
                while ( $analytics_query->have_posts() ) :
                    $analytics_query->the_post();
                    $epaper_id = get_the_ID();
                    $views     = absint( get_post_meta( $epaper_id, SK_EPAPER_PREFIX . 'views', true ) );
                    $images    = get_post_meta( $epaper_id, SK_EPAPER_PREFIX . 'images', true );
                    if ( ! is_array( $images ) ) {
                        $images = array_filter( explode( ',', $images ) );
                    }
                    $pages_count = count( $images );

                    $editions_terms  = get_the_terms( $epaper_id, 'epaper_edition' );
                    $languages_terms = get_the_terms( $epaper_id, 'epaper_language' );
                    $edition_names   = ( ! empty( $editions_terms ) && ! is_wp_error( $editions_terms ) ) ? implode( ', ', wp_list_pluck( $editions_terms, 'name' ) ) : '—';
                    $language_names  = ( ! empty( $languages_terms ) && ! is_wp_error( $languages_terms ) ) ? implode( ', ', wp_list_pluck( $languages_terms, 'name' ) ) : '—';

                    $rank_badge = ( 1 === $rank ) ? 'rank-gold' : (( 2 === $rank ) ? 'rank-silver' : (( 3 === $rank ) ? 'rank-bronze' : 'rank-normal'));
                    ?>
                    <tr>
                        <td style="text-align: center;">
                            <span class="sk-rank-badge <?php echo esc_attr( $rank_badge ); ?>">#<?php echo esc_html( $rank ); ?></span>
                        </td>
                        <td>
                            <strong><a href="<?php echo esc_url( get_permalink() ); ?>" target="_blank" class="sk-edition-link"><?php the_title(); ?></a></strong>
                            <div class="row-actions">
                                <span class="edit"><a href="<?php echo esc_url( get_edit_post_link( $epaper_id ) ); ?>"><?php esc_html_e( 'Edit', 'sk-epaper-manager' ); ?></a> | </span>
                                <span class="view"><a href="<?php echo esc_url( get_permalink() ); ?>" target="_blank"><?php esc_html_e( 'View', 'sk-epaper-manager' ); ?></a></span>
                            </div>
                        </td>
                        <td><span class="sk-meta-tag"><span class="dashicons dashicons-location"></span> <?php echo esc_html( $edition_names ); ?></span></td>
                        <td><span class="sk-meta-tag"><span class="dashicons dashicons-translation"></span> <?php echo esc_html( $language_names ); ?></span></td>
                        <td style="text-align: center;"><span class="badge-pill"><?php echo esc_html( $pages_count ); ?> Pages</span></td>
                        <td style="text-align: center;"><strong class="sk-views-count"><?php echo esc_html( number_format( $views ) ); ?></strong></td>
                    </tr>
                    <?php
                    $rank++;
                endwhile;
                wp_reset_postdata();
                ?>
            </tbody>
        </table>

        <?php if ( $total_pages > 1 ) : ?>
            <div class="sk-pagination-wrapper" style="margin-top: 20px; display: flex; justify-content: flex-end;">
                <div class="tablenav-pages">
                    <?php
                    $page_links = paginate_links( array(
                        'base'      => add_query_arg( 'paged', '%#%' ),
                        'format'    => '',
                        'prev_text' => __( '&laquo; Previous', 'sk-epaper-manager' ),
                        'next_text' => __( 'Next &raquo;', 'sk-epaper-manager' ),
                        'total'     => $total_pages,
                        'current'   => $paged,
                        'type'      => 'plain',
                    ) );
                    echo wp_kses_post( $page_links );
                    ?>
                </div>
            </div>
        <?php endif; ?>
    <?php else : ?>
        <div class="sk-empty-analytics">
            <span class="dashicons dashicons-info" style="font-size: 36px; width: 36px; height: 36px; color: #94a3b8;"></span>
            <p><?php esc_html_e( 'No matching analytics data found. Try adjusting your filters.', 'sk-epaper-manager' ); ?></p>
        </div>
    <?php endif;

    $content_html = ob_get_clean();

    /* translators: %d: Total number of ePaper editions */
    $total_html = sprintf( __( 'Total: %d Editions', 'sk-epaper-manager' ), $total_found );

    wp_send_json_success( array(
        'html'  => $content_html,
        'total' => esc_html( $total_html ),
    ) );
}
add_action( 'wp_ajax_sk_epaper_filter_analytics', 'sk_epaper_ajax_filter_analytics' );

/*
 * ePaper Details & Pages Meta Box
 */
function sk_epaper_add_image_meta_box() {
    add_meta_box(
        SK_EPAPER_PREFIX . 'image_meta_box',
        __( 'ePaper Details & Pages', 'sk-epaper-manager' ),
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

    $total_count = count( $image_ids );
    /* translators: %d: Total number of pages */
    $total_pages_label = sprintf( __( 'Total Pages: %d', 'sk-epaper-manager' ), $total_count );
    ?>
    <div class="sk-epaper-admin-meta-container">
        <div class="sk-epaper-meta-header-row">
            <div class="sk-meta-header-field">
                <label for="sk_epaper_edition_date">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <?php esc_html_e( 'Publication Date:', 'sk-epaper-manager' ); ?>
                </label>
                <input type="date" name="sk_epaper_edition_date" id="sk_epaper_edition_date" value="<?php echo esc_attr( $edition_date ); ?>" class="sk-date-picker-input">
            </div>

            <div class="sk-meta-header-stats">
                <span class="sk-page-counter-badge">
                    <span class="dashicons dashicons-images-alt2"></span>
                    <span class="count-text"><?php echo esc_html( $total_pages_label ); ?></span>
                </span>
            </div>
        </div>

        <div class="sk-pages-section-bar">
            <span class="section-title">
                <span class="dashicons dashicons-move"></span>
                <?php esc_html_e( 'ePaper Pages (Drag & drop to reorder):', 'sk-epaper-manager' ); ?>
            </span>
        </div>

        <ul class="sk-epaper-image-list">
            <?php
            $page_index = 1;
            foreach ( $image_ids as $id ) :
                $id = absint( $id );
                $is_image = wp_attachment_is( 'image', $id );
                $img_html = wp_get_attachment_image( $id, 'medium', true );
                $title    = get_the_title( $id );
                if ( ! $img_html ) {
                    $file_url = wp_get_attachment_url( $id );
                    $filename = wp_basename( $file_url );
                    $img_html = '<span class="dashicons dashicons-pdf"></span><span class="filename">' . esc_html( $filename ) . '</span>';
                }
                /* translators: %d: Page number */
                $page_pill_label = sprintf( __( 'Page %d', 'sk-epaper-manager' ), $page_index );
                ?>
                <li data-id="<?php echo esc_attr( $id ); ?>" class="<?php echo $is_image ? 'is-image' : 'is-file'; ?>">
                    <span class="drag-handle" title="<?php esc_attr_e( 'Drag to reorder page', 'sk-epaper-manager' ); ?>">
                        <span class="dashicons dashicons-menu"></span>
                    </span>
                    <span class="sk-page-number-pill"><?php echo esc_html( $page_pill_label ); ?></span>

                    <div class="sk-card-preview-container">
                        <?php echo wp_kses_post( $img_html ); ?>
                    </div>

                    <?php if ( ! $is_image ) : ?>
                        <span class="file-title"><?php echo esc_html( $title ); ?></span>
                    <?php endif; ?>

                    <span class="remove-image" title="<?php esc_attr_e( 'Remove Page', 'sk-epaper-manager' ); ?>">
                        <span class="text-remove-btn hidden">Remove</span>
                    </span>
                </li>
                <?php
                $page_index++;
            endforeach;
            ?>
        </ul>

        <div class="sk-upload-actions-row">
            <button type="button" class="button button-primary sk-epaper-upload-button">
                <span class="dashicons dashicons-plus-alt2"></span>
                <span><?php echo esc_html__( 'Add ePaper Pages', 'sk-epaper-manager' ); ?></span>
            </button>
            <span class="sk-upload-helper-text">
                <span class="dashicons dashicons-info"></span>
                <?php esc_html_e( 'Allowed Formats:', 'sk-epaper-manager' ); ?> <strong>JPG, PNG, WEBP, GIF, PDF</strong>
            </span>
            <input type="hidden" name="<?php echo esc_attr( SK_EPAPER_PREFIX . 'images' ); ?>" id="<?php echo esc_attr( SK_EPAPER_PREFIX . 'images' ); ?>" value="<?php echo esc_attr( implode( ',', $image_ids ) ); ?>">
        </div>
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
            $new_columns['taxonomy-epaper_edition']  = __( 'Edition', 'sk-epaper-manager' );
            $new_columns['taxonomy-epaper_language'] = __( 'Language', 'sk-epaper-manager' );
            $new_columns['epaper_date']               = __( 'Publication Date', 'sk-epaper-manager' );
            $new_columns['epaper_pages']              = __( 'Pages', 'sk-epaper-manager' );
            $new_columns['epaper_views']              = __( 'Views', 'sk-epaper-manager' );
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
            echo '<strong>' . esc_html( number_format( absint( $views ) ) ) . '</strong>';
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

    wp_enqueue_style( 'dashicons' );
    wp_enqueue_style( SK_EPAPER_PREFIX . 'style' );
    wp_enqueue_script( SK_EPAPER_PREFIX . 'script' );

    $enable_download = get_option( 'sk_epaper_enable_download', 1 );
    $enable_print    = get_option( 'sk_epaper_enable_print', 1 );
    $enable_zoom     = get_option( 'sk_epaper_enable_zoom', 1 );

    ob_start();
    ?>
    <div class="epaper-wrapper wrapper">
        <div class="epaper-top-header">
            <div class="epaper-row">
                <div class="epaper-col">
                    <div class="epaper-left">
                        <span class="current-date">ePaper: <?php echo esc_html( get_the_title( $post_id ) ); ?></span>
                    </div>
                    <div class="epaper-center">
                        <div class="epaper-controls-group">
                            <?php if ( $enable_zoom ) : ?>
                                <button id="zoom-in" class="sk-control-button" title="<?php echo esc_attr__( 'Zoom In', 'sk-epaper-manager' ); ?>">
                                    <span class="dashicons dashicons-plus-alt2"></span>
                                </button>
                                <button id="zoom-out" class="sk-control-button" title="<?php echo esc_attr__( 'Zoom Out', 'sk-epaper-manager' ); ?>">
                                    <span class="dashicons dashicons-minus"></span>
                                </button>
                            <?php endif; ?>
                            <?php if ( $enable_print ) : ?>
                                <button id="print-btn" class="sk-control-button" title="<?php echo esc_attr__( 'Print Page', 'sk-epaper-manager' ); ?>">
                                    <span class="dashicons dashicons-printer"></span>
                                </button>
                                <button id="print-all-btn" class="sk-control-button" title="<?php echo esc_attr__( 'Print All Pages', 'sk-epaper-manager' ); ?>">
                                    <span class="dashicons dashicons-admin-page"></span>
                                </button>
                            <?php endif; ?>
                            <?php if ( $enable_download ) : ?>
                                <a id="download-btn" href="#" download class="sk-control-button" title="<?php echo esc_attr__( 'Download Page', 'sk-epaper-manager' ); ?>">
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
                                                echo wp_kses_post( $img_tag );
                                            } else {
                                                echo '<iframe src="' . esc_url( $file_url ) . '" class="epaper-pdf-embed" width="100%" height="800px" style="border: none; min-height: 800px; width: 100%;"></iframe>';
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
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'sk_epaper', SK_EPAPER_PREFIX . 'shortcode_handler' );

function sk_epaper_archive_shortcode_handler( $atts ) {
    $atts = shortcode_atts( array(
        'count'    => 6,
        'edition'  => '',
        'language' => '',
    ), $atts, 'sk_epaper_archive' );

    $args = array(
        'post_type'      => 'epaper',
        'posts_per_page' => intval( $atts['count'] ),
    );

    $tax_query = array();

    if ( ! empty( $atts['edition'] ) ) {
        $tax_query[] = array(
            'taxonomy' => 'epaper_edition',
            'field'    => 'slug',
            'terms'    => sanitize_text_field( $atts['edition'] ),
        );
    }

    if ( ! empty( $atts['language'] ) ) {
        $tax_query[] = array(
            'taxonomy' => 'epaper_language',
            'field'    => 'slug',
            'terms'    => sanitize_text_field( $atts['language'] ),
        );
    }

    if ( count( $tax_query ) > 1 ) {
        $tax_query['relation'] = 'AND';
    }

    if ( ! empty( $tax_query ) ) {
        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
        $args['tax_query'] = $tax_query;
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
