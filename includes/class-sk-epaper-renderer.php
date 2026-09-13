<?php
/**
 * Frontend assets, shared viewer markup and shortcodes.
 *
 * The viewer markup used to be duplicated between the [sk_epaper] shortcode and
 * templates/single-epaper.php; both now render through render_viewer() so the
 * two can never drift apart. Every existing CSS class and element id is kept
 * intact so custom theme CSS keeps working.
 *
 * @package SK_ePaper_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Renders ePapers on the frontend.
 */
class SK_EPaper_Renderer {

    /**
     * Read the stored page attachment IDs for an ePaper.
     *
     * Handles both storage formats: the array written since 1.2.0 and the
     * comma-separated string written by 1.1.x and earlier. This fallback must
     * never be removed - older editions still rely on it.
     *
     * @param int $post_id ePaper post ID.
     * @return array Attachment IDs.
     */
    public static function get_images( $post_id ) {
        $images = get_post_meta( $post_id, SK_EPAPER_PREFIX . 'images', true );

        if ( ! is_array( $images ) ) {
            $images = array_filter( explode( ',', (string) $images ) );
        }

        $images = array_values( array_filter( array_map( 'absint', $images ) ) );

        /*
         * Skip pages whose attachment has since been deleted from the media
         * library. The stored meta is left alone - it is only rewritten when
         * the editor saves the edition - but a missing file would otherwise
         * render as a blank page and inflate the page count.
         */
        return array_values(
            array_filter(
                $images,
                static function ( $attachment_id ) {
                    return 'attachment' === get_post_type( $attachment_id );
                }
            )
        );
    }

    /**
     * Whether this request needs the viewer assets up front.
     *
     * Assets used to load on every page of the site. Rendering paths that we
     * cannot predict - page builders, widgets, late shortcodes - are still
     * covered by ensure_assets() at render time, and the filter is there for
     * anything exotic.
     *
     * @return bool
     */
    public static function needs_assets() {
        $needed = false;

        if ( is_singular( 'epaper' ) || is_post_type_archive( 'epaper' ) || is_tax( 'epaper_edition' ) || is_tax( 'epaper_language' ) ) {
            $needed = true;
        }

        if ( ! $needed && is_singular() ) {
            $post = get_post();

            if ( $post instanceof WP_Post ) {
                if ( has_shortcode( $post->post_content, 'sk_epaper' ) || has_shortcode( $post->post_content, 'sk_epaper_archive' ) ) {
                    $needed = true;
                } elseif ( SK_EPaper_Blocks::post_has_block( $post ) ) {
                    $needed = true;
                }
            }
        }

        /**
         * Force the ePaper viewer assets to load on a request.
         *
         * Useful when a theme or page builder renders the viewer somewhere the
         * content scan cannot see.
         *
         * @param bool $needed Current decision.
         */
        return (bool) apply_filters( 'sk_epaper_force_load_assets', $needed );
    }

    /**
     * Editor supplied labels for each page, keyed by attachment ID.
     *
     * @param int $post_id ePaper post ID.
     * @return array
     */
    public static function get_page_labels( $post_id ) {
        $labels = get_post_meta( $post_id, SK_EPAPER_PREFIX . 'page_labels', true );

        return is_array( $labels ) ? $labels : array();
    }

    /**
     * Optional label for one page in the thumbnail rail.
     *
     * Only editor supplied labels are shown. Falling back to the attachment
     * title or "Page N" just printed filenames under every thumbnail, which is
     * noise - the rail already communicates position.
     *
     * @param int   $attachment_id Attachment ID.
     * @param array $labels        Stored labels keyed by attachment ID.
     * @return string Empty string when no label was set.
     */
    public static function get_page_label( $attachment_id, $labels = array() ) {
        return isset( $labels[ $attachment_id ] ) ? (string) $labels[ $attachment_id ] : '';
    }

    /**
     * Enqueue frontend styles plus the colour theme overrides.
     *
     * @return void
     */
    public static function enqueue_styles() {
        wp_enqueue_style( 'dashicons' );
        wp_enqueue_style(
            SK_EPAPER_PREFIX . 'style',
            SK_EPAPER_URL . 'assets/epaper-style.css',
            array( 'dashicons' ),
            SK_EPAPER_VERSION,
            'all'
        );

        $header_bg     = SK_EPaper_Settings::get( 'header_bg' );
        $primary_color = SK_EPaper_Settings::get( 'primary_color' );

        $custom_css = '';

        /*
         * Every site's theme has its own content width, so the plugin cannot
         * assume one. The layout reads this custom property; setting the option
         * to 0 removes the constraint entirely and lets the theme's container
         * govern, which is what block themes with their own layout expect.
         */
        $content_width = absint( SK_EPaper_Settings::get( 'content_width' ) );

        /**
         * Filter the maximum width of the ePaper reader and archive.
         *
         * @param int $content_width Width in pixels, or 0 for no constraint.
         */
        $content_width = (int) apply_filters( 'sk_epaper_content_max_width', $content_width );

        $custom_css .= ':root { --sk-epaper-max-width: ' . ( $content_width > 0 ? absint( $content_width ) . 'px' : 'none' ) . "; }\n";

        if ( $header_bg ) {
            // The thumbnail rail is part of the reader chrome, so it follows the
            // same configured colour as the toolbar rather than a fixed slate.
            $custom_css .= '.epaper-top-header .epaper-row { background: ' . esc_attr( $header_bg ) . " !important; }\n";
            $custom_css .= '.epaper-thumb-strip { background: ' . esc_attr( $header_bg ) . " !important; }\n";
        }

        if ( $primary_color ) {
            $primary     = esc_attr( $primary_color );
            $custom_css .= "
            .epaper-hover-read-btn,
            .arrow:hover {
                background: {$primary} !important;
                color: #ffffff !important;
                border-color: {$primary} !important;
            }
            .epaper-card-item:hover {
                border-color: {$primary} !important;
            }
            .epaper-archive-pagination .page-numbers:hover,
            .epaper-archive-pagination .page-numbers.current {
                background: {$primary} !important;
                border-color: {$primary} !important;
                color: #ffffff !important;
            }
            .epaper-card-date .dashicons,
            .epaper-archive-hero-header h1.page-title .dashicons,
            .epaper-count-badge .dashicons,
            .epaper-card-title a:hover {
                color: {$primary} !important;
            }
            ";
        }

        if ( $primary_color && '#000000' !== strtolower( $primary_color ) ) {
            // The active page marker follows the configured brand colour, the
            // same as the arrows, pagination and hover states.
            $custom_css .= '
            .epaper-thumb-btn.is-active .epaper-thumb-frame {
                border-color: ' . esc_attr( $primary_color ) . ' !important;
            }
            .epaper-thumb-btn.is-active .epaper-thumb-num {
                background: ' . esc_attr( $primary_color ) . ' !important;
            }
            ';
        }

        if ( ! empty( $custom_css ) ) {
            wp_add_inline_style( SK_EPAPER_PREFIX . 'style', $custom_css );
        }
    }

    /**
     * Enqueue the frontend viewer script.
     *
     * @return void
     */
    public static function enqueue_scripts() {
        wp_enqueue_script(
            SK_EPAPER_PREFIX . 'script',
            SK_EPAPER_URL . 'assets/epaper-script.js',
            array( 'jquery' ),
            SK_EPAPER_VERSION,
            true
        );

        SK_EPaper_Views::localize( SK_EPAPER_PREFIX . 'script' );

        /*
         * The archive date filter greys out days with no edition. A native
         * <input type="date"> can only express a range, so the picker upgrades
         * to the datepicker WordPress already ships. Without JavaScript the
         * native input still works, bounded by its min/max.
         */
        if ( is_post_type_archive( 'epaper' ) || is_tax( 'epaper_edition' ) || is_tax( 'epaper_language' ) ) {
            wp_enqueue_script( 'jquery-ui-datepicker' );

            wp_localize_script(
                SK_EPAPER_PREFIX . 'script',
                'sk_epaper_dates',
                array(
                    'available' => SK_EPaper_CPT::get_available_dates(),
                    'today'     => current_time( 'Y-m-d' ),
                    'i18n'      => array(
                        'prev'  => __( 'Previous', 'sk-epaper-manager' ),
                        'next'  => __( 'Next', 'sk-epaper-manager' ),
                        'close' => __( 'Done', 'sk-epaper-manager' ),
                    ),
                )
            );
        }

        // Optional: only loaded when the operator opted into PDF.js *and* the
        // vendor build is present. Missing vendor files fall back to the iframe.
        if ( 'pdfjs' === self::get_pdf_mode() ) {
            wp_enqueue_script(
                SK_EPAPER_PREFIX . 'pdfjs',
                SK_EPAPER_URL . 'assets/vendor/pdfjs/pdf.min.js',
                array(),
                SK_EPAPER_VERSION,
                true
            );

            wp_localize_script(
                SK_EPAPER_PREFIX . 'script',
                'sk_epaper_pdfjs',
                array(
                    'worker' => SK_EPAPER_URL . 'assets/vendor/pdfjs/pdf.worker.min.js',
                )
            );
        }
    }

    /**
     * Make sure viewer assets are present when rendered late (shortcodes,
     * page builders, widgets).
     *
     * @return void
     */
    protected static function ensure_assets() {
        wp_enqueue_style( 'dashicons' );

        if ( ! wp_style_is( SK_EPAPER_PREFIX . 'style', 'enqueued' ) ) {
            self::enqueue_styles();
        }

        if ( ! wp_script_is( SK_EPAPER_PREFIX . 'script', 'enqueued' ) ) {
            self::enqueue_scripts();
        }
    }

    /**
     * Render the complete ePaper viewer.
     *
     * @param int $post_id ePaper post ID.
     * @return string HTML markup.
     */
    public static function render_viewer( $post_id ) {
        $post_id = absint( $post_id );
        $images  = self::get_images( $post_id );

        self::ensure_assets();

        $enable_download = SK_EPaper_Settings::is_enabled( 'download' );
        $enable_print    = SK_EPaper_Settings::is_enabled( 'print' );
        $enable_zoom     = SK_EPaper_Settings::is_enabled( 'zoom' );
        $show_thumbs     = (bool) SK_EPaper_Settings::get( 'show_thumbnails' ) && count( $images ) > 1;
        $thumb_position  = SK_EPaper_Settings::get( 'thumb_position' );

        if ( ! in_array( $thumb_position, array( 'bottom', 'left', 'right' ), true ) ) {
            $thumb_position = 'bottom';
        }
        $total           = count( $images );
        $title           = get_the_title( $post_id );

        ob_start();
        ?>
        <div class="epaper-wrapper wrapper" data-epaper-id="<?php echo esc_attr( $post_id ); ?>"
            data-lazy="<?php echo SK_EPaper_Settings::get( 'lazy_load' ) ? '1' : '0'; ?>"
            data-pdf-mode="<?php echo esc_attr( self::get_pdf_mode() ); ?>"
            data-thumb-position="<?php echo esc_attr( $thumb_position ); ?>"
            role="region"
            aria-roledescription="<?php esc_attr_e( 'ePaper reader', 'sk-epaper-manager' ); ?>"
            aria-label="<?php echo esc_attr( $title ); ?>">
            <div class="epaper-top-header">
                <div class="epaper-row">
                    <div class="epaper-col">
                        <div class="epaper-left">
                            <span class="current-date"><?php
                                /* translators: %s: ePaper edition title */
                                printf( esc_html__( 'ePaper: %s', 'sk-epaper-manager' ), esc_html( $title ) );
                            ?></span>
                        </div>
                        <div class="epaper-center">
                            <div class="epaper-controls-group">
                                <?php if ( $enable_zoom ) : ?>
                                    <button type="button" id="zoom-in" class="sk-control-button" title="<?php esc_attr_e( 'Zoom In', 'sk-epaper-manager' ); ?>" aria-label="<?php esc_attr_e( 'Zoom In', 'sk-epaper-manager' ); ?>">
                                        <span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span>
                                    </button>
                                    <button type="button" id="zoom-out" class="sk-control-button" title="<?php esc_attr_e( 'Zoom Out', 'sk-epaper-manager' ); ?>" aria-label="<?php esc_attr_e( 'Zoom Out', 'sk-epaper-manager' ); ?>">
                                        <span class="dashicons dashicons-minus" aria-hidden="true"></span>
                                    </button>
                                <?php endif; ?>
                                <?php if ( $enable_print ) : ?>
                                    <button type="button" id="print-btn" class="sk-control-button" title="<?php esc_attr_e( 'Print Page', 'sk-epaper-manager' ); ?>" aria-label="<?php esc_attr_e( 'Print current page', 'sk-epaper-manager' ); ?>">
                                        <span class="dashicons dashicons-printer" aria-hidden="true"></span>
                                    </button>
                                    <button type="button" id="print-all-btn" class="sk-control-button" title="<?php esc_attr_e( 'Print All Pages', 'sk-epaper-manager' ); ?>" aria-label="<?php esc_attr_e( 'Print all pages', 'sk-epaper-manager' ); ?>">
                                        <span class="dashicons dashicons-admin-page" aria-hidden="true"></span>
                                    </button>
                                <?php endif; ?>
                                <?php if ( $enable_download ) : ?>
                                    <a id="download-btn" href="#" download class="sk-control-button" title="<?php esc_attr_e( 'Download Page', 'sk-epaper-manager' ); ?>" aria-label="<?php esc_attr_e( 'Download current page', 'sk-epaper-manager' ); ?>">
                                        <span class="dashicons dashicons-download" aria-hidden="true"></span>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="epaper-right">
                            <span class="page-count" aria-live="polite" aria-atomic="true">
                                <?php esc_html_e( 'Page:', 'sk-epaper-manager' ); ?>
                                <span class="current-page">1</span>
                                <?php esc_html_e( 'of', 'sk-epaper-manager' ); ?>
                                <span class="total-page"><?php echo esc_html( $total ); ?></span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="epaper-body epaper-thumbs-<?php echo esc_attr( $thumb_position ); ?>">
            <div class="epaper-content">
                <div class="epaper-row">
                    <div class="epaper-col">
                        <div class="slider-container">
                            <button type="button" class="arrow left-arrow" id="prev" aria-label="<?php esc_attr_e( 'Previous page', 'sk-epaper-manager' ); ?>">
                                <span class="dashicons dashicons-arrow-left-alt2" aria-hidden="true"></span>
                            </button>
                            <div class="image-slider">
                                <?php echo self::render_slides( $images ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                            </div>
                            <button type="button" class="arrow right-arrow" id="next" aria-label="<?php esc_attr_e( 'Next page', 'sk-epaper-manager' ); ?>">
                                <span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ( $show_thumbs ) : ?>
                <?php echo self::render_thumbnails( $images, $post_id, $thumb_position ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <?php endif; ?>
            </div>
        </div>
        <?php
        echo SK_EPaper_Views::get_noscript_pixel( $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

        return ob_get_clean();
    }

    /**
     * Configured PDF rendering mode.
     *
     * Defaults to the classic iframe embed so existing installs keep the exact
     * behaviour they had. "pdfjs" additionally requires the vendor library in
     * assets/vendor/pdfjs/ - without it the viewer falls back to the iframe.
     *
     * @return string iframe|pdfjs
     */
    public static function get_pdf_mode() {
        $mode = SK_EPaper_Settings::get( 'pdf_mode' );

        if ( 'pdfjs' === $mode && self::pdfjs_available() ) {
            return 'pdfjs';
        }

        if ( 'image' === $mode ) {
            return 'image';
        }

        return 'iframe';
    }

    /**
     * Whether the optional PDF.js build has been added to the plugin.
     *
     * @return bool
     */
    public static function pdfjs_available() {
        return file_exists( SK_EPAPER_PATH . 'assets/vendor/pdfjs/pdf.min.js' );
    }

    /**
     * Render the individual page slides.
     *
     * Only the first page carries a real src; the rest ship as data-src and are
     * swapped in by the viewer script as the reader approaches them. A 24 page
     * edition used to push every full size image down the wire on first paint.
     *
     * @param array $images Attachment IDs.
     * @return string HTML markup (already escaped).
     */
    protected static function render_slides( $images ) {
        if ( empty( $images ) ) {
            return '';
        }

        $lazy     = (bool) SK_EPaper_Settings::get( 'lazy_load' );
        $pdf_mode = self::get_pdf_mode();
        $html     = '';
        $index    = 0;

        foreach ( $images as $attachment_id ) {
            $attachment_id = absint( $attachment_id );
            $file_url      = wp_get_attachment_url( $attachment_id );

            // Test the attachment itself. Once Ghostscript is installed WordPress
            // generates a "full" JPEG preview for PDFs too, and keying off that
            // would silently swap every PDF reader over to a flat image.
            $is_image = wp_attachment_is( 'image', $attachment_id );
            $src      = $is_image ? wp_get_attachment_image_src( $attachment_id, 'full' ) : false;

            if ( ! $is_image && 'image' === $pdf_mode ) {
                $src = wp_get_attachment_image_src( $attachment_id, 'full' );
            }

            $render_as_image = ! empty( $src[0] );
            $defer           = $lazy && $index > 0;

            $html .= '<div class="image-slide" data-index="' . esc_attr( $index ) . '"'
                . ' data-download-url="' . esc_url( $file_url ) . '"'
                . ' data-file-url="' . esc_url( $file_url ) . '"'
                . ' data-is-image="' . ( $render_as_image ? '1' : '0' ) . '"'
                . ' data-loaded="' . ( $defer ? '0' : '1' ) . '">';
            $html .= '<div class="zoom-container">';

            if ( $render_as_image ) {
                $html .= self::render_page_image( $attachment_id, $src, $defer, $index );
            } elseif ( 'pdfjs' === $pdf_mode ) {
                $html .= '<div class="epaper-pdf-canvas" data-pdf-url="' . esc_url( $file_url ) . '"></div>';
            } else {
                // Strip the built in PDF viewer chrome and fit to width so an
                // embedded PDF reads like a page image rather than a document.
                $embed_url  = esc_url( $file_url ) . '#toolbar=0&amp;navpanes=0&amp;scrollbar=0&amp;statusbar=0&amp;view=FitH';
                $iframe_src = $defer ? ' data-src="' . $embed_url . '"' : ' src="' . $embed_url . '"';
                $html      .= '<iframe' . $iframe_src . ' class="epaper-pdf-embed" width="100%" title="' . esc_attr( get_the_title( $attachment_id ) ) . '"></iframe>';
            }

            $html .= '</div></div>';

            $index++;
        }

        return $html;
    }

    /**
     * Markup for a single page image.
     *
     * @param int   $attachment_id Attachment ID.
     * @param array $src           Result of wp_get_attachment_image_src().
     * @param bool  $defer         Whether to defer loading.
     * @param int   $index         Zero based page index.
     * @return string
     */
    protected static function render_page_image( $attachment_id, $src, $defer, $index ) {
        list( $url, $width, $height ) = array( $src[0], isset( $src[1] ) ? $src[1] : 0, isset( $src[2] ) ? $src[2] : 0 );

        $srcset = wp_get_attachment_image_srcset( $attachment_id, 'full' );
        $sizes  = wp_get_attachment_image_sizes( $attachment_id, 'full' );
        $alt    = trim( wp_strip_all_tags( (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ) );

        if ( '' === $alt ) {
            $alt = get_the_title( $attachment_id );
        }

        $attrs = array(
            'class'    => 'attachment-full size-full epaper-page-img',
            'alt'      => $alt,
            'decoding' => 'async',
        );

        if ( $width && $height ) {
            $attrs['width']  = $width;
            $attrs['height'] = $height;
        }

        if ( $defer ) {
            $attrs['data-src'] = $url;
            $attrs['loading']  = 'lazy';

            if ( $srcset ) {
                $attrs['data-srcset'] = $srcset;
            }
            if ( $sizes ) {
                $attrs['data-sizes'] = $sizes;
            }
        } else {
            $attrs['src'] = $url;

            if ( $srcset ) {
                $attrs['srcset'] = $srcset;
            }
            if ( $sizes ) {
                $attrs['sizes'] = $sizes;
            }
            if ( $index > 0 ) {
                $attrs['loading'] = 'lazy';
            }
        }

        $rendered = '';

        foreach ( $attrs as $name => $value ) {
            $rendered .= ' ' . esc_attr( $name ) . '="' . esc_attr( $value ) . '"';
        }

        $placeholder = $defer ? '<span class="epaper-page-skeleton" aria-hidden="true"></span>' : '';

        return $placeholder . '<img' . $rendered . ' />';
    }

    /**
     * Render the page thumbnail rail.
     *
     * @param array  $images   Attachment IDs.
     * @param int    $post_id  ePaper post ID.
     * @param string $position bottom|left|right.
     * @return string
     */
    protected static function render_thumbnails( $images, $post_id, $position = 'bottom' ) {
        $labels   = self::get_page_labels( $post_id );
        $vertical = in_array( $position, array( 'left', 'right' ), true );

        $prev_icon = $vertical ? 'dashicons-arrow-up-alt2' : 'dashicons-arrow-left-alt2';
        $next_icon = $vertical ? 'dashicons-arrow-down-alt2' : 'dashicons-arrow-right-alt2';

        $html  = '<nav class="epaper-thumb-strip" aria-label="' . esc_attr__( 'ePaper pages', 'sk-epaper-manager' ) . '">';
        $html .= '<button type="button" class="epaper-thumb-nav epaper-thumb-prev" aria-label="' . esc_attr__( 'Show previous pages', 'sk-epaper-manager' ) . '" hidden>';
        $html .= '<span class="dashicons ' . esc_attr( $prev_icon ) . '" aria-hidden="true"></span></button>';
        $html .= '<div class="epaper-thumb-viewport"><ul class="epaper-thumb-list">';

        foreach ( array_values( $images ) as $index => $attachment_id ) {
            $attachment_id = absint( $attachment_id );
            $thumb         = self::get_thumbnail_src( $attachment_id );
            $label         = self::get_page_label( $attachment_id, $labels );
            /* translators: %d: Page number */
            $aria = sprintf( __( 'Go to page %d', 'sk-epaper-manager' ), $index + 1 );

            if ( '' !== $label ) {
                /* translators: 1: Page label, 2: Page number */
                $aria = sprintf( __( 'Go to %1$s (page %2$d)', 'sk-epaper-manager' ), $label, $index + 1 );
            }

            $html .= '<li class="epaper-thumb-item">';
            $html .= '<button type="button" class="epaper-thumb-btn' . ( 0 === $index ? ' is-active' : '' ) . '"'
                . ' data-index="' . esc_attr( $index ) . '"'
                . ' aria-label="' . esc_attr( $aria ) . '"'
                . ( 0 === $index ? ' aria-current="true"' : '' ) . '>';

            $html .= '<span class="epaper-thumb-frame">';

            if ( $thumb ) {
                $html .= '<img src="' . esc_url( $thumb ) . '" alt="" loading="lazy" decoding="async" />';
            } else {
                $html .= '<span class="epaper-thumb-fallback"><span class="dashicons dashicons-media-document" aria-hidden="true"></span></span>';
            }

            $html .= '<span class="epaper-thumb-num">' . esc_html( $index + 1 ) . '</span>';
            $html .= '</span>';

            if ( '' !== $label ) {
                $html .= '<span class="epaper-thumb-label">' . esc_html( $label ) . '</span>';
            }

            $html .= '</button></li>';
        }

        $html .= '</ul></div>';
        $html .= '<button type="button" class="epaper-thumb-nav epaper-thumb-next" aria-label="' . esc_attr__( 'Show more pages', 'sk-epaper-manager' ) . '" hidden>';
        $html .= '<span class="dashicons ' . esc_attr( $next_icon ) . '" aria-hidden="true"></span></button>';
        $html .= '</nav>';

        return $html;
    }

    /**
     * Best available preview URL for a page, image or PDF.
     *
     * PDFs get a generated preview from WordPress when Imagick and Ghostscript
     * are both present; the registered size names differ between the two cases,
     * so try each in turn rather than assuming "thumbnail" exists.
     *
     * @param int $attachment_id Attachment ID.
     * @return string Empty string when no preview exists.
     */
    public static function get_thumbnail_src( $attachment_id ) {
        foreach ( array( 'large', 'medium_large', 'medium', 'thumbnail', 'full' ) as $size ) {
            $src = wp_get_attachment_image_src( $attachment_id, $size );

            if ( ! empty( $src[0] ) ) {
                return $src[0];
            }
        }

        return '';
    }

    /**
     * Render "More editions" below the reader.
     *
     * Prefers editions sharing an Edition term, then a Language term, then the
     * latest editions - so the block is never empty on a small site.
     *
     * @param int $post_id Current ePaper post ID.
     * @return string
     */
    public static function render_related( $post_id ) {
        $post_id = absint( $post_id );

        if ( ! SK_EPaper_Settings::get( 'show_related' ) ) {
            return '';
        }

        $count = absint( SK_EPaper_Settings::get( 'related_count' ) );
        $count = $count > 0 ? $count : 3;

        $related = self::query_related( $post_id, $count );

        if ( empty( $related ) ) {
            return '';
        }

        ob_start();
        ?>
        <section class="epaper-related">
            <div class="epaper-related-head">
                <h2 class="epaper-related-title">
                    <span class="dashicons dashicons-images-alt2" aria-hidden="true"></span>
                    <?php esc_html_e( 'More Editions', 'sk-epaper-manager' ); ?>
                </h2>
                <a class="epaper-related-all" href="<?php echo esc_url( get_post_type_archive_link( 'epaper' ) ); ?>">
                    <?php esc_html_e( 'View all', 'sk-epaper-manager' ); ?>
                    <span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span>
                </a>
            </div>

            <div class="epaper-grid-container epaper-related-grid">
                <?php foreach ( $related as $related_post ) : ?>
                    <?php echo self::render_card( $related_post->ID ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <?php endforeach; ?>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }

    /**
     * Find editions related to the given one.
     *
     * Runs up to three passes - same Edition, then same Language, then simply
     * the most recent - stopping as soon as enough are found.
     *
     * Each pass over-fetches by the size of the skip list and filters in PHP
     * rather than passing post__not_in: MySQL optimises NOT IN poorly, and the
     * list of already-seen IDs here is only ever a handful long.
     *
     * @param int $post_id Current ePaper post ID.
     * @param int $count   How many to return.
     * @return WP_Post[]
     */
    protected static function query_related( $post_id, $count ) {
        $base = array(
            'post_type'           => 'epaper',
            'post_status'         => 'publish',
            'ignore_sticky_posts' => true,
            'no_found_rows'       => true,
            'orderby'             => 'date',
            'order'               => 'DESC',
        );

        $passes = array();

        foreach ( array( 'epaper_edition', 'epaper_language' ) as $taxonomy ) {
            $terms = wp_get_object_terms( $post_id, $taxonomy, array( 'fields' => 'slugs' ) );

            if ( is_wp_error( $terms ) || empty( $terms ) ) {
                continue;
            }

            $passes[] = array(
                // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
                'tax_query' => array(
                    array(
                        'taxonomy' => $taxonomy,
                        'field'    => 'slug',
                        'terms'    => $terms,
                    ),
                ),
            );
        }

        // Last resort so the row is never half empty on a small site.
        $passes[] = array();

        $found = array();
        $seen  = array( (int) $post_id => true );

        foreach ( $passes as $pass ) {
            if ( count( $found ) >= $count ) {
                break;
            }

            $args                   = array_merge( $base, $pass );
            $args['posts_per_page'] = $count + count( $seen );

            foreach ( get_posts( $args ) as $match ) {
                if ( isset( $seen[ $match->ID ] ) ) {
                    continue;
                }

                $found[]              = $match;
                $seen[ $match->ID ]   = true;

                if ( count( $found ) >= $count ) {
                    break;
                }
            }
        }

        /**
         * Filter the related editions shown under the reader.
         *
         * @param WP_Post[] $found   Related editions.
         * @param int       $post_id Current ePaper post ID.
         */
        return apply_filters( 'sk_epaper_related_editions', $found, $post_id );
    }

    /**
     * One edition card, shared by the archive grid and the related row.
     *
     * Both places use the same card on purpose: a related row that looks
     * different from the archive reads as a different kind of content.
     *
     * @param int $post_id ePaper post ID.
     * @return string
     */
    public static function render_card( $post_id ) {
        $post_id      = absint( $post_id );
        $edition_date = get_post_meta( $post_id, SK_EPAPER_PREFIX . 'edition_date', true );
        $page_count   = count( self::get_images( $post_id ) );
        $editions     = get_the_terms( $post_id, 'epaper_edition' );
        $languages    = get_the_terms( $post_id, 'epaper_language' );
        $permalink    = get_permalink( $post_id );
        $title        = get_the_title( $post_id );

        ob_start();
        ?>
        <article class="epaper-card-item">
            <?php
            /*
             * One link stretched over the whole card. The title, preview and
             * button used to be three separate anchors pointing at the same
             * place, which meant most of the card was dead space and screen
             * readers announced the same destination three times.
             */
            ?>
            <a class="epaper-card-overlay-link" href="<?php echo esc_url( $permalink ); ?>"
                aria-label="<?php
                    /* translators: %s: ePaper edition title */
                    echo esc_attr( sprintf( __( 'Read %s', 'sk-epaper-manager' ), $title ) );
                ?>"></a>

            <div class="epaper-card-thumb-wrap">
                <?php if ( has_post_thumbnail( $post_id ) ) : ?>
                    <?php echo get_the_post_thumbnail( $post_id, 'large', array( 'class' => 'epaper-card-img', 'alt' => '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <?php else : ?>
                    <div class="epaper-placeholder-card">
                        <span class="dashicons dashicons-media-document" aria-hidden="true"></span>
                    </div>
                <?php endif; ?>

                <span class="epaper-card-page-badge">
                    <span class="dashicons dashicons-images-alt2" aria-hidden="true"></span>
                    <?php
                    /* translators: %d: Total number of pages */
                    echo esc_html( sprintf( _n( '%d Page', '%d Pages', $page_count, 'sk-epaper-manager' ), $page_count ) );
                    ?>
                </span>

                <div class="epaper-card-hover-overlay">
                    <span class="epaper-hover-read-btn">
                        <span class="dashicons dashicons-visibility" aria-hidden="true"></span>
                        <?php esc_html_e( 'Read ePaper', 'sk-epaper-manager' ); ?>
                    </span>
                </div>
            </div>

            <div class="epaper-card-body">
                <div class="epaper-card-meta-row">
                    <?php if ( $edition_date ) : ?>
                        <span class="epaper-card-date">
                            <span class="dashicons dashicons-calendar-alt" aria-hidden="true"></span>
                            <?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $edition_date ) ) ); ?>
                        </span>
                    <?php endif; ?>

                    <span class="epaper-card-tax-meta">
                        <?php
                        /*
                         * Badges link to their term archive. They sit above the
                         * card-wide overlay link so a click on a badge filters
                         * instead of opening the edition.
                         */
                        $sk_badges = array(
                            'pill-edition'  => array( 'terms' => $editions, 'icon' => 'dashicons-location' ),
                            'pill-language' => array( 'terms' => $languages, 'icon' => 'dashicons-translation' ),
                        );

                        foreach ( $sk_badges as $sk_badge_class => $sk_badge ) :
                            if ( empty( $sk_badge['terms'] ) || is_wp_error( $sk_badge['terms'] ) ) {
                                continue;
                            }

                            foreach ( $sk_badge['terms'] as $sk_term ) :
                                $sk_term_link = get_term_link( $sk_term );
                                $sk_tag       = is_wp_error( $sk_term_link ) ? 'span' : 'a';
                                ?>
                                <<?php echo esc_html( $sk_tag ); ?>
                                    class="epaper-tax-pill <?php echo esc_attr( $sk_badge_class ); ?>"
                                    <?php if ( 'a' === $sk_tag ) : ?>
                                        href="<?php echo esc_url( $sk_term_link ); ?>"
                                    <?php endif; ?>>
                                    <span class="dashicons <?php echo esc_attr( $sk_badge['icon'] ); ?>" aria-hidden="true"></span>
                                    <?php echo esc_html( $sk_term->name ); ?>
                                </<?php echo esc_html( $sk_tag ); ?>>
                                <?php
                            endforeach;
                        endforeach;
                        ?>
                    </span>
                </div>

                <h3 class="epaper-card-title"><?php echo esc_html( $title ); ?></h3>
            </div>
        </article>
        <?php
        return ob_get_clean();
    }

    /**
     * [sk_epaper id="123"] - single ePaper viewer.
     *
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public static function shortcode_handler( $atts ) {
        $atts = shortcode_atts(
            array(
                'id' => 0,
            ),
            $atts,
            'sk_epaper'
        );

        $post_id = absint( $atts['id'] );

        if ( ! $post_id || 'epaper' !== get_post_type( $post_id ) ) {
            return '<p class="sk-epaper-error">' . esc_html__( 'Invalid ePaper ID.', 'sk-epaper-manager' ) . '</p>';
        }

        return self::render_viewer( $post_id );
    }

    /**
     * [sk_epaper_archive] - grid of recent ePapers.
     *
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public static function archive_shortcode_handler( $atts ) {
        $atts = shortcode_atts(
            array(
                'count'    => 6,
                'edition'  => '',
                'language' => '',
            ),
            $atts,
            'sk_epaper_archive'
        );

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

        wp_enqueue_style( 'dashicons' );

        if ( ! wp_style_is( SK_EPAPER_PREFIX . 'style', 'enqueued' ) ) {
            self::enqueue_styles();
        }

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
}
