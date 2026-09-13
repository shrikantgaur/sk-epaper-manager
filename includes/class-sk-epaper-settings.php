<?php
/**
 * Centralised option access.
 *
 * Option names are frozen: renaming them would silently reset every existing
 * install's saved configuration.
 *
 * @package SK_ePaper_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Reads plugin options with their documented defaults.
 */
class SK_EPaper_Settings {

    /**
     * Option name => default value.
     *
     * @var array
     */
    protected static $defaults = array(
        'sk_epaper_enable_download'   => 1,
        'sk_epaper_enable_print'      => 1,
        'sk_epaper_enable_zoom'       => 1,
        'sk_epaper_header_bg'         => '#000000',
        'sk_epaper_primary_color'     => '#000000',
        'sk_epaper_archive_per_page'  => 24,
        'sk_epaper_lazy_load'         => 1,
        'sk_epaper_show_thumbnails'   => 1,
        'sk_epaper_thumb_position'    => 'bottom',
        // 0 means "do not constrain" - let the theme's own container decide.
        'sk_epaper_content_width'     => 1240,
        'sk_epaper_show_related'      => 1,
        'sk_epaper_related_count'     => 3,
        // "iframe" keeps the behaviour every existing install already has.
        'sk_epaper_pdf_mode'          => 'iframe',
        // Existing installs keep the classic editor they are used to; the
        // migration stamps 1 only for brand new sites.
        'sk_epaper_use_block_editor'  => 0,
        // Deleting a plugin is a troubleshooting step; never destroy data by default.
        'sk_epaper_delete_data_on_uninstall' => 0,
    );

    /**
     * Get an option value, falling back to the registered default.
     *
     * @param string $name    Option name, with or without the sk_epaper_ prefix.
     * @param mixed  $default Optional explicit default.
     * @return mixed
     */
    public static function get( $name, $default = null ) {
        if ( 0 !== strpos( $name, 'sk_epaper_' ) ) {
            $name = 'sk_epaper_' . $name;
        }

        if ( null === $default ) {
            $default = isset( self::$defaults[ $name ] ) ? self::$defaults[ $name ] : '';
        }

        return get_option( $name, $default );
    }

    /**
     * All defaults, for the settings screen and uninstall routine.
     *
     * @return array
     */
    public static function defaults() {
        return self::$defaults;
    }

    /**
     * Settings group used by the options screen.
     */
    const GROUP = 'sk_epaper_settings';

    /**
     * Register every option with the Settings API.
     *
     * Option names are unchanged from the manual $_POST handling this replaces,
     * so saved configuration carries over untouched.
     *
     * @return void
     */
    public static function register() {
        $schema = array(
            'sk_epaper_enable_download'  => array( 'boolean', array( __CLASS__, 'sanitize_checkbox' ) ),
            'sk_epaper_enable_print'     => array( 'boolean', array( __CLASS__, 'sanitize_checkbox' ) ),
            'sk_epaper_enable_zoom'      => array( 'boolean', array( __CLASS__, 'sanitize_checkbox' ) ),
            'sk_epaper_lazy_load'        => array( 'boolean', array( __CLASS__, 'sanitize_checkbox' ) ),
            'sk_epaper_show_thumbnails'  => array( 'boolean', array( __CLASS__, 'sanitize_checkbox' ) ),
            'sk_epaper_thumb_position'   => array( 'string', array( __CLASS__, 'sanitize_thumb_position' ) ),
            'sk_epaper_content_width'    => array( 'integer', array( __CLASS__, 'sanitize_content_width' ) ),
            'sk_epaper_show_related'     => array( 'boolean', array( __CLASS__, 'sanitize_checkbox' ) ),
            'sk_epaper_related_count'    => array( 'integer', array( __CLASS__, 'sanitize_related_count' ) ),
            'sk_epaper_use_block_editor' => array( 'boolean', array( __CLASS__, 'sanitize_checkbox' ) ),
            'sk_epaper_delete_data_on_uninstall' => array( 'boolean', array( __CLASS__, 'sanitize_checkbox' ) ),
            'sk_epaper_archive_per_page' => array( 'integer', array( __CLASS__, 'sanitize_per_page' ) ),
            'sk_epaper_header_bg'        => array( 'string', array( __CLASS__, 'sanitize_color' ) ),
            'sk_epaper_primary_color'    => array( 'string', array( __CLASS__, 'sanitize_color' ) ),
            'sk_epaper_pdf_mode'         => array( 'string', array( __CLASS__, 'sanitize_pdf_mode' ) ),
        );

        foreach ( $schema as $name => $config ) {
            register_setting(
                self::GROUP,
                $name,
                array(
                    'type'              => $config[0],
                    'sanitize_callback' => $config[1],
                    'default'           => isset( self::$defaults[ $name ] ) ? self::$defaults[ $name ] : '',
                    'show_in_rest'      => false,
                )
            );
        }
    }

    /**
     * Checkboxes post nothing when unchecked, so the form pairs each one with a
     * hidden zero. Everything collapses to 1 or 0 here.
     *
     * @param mixed $value Raw value.
     * @return int
     */
    public static function sanitize_checkbox( $value ) {
        return empty( $value ) ? 0 : 1;
    }

    /**
     * Archive page size.
     *
     * @param mixed $value Raw value.
     * @return int
     */
    public static function sanitize_per_page( $value ) {
        $value = absint( $value );

        if ( $value < 1 ) {
            $value = 24;
        }

        return min( $value, 200 );
    }

    /**
     * Hex colour, falling back to the stored value rather than blanking it.
     *
     * @param mixed $value Raw value.
     * @return string
     */
    public static function sanitize_color( $value ) {
        $color = sanitize_hex_color( $value );

        return $color ? $color : '#000000';
    }

    /**
     * How many related editions to show under the reader.
     *
     * @param mixed $value Raw value.
     * @return int
     */
    public static function sanitize_related_count( $value ) {
        $value = absint( $value );

        if ( $value < 1 ) {
            $value = 3;
        }

        return min( $value, 24 );
    }

    /**
     * Maximum content width in pixels, or 0 to inherit the theme's container.
     *
     * @param mixed $value Raw value.
     * @return int
     */
    public static function sanitize_content_width( $value ) {
        $value = absint( $value );

        if ( 0 === $value ) {
            return 0;
        }

        return max( 320, min( $value, 3000 ) );
    }

    /**
     * Thumbnail rail placement.
     *
     * @param mixed $value Raw value.
     * @return string
     */
    public static function sanitize_thumb_position( $value ) {
        $value = sanitize_key( $value );

        return in_array( $value, array( 'bottom', 'left', 'right' ), true ) ? $value : 'bottom';
    }

    /**
     * PDF rendering mode.
     *
     * @param mixed $value Raw value.
     * @return string
     */
    public static function sanitize_pdf_mode( $value ) {
        $value = sanitize_key( $value );

        return in_array( $value, array( 'iframe', 'image', 'pdfjs' ), true ) ? $value : 'iframe';
    }

    /**
     * Whether a viewer toolbar control is enabled.
     *
     * @param string $control download|print|zoom.
     * @return bool
     */
    public static function is_enabled( $control ) {
        return (bool) self::get( 'enable_' . $control );
    }
}
