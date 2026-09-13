<?php
/**
 * Post type, taxonomies, rewrite rules, archive query and template loading.
 *
 * The registered slugs (epapers, epaper-edition, epaper-language) are frozen:
 * changing them would 404 every URL already indexed by search engines.
 *
 * @package SK_ePaper_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Content model for ePapers.
 */
class SK_EPaper_CPT {

    /**
     * Register the ePaper post type.
     *
     * @return void
     */
    public static function register_post_type() {
        $labels = array(
            'name'               => __( 'ePapers', 'sk-epaper-manager' ),
            'singular_name'      => __( 'ePaper', 'sk-epaper-manager' ),
            'menu_name'          => __( 'ePapers', 'sk-epaper-manager' ),
            'all_items'          => __( 'All ePapers', 'sk-epaper-manager' ),
            'add_new'            => __( 'Add New', 'sk-epaper-manager' ),
            'add_new_item'       => __( 'Add New ePaper', 'sk-epaper-manager' ),
            'edit_item'          => __( 'Edit ePaper', 'sk-epaper-manager' ),
            'new_item'           => __( 'New ePaper', 'sk-epaper-manager' ),
            'view_item'          => __( 'View ePaper', 'sk-epaper-manager' ),
            'search_items'       => __( 'Search ePapers', 'sk-epaper-manager' ),
            'not_found'          => __( 'No ePapers found', 'sk-epaper-manager' ),
            'not_found_in_trash' => __( 'No ePapers found in Trash', 'sk-epaper-manager' ),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'has_archive'        => true,
            'rewrite'            => array( 'slug' => 'epapers', 'with_front' => false ),
            'hierarchical'       => false,
            'supports'           => array( 'title', 'editor', 'thumbnail' ),
            // REST is on so blocks, the block editor and headless clients can
            // read ePapers. Which editor actually loads is decided separately
            // by use_block_editor() below.
            'show_in_rest'       => true,
            'rest_base'          => 'epapers',
        );

        register_post_type( 'epaper', $args );
    }

    /**
     * Register the Editions and Languages taxonomies.
     *
     * @return void
     */
    public static function register_taxonomies() {
        $edition_labels = array(
            'name'              => _x( 'Editions', 'taxonomy general name', 'sk-epaper-manager' ),
            'singular_name'     => _x( 'Edition', 'taxonomy singular name', 'sk-epaper-manager' ),
            'search_items'      => __( 'Search Editions', 'sk-epaper-manager' ),
            'all_items'         => __( 'All Editions', 'sk-epaper-manager' ),
            'parent_item'       => __( 'Parent Edition', 'sk-epaper-manager' ),
            'parent_item_colon' => __( 'Parent Edition:', 'sk-epaper-manager' ),
            'edit_item'         => __( 'Edit Edition', 'sk-epaper-manager' ),
            'update_item'       => __( 'Update Edition', 'sk-epaper-manager' ),
            'add_new_item'      => __( 'Add New Edition', 'sk-epaper-manager' ),
            'new_item_name'     => __( 'New Edition Name', 'sk-epaper-manager' ),
            'menu_name'         => __( 'Editions', 'sk-epaper-manager' ),
        );

        register_taxonomy( 'epaper_edition', array( 'epaper' ), array(
            'hierarchical'      => true,
            'labels'            => $edition_labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array( 'slug' => 'epaper-edition', 'with_front' => false ),
            'show_in_rest'      => true,
        ) );

        $lang_labels = array(
            'name'              => _x( 'Languages', 'taxonomy general name', 'sk-epaper-manager' ),
            'singular_name'     => _x( 'Language', 'taxonomy singular name', 'sk-epaper-manager' ),
            'search_items'      => __( 'Search Languages', 'sk-epaper-manager' ),
            'all_items'         => __( 'All Languages', 'sk-epaper-manager' ),
            'edit_item'         => __( 'Edit Language', 'sk-epaper-manager' ),
            'update_item'       => __( 'Update Language', 'sk-epaper-manager' ),
            'add_new_item'      => __( 'Add New Language', 'sk-epaper-manager' ),
            'new_item_name'     => __( 'New Language Name', 'sk-epaper-manager' ),
            'menu_name'         => __( 'Languages', 'sk-epaper-manager' ),
        );

        register_taxonomy( 'epaper_language', array( 'epaper' ), array(
            'hierarchical'      => true,
            'labels'            => $lang_labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array( 'slug' => 'epaper-language', 'with_front' => false ),
            'show_in_rest'      => true,
        ) );
    }

    /**
     * One-time rewrite flush, re-keyed whenever rewrite behaviour changes.
     *
     * @return void
     */
    public static function check_flush_rules() {
        if ( '1' !== get_option( 'sk_epaper_flush_rules_v130' ) ) {
            self::register_post_type();
            self::register_taxonomies();
            flush_rewrite_rules();
            update_option( 'sk_epaper_flush_rules_v130', '1' );
        }
    }

    /**
     * Activation: register everything, flush, then run pending upgrades.
     *
     * @return void
     */
    public static function activate() {
        self::register_post_type();
        self::register_taxonomies();
        flush_rewrite_rules();

        SK_EPaper_Migrations::maybe_upgrade();
    }

    /**
     * Deactivation: only flush. WordPress tears the registrations down anyway.
     *
     * @return void
     */
    public static function deactivate() {
        flush_rewrite_rules();
        SK_EPaper_Migrations::unschedule();
    }

    /**
     * Decide which editor loads for ePapers.
     *
     * Enabling show_in_rest would normally flip every existing site to the block
     * editor overnight. Publishers who upload an edition every morning should
     * not have their workflow change under them, so the choice is an explicit
     * setting: existing installs stay on classic, new ones start on blocks.
     *
     * @param bool   $use_block_editor Current decision.
     * @param string $post_type        Post type being edited.
     * @return bool
     */
    public static function use_block_editor( $use_block_editor, $post_type ) {
        if ( 'epaper' !== $post_type ) {
            return $use_block_editor;
        }

        return (bool) SK_EPaper_Settings::get( 'use_block_editor' );
    }

    /**
     * Editions shown per archive page.
     *
     * @return int
     */
    public static function get_archive_per_page() {
        $per_page = absint( SK_EPaper_Settings::get( 'archive_per_page' ) );

        if ( $per_page < 1 ) {
            $per_page = 24;
        }

        return (int) apply_filters( 'sk_epaper_archive_per_page', $per_page );
    }

    /**
     * Paginate and filter the ePaper archive and taxonomy archives.
     *
     * @param WP_Query $query Query object.
     * @return void
     */
    public static function archive_pre_get_posts( $query ) {
        if ( is_admin() || ! $query->is_main_query() ) {
            return;
        }

        if (
            ! $query->is_post_type_archive( 'epaper' ) &&
            ! $query->is_tax( 'epaper_edition' ) &&
            ! $query->is_tax( 'epaper_language' )
        ) {
            return;
        }

        $query->set( 'posts_per_page', self::get_archive_per_page() );

        $filters = self::get_archive_filters();

        // Search runs through the query var rather than ?s= on purpose: ?s=
        // flips the request into a search query, which would drop the archive
        // template and the taxonomy context along with it.
        if ( '' !== $filters['search'] ) {
            $query->set( 's', $filters['search'] );
        }

        $tax_query = (array) $query->get( 'tax_query' );

        if ( '' !== $filters['edition'] && ! $query->is_tax( 'epaper_edition' ) ) {
            $tax_query[] = array(
                'taxonomy' => 'epaper_edition',
                'field'    => 'slug',
                'terms'    => $filters['edition'],
            );
        }

        if ( '' !== $filters['language'] && ! $query->is_tax( 'epaper_language' ) ) {
            $tax_query[] = array(
                'taxonomy' => 'epaper_language',
                'field'    => 'slug',
                'terms'    => $filters['language'],
            );
        }

        if ( count( $tax_query ) > 1 ) {
            $tax_query['relation'] = 'AND';
        }

        if ( ! empty( $tax_query ) ) {
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
            $query->set( 'tax_query', $tax_query );
        }

        /*
         * Date filtering runs on the edition date the publisher entered rather
         * than post_date: an edition is often uploaded the night before or the
         * morning after, and the date printed on the card is the one readers
         * mean when they pick a day.
         */
        $meta_query = (array) $query->get( 'meta_query' );

        if ( '' !== $filters['date'] ) {
            $meta_query[] = array(
                'key'     => SK_EPAPER_PREFIX . 'edition_date',
                'value'   => $filters['date'],
                'compare' => '=',
            );
        } elseif ( '' !== $filters['month'] ) {
            $meta_query[] = array(
                'key'     => SK_EPAPER_PREFIX . 'edition_date',
                'value'   => '^' . $filters['month'],
                'compare' => 'REGEXP',
            );
        }

        if ( ! empty( $meta_query ) ) {
            if ( count( $meta_query ) > 1 && ! isset( $meta_query['relation'] ) ) {
                $meta_query['relation'] = 'AND';
            }

            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
            $query->set( 'meta_query', $meta_query );
        }
    }

    /**
     * Read the archive filter values from the request.
     *
     * Read-only navigation, so no nonce: these only ever narrow a public listing.
     *
     * @return array {
     *     @type string $edition  Edition term slug.
     *     @type string $language Language term slug.
     *     @type string $search   Free text search.
     *     @type string $month    YYYY-MM.
     *     @type string $date     YYYY-MM-DD, an exact edition date.
     * }
     */
    public static function get_archive_filters() {
        // phpcs:disable WordPress.Security.NonceVerification.Recommended
        $edition  = isset( $_GET['sk_edition'] ) ? sanitize_title( wp_unslash( $_GET['sk_edition'] ) ) : '';
        $language = isset( $_GET['sk_language'] ) ? sanitize_title( wp_unslash( $_GET['sk_language'] ) ) : '';
        $search   = isset( $_GET['sk_q'] ) ? sanitize_text_field( wp_unslash( $_GET['sk_q'] ) ) : '';
        $month    = isset( $_GET['sk_month'] ) ? sanitize_text_field( wp_unslash( $_GET['sk_month'] ) ) : '';
        $date     = isset( $_GET['sk_date'] ) ? sanitize_text_field( wp_unslash( $_GET['sk_date'] ) ) : '';
        // phpcs:enable WordPress.Security.NonceVerification.Recommended

        if ( ! preg_match( '/^\d{4}-\d{2}$/', $month ) ) {
            $month = '';
        }

        if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
            $date = '';
        }

        return compact( 'edition', 'language', 'search', 'month', 'date' );
    }

    /**
     * Whether this is our archive query carrying a search term.
     *
     * @param WP_Query $query Query object.
     * @return bool
     */
    protected static function is_archive_search( $query ) {
        if ( is_admin() || ! $query instanceof WP_Query || ! $query->is_main_query() ) {
            return false;
        }

        if ( '' === (string) $query->get( 's' ) ) {
            return false;
        }

        return $query->is_post_type_archive( 'epaper' )
            || $query->is_tax( 'epaper_edition' )
            || $query->is_tax( 'epaper_language' );
    }

    /**
     * Join the tables needed to search editions by more than title and content.
     *
     * A reader searching "city" means the City Edition, or a page labelled
     * City. WordPress core only looks at post_title, post_excerpt and
     * post_content, so that search came back empty.
     *
     * @param string   $join  Existing JOIN clause.
     * @param WP_Query $query Query object.
     * @return string
     */
    public static function search_join( $join, $query ) {
        global $wpdb;

        if ( ! self::is_archive_search( $query ) ) {
            return $join;
        }

        $join .= " LEFT JOIN {$wpdb->term_relationships} sk_ep_tr ON {$wpdb->posts}.ID = sk_ep_tr.object_id";
        $join .= " LEFT JOIN {$wpdb->term_taxonomy} sk_ep_tt ON sk_ep_tr.term_taxonomy_id = sk_ep_tt.term_taxonomy_id"
            . " AND sk_ep_tt.taxonomy IN ('epaper_edition', 'epaper_language')";
        $join .= " LEFT JOIN {$wpdb->terms} sk_ep_t ON sk_ep_tt.term_id = sk_ep_t.term_id";
        $join .= " LEFT JOIN {$wpdb->postmeta} sk_ep_pl ON {$wpdb->posts}.ID = sk_ep_pl.post_id"
            . " AND sk_ep_pl.meta_key = '" . SK_EPAPER_PREFIX . "page_labels'";

        return $join;
    }

    /**
     * Widen the search clause to cover Edition, Language and page labels.
     *
     * The extra terms are folded into the search fragment rather than appended
     * to the WHERE, so any active edition/language/month filter still applies.
     *
     * @param string   $search Existing search SQL.
     * @param WP_Query $query  Query object.
     * @return string
     */
    public static function search_where( $search, $query ) {
        global $wpdb;

        if ( ! self::is_archive_search( $query ) || '' === trim( $search ) ) {
            return $search;
        }

        $like  = '%' . $wpdb->esc_like( (string) $query->get( 's' ) ) . '%';
        $inner = preg_replace( '/^\s*AND\s*/i', '', $search );

        $extra = $wpdb->prepare(
            '( sk_ep_t.name LIKE %s OR sk_ep_pl.meta_value LIKE %s )',
            $like,
            $like
        );

        return " AND ( {$inner} OR {$extra} ) ";
    }

    /**
     * The term joins can return a post once per term, so de-duplicate.
     *
     * @param string   $distinct Existing DISTINCT clause.
     * @param WP_Query $query    Query object.
     * @return string
     */
    public static function search_distinct( $distinct, $query ) {
        if ( ! self::is_archive_search( $query ) ) {
            return $distinct;
        }

        return 'DISTINCT';
    }

    /**
     * Months that actually have published editions, newest first.
     *
     * Built from the whole archive rather than the current page: deriving them
     * from the result set made the Month dropdown vanish the moment a filter
     * returned nothing, so there was no way to change the month back.
     *
     * @return array YYYY-MM => localised label.
     */
    public static function get_archive_months() {
        $cached = get_transient( 'sk_epaper_archive_months' );

        if ( is_array( $cached ) ) {
            return $cached;
        }

        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $rows = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT DISTINCT LEFT(pm.meta_value, 7) AS ym
                 FROM {$wpdb->postmeta} pm
                 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
                 WHERE pm.meta_key = %s
                   AND pm.meta_value <> ''
                   AND p.post_type = 'epaper'
                   AND p.post_status = 'publish'
                 ORDER BY ym DESC
                 LIMIT 60",
                SK_EPAPER_PREFIX . 'edition_date'
            )
        );

        $months = array();

        foreach ( (array) $rows as $row ) {
            $timestamp = strtotime( $row . '-01' );

            if ( $timestamp ) {
                $months[ $row ] = date_i18n( 'F Y', $timestamp );
            }
        }

        set_transient( 'sk_epaper_archive_months', $months, 6 * HOUR_IN_SECONDS );

        return $months;
    }

    /**
     * Drop the cached month list when editions change.
     *
     * @return void
     */
    public static function flush_month_cache() {
        delete_transient( 'sk_epaper_archive_months' );
        delete_transient( 'sk_epaper_available_dates' );
    }

    /**
     * Edition dates that actually exist, newest first, never in the future.
     *
     * Used to bound the date picker and to grey out days with no edition, so a
     * reader cannot pick a date that can only return an empty page.
     *
     * @return array List of Y-m-d strings.
     */
    public static function get_available_dates() {
        $cached = get_transient( 'sk_epaper_available_dates' );

        if ( is_array( $cached ) ) {
            return $cached;
        }

        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $rows = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT DISTINCT pm.meta_value
                 FROM {$wpdb->postmeta} pm
                 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
                 WHERE pm.meta_key = %s
                   AND pm.meta_value <> ''
                   AND p.post_type = 'epaper'
                   AND p.post_status = 'publish'
                 ORDER BY pm.meta_value DESC
                 LIMIT 3000",
                SK_EPAPER_PREFIX . 'edition_date'
            )
        );

        $today = current_time( 'Y-m-d' );
        $dates = array();

        foreach ( (array) $rows as $row ) {
            if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $row ) && $row <= $today ) {
                $dates[] = $row;
            }
        }

        set_transient( 'sk_epaper_available_dates', $dates, 6 * HOUR_IN_SECONDS );

        return $dates;
    }

    /**
     * Whether any archive filter is currently applied.
     *
     * @return bool
     */
    public static function has_archive_filters() {
        foreach ( self::get_archive_filters() as $value ) {
            if ( '' !== $value ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Load plugin templates, letting the active theme override them.
     *
     * Themes may place overrides in either:
     *   yourtheme/sk-epaper/single-epaper.php
     *   yourtheme/single-epaper.php
     *
     * @param string $template Resolved template path.
     * @return string
     */
    public static function template_include( $template ) {
        $file = '';

        if ( is_post_type_archive( 'epaper' ) || is_tax( 'epaper_edition' ) || is_tax( 'epaper_language' ) ) {
            $file = 'archive-epaper.php';
        } elseif ( is_singular( 'epaper' ) ) {
            $file = 'single-epaper.php';
        }

        if ( ! $file ) {
            return $template;
        }

        $theme_template = locate_template( array( 'sk-epaper/' . $file, $file ) );

        if ( $theme_template ) {
            return $theme_template;
        }

        return SK_EPAPER_PATH . 'templates/' . $file;
    }
}
