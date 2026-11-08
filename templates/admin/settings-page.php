<?php
/**
 * Settings screen.
 *
 * Saving runs through the Settings API (options.php). Option names are the same
 * ones the manual handler used, so existing configuration is preserved.
 *
 * @package SK_ePaper_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$sk_epaper_enable_download   = SK_EPaper_Settings::get( 'enable_download' );
$sk_epaper_enable_print      = SK_EPaper_Settings::get( 'enable_print' );
$sk_epaper_enable_zoom       = SK_EPaper_Settings::get( 'enable_zoom' );
$sk_epaper_archive_per_page  = absint( SK_EPaper_Settings::get( 'archive_per_page' ) );
$sk_epaper_lazy_load         = SK_EPaper_Settings::get( 'lazy_load' );
$sk_epaper_show_thumbnails   = SK_EPaper_Settings::get( 'show_thumbnails' );
$sk_epaper_thumb_position    = SK_EPaper_Settings::get( 'thumb_position' );
$sk_epaper_content_width     = absint( SK_EPaper_Settings::get( 'content_width' ) );
$sk_epaper_show_related      = SK_EPaper_Settings::get( 'show_related' );
$sk_epaper_related_count     = absint( SK_EPaper_Settings::get( 'related_count' ) );
$sk_epaper_pdf_mode          = SK_EPaper_Settings::get( 'pdf_mode' );
$sk_epaper_use_block_editor  = SK_EPaper_Settings::get( 'use_block_editor' );
$sk_epaper_header_bg         = SK_EPaper_Settings::get( 'header_bg' );
$sk_epaper_primary_color     = SK_EPaper_Settings::get( 'primary_color' );
$sk_epaper_delete_on_uninstall = SK_EPaper_Settings::get( 'delete_data_on_uninstall' );
$sk_epaper_pdfjs_available   = SK_EPaper_Renderer::pdfjs_available();
$sk_epaper_enable_share      = SK_EPaper_Settings::get( 'enable_share' );
$sk_epaper_share_networks    = (array) SK_EPaper_Settings::get( 'share_networks' );
$sk_epaper_output_og         = SK_EPaper_Settings::get( 'output_og' );
$sk_epaper_share_position    = SK_EPaper_Share::get_position();
$sk_epaper_share_on_cards    = SK_EPaper_Settings::get( 'share_on_cards' );
$sk_epaper_seo_plugin        = SK_EPaper_Share::detect_seo_plugin();
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

    <?php
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if ( isset( $_GET['settings-updated'] ) ) {
        add_settings_error(
            'sk_epaper_messages',
            'sk_epaper_settings_saved',
            __( 'Settings saved successfully.', 'sk-epaper-manager' ),
            'updated'
        );
    }

    settings_errors( 'sk_epaper_messages' );
    ?>

    <form method="post" action="options.php">
        <?php settings_fields( SK_EPaper_Settings::GROUP ); ?>

        <div class="sk-settings-grid">

        <div class="sk-dashboard-card">
            <h2><span class="dashicons dashicons-controls-play"></span>
                <?php esc_html_e( 'Reader Controls', 'sk-epaper-manager' ); ?></h2>

            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Download Button', 'sk-epaper-manager' ); ?></th>
                    <td>
                        <input type="hidden" name="sk_epaper_enable_download" value="0">
                        <label>
                            <input type="checkbox" name="sk_epaper_enable_download" value="1" <?php checked( $sk_epaper_enable_download, 1 ); ?>>
                            <?php esc_html_e( 'Allow visitors to download ePaper pages', 'sk-epaper-manager' ); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e( 'Note: this hides the button only. Uploaded files stay reachable at their direct media URLs.', 'sk-epaper-manager' ); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Print Buttons', 'sk-epaper-manager' ); ?></th>
                    <td>
                        <input type="hidden" name="sk_epaper_enable_print" value="0">
                        <label>
                            <input type="checkbox" name="sk_epaper_enable_print" value="1" <?php checked( $sk_epaper_enable_print, 1 ); ?>>
                            <?php esc_html_e( 'Allow visitors to print current or all pages', 'sk-epaper-manager' ); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Zoom Controls', 'sk-epaper-manager' ); ?></th>
                    <td>
                        <input type="hidden" name="sk_epaper_enable_zoom" value="0">
                        <label>
                            <input type="checkbox" name="sk_epaper_enable_zoom" value="1" <?php checked( $sk_epaper_enable_zoom, 1 ); ?>>
                            <?php esc_html_e( 'Show Zoom In / Zoom Out buttons', 'sk-epaper-manager' ); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Page Thumbnails', 'sk-epaper-manager' ); ?></th>
                    <td>
                        <input type="hidden" name="sk_epaper_show_thumbnails" value="0">
                        <label>
                            <input type="checkbox" name="sk_epaper_show_thumbnails" value="1" <?php checked( $sk_epaper_show_thumbnails, 1 ); ?>>
                            <?php esc_html_e( 'Show the page thumbnail rail for jumping between pages', 'sk-epaper-manager' ); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Related Editions', 'sk-epaper-manager' ); ?></th>
                    <td>
                        <input type="hidden" name="sk_epaper_show_related" value="0">
                        <label>
                            <input type="checkbox" name="sk_epaper_show_related" value="1" <?php checked( $sk_epaper_show_related, 1 ); ?>>
                            <?php esc_html_e( 'Show a "More Editions" row below the reader', 'sk-epaper-manager' ); ?>
                        </label>
                        <p>
                            <input type="number" name="sk_epaper_related_count" min="1" max="24" step="1"
                                value="<?php echo esc_attr( $sk_epaper_related_count ); ?>" class="small-text">
                            <span class="description"><?php esc_html_e( 'How many to show, three per row. Matched first by Edition, then Language, then most recent.', 'sk-epaper-manager' ); ?></span>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Thumbnail Position', 'sk-epaper-manager' ); ?></th>
                    <td>
                        <select name="sk_epaper_thumb_position">
                            <option value="bottom" <?php selected( $sk_epaper_thumb_position, 'bottom' ); ?>><?php esc_html_e( 'Below the reader', 'sk-epaper-manager' ); ?></option>
                            <option value="left" <?php selected( $sk_epaper_thumb_position, 'left' ); ?>><?php esc_html_e( 'Left side', 'sk-epaper-manager' ); ?></option>
                            <option value="right" <?php selected( $sk_epaper_thumb_position, 'right' ); ?>><?php esc_html_e( 'Right side', 'sk-epaper-manager' ); ?></option>
                        </select>
                        <span class="description"><?php esc_html_e( 'Side rails automatically drop below the reader on small screens.', 'sk-epaper-manager' ); ?></span>
                    </td>
                </tr>
            </table>
        </div>

        <div class="sk-dashboard-card">
            <h2><span class="dashicons dashicons-performance"></span>
                <?php esc_html_e( 'Performance', 'sk-epaper-manager' ); ?></h2>

            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Lazy Load Pages', 'sk-epaper-manager' ); ?></th>
                    <td>
                        <input type="hidden" name="sk_epaper_lazy_load" value="0">
                        <label>
                            <input type="checkbox" name="sk_epaper_lazy_load" value="1" <?php checked( $sk_epaper_lazy_load, 1 ); ?>>
                            <?php esc_html_e( 'Load pages as the reader moves through them instead of all at once (strongly recommended)', 'sk-epaper-manager' ); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'ePapers Per Archive Page', 'sk-epaper-manager' ); ?></th>
                    <td>
                        <input type="number" name="sk_epaper_archive_per_page" min="1" max="200" step="1"
                            value="<?php echo esc_attr( $sk_epaper_archive_per_page ); ?>" class="small-text">
                        <span class="description"><?php esc_html_e( 'How many editions to show on the ePaper archive and taxonomy pages before paginating (Default: 24).', 'sk-epaper-manager' ); ?></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'PDF Rendering', 'sk-epaper-manager' ); ?></th>
                    <td>
                        <select name="sk_epaper_pdf_mode">
                            <option value="iframe" <?php selected( $sk_epaper_pdf_mode, 'iframe' ); ?>><?php esc_html_e( 'Browser embed (default)', 'sk-epaper-manager' ); ?></option>
                            <option value="image" <?php selected( $sk_epaper_pdf_mode, 'image' ); ?>><?php esc_html_e( 'Page images (recommended)', 'sk-epaper-manager' ); ?></option>
                            <option value="pdfjs" <?php selected( $sk_epaper_pdf_mode, 'pdfjs' ); ?> <?php disabled( ! $sk_epaper_pdfjs_available ); ?>><?php esc_html_e( 'PDF.js renderer', 'sk-epaper-manager' ); ?></option>
                        </select>
                        <span class="description">
                            <?php esc_html_e( 'Page images use the preview WordPress generates for each PDF, so zoom, swipe, printing and lazy loading all behave exactly like image editions. Requires Imagick with Ghostscript on the server.', 'sk-epaper-manager' ); ?>
                            <?php if ( ! $sk_epaper_pdfjs_available ) : ?>
                                <br><?php esc_html_e( 'PDF.js is unavailable: add pdf.min.js and pdf.worker.min.js to assets/vendor/pdfjs/ to enable it.', 'sk-epaper-manager' ); ?>
                            <?php endif; ?>
                        </span>
                    </td>
                </tr>
            </table>
        </div>

        <div class="sk-dashboard-card">
            <h2><span class="dashicons dashicons-admin-appearance"></span>
                <?php esc_html_e( 'Appearance & Editing', 'sk-epaper-manager' ); ?></h2>

            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Content Width', 'sk-epaper-manager' ); ?></th>
                    <td>
                        <input type="number" name="sk_epaper_content_width" min="0" max="3000" step="10"
                            value="<?php echo esc_attr( $sk_epaper_content_width ); ?>" class="small-text">
                        <span class="description"><?php esc_html_e( 'px', 'sk-epaper-manager' ); ?></span>
                        <p class="description">
                            <?php esc_html_e( 'Maximum width of the reader and the archive, so both line up with the rest of your theme. Set to 0 to remove the limit and let your theme\'s own container control the width.', 'sk-epaper-manager' ); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Header Background Color', 'sk-epaper-manager' ); ?></th>
                    <td>
                        <input type="color" name="sk_epaper_header_bg" value="<?php echo esc_attr( $sk_epaper_header_bg ); ?>"
                            style="width: 60px; height: 35px; vertical-align: middle;">
                        <span class="description"><?php esc_html_e( 'Select background color for the ePaper viewer header toolbar (Default: #000000).', 'sk-epaper-manager' ); ?></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Frontend Primary Brand Color', 'sk-epaper-manager' ); ?></th>
                    <td>
                        <input type="color" name="sk_epaper_primary_color" value="<?php echo esc_attr( $sk_epaper_primary_color ); ?>"
                            style="width: 60px; height: 35px; vertical-align: middle;">
                        <span class="description"><?php esc_html_e( 'Select primary accent color for frontend buttons, hovers, navigation arrows, and taxonomy badges (Default: #000000).', 'sk-epaper-manager' ); ?></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Block Editor', 'sk-epaper-manager' ); ?></th>
                    <td>
                        <input type="hidden" name="sk_epaper_use_block_editor" value="0">
                        <label>
                            <input type="checkbox" name="sk_epaper_use_block_editor" value="1" <?php checked( $sk_epaper_use_block_editor, 1 ); ?>>
                            <?php esc_html_e( 'Edit ePapers with the block editor instead of the classic editor', 'sk-epaper-manager' ); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e( 'The pages meta box works in both editors. Existing sites keep the classic editor unless you switch here.', 'sk-epaper-manager' ); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>

        <div class="sk-dashboard-card" id="sk-epaper-sharing">
            <h2><span class="dashicons dashicons-share"></span>
                <?php esc_html_e( 'Sharing', 'sk-epaper-manager' ); ?></h2>

            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Share Bar', 'sk-epaper-manager' ); ?></th>
                    <td>
                        <input type="hidden" name="sk_epaper_enable_share" value="0">
                        <label>
                            <input type="checkbox" name="sk_epaper_enable_share" value="1" <?php checked( $sk_epaper_enable_share, 1 ); ?>>
                            <?php esc_html_e( 'Show share buttons under the reader', 'sk-epaper-manager' ); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e( 'Readers share the page they are on, and the link opens the edition on that page. Plain links only: no scripts or trackers from any network are loaded.', 'sk-epaper-manager' ); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="sk_epaper_share_position"><?php esc_html_e( 'Position', 'sk-epaper-manager' ); ?></label></th>
                    <td>
                        <select name="sk_epaper_share_position" id="sk_epaper_share_position">
                            <option value="both" <?php selected( $sk_epaper_share_position, 'both' ); ?>><?php esc_html_e( 'Toolbar button and below the reader', 'sk-epaper-manager' ); ?></option>
                            <option value="toolbar" <?php selected( $sk_epaper_share_position, 'toolbar' ); ?>><?php esc_html_e( 'Toolbar button only', 'sk-epaper-manager' ); ?></option>
                            <option value="bottom" <?php selected( $sk_epaper_share_position, 'bottom' ); ?>><?php esc_html_e( 'Below the reader only', 'sk-epaper-manager' ); ?></option>
                        </select>
                        <p class="description">
                            <?php esc_html_e( 'The toolbar stays on screen while reading, so its Share button is always one tap away.', 'sk-epaper-manager' ); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Edition Cards', 'sk-epaper-manager' ); ?></th>
                    <td>
                        <input type="hidden" name="sk_epaper_share_on_cards" value="0">
                        <label>
                            <input type="checkbox" name="sk_epaper_share_on_cards" value="1" <?php checked( $sk_epaper_share_on_cards, 1 ); ?>>
                            <?php esc_html_e( 'Add a share button to edition cards on the archive and in "More Editions"', 'sk-epaper-manager' ); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e( 'Shares the whole edition, so readers can pass on today\'s paper straight from the archive.', 'sk-epaper-manager' ); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Networks', 'sk-epaper-manager' ); ?></th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text"><?php esc_html_e( 'Share networks', 'sk-epaper-manager' ); ?></legend>
                            <input type="hidden" name="sk_epaper_share_networks[]" value="">
                            <?php foreach ( SK_EPaper_Share::get_network_labels() as $sk_epaper_network => $sk_epaper_network_label ) : ?>
                                <label style="display:block;margin-bottom:6px;">
                                    <input type="checkbox" name="sk_epaper_share_networks[]" value="<?php echo esc_attr( $sk_epaper_network ); ?>" <?php checked( in_array( $sk_epaper_network, $sk_epaper_share_networks, true ) ); ?>>
                                    <?php echo esc_html( $sk_epaper_network_label ); ?>
                                </label>
                            <?php endforeach; ?>
                        </fieldset>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Open Graph Tags', 'sk-epaper-manager' ); ?></th>
                    <td>
                        <input type="hidden" name="sk_epaper_output_og" value="0">
                        <label>
                            <input type="checkbox" name="sk_epaper_output_og" value="1" <?php checked( $sk_epaper_output_og, 1 ); ?>>
                            <?php esc_html_e( 'Add Open Graph tags to editions, so shared links show a page preview', 'sk-epaper-manager' ); ?>
                        </label>
                        <p class="description">
                            <?php if ( $sk_epaper_seo_plugin ) : ?>
                                <?php
                                printf(
                                    /* translators: %s: Name of the active SEO plugin */
                                    esc_html__( 'Currently skipped: %s is active and adds its own Open Graph tags.', 'sk-epaper-manager' ),
                                    esc_html( $sk_epaper_seo_plugin )
                                );
                                ?>
                            <?php else : ?>
                                <?php esc_html_e( 'Skipped automatically when Yoast SEO, Rank Math, All in One SEO, SEOPress, The SEO Framework or Jetpack adds its own tags.', 'sk-epaper-manager' ); ?>
                            <?php endif; ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>

        <div class="sk-dashboard-card">
            <h2><span class="dashicons dashicons-database"></span>
                <?php esc_html_e( 'Data', 'sk-epaper-manager' ); ?></h2>

            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e( 'On Uninstall', 'sk-epaper-manager' ); ?></th>
                    <td>
                        <input type="hidden" name="sk_epaper_delete_data_on_uninstall" value="0">
                        <label>
                            <input type="checkbox" name="sk_epaper_delete_data_on_uninstall" value="1" <?php checked( $sk_epaper_delete_on_uninstall, 1 ); ?>>
                            <?php esc_html_e( 'Delete plugin settings and stored ePaper data when the plugin is deleted', 'sk-epaper-manager' ); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e( 'Off by default, so deleting and reinstalling the plugin for troubleshooting is safe. ePaper posts and uploaded media are never removed either way.', 'sk-epaper-manager' ); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>

        </div><!-- .sk-settings-grid -->

        <div class="sk-settings-actions">
            <?php submit_button( __( 'Save Settings', 'sk-epaper-manager' ), 'primary', 'submit', false ); ?>
        </div>
    </form>
</div>
