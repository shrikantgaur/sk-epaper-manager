<?php
/**
 * Admin screens: menus, meta box, list table columns and analytics.
 *
 * @package SK_ePaper_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Everything that only runs in wp-admin.
 */
class SK_EPaper_Admin {

    /**
     * Register the ePaper sub-menu pages.
     *
     * @return void
     */
    public static function admin_menu_pages() {
        add_submenu_page(
            'edit.php?post_type=epaper',
            __( 'Getting Started', 'sk-epaper-manager' ),
            __( 'Getting Started', 'sk-epaper-manager' ),
            'manage_options',
            'sk-epaper-guide',
            'sk_epaper_render_guide_page'
        );

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

    /**
     * Render an admin template.
     *
     * @param string $file Template filename inside templates/admin/.
     * @return void
     */
    public static function render_admin_page( $file ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to access this page.', 'sk-epaper-manager' ) );
        }

        include SK_EPAPER_PATH . 'templates/admin/' . $file;
    }

    /**
     * Admin stylesheet.
     *
     * @return void
     */
    public static function enqueue_admin_styles() {
        wp_enqueue_style(
            SK_EPAPER_PREFIX . 'admin_style',
            SK_EPAPER_URL . 'assets/admin/epaper-admin.css',
            array(),
            SK_EPAPER_VERSION,
            'all'
        );
    }

    /**
     * Admin scripts plus the AJAX handshake data.
     *
     * @return void
     */
    public static function enqueue_admin_scripts() {
        wp_enqueue_script( 'jquery-ui-sortable' );
        wp_enqueue_script(
            SK_EPAPER_PREFIX . 'admin_script',
            SK_EPAPER_URL . 'assets/admin/epaper-admin.js',
            array( 'jquery', 'jquery-ui-sortable' ),
            SK_EPAPER_VERSION,
            true
        );

        wp_localize_script(
            SK_EPAPER_PREFIX . 'admin_script',
            'sk_epaper_admin_ajax',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'sk_epaper_analytics_nonce' ),
                'i18n'     => array(
                    /* translators: %d: Page number */
                    'page'          => __( 'Page %d', 'sk-epaper-manager' ),
                    /* translators: %d: Total number of pages */
                    'totalPages'    => __( 'Total Pages: %d', 'sk-epaper-manager' ),
                    'mediaTitle'    => __( 'Choose Images or PDF Files for ePaper Pages', 'sk-epaper-manager' ),
                    'mediaButton'   => __( 'Add to ePaper', 'sk-epaper-manager' ),
                    'dragToReorder' => __( 'Drag to reorder page', 'sk-epaper-manager' ),
                    'removePage'    => __( 'Remove Page', 'sk-epaper-manager' ),
                    'remove'        => __( 'Remove', 'sk-epaper-manager' ),
                    'copied'        => __( 'Copied!', 'sk-epaper-manager' ),
                    'copiedToast'   => __( 'Shortcode copied to clipboard!', 'sk-epaper-manager' ),
                    /* translators: %s: Comma separated list of rejected file names */
                    'invalidFiles'  => __( 'Invalid file format rejected: %s', 'sk-epaper-manager' ),
                    'allowedFormats' => __( 'Please select only Image (JPG, PNG, WEBP) or PDF files for ePaper pages.', 'sk-epaper-manager' ),
                ),
            )
        );
    }

    /**
     * Build the WP_Query arguments used by the Analytics table.
     *
     * Shared by the admin page and the AJAX filter handler so the two stay in
     * sync.
     *
     * The meta_query below deliberately uses an EXISTS / NOT EXISTS pair rather
     * than the simpler meta_key + meta_value_num ordering: with meta_key set,
     * WordPress INNER JOINs postmeta and silently drops every edition that has
     * never been viewed, so freshly published ePapers never showed up at all.
     *
     * @param array $args {
     *     @type int    $paged    Current page number.
     *     @type int    $per_page Results per page.
     *     @type string $edition  Edition term slug filter.
     *     @type string $language Language term slug filter.
     *     @type string $search   Search term.
     *     @type string $from     Earliest edition date, Y-m-d.
     *     @type string $to       Latest edition date, Y-m-d.
     * }
     * @return array WP_Query arguments.
     */
    public static function get_analytics_query_args( $args = array() ) {
        $args = wp_parse_args(
            $args,
            array(
                'paged'    => 1,
                'per_page' => 15,
                'edition'  => '',
                'language' => '',
                'search'   => '',
                'from'     => '',
                'to'       => '',
            )
        );

        $query_args = array(
            'post_type'      => 'epaper',
            'posts_per_page' => absint( $args['per_page'] ),
            'paged'          => max( 1, absint( $args['paged'] ) ),
            'post_status'    => 'publish',
            's'              => $args['search'],
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
            'meta_query'     => array(
                'relation'        => 'OR',
                'sk_views'        => array(
                    'key'     => SK_EPaper_Views::META_KEY,
                    'compare' => 'EXISTS',
                    'type'    => 'NUMERIC',
                ),
                'sk_views_absent' => array(
                    'key'     => SK_EPaper_Views::META_KEY,
                    'compare' => 'NOT EXISTS',
                ),
            ),
            'orderby'        => array(
                'sk_views' => 'DESC',
            ),
        );

        // Date range runs on the edition date the publisher entered, matching
        // the date shown in the table and on the frontend.
        $date_clause = array();

        if ( ! empty( $args['from'] ) ) {
            $date_clause[] = array(
                'key'     => SK_EPAPER_PREFIX . 'edition_date',
                'value'   => $args['from'],
                'compare' => '>=',
                'type'    => 'DATE',
            );
        }

        if ( ! empty( $args['to'] ) ) {
            $date_clause[] = array(
                'key'     => SK_EPAPER_PREFIX . 'edition_date',
                'value'   => $args['to'],
                'compare' => '<=',
                'type'    => 'DATE',
            );
        }

        if ( $date_clause ) {
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
            $query_args['meta_query'] = array(
                'relation' => 'AND',
                array(
                    'relation'        => 'OR',
                    'sk_views'        => array(
                        'key'     => SK_EPaper_Views::META_KEY,
                        'compare' => 'EXISTS',
                        'type'    => 'NUMERIC',
                    ),
                    'sk_views_absent' => array(
                        'key'     => SK_EPaper_Views::META_KEY,
                        'compare' => 'NOT EXISTS',
                    ),
                ),
                array_merge( array( 'relation' => 'AND' ), $date_clause ),
            );
        }

        $tax_query = array();

        if ( ! empty( $args['edition'] ) ) {
            $tax_query[] = array(
                'taxonomy' => 'epaper_edition',
                'field'    => 'slug',
                'terms'    => $args['edition'],
            );
        }

        if ( ! empty( $args['language'] ) ) {
            $tax_query[] = array(
                'taxonomy' => 'epaper_language',
                'field'    => 'slug',
                'terms'    => $args['language'],
            );
        }

        if ( count( $tax_query ) > 1 ) {
            $tax_query['relation'] = 'AND';
        }

        if ( ! empty( $tax_query ) ) {
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
            $query_args['tax_query'] = $tax_query;
        }

        return $query_args;
    }

    /**
     * AJAX: filter and paginate the analytics table without a reload.
     *
     * @return void
     */
    public static function ajax_filter_analytics() {
        check_ajax_referer( 'sk_epaper_analytics_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'sk-epaper-manager' ) ) );
        }

        $paged           = isset( $_POST['paged'] ) ? max( 1, absint( $_POST['paged'] ) ) : 1;
        $filter_edition  = isset( $_POST['filter_edition'] ) ? sanitize_text_field( wp_unslash( $_POST['filter_edition'] ) ) : '';
        $filter_language = isset( $_POST['filter_language'] ) ? sanitize_text_field( wp_unslash( $_POST['filter_language'] ) ) : '';
        $filter_search   = isset( $_POST['filter_search'] ) ? sanitize_text_field( wp_unslash( $_POST['filter_search'] ) ) : '';
        $filter_from     = isset( $_POST['filter_from'] ) ? sanitize_text_field( wp_unslash( $_POST['filter_from'] ) ) : '';
        $filter_to       = isset( $_POST['filter_to'] ) ? sanitize_text_field( wp_unslash( $_POST['filter_to'] ) ) : '';

        foreach ( array( 'filter_from', 'filter_to' ) as $sk_date_field ) {
            if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $$sk_date_field ) ) {
                $$sk_date_field = '';
            }
        }

        $per_page = 15;
        $offset   = ( $paged - 1 ) * $per_page;

        $query_args = self::get_analytics_query_args(
            array(
                'paged'    => $paged,
                'per_page' => $per_page,
                'edition'  => $filter_edition,
                'language' => $filter_language,
                'search'   => $filter_search,
                'from'     => $filter_from,
                'to'       => $filter_to,
            )
        );

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
                        $epaper_id   = get_the_ID();
                        $views       = SK_EPaper_Views::get( $epaper_id );
                        $pages_count = count( SK_EPaper_Renderer::get_images( $epaper_id ) );

                        $editions_terms  = get_the_terms( $epaper_id, 'epaper_edition' );
                        $languages_terms = get_the_terms( $epaper_id, 'epaper_language' );
                        $edition_names   = ( ! empty( $editions_terms ) && ! is_wp_error( $editions_terms ) ) ? implode( ', ', wp_list_pluck( $editions_terms, 'name' ) ) : '—';
                        $language_names  = ( ! empty( $languages_terms ) && ! is_wp_error( $languages_terms ) ) ? implode( ', ', wp_list_pluck( $languages_terms, 'name' ) ) : '—';

                        $rank_badge = ( 1 === $rank ) ? 'rank-gold' : ( ( 2 === $rank ) ? 'rank-silver' : ( ( 3 === $rank ) ? 'rank-bronze' : 'rank-normal' ) );
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
                            <td style="text-align: center;"><span class="badge-pill"><?php
                                /* translators: %d: Number of pages in the edition */
                                printf( esc_html( _n( '%d Page', '%d Pages', $pages_count, 'sk-epaper-manager' ) ), esc_html( $pages_count ) );
                            ?></span></td>
                            <td style="text-align: center;"><strong class="sk-views-count"><?php echo esc_html( number_format_i18n( $views ) ); ?></strong></td>
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
                        $page_links = paginate_links(
                            array(
                                'base'      => add_query_arg( 'paged', '%#%' ),
                                'format'    => '',
                                'prev_text' => __( '&laquo; Previous', 'sk-epaper-manager' ),
                                'next_text' => __( 'Next &raquo;', 'sk-epaper-manager' ),
                                'total'     => $total_pages,
                                'current'   => $paged,
                                'type'      => 'plain',
                            )
                        );
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

        wp_send_json_success(
            array(
                'html'  => $content_html,
                'total' => esc_html( $total_html ),
            )
        );
    }

    /**
     * Register the pages meta box.
     *
     * @return void
     */
    public static function add_image_meta_box() {
        add_meta_box(
            SK_EPAPER_PREFIX . 'image_meta_box',
            __( 'ePaper Details & Pages', 'sk-epaper-manager' ),
            'sk_epaper_render_image_meta_box',
            'epaper',
            'normal',
            'high'
        );
    }

    /**
     * Render the pages meta box.
     *
     * @param WP_Post $post Current post.
     * @return void
     */
    public static function render_image_meta_box( $post ) {
        $image_ids    = SK_EPaper_Renderer::get_images( $post->ID );
        $page_labels  = SK_EPaper_Renderer::get_page_labels( $post->ID );
        $edition_date = get_post_meta( $post->ID, SK_EPAPER_PREFIX . 'edition_date', true );

        if ( empty( $edition_date ) ) {
            $edition_date = get_the_date( 'Y-m-d', $post->ID );
        }

        wp_nonce_field( 'sk_epaper_save_pages', SK_EPAPER_PREFIX . 'images_nonce' );

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
                <span class="sk-pages-section-hint">
                    <?php esc_html_e( 'Name a page to label it in the reader, e.g. Front Page, Sports, City.', 'sk-epaper-manager' ); ?>
                </span>
            </div>

            <ul class="sk-epaper-image-list">
                <?php
                $page_index = 1;
                foreach ( $image_ids as $id ) :
                    $id       = absint( $id );
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

                        <input type="text"
                            class="sk-page-label-input"
                            name="sk_epaper_page_labels[<?php echo esc_attr( $id ); ?>]"
                            value="<?php echo esc_attr( isset( $page_labels[ $id ] ) ? $page_labels[ $id ] : '' ); ?>"
                            placeholder="<?php echo esc_attr( $page_pill_label ); ?>"
                            aria-label="<?php esc_attr_e( 'Page label shown in the reader', 'sk-epaper-manager' ); ?>">

                        <span class="remove-image" title="<?php esc_attr_e( 'Remove Page', 'sk-epaper-manager' ); ?>">
                            <span class="text-remove-btn hidden"><?php esc_html_e( 'Remove', 'sk-epaper-manager' ); ?></span>
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
                    <span><?php esc_html_e( 'Add ePaper Pages', 'sk-epaper-manager' ); ?></span>
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

    /**
     * Persist the pages meta box.
     *
     * @param int $post_id Post being saved.
     * @return void
     */
    public static function save_image_meta( $post_id ) {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( 'epaper' !== get_post_type( $post_id ) ) {
            return;
        }

        if ( wp_is_post_revision( $post_id ) ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        if ( ! isset( $_POST[ SK_EPAPER_PREFIX . 'images_nonce' ] ) ) {
            return;
        }

        $nonce = sanitize_text_field( wp_unslash( $_POST[ SK_EPAPER_PREFIX . 'images_nonce' ] ) );

        // basename( __FILE__ ) was the 1.2.x nonce action; accept it so an edit
        // screen loaded before the update can still be saved after it.
        if ( ! wp_verify_nonce( $nonce, 'sk_epaper_save_pages' ) && ! wp_verify_nonce( $nonce, 'sk-epaper-manager.php' ) ) {
            return;
        }

        if ( isset( $_POST[ SK_EPAPER_PREFIX . 'images' ] ) ) {
            $raw_input = sanitize_text_field( wp_unslash( $_POST[ SK_EPAPER_PREFIX . 'images' ] ) );
            $image_ids = array_filter( array_map( 'absint', explode( ',', $raw_input ) ) );

            // Only keep IDs that really are attachments.
            $image_ids = array_values(
                array_filter(
                    $image_ids,
                    static function ( $attachment_id ) {
                        return 'attachment' === get_post_type( $attachment_id );
                    }
                )
            );

            update_post_meta( $post_id, SK_EPAPER_PREFIX . 'images', $image_ids );
        }

        // Page labels are keyed by attachment ID so reordering cannot shuffle them.
        if ( isset( $_POST['sk_epaper_page_labels'] ) && is_array( $_POST['sk_epaper_page_labels'] ) ) {
            $raw_labels = wp_unslash( $_POST['sk_epaper_page_labels'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $labels     = array();

            foreach ( $raw_labels as $attachment_id => $label ) {
                $label = sanitize_text_field( $label );

                if ( '' !== $label ) {
                    $labels[ absint( $attachment_id ) ] = $label;
                }
            }

            if ( $labels ) {
                update_post_meta( $post_id, SK_EPAPER_PREFIX . 'page_labels', $labels );
            } else {
                delete_post_meta( $post_id, SK_EPAPER_PREFIX . 'page_labels' );
            }
        }

        if ( isset( $_POST['sk_epaper_edition_date'] ) ) {
            $edition_date = sanitize_text_field( wp_unslash( $_POST['sk_epaper_edition_date'] ) );
            update_post_meta( $post_id, SK_EPAPER_PREFIX . 'edition_date', $edition_date );
        }
    }

    /**
     * Add columns to the ePaper list table.
     *
     * @param array $columns Existing columns.
     * @return array
     */
    public static function set_custom_columns( $columns ) {
        $new_columns = array();

        foreach ( $columns as $key => $value ) {
            $new_columns[ $key ] = $value;

            if ( 'title' === $key ) {
                $new_columns['taxonomy-epaper_edition']  = __( 'Edition', 'sk-epaper-manager' );
                $new_columns['taxonomy-epaper_language'] = __( 'Language', 'sk-epaper-manager' );
                $new_columns['epaper_date']              = __( 'Publication Date', 'sk-epaper-manager' );
                $new_columns['epaper_pages']             = __( 'Pages', 'sk-epaper-manager' );
                $new_columns['epaper_views']             = __( 'Views', 'sk-epaper-manager' );
            }
        }

        return $new_columns;
    }

    /**
     * Render the custom list table columns.
     *
     * @param string $column  Column key.
     * @param int    $post_id Post ID.
     * @return void
     */
    public static function render_custom_columns( $column, $post_id ) {
        switch ( $column ) {
            case 'epaper_date':
                $date = get_post_meta( $post_id, SK_EPAPER_PREFIX . 'edition_date', true );
                echo $date ? esc_html( date_i18n( get_option( 'date_format' ), strtotime( $date ) ) ) : '—';
                break;

            case 'epaper_pages':
                echo esc_html( count( SK_EPaper_Renderer::get_images( $post_id ) ) );
                break;

            case 'epaper_views':
                echo '<strong>' . esc_html( number_format_i18n( SK_EPaper_Views::get( $post_id ) ) ) . '</strong>';
                break;
        }
    }
}
