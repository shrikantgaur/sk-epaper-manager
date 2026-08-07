<?php
/**
 * Shortcodes & Embed Documentation Page
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

$sk_all_epapers = get_posts( array(
    'post_type'      => 'epaper',
    'posts_per_page' => 20,
    'post_status'    => 'publish',
) );

$sk_editions = get_terms( array(
    'taxonomy'   => 'epaper_edition',
    'hide_empty' => false,
) );

$sk_languages = get_terms( array(
    'taxonomy'   => 'epaper_language',
    'hide_empty' => false,
) );
?>

<div class="wrap sk-epaper-admin-wrap">
    <div class="sk-admin-header-flex">
        <div>
            <h1 class="wp-heading-inline">
                <span class="dashicons dashicons-shortcode sk-header-icon"></span>
                <?php esc_html_e( 'Shortcodes & Embed Documentation', 'sk-epaper-manager' ); ?>
            </h1>
            <p class="description">
                <?php esc_html_e( 'Easily embed any ePaper viewer or edition grid into Pages, Posts, Widgets, Elementor, Divi, or Gutenberg.', 'sk-epaper-manager' ); ?>
            </p>
        </div>
    </div>

    <!-- Responsive Fluid 2-Column Cards Grid -->
    <div class="sk-epaper-shortcodes-grid" style="margin-top: 20px;">
        <!-- Single ePaper Shortcode -->
        <div class="sk-shortcode-card">
            <div class="sk-card-header">
                <h2>
                    <span class="dashicons dashicons-media-document"></span>
                    <?php esc_html_e( '1. Single ePaper Reader', 'sk-epaper-manager' ); ?>
                </h2>
                <span
                    class="badge-pill bg-blue"><?php esc_html_e( 'Interactive Reader', 'sk-epaper-manager' ); ?></span>
            </div>
            <div class="sk-card-body">
                <p class="sk-card-desc">
                    <?php esc_html_e( 'Embeds a responsive ePaper viewer slider for a specific edition.', 'sk-epaper-manager' ); ?>
                </p>

                <div class="sk-shortcode-box">
                    <code
                        id="sc-single-code">[sk_epaper id="<?php echo ! empty( $sk_all_epapers ) ? esc_attr( $sk_all_epapers[0]->ID ) : '3337'; ?>"]</code>
                    <button class="button button-primary sk-copy-btn" data-target="#sc-single-code">
                        <span class="dashicons dashicons-admin-page"></span>
                        <?php esc_html_e( 'Copy Shortcode', 'sk-epaper-manager' ); ?>
                    </button>
                </div>

                <?php if ( ! empty( $sk_all_epapers ) ) : ?>
                    <div class="sk-shortcode-builder" style="margin-top: 15px;">
                        <label
                            for="sk-select-epaper"><strong><?php esc_html_e( 'Select ePaper Edition:', 'sk-epaper-manager' ); ?></strong></label>
                        <select id="sk-select-epaper" class="widefat sk-custom-select" style="margin-top: 6px;">
                            <?php foreach ( $sk_all_epapers as $sk_epaper ) : ?>
                                <option value="<?php echo esc_attr( $sk_epaper->ID ); ?>">
                                    <?php echo esc_html( get_the_title( $sk_epaper->ID ) ); ?> (ID:
                                    <?php echo esc_html( $sk_epaper->ID ); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="sk-code-usage-box">
                    <label><strong><?php esc_html_e( 'PHP Template Code:', 'sk-epaper-manager' ); ?></strong></label>
                    <pre><code id="sc-single-php">&lt;?php echo do_shortcode('[sk_epaper id="<?php echo ! empty( $sk_all_epapers ) ? esc_attr( $sk_all_epapers[0]->ID ) : '3337'; ?>"]'); ?&gt;</code></pre>
                </div>
            </div>
        </div>

        <!-- Archive Grid Shortcode -->
        <div class="sk-shortcode-card">
            <div class="sk-card-header">
                <h2>
                    <span class="dashicons dashicons-grid-view"></span>
                    <?php esc_html_e( '2. ePaper Archive Grid', 'sk-epaper-manager' ); ?>
                </h2>
                <span class="badge-pill bg-green"><?php esc_html_e( 'Edition Grid', 'sk-epaper-manager' ); ?></span>
            </div>
            <div class="sk-card-body">
                <p class="sk-card-desc">
                    <?php esc_html_e( 'Displays a grid listing of ePaper editions with optional filters.', 'sk-epaper-manager' ); ?>
                </p>

                <div class="sk-shortcode-box">
                    <code id="sc-archive-code">[sk_epaper_archive count="6"]</code>
                    <button class="button button-primary sk-copy-btn" data-target="#sc-archive-code">
                        <span class="dashicons dashicons-admin-page"></span>
                        <?php esc_html_e( 'Copy Shortcode', 'sk-epaper-manager' ); ?>
                    </button>
                </div>

                <div class="sk-builder-grid" style="display: flex; gap: 10px; margin-top: 15px; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 80px;">
                        <label
                            for="sk-archive-count"><strong><?php esc_html_e( 'Count:', 'sk-epaper-manager' ); ?></strong></label>
                        <input type="number" id="sk-archive-count" value="6" min="1" max="50"
                            class="widefat sk-custom-select" style="margin-top: 6px;">
                    </div>
                    <?php if ( ! empty( $sk_editions ) && ! is_wp_error( $sk_editions ) ) : ?>
                        <div style="flex: 1; min-width: 120px;">
                            <label
                                for="sk-archive-edition"><strong><?php esc_html_e( 'City / Edition:', 'sk-epaper-manager' ); ?></strong></label>
                            <select id="sk-archive-edition" class="widefat sk-custom-select" style="margin-top: 6px;">
                                <option value=""><?php esc_html_e( 'All Cities', 'sk-epaper-manager' ); ?></option>
                                <?php foreach ( $sk_editions as $sk_term ) : ?>
                                    <option value="<?php echo esc_attr( $sk_term->slug ); ?>">
                                        <?php echo esc_html( $sk_term->name ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                    <?php if ( ! empty( $sk_languages ) && ! is_wp_error( $sk_languages ) ) : ?>
                        <div style="flex: 1; min-width: 120px;">
                            <label
                                for="sk-archive-lang"><strong><?php esc_html_e( 'Language:', 'sk-epaper-manager' ); ?></strong></label>
                            <select id="sk-archive-lang" class="widefat sk-custom-select" style="margin-top: 6px;">
                                <option value=""><?php esc_html_e( 'All Languages', 'sk-epaper-manager' ); ?></option>
                                <?php foreach ( $sk_languages as $sk_term ) : ?>
                                    <option value="<?php echo esc_attr( $sk_term->slug ); ?>">
                                        <?php echo esc_html( $sk_term->name ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="sk-code-usage-box">
                    <label><strong><?php esc_html_e( 'PHP Template Code:', 'sk-epaper-manager' ); ?></strong></label>
                    <pre><code id="sc-archive-php">&lt;?php echo do_shortcode('[sk_epaper_archive count="6"]'); ?&gt;</code></pre>
                </div>
            </div>
        </div>
    </div>

    <!-- How to Use Section -->
    <div class="sk-dashboard-card" style="margin-top: 25px;">
        <h2><span class="dashicons dashicons-book-alt"></span>
            <?php esc_html_e( 'Page Builder & Theme Integration Guide', 'sk-epaper-manager' ); ?></h2>

        <div class="sk-builder-guides-grid">
            <div class="sk-guide-box">
                <div class="guide-icon"><span class="dashicons dashicons-block-default"></span></div>
                <div>
                    <h4><?php esc_html_e( 'Gutenberg Block Editor', 'sk-epaper-manager' ); ?></h4>
                    <p><?php esc_html_e( 'Add a Shortcode block inside any page or post, then paste your copied shortcode.', 'sk-epaper-manager' ); ?>
                    </p>
                </div>
            </div>

            <div class="sk-guide-box">
                <div class="guide-icon"><span class="dashicons dashicons-layout"></span></div>
                <div>
                    <h4><?php esc_html_e( 'Elementor Page Builder', 'sk-epaper-manager' ); ?></h4>
                    <p><?php esc_html_e( 'Drag the Shortcode widget onto your Elementor canvas and paste the code into the Shortcode field.', 'sk-epaper-manager' ); ?>
                    </p>
                </div>
            </div>

            <div class="sk-guide-box">
                <div class="guide-icon"><span class="dashicons dashicons-editor-code"></span></div>
                <div>
                    <h4><?php esc_html_e( 'Theme Templates (PHP)', 'sk-epaper-manager' ); ?></h4>
                    <p><?php esc_html_e( 'Use do_shortcode() inside your theme PHP files (single.php, page.php, header.php).', 'sk-epaper-manager' ); ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>