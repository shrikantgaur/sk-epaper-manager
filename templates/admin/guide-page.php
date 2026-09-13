<?php
/**
 * Getting Started guide and demo content importer.
 *
 * @package SK_ePaper_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$sk_epaper_demo_count = SK_EPaper_Demo::count();
$sk_epaper_has_real   = (int) wp_count_posts( 'epaper' )->publish > $sk_epaper_demo_count;

// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$sk_epaper_notice = isset( $_GET['sk_demo_notice'] ) ? sanitize_text_field( wp_unslash( $_GET['sk_demo_notice'] ) ) : '';
?>

<div class="wrap sk-epaper-admin-wrap">
    <div class="sk-admin-header-flex">
        <div>
            <h1 class="wp-heading-inline">
                <span class="dashicons dashicons-welcome-learn-more sk-header-icon"></span>
                <?php esc_html_e( 'Getting Started', 'sk-epaper-manager' ); ?>
            </h1>
            <p class="description">
                <?php esc_html_e( 'Publish your first digital edition in a few minutes.', 'sk-epaper-manager' ); ?>
            </p>
        </div>
    </div>

    <?php if ( $sk_epaper_notice ) : ?>
        <?php
        list( $sk_epaper_notice_type, $sk_epaper_notice_count ) = array_pad( explode( ':', $sk_epaper_notice, 2 ), 2, '0' );
        ?>
        <div class="notice notice-success is-dismissible">
            <p>
                <?php if ( 'imported' === $sk_epaper_notice_type ) : ?>
                    <?php
                    printf(
                        /* translators: %d: Number of sample editions created */
                        esc_html( _n( '%d sample edition created. Open ePapers to see it.', '%d sample editions created. Open ePapers to see them.', absint( $sk_epaper_notice_count ), 'sk-epaper-manager' ) ),
                        absint( $sk_epaper_notice_count )
                    );
                    ?>
                <?php else : ?>
                    <?php
                    printf(
                        /* translators: %d: Number of sample editions removed */
                        esc_html( _n( '%d sample edition removed.', '%d sample editions removed.', absint( $sk_epaper_notice_count ), 'sk-epaper-manager' ) ),
                        absint( $sk_epaper_notice_count )
                    );
                    ?>
                <?php endif; ?>
            </p>
        </div>
    <?php endif; ?>

    <div class="sk-guide-grid">
        <div class="sk-guide-main">
            <div class="sk-dashboard-card">
                <h2><span class="dashicons dashicons-list-view"></span>
                    <?php esc_html_e( 'Publish an edition', 'sk-epaper-manager' ); ?></h2>

                <ol class="sk-guide-steps">
                    <li>
                        <strong><?php esc_html_e( 'Add the edition', 'sk-epaper-manager' ); ?></strong>
                        <p>
                            <?php
                            printf(
                                /* translators: %s: Link to the Add New ePaper screen */
                                esc_html__( 'Go to %s and give it a title, for example "City Edition - 12 September".', 'sk-epaper-manager' ),
                                '<a href="' . esc_url( admin_url( 'post-new.php?post_type=epaper' ) ) . '">' . esc_html__( 'ePapers &rarr; Add New', 'sk-epaper-manager' ) . '</a>'
                            );
                            ?>
                        </p>
                    </li>
                    <li>
                        <strong><?php esc_html_e( 'Upload the pages', 'sk-epaper-manager' ); ?></strong>
                        <p><?php esc_html_e( 'In "ePaper Details & Pages", click Add ePaper Pages and select the images or PDFs for that day. Drag them to set the order.', 'sk-epaper-manager' ); ?></p>
                    </li>
                    <li>
                        <strong><?php esc_html_e( 'Name the pages (optional)', 'sk-epaper-manager' ); ?></strong>
                        <p><?php esc_html_e( 'Type a label under each page - Front Page, City, Sports - and readers see those names in the page rail.', 'sk-epaper-manager' ); ?></p>
                    </li>
                    <li>
                        <strong><?php esc_html_e( 'Set the publication date, Edition and Language', 'sk-epaper-manager' ); ?></strong>
                        <p><?php esc_html_e( 'The date drives the archive date filter. Edition and Language become the badges and filters readers use.', 'sk-epaper-manager' ); ?></p>
                    </li>
                    <li>
                        <strong><?php esc_html_e( 'Publish', 'sk-epaper-manager' ); ?></strong>
                        <p>
                            <?php
                            printf(
                                /* translators: %s: Link to the ePaper archive */
                                esc_html__( 'The edition appears on your archive at %s.', 'sk-epaper-manager' ),
                                '<a href="' . esc_url( get_post_type_archive_link( 'epaper' ) ) . '" target="_blank" rel="noopener">' . esc_html( get_post_type_archive_link( 'epaper' ) ) . '</a>'
                            );
                            ?>
                        </p>
                    </li>
                </ol>
            </div>

            <div class="sk-dashboard-card">
                <h2><span class="dashicons dashicons-chart-bar"></span>
                    <?php esc_html_e( 'How view counts work', 'sk-epaper-manager' ); ?></h2>

                <ul class="sk-guide-list">
                    <li><?php esc_html_e( 'A view is counted when a reader opens an edition, not when it appears in the archive list.', 'sk-epaper-manager' ); ?></li>
                    <li><?php esc_html_e( 'The same reader opening the same edition again within 24 hours counts once. Refreshing does not inflate the number.', 'sk-epaper-manager' ); ?></li>
                    <li><?php esc_html_e( 'Search engine crawlers and other known bots are ignored.', 'sk-epaper-manager' ); ?></li>
                    <li><?php esc_html_e( 'Counting happens through a small background request, so it keeps working on sites with full page caching.', 'sk-epaper-manager' ); ?></li>
                    <li><?php esc_html_e( 'Counts are per edition, not per page. Turning pages inside an edition does not add views.', 'sk-epaper-manager' ); ?></li>
                </ul>

                <p class="description">
                    <?php esc_html_e( 'Numbers can look lower than other analytics tools because those often count every request, including repeats and bots.', 'sk-epaper-manager' ); ?>
                </p>
            </div>

            <div class="sk-dashboard-card">
                <h2><span class="dashicons dashicons-shortcode"></span>
                    <?php esc_html_e( 'Show ePapers anywhere', 'sk-epaper-manager' ); ?></h2>

                <table class="widefat striped sk-guide-table">
                    <tbody>
                        <tr>
                            <td><code>[sk_epaper id="123"]</code></td>
                            <td><?php esc_html_e( 'One edition with the full reader.', 'sk-epaper-manager' ); ?></td>
                        </tr>
                        <tr>
                            <td><code>[sk_epaper_archive count="6"]</code></td>
                            <td><?php esc_html_e( 'A grid of the latest editions.', 'sk-epaper-manager' ); ?></td>
                        </tr>
                        <tr>
                            <td><code>[sk_epaper_archive edition="city-edition"]</code></td>
                            <td><?php esc_html_e( 'Only one city or edition.', 'sk-epaper-manager' ); ?></td>
                        </tr>
                    </tbody>
                </table>

                <p class="description">
                    <?php
                    printf(
                        /* translators: %s: Link to the shortcode helper screen */
                        esc_html__( 'In the block editor, search for "ePaper" to add the same thing as a block. More options on the %s screen.', 'sk-epaper-manager' ),
                        '<a href="' . esc_url( admin_url( 'edit.php?post_type=epaper&page=sk-epaper-shortcodes' ) ) . '">' . esc_html__( 'Shortcodes & Helper', 'sk-epaper-manager' ) . '</a>'
                    );
                    ?>
                </p>
            </div>
        </div>

        <div class="sk-guide-side">
            <div class="sk-dashboard-card">
                <h2><span class="dashicons dashicons-download"></span>
                    <?php esc_html_e( 'Sample content', 'sk-epaper-manager' ); ?></h2>

                <p><?php esc_html_e( 'Not ready to upload a real edition yet? Create three sample editions with six labelled pages each, so you can try the reader, the filters and the settings straight away.', 'sk-epaper-manager' ); ?></p>

                <?php if ( $sk_epaper_demo_count ) : ?>
                    <p class="sk-guide-status">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <?php
                        printf(
                            /* translators: %d: Number of sample editions installed */
                            esc_html( _n( '%d sample edition installed.', '%d sample editions installed.', $sk_epaper_demo_count, 'sk-epaper-manager' ) ),
                            absint( $sk_epaper_demo_count )
                        );
                        ?>
                    </p>
                <?php endif; ?>

                <form method="post" action="">
                    <?php wp_nonce_field( 'sk_epaper_demo' ); ?>

                    <?php if ( ! $sk_epaper_demo_count ) : ?>
                        <button type="submit" name="sk_epaper_demo_action" value="import" class="button button-primary">
                            <span class="dashicons dashicons-database-import"></span>
                            <?php esc_html_e( 'Import sample editions', 'sk-epaper-manager' ); ?>
                        </button>
                    <?php else : ?>
                        <button type="submit" name="sk_epaper_demo_action" value="remove" class="button">
                            <span class="dashicons dashicons-trash"></span>
                            <?php esc_html_e( 'Remove sample content', 'sk-epaper-manager' ); ?>
                        </button>
                    <?php endif; ?>
                </form>

                <p class="description">
                    <?php esc_html_e( 'Sample pages are drawn on your own server, nothing is downloaded. Removing them deletes only the sample editions and their images - your own editions are never touched.', 'sk-epaper-manager' ); ?>
                </p>
            </div>

            <div class="sk-dashboard-card">
                <h2><span class="dashicons dashicons-admin-settings"></span>
                    <?php esc_html_e( 'Worth setting up', 'sk-epaper-manager' ); ?></h2>

                <ul class="sk-guide-list">
                    <li><?php esc_html_e( 'PDF Rendering: choose "Page images" so PDF editions zoom, print and swipe like image editions.', 'sk-epaper-manager' ); ?></li>
                    <li><?php esc_html_e( 'Content Width: match your theme so the reader lines up with the rest of your site.', 'sk-epaper-manager' ); ?></li>
                    <li><?php esc_html_e( 'Brand colour: used for the arrows, the active page and pagination.', 'sk-epaper-manager' ); ?></li>
                </ul>

                <p>
                    <a class="button" href="<?php echo esc_url( admin_url( 'edit.php?post_type=epaper&page=sk-epaper-settings' ) ); ?>">
                        <?php esc_html_e( 'Open Settings', 'sk-epaper-manager' ); ?>
                    </a>
                </p>
            </div>
        </div>
    </div>
</div>
