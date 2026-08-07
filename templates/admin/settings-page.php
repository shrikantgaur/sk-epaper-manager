<?php
/**
 * Settings Page
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

if ( isset( $_POST['sk_epaper_save_settings_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sk_epaper_save_settings_nonce'] ) ), 'sk_epaper_save_settings' ) ) {
    $sk_enable_download = isset( $_POST['sk_epaper_enable_download'] ) ? 1 : 0;
    $sk_enable_print    = isset( $_POST['sk_epaper_enable_print'] ) ? 1 : 0;
    $sk_enable_zoom     = isset( $_POST['sk_epaper_enable_zoom'] ) ? 1 : 0;
    $sk_header_bg       = isset( $_POST['sk_epaper_header_bg'] ) ? sanitize_hex_color( wp_unslash( $_POST['sk_epaper_header_bg'] ) ) : '';
    $sk_primary_color   = isset( $_POST['sk_epaper_primary_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['sk_epaper_primary_color'] ) ) : '';

    update_option( 'sk_epaper_enable_download', $sk_enable_download );
    update_option( 'sk_epaper_enable_print', $sk_enable_print );
    update_option( 'sk_epaper_enable_zoom', $sk_enable_zoom );
    if ( $sk_header_bg ) {
        update_option( 'sk_epaper_header_bg', $sk_header_bg );
    }
    if ( $sk_primary_color ) {
        update_option( 'sk_epaper_primary_color', $sk_primary_color );
    }

    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved successfully.', 'sk-epaper-manager' ) . '</p></div>';
}

$sk_enable_download = get_option( 'sk_epaper_enable_download', 1 );
$sk_enable_print    = get_option( 'sk_epaper_enable_print', 1 );
$sk_enable_zoom     = get_option( 'sk_epaper_enable_zoom', 1 );
$sk_header_bg       = get_option( 'sk_epaper_header_bg', '#000000' );
$sk_primary_color   = get_option( 'sk_epaper_primary_color', '#000000' );
?>

<div class="wrap sk-epaper-admin-wrap">
    <div class="sk-admin-header-flex">
        <div>
            <h1 class="wp-heading-inline">
                <span class="dashicons dashicons-admin-settings sk-header-icon"></span>
                <?php esc_html_e( 'ePaper Viewer Settings', 'sk-epaper-manager' ); ?>
            </h1>
            <p class="description">
                <?php esc_html_e( 'Customize viewer toolbar controls, buttons, and brand color theme.', 'sk-epaper-manager' ); ?>
            </p>
        </div>
    </div>

    <form method="post" action="">
        <?php wp_nonce_field( 'sk_epaper_save_settings', 'sk_epaper_save_settings_nonce' ); ?>

        <div class="sk-dashboard-card" style="max-width: 800px; margin-top: 20px;">
            <h2><span class="dashicons dashicons-controls-play"></span>
                <?php esc_html_e( 'Toolbar Controls & Color Customization', 'sk-epaper-manager' ); ?></h2>

            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Download Button', 'sk-epaper-manager' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="sk_epaper_enable_download" value="1" <?php checked( $sk_enable_download, 1 ); ?>>
                            <?php esc_html_e( 'Allow visitors to download ePaper pages', 'sk-epaper-manager' ); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Print Buttons', 'sk-epaper-manager' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="sk_epaper_enable_print" value="1" <?php checked( $sk_enable_print, 1 ); ?>>
                            <?php esc_html_e( 'Allow visitors to print current or all pages', 'sk-epaper-manager' ); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Zoom Controls', 'sk-epaper-manager' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="sk_epaper_enable_zoom" value="1" <?php checked( $sk_enable_zoom, 1 ); ?>>
                            <?php esc_html_e( 'Show Zoom In / Zoom Out buttons', 'sk-epaper-manager' ); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Header Background Color', 'sk-epaper-manager' ); ?></th>
                    <td>
                        <input type="color" name="sk_epaper_header_bg" value="<?php echo esc_attr( $sk_header_bg ); ?>"
                            style="width: 60px; height: 35px; vertical-align: middle;">
                        <span
                            class="description"><?php esc_html_e( 'Select background color for the ePaper viewer header toolbar (Default: #000000).', 'sk-epaper-manager' ); ?></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Frontend Primary Brand Color', 'sk-epaper-manager' ); ?></th>
                    <td>
                        <input type="color" name="sk_epaper_primary_color"
                            value="<?php echo esc_attr( $sk_primary_color ); ?>"
                            style="width: 60px; height: 35px; vertical-align: middle;">
                        <span
                            class="description"><?php esc_html_e( 'Select primary accent color for frontend buttons, hovers, navigation arrows, and taxonomy badges (Default: #000000).', 'sk-epaper-manager' ); ?></span>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <input type="submit" class="button button-primary"
                    value="<?php esc_attr_e( 'Save Settings', 'sk-epaper-manager' ); ?>">
            </p>
        </div>
    </form>
</div>