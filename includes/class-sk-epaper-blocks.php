<?php
/**
 * Gutenberg blocks.
 *
 * Both blocks are server rendered through the same handlers the shortcodes use,
 * so markup can never diverge between the two. The shortcodes stay supported
 * exactly as before - the blocks are an addition, not a replacement.
 *
 * The editor script is deliberately plain JavaScript against the wp.* globals
 * rather than JSX, so the plugin needs no build step.
 *
 * @package SK_ePaper_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Registers the ePaper blocks.
 */
class SK_EPaper_Blocks {

    /**
     * Register block types.
     *
     * @return void
     */
    public static function register() {
        if ( ! function_exists( 'register_block_type' ) ) {
            return;
        }

        wp_register_script(
            SK_EPAPER_PREFIX . 'blocks',
            SK_EPAPER_URL . 'assets/admin/epaper-blocks.js',
            array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-data', 'wp-server-side-render' ),
            SK_EPAPER_VERSION,
            true
        );

        register_block_type(
            'sk/epaper-viewer',
            array(
                'api_version'     => 2,
                'editor_script'   => SK_EPAPER_PREFIX . 'blocks',
                'attributes'      => array(
                    'id' => array(
                        'type'    => 'number',
                        'default' => 0,
                    ),
                ),
                'render_callback' => array( __CLASS__, 'render_viewer_block' ),
            )
        );

        register_block_type(
            'sk/epaper-archive',
            array(
                'api_version'     => 2,
                'editor_script'   => SK_EPAPER_PREFIX . 'blocks',
                'attributes'      => array(
                    'count'    => array(
                        'type'    => 'number',
                        'default' => 6,
                    ),
                    'edition'  => array(
                        'type'    => 'string',
                        'default' => '',
                    ),
                    'language' => array(
                        'type'    => 'string',
                        'default' => '',
                    ),
                ),
                'render_callback' => array( __CLASS__, 'render_archive_block' ),
            )
        );
    }

    /**
     * Render the single ePaper block.
     *
     * @param array $attributes Block attributes.
     * @return string
     */
    public static function render_viewer_block( $attributes ) {
        $post_id = isset( $attributes['id'] ) ? absint( $attributes['id'] ) : 0;

        if ( ! $post_id ) {
            return '<p class="sk-epaper-error">' . esc_html__( 'Select an ePaper edition to display.', 'sk-epaper-manager' ) . '</p>';
        }

        return SK_EPaper_Renderer::shortcode_handler( array( 'id' => $post_id ) );
    }

    /**
     * Render the ePaper archive block.
     *
     * @param array $attributes Block attributes.
     * @return string
     */
    public static function render_archive_block( $attributes ) {
        return SK_EPaper_Renderer::archive_shortcode_handler(
            array(
                'count'    => isset( $attributes['count'] ) ? absint( $attributes['count'] ) : 6,
                'edition'  => isset( $attributes['edition'] ) ? sanitize_text_field( $attributes['edition'] ) : '',
                'language' => isset( $attributes['language'] ) ? sanitize_text_field( $attributes['language'] ) : '',
            )
        );
    }

    /**
     * Whether a post uses one of the plugin's blocks.
     *
     * @param int|WP_Post|null $post Post to inspect.
     * @return bool
     */
    public static function post_has_block( $post = null ) {
        if ( ! function_exists( 'has_block' ) ) {
            return false;
        }

        return has_block( 'sk/epaper-viewer', $post ) || has_block( 'sk/epaper-archive', $post );
    }
}
