<?php
/**
 * One-click demo content.
 *
 * A publisher evaluating the plugin has nothing to look at until they have
 * uploaded a real edition, which makes the settings and shortcodes hard to
 * judge. This creates a few sample editions - pages drawn on the fly with GD,
 * so nothing has to ship in the plugin - and removes them again on request.
 *
 * @package SK_ePaper_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Creates and removes sample editions.
 */
class SK_EPaper_Demo {

    const FLAG = '_sk_epaper_demo';

    /** Sections used for the sample pages. */
    const SECTIONS = array( 'Front Page', 'City', 'Nation', 'Region', 'Sports', 'Business', 'Culture', 'Opinion' );

    /** How many sample editions to create. Enough to exercise pagination. */
    const EDITIONS = 12;

    /**
     * Handle the import / remove actions.
     *
     * @return void
     */
    public static function handle_actions() {
        if ( ! isset( $_POST['sk_epaper_demo_action'] ) ) {
            return;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to do this.', 'sk-epaper-manager' ) );
        }

        check_admin_referer( 'sk_epaper_demo' );

        $action = sanitize_key( wp_unslash( $_POST['sk_epaper_demo_action'] ) );
        $notice = '';

        if ( 'import' === $action ) {
            $created = self::import();
            /* translators: %d: Number of demo editions created */
            $notice = 'imported:' . absint( $created );
        } elseif ( 'remove' === $action ) {
            $removed = self::remove();
            $notice  = 'removed:' . absint( $removed );
        }

        wp_safe_redirect(
            add_query_arg(
                'sk_demo_notice',
                rawurlencode( $notice ),
                admin_url( 'edit.php?post_type=epaper&page=sk-epaper-guide' )
            )
        );
        exit;
    }

    /**
     * Whether demo content is currently installed.
     *
     * @return int Number of demo editions.
     */
    public static function count() {
        $found = get_posts(
            array(
                'post_type'      => 'epaper',
                'post_status'    => 'any',
                'posts_per_page' => -1,
                'fields'         => 'ids',
                'no_found_rows'  => true,
                'meta_key'       => self::FLAG, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
            )
        );

        return count( $found );
    }

    /**
     * Create the sample editions.
     *
     * @return int Number created.
     */
    public static function import() {
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $editions = array(
            'City Edition'    => 'city-edition',
            'Metro Edition'   => 'metro-edition',
            'North Edition'   => 'north-edition',
            'South Edition'   => 'south-edition',
            'Weekend Edition' => 'weekend-edition',
        );

        $languages = array(
            'English'  => 'english',
            'Hindi'    => 'hindi',
            'Punjabi'  => 'punjabi',
        );

        foreach ( array( 'epaper_edition' => $editions, 'epaper_language' => $languages ) as $taxonomy => $terms ) {
            foreach ( $terms as $name => $slug ) {
                if ( term_exists( $slug, $taxonomy ) ) {
                    continue;
                }

                $term = wp_insert_term( $name, $taxonomy, array( 'slug' => $slug ) );

                if ( ! is_wp_error( $term ) ) {
                    add_term_meta( $term['term_id'], self::FLAG, 1 );
                }
            }
        }

        /*
         * Draw the section pages once and share them across editions. Twelve
         * editions with their own images would mean ~90 GD renders and a very
         * slow request for no extra realism.
         */
        $pages = array();

        foreach ( self::SECTIONS as $index => $section ) {
            $attachment_id = self::create_page_image( $section, $index + 1 );

            if ( $attachment_id ) {
                $pages[] = $attachment_id;
            }
        }

        if ( empty( $pages ) ) {
            return 0;
        }

        $edition_slugs  = array_values( $editions );
        $language_slugs = array_values( $languages );
        $created        = 0;

        for ( $i = 0; $i < self::EDITIONS; $i++ ) {
            $date       = gmdate( 'Y-m-d', strtotime( "-{$i} day" ) );
            $page_count = 4 + ( $i % 5 );
            $selection  = array_slice( $pages, 0, min( $page_count, count( $pages ) ) );

            $post_id = wp_insert_post(
                array(
                    'post_type'    => 'epaper',
                    'post_status'  => 'publish',
                    'post_date'    => $date . ' 07:00:00',
                    'post_title'   => sprintf(
                        /* translators: %s: Edition date */
                        __( 'Sample Edition - %s', 'sk-epaper-manager' ),
                        date_i18n( get_option( 'date_format' ), strtotime( $date ) )
                    ),
                    'post_content' => __( 'Sample edition created by SK ePaper Manager so you can try the reader.', 'sk-epaper-manager' ),
                )
            );

            if ( is_wp_error( $post_id ) ) {
                continue;
            }

            $labels = array();

            foreach ( $selection as $position => $attachment_id ) {
                $labels[ $attachment_id ] = self::SECTIONS[ $position ];
            }

            update_post_meta( $post_id, self::FLAG, 1 );
            update_post_meta( $post_id, SK_EPAPER_PREFIX . 'images', $selection );
            update_post_meta( $post_id, SK_EPAPER_PREFIX . 'page_labels', $labels );
            update_post_meta( $post_id, SK_EPAPER_PREFIX . 'edition_date', $date );
            update_post_meta( $post_id, SK_EPaper_Views::META_KEY, wp_rand( 40, 4200 ) );

            wp_set_object_terms( $post_id, $edition_slugs[ $i % count( $edition_slugs ) ], 'epaper_edition' );
            wp_set_object_terms( $post_id, $language_slugs[ $i % count( $language_slugs ) ], 'epaper_language' );
            set_post_thumbnail( $post_id, $selection[0] );

            $created++;
        }

        SK_EPaper_CPT::flush_month_cache();

        return $created;
    }

    /**
     * Delete everything the importer created.
     *
     * @return int Number of editions removed.
     */
    public static function remove() {
        $posts = get_posts(
            array(
                'post_type'      => array( 'epaper', 'attachment' ),
                'post_status'    => 'any',
                'posts_per_page' => -1,
                'fields'         => 'ids',
                'no_found_rows'  => true,
                'meta_key'       => self::FLAG, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
            )
        );

        $removed = 0;

        foreach ( $posts as $post_id ) {
            if ( 'epaper' === get_post_type( $post_id ) ) {
                $removed++;
            }

            wp_delete_post( $post_id, true );
        }

        foreach ( array( 'epaper_edition', 'epaper_language' ) as $taxonomy ) {
            $terms = get_terms(
                array(
                    'taxonomy'   => $taxonomy,
                    'hide_empty' => false,
                    'meta_key'   => self::FLAG, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
                )
            );

            if ( is_wp_error( $terms ) ) {
                continue;
            }

            foreach ( $terms as $term ) {
                wp_delete_term( $term->term_id, $taxonomy );
            }
        }

        SK_EPaper_CPT::flush_month_cache();

        return $removed;
    }

    /**
     * Draw one sample newspaper page and add it to the media library.
     *
     * GD rather than Imagick: GD is present on effectively every host, and the
     * page only needs to read as a newspaper at thumbnail size.
     *
     * @param string $section Section name.
     * @param int    $page    Page number.
     * @return int Attachment ID, or 0 on failure.
     */
    protected static function create_page_image( $section, $page ) {
        if ( ! function_exists( 'imagecreatetruecolor' ) ) {
            return 0;
        }

        $width  = 827;
        $height = 1169;

        $image = imagecreatetruecolor( $width, $height );

        $paper = imagecolorallocate( $image, 251, 250, 247 );
        $ink   = imagecolorallocate( $image, 17, 17, 17 );
        $body  = imagecolorallocate( $image, 141, 141, 141 );
        $photo = imagecolorallocate( $image, 201, 211, 221 );
        $rule  = imagecolorallocate( $image, 204, 204, 204 );

        imagefilledrectangle( $image, 0, 0, $width, $height, $paper );

        $margin = 44;

        // Masthead.
        imagestring( $image, 5, $margin, 40, 'SK EPAPER DEMO', $ink );
        imagefilledrectangle( $image, $margin, 66, $width - $margin, 68, $ink );
        imagestring( $image, 2, $margin, 76, strtoupper( $section ) . '   |   DEMO EDITION   |   PAGE ' . $page, $body );

        // Lead headline.
        imagestring( $image, 5, $margin, 112, $section . ' Report For Today', $ink );

        // Lead photo.
        imagefilledrectangle( $image, $margin, 146, $width - $margin - 220, 380, $photo );

        // Body copy as ruled lines, three columns.
        $col_width = ( $width - ( $margin * 2 ) - 40 ) / 3;

        for ( $col = 0; $col < 3; $col++ ) {
            $x = $margin + $col * ( $col_width + 20 );
            $y = ( 2 === $col ) ? 150 : 400;

            for ( $line = 0; $line < 34; $line++ ) {
                $line_y = $y + $line * 13;

                if ( $line_y > $height - 210 ) {
                    break;
                }

                imagefilledrectangle( $image, $x, $line_y, $x + $col_width - ( $line % 5 === 4 ? 60 : 0 ), $line_y + 3, $body );
            }
        }

        // Advertisement block.
        imagerectangle( $image, $margin, $height - 190, $width - $margin, $height - 70, $ink );
        imagestring( $image, 4, $margin + 20, $height - 150, 'ADVERTISEMENT', $ink );
        imagefilledrectangle( $image, $margin, $height - 46, $width - $margin, $height - 45, $rule );
        imagestring( $image, 1, $margin, $height - 36, 'Demo content generated by SK ePaper Manager', $body );

        $uploads = wp_upload_dir();

        if ( ! empty( $uploads['error'] ) ) {
            imagedestroy( $image );
            return 0;
        }

        $filename = sanitize_file_name( 'sk-epaper-demo-page-' . $page . '.jpg' );
        $path     = trailingslashit( $uploads['path'] ) . $filename;

        imagejpeg( $image, $path, 82 );
        imagedestroy( $image );

        if ( ! file_exists( $path ) ) {
            return 0;
        }

        $attachment_id = wp_insert_attachment(
            array(
                'post_mime_type' => 'image/jpeg',
                'post_title'     => $section . ' - Page ' . $page,
                'post_status'    => 'inherit',
            ),
            $path
        );

        if ( is_wp_error( $attachment_id ) || ! $attachment_id ) {
            return 0;
        }

        wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $path ) );
        update_post_meta( $attachment_id, self::FLAG, 1 );

        return (int) $attachment_id;
    }
}
