<?php
/**
 * Page deep links, the reader share bar and Open Graph tags.
 *
 * A reader can link straight to one page of an edition, either as
 * /epapers/{edition}/p/5/ or as ?epage=5. "page" and "paged" are deliberately
 * not used: WordPress already gives both a meaning on singular requests.
 *
 * Sharing is plain links - wa.me, facebook.com/sharer, x.com/intent - built on
 * the server and kept current by the reader script. No SDK, script or pixel
 * from any network is ever loaded.
 *
 * @package SK_ePaper_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Deep links, share bar, Open Graph and the "new in 2.1" share notice.
 */
class SK_EPaper_Share {

    const QUERY_VAR      = 'epage';
    const PLACEHOLDER    = '__EPAGE__';
    const NOTICE_OPTION  = 'sk_epaper_share_notice';
    const NOTICE_NONCE   = 'sk_epaper_share_notice';
    const NOTICE_ACTION  = 'sk_epaper_share_notice';

    /**
     * Every network the share bar knows about, in display order.
     *
     * @var string[]
     */
    protected static $networks = array( 'whatsapp', 'facebook', 'x', 'copy', 'native' );

    /**
     * Allowed network keys.
     *
     * @return string[]
     */
    public static function get_network_keys() {
        return self::$networks;
    }

    /**
     * Human readable network names, for the settings screen.
     *
     * @return array
     */
    public static function get_network_labels() {
        return array(
            'whatsapp' => __( 'WhatsApp', 'sk-epaper-manager' ),
            'facebook' => __( 'Facebook', 'sk-epaper-manager' ),
            'x'        => __( 'X (Twitter)', 'sk-epaper-manager' ),
            'copy'     => __( 'Copy link', 'sk-epaper-manager' ),
            'native'   => __( 'Device share menu (mobile, where supported)', 'sk-epaper-manager' ),
        );
    }

    /* ---------------------------------------------------------------------
       Deep links
    --------------------------------------------------------------------- */

    /**
     * The regex key of our rewrite rule, shared by registration and lookup.
     *
     * @return string
     */
    protected static function rule_regex() {
        return '^' . self::get_cpt_slug() . '/([^/]+)/p/([0-9]{1,4})/?$';
    }

    /**
     * Rewrite slug of the ePaper post type ("epapers" - frozen).
     *
     * @return string
     */
    protected static function get_cpt_slug() {
        $object = get_post_type_object( 'epaper' );

        if ( $object && ! empty( $object->rewrite['slug'] ) ) {
            return trim( $object->rewrite['slug'], '/' );
        }

        return 'epapers';
    }

    /**
     * Register /epapers/{edition}/p/{n}/.
     *
     * Only registers; flushing happens once per upgrade in
     * SK_EPaper_CPT::check_flush_rules() and on activation.
     *
     * @return void
     */
    public static function add_rewrite_rule() {
        add_rewrite_rule(
            self::rule_regex(),
            'index.php?epaper=$matches[1]&' . self::QUERY_VAR . '=$matches[2]',
            'top'
        );
    }

    /**
     * Make "epage" a public query var.
     *
     * @param array $vars Query vars.
     * @return array
     */
    public static function register_query_var( $vars ) {
        $vars[] = self::QUERY_VAR;

        return $vars;
    }

    /**
     * Whether pretty /p/N/ links will actually resolve on this site.
     *
     * Checked against the stored rules rather than assumed: on a site that
     * auto-updated and has not had an admin visit yet, the rules have not been
     * flushed, and a /p/N/ link would 404.
     *
     * @return bool
     */
    public static function has_pretty_rule() {
        if ( '' === (string) get_option( 'permalink_structure' ) ) {
            return false;
        }

        $rules = get_option( 'rewrite_rules' );

        return is_array( $rules ) && isset( $rules[ self::rule_regex() ] );
    }

    /**
     * URL of one page of an edition.
     *
     * @param int $post_id ePaper post ID.
     * @param int $page    1-based page number.
     * @return string
     */
    public static function get_page_url( $post_id, $page ) {
        $permalink = get_permalink( $post_id );
        $page      = absint( $page );

        if ( ! $permalink || $page <= 1 ) {
            return (string) $permalink;
        }

        return str_replace( self::PLACEHOLDER, (string) $page, self::get_page_url_template( $post_id ) );
    }

    /**
     * Page URL with a placeholder for the number, handed to the reader script
     * so it builds links exactly the way the server does.
     *
     * @param int $post_id ePaper post ID.
     * @return string
     */
    public static function get_page_url_template( $post_id ) {
        $permalink = (string) get_permalink( $post_id );

        if ( '' === $permalink ) {
            return '';
        }

        // Drafts and previews use ?p= style links, which the rule cannot match.
        if ( self::has_pretty_rule() && false === strpos( $permalink, '?' ) && 'publish' === get_post_status( $post_id ) ) {
            return user_trailingslashit( trailingslashit( $permalink ) . 'p/' . self::PLACEHOLDER );
        }

        return add_query_arg( self::QUERY_VAR, self::PLACEHOLDER, $permalink );
    }

    /**
     * Requested page for a viewer, 1-based, or 0 when none applies.
     *
     * Only the edition the request is actually for honours the deep link:
     * shortcode and block embeds elsewhere always open on page 1.
     *
     * @param int $post_id ePaper post ID being rendered.
     * @return int
     */
    public static function get_requested_page( $post_id ) {
        if ( ! self::is_main_viewer( $post_id ) ) {
            return 0;
        }

        return absint( get_query_var( self::QUERY_VAR ) );
    }

    /**
     * Whether this viewer is the edition the current request is for.
     *
     * @param int $post_id ePaper post ID.
     * @return bool
     */
    public static function is_main_viewer( $post_id ) {
        return is_singular( 'epaper' ) && (int) get_queried_object_id() === (int) $post_id;
    }

    /* ---------------------------------------------------------------------
       Share bar
    --------------------------------------------------------------------- */

    /**
     * Whether the share bar is switched on.
     *
     * @return bool
     */
    public static function is_enabled() {
        return (bool) SK_EPaper_Settings::get( 'enable_share' );
    }

    /**
     * Where the share controls appear: under the reader, as a Share button in
     * the (sticky) toolbar, or both.
     *
     * @return string bottom|toolbar|both
     */
    public static function get_position() {
        $position = SK_EPaper_Settings::get( 'share_position' );

        return in_array( $position, array( 'bottom', 'toolbar', 'both' ), true ) ? $position : 'both';
    }

    /**
     * Whether share controls should render in a given place.
     *
     * @param string $place bottom|toolbar.
     * @return bool
     */
    public static function shows_in( $place ) {
        if ( ! self::is_enabled() || ! self::get_enabled_networks() ) {
            return false;
        }

        $position = self::get_position();

        return 'both' === $position || $place === $position;
    }

    /**
     * Networks chosen in Settings, in display order.
     *
     * @return string[]
     */
    public static function get_enabled_networks() {
        $chosen = SK_EPaper_Settings::get( 'share_networks' );

        if ( ! is_array( $chosen ) ) {
            $chosen = array();
        }

        return array_values( array_intersect( self::$networks, $chosen ) );
    }

    /**
     * Plain text for titles and site names, which arrive HTML encoded.
     *
     * @param string $text Raw text.
     * @return string
     */
    protected static function plain( $text ) {
        return html_entity_decode( wp_strip_all_tags( (string) $text ), ENT_QUOTES, get_bloginfo( 'charset' ) );
    }

    /**
     * Translatable share text template: 1 = edition title, 2 = page, 3 = site.
     *
     * @return string
     */
    public static function get_text_template() {
        /* translators: 1: ePaper edition title, 2: Page number, 3: Site name. The page URL is added after this text. */
        return __( '%1$s – Page %2$d | %3$s', 'sk-epaper-manager' );
    }

    /**
     * Share text for one page (without the URL).
     *
     * @param int $post_id ePaper post ID.
     * @param int $page    1-based page.
     * @return string
     */
    public static function get_share_text( $post_id, $page ) {
        return sprintf(
            self::get_text_template(),
            self::plain( get_the_title( $post_id ) ),
            absint( $page ),
            self::plain( get_bloginfo( 'name' ) )
        );
    }

    /**
     * Network share URL. Mirrors shareHref() in epaper-script.js.
     *
     * @param string $network whatsapp|facebook|x.
     * @param string $url     Page URL.
     * @param string $text    Share text.
     * @return string
     */
    protected static function network_href( $network, $url, $text ) {
        switch ( $network ) {
            case 'whatsapp':
                return 'https://wa.me/?text=' . rawurlencode( $text . ' ' . $url );
            case 'facebook':
                return 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode( $url );
            case 'x':
                return 'https://x.com/intent/tweet?text=' . rawurlencode( $text ) . '&url=' . rawurlencode( $url );
        }

        return '';
    }

    /**
     * Inline SVG icon. Static markup only; nothing user supplied.
     *
     * @param string $name Icon key.
     * @return string
     */
    protected static function icon( $name ) {
        $paths = array(
            'whatsapp' => '<path d="M12 2.2a9.8 9.8 0 0 0-8.4 14.9L2.3 21.7l4.7-1.2A9.8 9.8 0 1 0 12 2.2z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M8.6 7.3c.2-.5.5-.5.8-.5h.6c.2 0 .4 0 .6.5l.8 1.9c.1.2.1.4 0 .6l-.5.7c-.1.2-.2.4 0 .6.4.8 1 1.5 1.7 2.1.6.5 1.2.8 1.6 1 .2.1.4.1.6-.1l.7-.8c.2-.2.4-.2.6-.1l1.8.9c.2.1.4.2.4.4 0 .4-.1 1-.5 1.5-.5.5-1.4.9-2.2.8-1-.1-2.6-.6-4.2-2.1-1.5-1.4-2.4-3-2.6-3.9-.2-.9.1-1.7.6-2.3z" fill="currentColor"/>',
            'facebook' => '<path d="M14 8h3V4h-3c-2.8 0-5 2.2-5 5v2H7v4h2v9h4v-9h3l1-4h-4V9c0-.6.4-1 1-1z" fill="currentColor"/>',
            'x'        => '<path d="M18.2 2.3h3.3l-7.2 8.3 8.5 11.2h-6.6l-5.2-6.8-6 6.8H1.7l7.7-8.8L1.3 2.3H8l4.7 6.2zm-1.1 17.5h1.8L7.1 4.1H5.1z" fill="currentColor"/>',
            'copy'     => '<path d="M10 13a5 5 0 0 0 7.1 0l3-3A5 5 0 0 0 13 2.9l-1.7 1.7M14 11a5 5 0 0 0-7.1 0l-3 3A5 5 0 0 0 11 21.1l1.7-1.7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
            'native'   => '<circle cx="18" cy="5" r="3" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="6" cy="12" r="3" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="18" cy="19" r="3" fill="none" stroke="currentColor" stroke-width="2"/><path d="M8.6 13.5l6.8 4M15.4 6.5l-6.8 4" fill="none" stroke="currentColor" stroke-width="2"/>',
        );

        if ( ! isset( $paths[ $name ] ) ) {
            return '';
        }

        return '<svg class="epaper-share-icon" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false">' . $paths[ $name ] . '</svg>';
    }

    /**
     * The share controls themselves, shared by the bar and the toolbar menu.
     *
     * Network links work without JavaScript (they point at the page the reader
     * opened on); Copy and the device share button need JavaScript and stay
     * hidden until the script reveals them.
     *
     * @param int         $post_id ePaper post ID.
     * @param int         $page    1-based page the viewer opens on.
     * @param string|null $url     Explicit URL (cards share the edition itself).
     * @param string|null $text    Explicit share text.
     * @return string
     */
    protected static function render_buttons( $post_id, $page, $url = null, $text = null ) {
        $url    = null === $url ? self::get_page_url( $post_id, $page ) : $url;
        $text   = null === $text ? self::get_share_text( $post_id, $page ) : $text;
        $labels = array(
            /* translators: Accessible label for a share link that opens in a new tab. */
            'whatsapp' => __( 'Share this page on WhatsApp (opens in a new tab)', 'sk-epaper-manager' ),
            /* translators: Accessible label for a share link that opens in a new tab. */
            'facebook' => __( 'Share this page on Facebook (opens in a new tab)', 'sk-epaper-manager' ),
            /* translators: Accessible label for a share link that opens in a new tab. */
            'x'        => __( 'Share this page on X (opens in a new tab)', 'sk-epaper-manager' ),
            'copy'     => __( 'Copy link to this page', 'sk-epaper-manager' ),
            'native'   => __( 'Share this page', 'sk-epaper-manager' ),
        );
        $titles = array(
            'whatsapp' => __( 'WhatsApp', 'sk-epaper-manager' ),
            'facebook' => __( 'Facebook', 'sk-epaper-manager' ),
            'x'        => __( 'X', 'sk-epaper-manager' ),
            'copy'     => __( 'Copy link', 'sk-epaper-manager' ),
            'native'   => __( 'Share', 'sk-epaper-manager' ),
        );

        $html = '';

        foreach ( self::get_enabled_networks() as $network ) {
            $class = 'epaper-share-btn epaper-share-' . $network;

            if ( in_array( $network, array( 'copy', 'native' ), true ) ) {
                $html .= '<button type="button" class="' . esc_attr( $class ) . '" data-share="' . esc_attr( $network ) . '"'
                    . ' aria-label="' . esc_attr( $labels[ $network ] ) . '" title="' . esc_attr( $titles[ $network ] ) . '" hidden>'
                    . self::icon( $network ) . '</button>';
                continue;
            }

            $html .= '<a class="' . esc_attr( $class ) . '" data-share="' . esc_attr( $network ) . '"'
                . ' href="' . esc_url( self::network_href( $network, $url, $text ) ) . '"'
                . ' target="_blank" rel="noopener noreferrer nofollow"'
                . ' aria-label="' . esc_attr( $labels[ $network ] ) . '" title="' . esc_attr( $titles[ $network ] ) . '">'
                . self::icon( $network ) . '</a>';
        }

        return $html;
    }

    /**
     * Data attributes the reader script uses to rebuild the share text.
     *
     * @param int $post_id ePaper post ID.
     * @return string
     */
    protected static function data_attributes( $post_id ) {
        return ' data-title="' . esc_attr( self::plain( get_the_title( $post_id ) ) ) . '"'
            . ' data-site="' . esc_attr( self::plain( get_bloginfo( 'name' ) ) ) . '"';
    }

    /**
     * Share bar under the reader.
     *
     * @param int $post_id ePaper post ID.
     * @param int $page    1-based page the viewer opens on.
     * @return string
     */
    public static function render_bar( $post_id, $page ) {
        if ( ! self::shows_in( 'bottom' ) ) {
            return '';
        }

        $html  = '<div class="epaper-share-bar epaper-share-group" role="group" aria-label="' . esc_attr__( 'Share this page', 'sk-epaper-manager' ) . '"' . self::data_attributes( $post_id ) . '>';
        $html .= '<span class="epaper-share-label" aria-hidden="true">' . esc_html__( 'Share', 'sk-epaper-manager' ) . '</span>';
        $html .= self::render_buttons( $post_id, $page );
        $html .= '<span class="epaper-share-status" role="status" aria-live="polite"></span>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Share button for the reader toolbar, with a small menu of the same
     * controls. The toolbar stays on screen while reading, so sharing is
     * always one tap away, wherever the reader is on the page.
     *
     * Without JavaScript the menu cannot open, so the button is hidden and the
     * script reveals it.
     *
     * @param int $post_id ePaper post ID.
     * @param int $page    1-based page the viewer opens on.
     * @return string
     */
    public static function render_toolbar_button( $post_id, $page ) {
        static $instance = 0;

        if ( ! self::shows_in( 'toolbar' ) ) {
            return '';
        }

        $instance++;
        $menu_id = 'epaper-share-menu-' . absint( $post_id ) . '-' . $instance;

        $html  = '<div class="epaper-share-menu">';
        $html .= '<button type="button" class="sk-control-button epaper-share-toggle" aria-haspopup="true" aria-expanded="false"'
            . ' aria-controls="' . esc_attr( $menu_id ) . '"'
            . ' title="' . esc_attr__( 'Share', 'sk-epaper-manager' ) . '"'
            . ' aria-label="' . esc_attr__( 'Share this page', 'sk-epaper-manager' ) . '" hidden>'
            . self::icon( 'native' ) . '</button>';
        $html .= '<div class="epaper-share-popover epaper-share-group" id="' . esc_attr( $menu_id ) . '" role="group"'
            . ' aria-label="' . esc_attr__( 'Share this page', 'sk-epaper-manager' ) . '"' . self::data_attributes( $post_id ) . ' hidden>';
        $html .= self::render_buttons( $post_id, $page );
        $html .= '<span class="epaper-share-status" role="status" aria-live="polite"></span>';
        $html .= '</div></div>';

        return $html;
    }

    /**
     * Whether archive and "More Editions" cards get a share button.
     *
     * @return bool
     */
    public static function shows_on_cards() {
        return self::is_enabled() && (bool) SK_EPaper_Settings::get( 'share_on_cards' ) && self::get_enabled_networks();
    }

    /**
     * Share text for a whole edition (cards have no current page).
     *
     * @param int $post_id ePaper post ID.
     * @return string
     */
    public static function get_card_share_text( $post_id ) {
        return sprintf(
            /* translators: 1: ePaper edition title, 2: Site name. The edition URL is added after this text. */
            __( '%1$s | %2$s', 'sk-epaper-manager' ),
            self::plain( get_the_title( $post_id ) ),
            self::plain( get_bloginfo( 'name' ) )
        );
    }

    /**
     * Share button and menu for one edition card.
     *
     * The card is a single stretched link; this sits above it so a tap on
     * Share never opens the edition. Links are fully rendered on the server
     * (a card never changes page), so the script only opens and closes the
     * menu and handles Copy / device share.
     *
     * @param int $post_id ePaper post ID.
     * @return string
     */
    public static function render_card_share( $post_id ) {
        static $instance = 0;

        if ( ! self::shows_on_cards() ) {
            return '';
        }

        $instance++;
        $menu_id = 'epaper-card-share-' . absint( $post_id ) . '-' . $instance;
        $url     = (string) get_permalink( $post_id );
        $text    = self::get_card_share_text( $post_id );
        $title   = self::plain( get_the_title( $post_id ) );

        $html  = '<div class="epaper-card-share">';
        $html .= '<button type="button" class="epaper-card-share-toggle" aria-haspopup="true" aria-expanded="false"'
            . ' aria-controls="' . esc_attr( $menu_id ) . '"'
            /* translators: %s: ePaper edition title */
            . ' aria-label="' . esc_attr( sprintf( __( 'Share %s', 'sk-epaper-manager' ), $title ) ) . '"'
            . ' title="' . esc_attr__( 'Share', 'sk-epaper-manager' ) . '" hidden>'
            . self::icon( 'native' ) . '</button>';
        $html .= '<div class="epaper-share-popover epaper-card-share-popover" id="' . esc_attr( $menu_id ) . '" role="group"'
            /* translators: %s: ePaper edition title */
            . ' aria-label="' . esc_attr( sprintf( __( 'Share %s', 'sk-epaper-manager' ), $title ) ) . '"'
            . ' data-url="' . esc_url( $url ) . '" data-text="' . esc_attr( $text ) . '" hidden>';
        $html .= self::render_buttons( $post_id, 1, $url, $text );
        $html .= '<span class="epaper-share-status" role="status" aria-live="polite"></span>';
        $html .= '</div></div>';

        return $html;
    }

    /**
     * Strings and formats for the reader script.
     *
     * @param string $handle Script handle.
     * @return void
     */
    public static function localize( $handle ) {
        wp_localize_script(
            $handle,
            'sk_epaper_share',
            array(
                'placeholder' => self::PLACEHOLDER,
                'text'        => self::get_text_template(),
                'i18n'        => array(
                    'copied'     => __( 'Link copied', 'sk-epaper-manager' ),
                    'copyFailed' => __( 'Could not copy the link', 'sk-epaper-manager' ),
                ),
            )
        );
    }

    /* ---------------------------------------------------------------------
       Open Graph
    --------------------------------------------------------------------- */

    /**
     * Name of an active SEO plugin that writes its own Open Graph tags.
     *
     * @return string Empty when none is detected.
     */
    public static function detect_seo_plugin() {
        if ( defined( 'WPSEO_VERSION' ) ) {
            return 'Yoast SEO';
        }

        if ( defined( 'RANK_MATH_VERSION' ) || class_exists( 'RankMath' ) ) {
            return 'Rank Math';
        }

        if ( defined( 'AIOSEO_VERSION' ) || function_exists( 'aioseo' ) ) {
            return 'All in One SEO';
        }

        if ( defined( 'SEOPRESS_VERSION' ) ) {
            return 'SEOPress';
        }

        if ( defined( 'THE_SEO_FRAMEWORK_VERSION' ) ) {
            return 'The SEO Framework';
        }

        if ( class_exists( 'Jetpack' ) && apply_filters( 'jetpack_enable_open_graph', false ) ) {
            return 'Jetpack';
        }

        return '';
    }

    /**
     * Best image for one page: the page itself, then the featured image.
     *
     * @param int $post_id ePaper post ID.
     * @param int $page    1-based page.
     * @return array|false Result of wp_get_attachment_image_src(), or false.
     */
    protected static function get_page_image( $post_id, $page ) {
        $images = SK_EPaper_Renderer::get_images( $post_id );
        $index  = max( 0, min( count( $images ), max( 1, $page ) ) - 1 );

        if ( isset( $images[ $index ] ) ) {
            foreach ( array( 'large', 'medium_large', 'full' ) as $size ) {
                $src = wp_get_attachment_image_src( $images[ $index ], $size );

                if ( ! empty( $src[0] ) ) {
                    return $src;
                }
            }
        }

        $thumb_id = get_post_thumbnail_id( $post_id );

        if ( $thumb_id ) {
            $src = wp_get_attachment_image_src( $thumb_id, 'large' );

            if ( ! empty( $src[0] ) ) {
                return $src;
            }
        }

        return false;
    }

    /**
     * Print Open Graph tags on single editions, unless an SEO plugin does.
     *
     * For a deep link the title, image and URL describe that page, so a
     * WhatsApp or Facebook preview shows the page that was actually shared.
     * The canonical link is left alone: it stays the plain permalink.
     *
     * @return void
     */
    public static function output_og_tags() {
        if ( ! is_singular( 'epaper' ) ) {
            return;
        }

        $post_id = (int) get_queried_object_id();
        $output  = (bool) SK_EPaper_Settings::get( 'output_og' ) && '' === self::detect_seo_plugin();

        /**
         * Filter whether the plugin prints Open Graph tags on an edition.
         *
         * @param bool $output  Default decision.
         * @param int  $post_id ePaper post ID.
         */
        if ( ! apply_filters( 'sk_epaper_output_og_tags', $output, $post_id ) ) {
            return;
        }

        $total = count( SK_EPaper_Renderer::get_images( $post_id ) );
        $page  = min( self::get_requested_page( $post_id ), $total );
        $title = self::plain( get_the_title( $post_id ) );

        if ( $page > 1 ) {
            /* translators: 1: ePaper edition title, 2: Page number */
            $title = sprintf( __( '%1$s – Page %2$d', 'sk-epaper-manager' ), $title, $page );
        }

        $tags = array(
            'og:type'      => 'article',
            'og:title'     => $title,
            'og:url'       => self::get_page_url( $post_id, $page ),
            'og:site_name' => self::plain( get_bloginfo( 'name' ) ),
        );

        $excerpt = has_excerpt( $post_id ) ? self::plain( get_the_excerpt( $post_id ) ) : '';

        if ( '' !== $excerpt ) {
            $tags['og:description'] = $excerpt;
        }

        $image = self::get_page_image( $post_id, max( 1, $page ) );

        if ( $image ) {
            $tags['og:image'] = $image[0];

            if ( ! empty( $image[1] ) && ! empty( $image[2] ) ) {
                $tags['og:image:width']  = (int) $image[1];
                $tags['og:image:height'] = (int) $image[2];
            }
        }

        echo "\n<!-- SK ePaper Manager: Open Graph -->\n";

        foreach ( $tags as $property => $content ) {
            $value = ( 'og:url' === $property || 'og:image' === $property ) ? esc_url( $content ) : esc_attr( $content );
            echo '<meta property="' . esc_attr( $property ) . '" content="' . $value . '" />' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped just above.
        }

        if ( $image ) {
            echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
        }
    }

    /* ---------------------------------------------------------------------
       Defaults and the one-time "new in 2.1" notice
    --------------------------------------------------------------------- */

    /**
     * Decide the share bar default the first time 2.1 runs.
     *
     * New sites start with it on. Sites upgrading from 2.0.x start with it off
     * - nothing new appears on their public pages unannounced - and get a
     * one-time notice offering to switch it on. A 2.0.x site is recognised by
     * the stored schema version, which every 2.0.x install has.
     *
     * Runs on activation (before migrations stamp the version on a fresh
     * install) and early on admin_init for sites that updated in place.
     *
     * @return void
     */
    public static function maybe_set_defaults() {
        if ( false !== get_option( 'sk_epaper_enable_share', false ) ) {
            return;
        }

        $existing = false !== get_option( 'sk_epaper_db_version', false );

        if ( $existing ) {
            add_option( 'sk_epaper_enable_share', 0 );
            add_option( self::NOTICE_OPTION, 'pending', '', 'no' );
        } else {
            add_option( 'sk_epaper_enable_share', 1 );
        }
    }

    /**
     * Whether the share notice should show on this request.
     *
     * @return bool
     */
    public static function notice_should_show() {
        if ( 'pending' !== get_option( self::NOTICE_OPTION, '' ) ) {
            return false;
        }

        if ( self::is_enabled() ) {
            return false;
        }

        return current_user_can( 'manage_options' ) && SK_EPaper_Feedback::is_allowed_screen();
    }

    /**
     * The share notice takes priority: never show both on one screen load.
     *
     * @param bool $show Whether the review notice would show.
     * @return bool
     */
    public static function suppress_review_notice( $show ) {
        return $show && ! self::notice_should_show();
    }

    /**
     * Store an answer to the share notice.
     *
     * @param string $choice enable|dismiss.
     * @return bool
     */
    public static function apply_notice_choice( $choice ) {
        if ( 'enable' === $choice ) {
            update_option( 'sk_epaper_enable_share', 1 );
            update_option( self::NOTICE_OPTION, 'enabled', false );
            return true;
        }

        if ( 'dismiss' === $choice ) {
            update_option( self::NOTICE_OPTION, 'dismissed', false );
            return true;
        }

        return false;
    }

    /**
     * AJAX handler for the share notice.
     *
     * @return void
     */
    public static function ajax_notice_choice() {
        check_ajax_referer( self::NOTICE_NONCE, 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'sk-epaper-manager' ) ), 403 );
        }

        $choice = isset( $_POST['choice'] ) ? sanitize_key( wp_unslash( $_POST['choice'] ) ) : '';

        if ( ! self::apply_notice_choice( $choice ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid request.', 'sk-epaper-manager' ) ), 400 );
        }

        wp_send_json_success();
    }

    /**
     * No-JavaScript fallback for the share notice.
     *
     * @return void
     */
    public static function handle_notice_fallback() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- verified below.
        if ( ! isset( $_GET['sk_epaper_share_notice'] ) || ! current_user_can( 'manage_options' ) ) {
            return;
        }

        check_admin_referer( self::NOTICE_NONCE );

        $choice = sanitize_key( wp_unslash( $_GET['sk_epaper_share_notice'] ) );

        if ( ! self::apply_notice_choice( $choice ) ) {
            return;
        }

        $back = remove_query_arg( array( 'sk_epaper_share_notice', '_wpnonce' ) );

        if ( 'enable' === $choice ) {
            $back = add_query_arg( 'sk_epaper_share_enabled', '1', $back );
        }

        wp_safe_redirect( $back );
        exit;
    }

    /**
     * Notice script, only when the notice is on screen.
     *
     * @return void
     */
    public static function enqueue_notice_assets() {
        if ( ! self::notice_should_show() ) {
            return;
        }

        wp_enqueue_script(
            SK_EPAPER_PREFIX . 'notice',
            SK_EPAPER_URL . 'assets/admin/epaper-notice.js',
            array( 'wp-a11y' ),
            SK_EPAPER_VERSION,
            true
        );

        wp_localize_script(
            SK_EPAPER_PREFIX . 'notice',
            'sk_epaper_share_notice',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'action'   => self::NOTICE_ACTION,
                'nonce'    => wp_create_nonce( self::NOTICE_NONCE ),
            )
        );
    }

    /**
     * Print the share notice, or the confirmation after a no-JS "Enable".
     *
     * @return void
     */
    public static function render_notice() {
        $settings_url = admin_url( 'edit.php?post_type=epaper&page=sk-epaper-settings#sk-epaper-sharing' );

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only.
        if ( isset( $_GET['sk_epaper_share_enabled'] ) && current_user_can( 'manage_options' ) && self::is_enabled() ) {
            echo '<div class="notice notice-success is-dismissible"><p>'
                . esc_html__( 'Share bar enabled.', 'sk-epaper-manager' )
                . ' <a href="' . esc_url( $settings_url ) . '">' . esc_html__( 'Choose networks in Settings', 'sk-epaper-manager' ) . '</a></p></div>';
        }

        if ( ! self::notice_should_show() ) {
            return;
        }

        $enable_url  = wp_nonce_url( add_query_arg( 'sk_epaper_share_notice', 'enable' ), self::NOTICE_NONCE );
        $dismiss_url = wp_nonce_url( add_query_arg( 'sk_epaper_share_notice', 'dismiss' ), self::NOTICE_NONCE );
        ?>
        <div class="notice notice-info is-dismissible sk-epaper-share-notice" id="sk-epaper-share-notice" role="region" aria-labelledby="sk-epaper-share-notice-title">
            <div class="sk-share-notice-body">
                <p class="sk-review-notice-title" id="sk-epaper-share-notice-title">
                    <strong><?php esc_html_e( 'New in 2.1: share buttons and page links for your ePaper readers', 'sk-epaper-manager' ); ?></strong>
                </p>
                <p>
                    <?php esc_html_e( 'Readers can share the exact page they are on to WhatsApp, Facebook and X, or copy a link that opens the edition on that page. The share bar is off on your site until you switch it on.', 'sk-epaper-manager' ); ?>
                </p>
                <p class="sk-review-notice-actions">
                    <a class="button button-primary" data-sk-share="enable" role="button" href="<?php echo esc_url( $enable_url ); ?>">
                        <?php esc_html_e( 'Enable share bar', 'sk-epaper-manager' ); ?>
                    </a>
                    <a class="button-link sk-review-secondary" data-sk-share="dismiss" role="button" href="<?php echo esc_url( $dismiss_url ); ?>">
                        <?php esc_html_e( 'Dismiss', 'sk-epaper-manager' ); ?>
                    </a>
                </p>
            </div>
            <div class="sk-share-notice-done" hidden>
                <p>
                    <?php esc_html_e( 'Share bar enabled.', 'sk-epaper-manager' ); ?>
                    <a href="<?php echo esc_url( $settings_url ); ?>"><?php esc_html_e( 'Choose networks in Settings', 'sk-epaper-manager' ); ?></a>
                </p>
            </div>
        </div>
        <?php
    }
}
