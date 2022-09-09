<?php
/**
 * Post Type: Grant Awards
 *
 * @package CaGov\Grants
 */

namespace CaGov\Grants\PostTypes;

/**
 * Grants Awards post type class.
 */
class GrantAwards {
	const CPT_SLUG = 'csl_grant_awards';

	/**
	 * Init
	 *
	 * @var boolean
	 */
	public static $init = false;

	/**
	 * Constructor.
	 */
	public function __construct() {}

	/**
	 * Setup actions and filters with the WordPress API.
	 *
	 * @return void
	 */
	public function setup() {
		if ( self::$init ) {
			return;
		}

		add_action( 'init', array( $this, 'register_post_type' ) );

		add_action( 'restrict_manage_posts', array( $this, 'add_post_filters' ) );
		add_action( 'parse_query', array( $this, 'filter_query' ) );

		add_filter( 'ep_indexable_post_types', [ $this, 'include_in_es_index' ], 20 );
		add_filter( 'ep_searchable_post_types', [ $this, 'include_in_es_index' ], 20 );

		add_filter( 'manage_' . self::CPT_SLUG . '_posts_columns', array( $this, 'set_custom_edit_columns' ) );
		add_action( 'manage_' . self::CPT_SLUG . '_posts_custom_column', array( $this, 'custom_column_renderer' ), 10, 2 );
		add_filter( 'manage_edit-' . self::CPT_SLUG . '_sortable_columns', array( $this, 'custom_columns_sortable' ) );

		add_filter( 'posts_clauses', array( $this, 'meta_or_title_search_clauses' ), 10, 2 );

		self::$init = true;
	}

	/**
	 * Column Order
	 *
	 * @var array
	 */
	private function get_column_order() {
		return [
			'portal_id',
			'associated_grant_name',
			'project_title',
			'title',
			'author',
			'taxonomy-recipient-types',
			'taxonomy-fiscal-year',
			'taxonomy-counties',
			'date',
		];
	}

	/**
	 * Get custom column data.
	 *
	 * @return array
	 */
	private function get_custom_columns() {
		return [

			'portal_id'             => __( 'Portal ID', 'ca-grants-plugin' ),
			'project_title'         => __( 'Project Title', 'ca-grants-plugin' ),
			'associated_grant_name' => __( 'Associated Grant Name', 'ca-grants-plugin' ),
		];
	}

	/**
	 * Make Custom Columns Sortable
	 *
	 * @param array $columns List of post columns.
	 * @return array
	 */
	public function custom_columns_sortable( $columns ) {
		$custom_columns = $this->get_custom_columns();
		unset( $custom_columns['associated_grant_name'] );

		foreach ( $custom_columns as $key => $value ) {
			$columns[ $key ] = $key;
		}

		$columns['author'] = 'author';

		return $columns;
	}

	/**
	 * Add custom column to grant awards CPT.
	 *
	 * @param array $columns List of post columns.
	 *
	 * @return array Return all columns data.
	 */
	public function set_custom_edit_columns( $columns ) {
		$custom_columns = $this->get_custom_columns();
		$column_order   = $this->get_column_order();

		// Add custom columns to the columns array.
		foreach ( $custom_columns as $key => $value ) {
			$columns[ $key ] = $value;
		}

		// Reorder columns.
		uksort(
			$columns,
			function ( $a, $b ) use ( $column_order ) {
				$pos_a = array_search( $a, $column_order, true );
				$pos_b = array_search( $b, $column_order, true );
				return $pos_a - $pos_b;
			}
		);

		// Change Title label to Recipient Name.
		$columns['title'] = __( 'Recipient Name', 'ca-grants-plugin' );

		return $columns;
	}

	/**
	 * Custom column renderer to show data for custom defined column.
	 *
	 * @param string $column Column name/slug.
	 * @param int    $grant_award_id The current grant ID.
	 *
	 * @return void
	 */
	public function custom_column_renderer( $column, $grant_award_id ) {
		$custom_columns = $this->get_custom_columns();

		if ( ! in_array( $column, array_keys( $custom_columns ), true ) ) {
			return;
		}

		$method = 'render_' . str_replace( '-', '_', $column );
		if ( method_exists( $this, $method ) ) {
			echo esc_html( $this->$method( $grant_award_id ) );
		}

	}

	/**
	 * Render project title.
	 *
	 * @param int $grant_award_id The current grant ID.
	 *
	 * @return string
	 */
	private function render_project_title( $grant_award_id ) {
		return get_post_meta( $grant_award_id, 'projectTitle', true );
	}

	/**
	 * Render Portal ID.
	 *
	 * @param int $grant_award_id The current grant ID.
	 *
	 * @return string
	 */
	private function render_portal_id( $grant_award_id ) {
		return $grant_award_id;
	}

	/**
	 * Render Portal ID.
	 *
	 * @param int $grant_award_id The current grant ID.
	 *
	 * @return string
	 */
	private function render_associated_grant_name( $grant_award_id ) {
		$grant_id = get_post_meta( $grant_award_id, 'grantID', true );
		return get_the_title( $grant_id );
	}

	/**
	 * Register grant post type.
	 *
	 * @return void
	 */
	public function register_post_type() {
		$args = array(
			'labels'             => $this->get_labels(),
			'description'        => __( 'California State Grant Awards.', 'ca-grants-plugin' ),
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_rest'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'grant-awards' ),
			'rest_base'          => 'grant-awards',
			'capability_type'    => 'post',
			'has_archive'        => false,
			'hierarchical'       => false,
			'menu_icon'          => 'dashicons-awards',
			'menu_position'      => null,
			'supports'           => array( 'author' ),
		);

		/**
		 * Filter the California Grants Awards post type arguments.
		 *
		 * @param array $args The post type arguments.
		 */
		$args = apply_filters( 'csl_grant_awards_post_type_args', $args );

		register_post_type( self::CPT_SLUG, $args );
	}

	/**
	 * Get grant post type labels.
	 *
	 * @return array
	 */
	public function get_labels() {
		return array(
			'name'               => _x( 'Grant Awards', 'post type general name', 'ca-grants-plugin' ),
			'singular_name'      => _x( 'Grant Award', 'post type singular name', 'ca-grants-plugin' ),
			'menu_name'          => _x( 'Grant Awards', 'admin menu', 'ca-grants-plugin' ),
			'name_admin_bar'     => _x( 'Grant Award', 'add new on admin bar', 'ca-grants-plugin' ),
			'add_new'            => _x( 'Add New', 'grant award', 'ca-grants-plugin' ),
			'add_new_item'       => __( 'Add New Grant Award', 'ca-grants-plugin' ),
			'new_item'           => __( 'New Grant Award', 'ca-grants-plugin' ),
			'edit_item'          => __( 'Edit Grant Award', 'ca-grants-plugin' ),
			'view_item'          => __( 'View Grant Award', 'ca-grants-plugin' ),
			'all_items'          => __( 'All Grant Awards', 'ca-grants-plugin' ),
			'search_items'       => __( 'Search Grant Awards', 'ca-grants-plugin' ),
			'parent_item_colon'  => __( 'Parent Grant Awards:', 'ca-grants-plugin' ),
			'not_found'          => __( 'No grants found.', 'ca-grants-plugin' ),
			'not_found_in_trash' => __( 'No grants found in Trash.', 'ca-grants-plugin' ),
		);
	}

	/**
	 * Add custom filter.
	 * i.e Filter awards by grant id.
	 *
	 * @param string $post_type current post type.
	 */
	public function add_post_filters( $post_type ) {
		if ( self::CPT_SLUG !== $post_type ) {
			return;
		}

		$fiscal_year = filter_input( INPUT_GET, 'fiscal-year', FILTER_SANITIZE_STRING );
		$award_maker = filter_input( INPUT_GET, 'award_maker', FILTER_VALIDATE_INT ) ?: 0;
		$grant_id    = filter_input( INPUT_GET, 'grant_id', FILTER_VALIDATE_INT ) ?: 0;
		$grant_title = get_the_title( $grant_id );

		if ( ! empty( $grant_id ) || ! empty( $grant_title ) ) {
			sprintf(
				'<label class="screen-reader-text" for="ca-grants-filter">%s</label>',
				esc_html__( 'Filter by Grant', 'ca-grants-plugin' )
			);
			echo '<select name="grant_id" id="ca-grants-filter">';
				printf(
					'<option value="">%s</option>',
					esc_html__( 'Any Grant', 'ca-grants-plugin' )
				);
			if ( ! empty( $grant_id ) && ! empty( $grant_title ) ) {
				printf(
					'<option value="%d" selected="selected">%s</option>',
					esc_attr( $grant_id ),
					esc_html( $grant_title )
				);
			}
			echo '</select>';
		}

		if ( ( 'CSL_IS_PORTAL' ) && CSL_IS_PORTAL ) {
			$taxonomy = get_taxonomy( 'fiscal-year' );
			$selected = ! empty( $fiscal_year ) ? get_term_by( 'slug', $fiscal_year, 'fiscal-year' ) : null;
			$fy_args  = array(
				'show_option_all' => $taxonomy->labels->all_items,
				'taxonomy'        => 'fiscal-year',
				'name'            => 'fiscal-year',
				'orderby'         => 'name',
				'value_field'     => 'slug',
				'selected'        => ! is_null( $selected ) ? $selected->slug : 0,
				'hierarchical'    => true,
			);
			wp_dropdown_categories( $fy_args );
		}

		$author_args = array(
			'show_option_all'  => 'All Grant Makers',
			'orderby'          => 'display_name',
			'order'            => 'ASC',
			'name'             => 'award_maker',
			'who'              => 'authors',
			'role__in'         => array( 'grant-contributor', 'grant-editor', 'administrator' ),
			'include_selected' => true,
			'selected'         => $award_maker,
		);
		wp_dropdown_users( $author_args );
	}

	/**
	 * Filter Grants for WP_Query post list view.
	 *
	 * @param \WP_Query $wp_query WP_Query object.
	 */
	public function filter_query( $wp_query ) {

		// This meta query should only run in admin post list screen.
		if ( ! is_admin() || ! function_exists( 'get_current_screen' ) ) {
			return;
		}

		$screen = get_current_screen();

		// Check if current page is from grant award cpt and it's a list page.
		if ( ! $screen || static::CPT_SLUG !== $screen->post_type || 'edit' !== $screen->base ) {
			return;
		}

		$grant_id    = filter_input( INPUT_GET, 'grant_id', FILTER_VALIDATE_INT );
		$award_maker = filter_input( INPUT_GET, 'award_maker', FILTER_VALIDATE_INT );
		$fiscal_year = filter_input( INPUT_GET, 'fiscal-year', FILTER_SANITIZE_STRING );
		$meta_query  = array( 'relation' => 'AND' );

		if ( ! empty( $grant_id ) ) {
			$meta_query[] = array(
				'key'     => 'grant_id',
				'value'   => $grant_id,
				'compare' => '=',
			);
		}

		if ( ! empty( $award_maker ) ) {
			$wp_query->set( 'author', $award_maker );
		}

		if ( ! empty( $fiscal_year ) ) {
			$tax_query = array(
				array(
					'taxonomy' => 'fiscal-year',
					'field'    => 'slug',
					'terms'    => $fiscal_year,
				),
			);

			$wp_query->set( 'tax_query', $tax_query );
		}

		$search_query = urldecode( $wp_query->get( 's' ) );

		$is_query_int = filter_var( $search_query, FILTER_VALIDATE_INT );

		if ( $wp_query->is_search() && ! empty( $search_query ) ) {

			// Reset search param to clean-up title/excerpt/content search query in posts_where.
			$wp_query->set( 's', '' );

			if ( $is_query_int ) {
				// Search Portal ID
				$wp_query->set( 'p', intval( $search_query ) );
			} else {
				// Search Project Title
				$search_meta_fields = [
					'projectTitle',
				];
				$wp_query->set( '_meta_or_title', $search_query );
				$wp_query->set( '_search_meta_fields', $search_meta_fields );
			}
		}

		$wp_query->set( 'meta_query', $meta_query );
	}

	/**
	 * Search posts by meta or title matching query string.
	 *
	 * @param array     $clauses Associative array of the clauses for the query.
	 * @param \WP_Query $wp_query The WP_Query instance (passed by reference).
	 *
	 * @return array
	 */
	public function meta_or_title_search_clauses( $clauses, $wp_query ) {

		if ( isset( $wp_query->query['orderby'] ) ) {
			global $wpdb;
			$order = $wp_query->get( 'order' );

			// author orderby
			if ( 'author' === $wp_query->query['orderby'] ) {
				$clauses['join'] .= " LEFT JOIN {$wpdb->users} u
				ON u.ID = {$wpdb->posts}.post_author ";

				$clauses['orderby'] = str_replace(
					"{$wpdb->posts}.post_author",
					' u.display_name',
					$clauses['orderby']
				);
			}

			// portal id orderby
			if ( 'portal_id' === $wp_query->query['orderby'] ) {
				$clauses['orderby'] = "{$wpdb->posts}.ID $order";
			}

			// project title orderby
			if ( 'project_title' === $wp_query->query['orderby'] ) {
				$clauses['join'] .= " JOIN {$wpdb->postmeta} AS meta ON meta.post_id = {$wpdb->posts}.ID AND
meta.meta_key = 'projectTitle'";

				$clauses['orderby'] = "meta.meta_value $order";
			}
		}

		// bail if we're not searching
		if ( ! $wp_query->is_main_query() || ! $wp_query->is_search() ) {
			return $clauses;
		}

		$search_query       = $wp_query->get( '_meta_or_title' );
		$search_meta_fields = $wp_query->get( '_search_meta_fields' );

		if ( empty( $search_query ) || empty( $search_meta_fields ) ) {
			return $clauses;
		}

		global $wpdb;

		$meta_field_like = [];
		$join            = '';

		foreach ( $search_meta_fields as $index => $meta_field_key ) {
			$join .= " INNER JOIN {$wpdb->postmeta} as grantportal_meta_search_{$index} ON ( wp_posts.ID = grantportal_meta_search_{$index}.post_id ) ";

			$meta_field_like[] = "(grantportal_meta_search_{$index}.meta_key = '{$meta_field_key}' AND grantportal_meta_search_{$index}.meta_value LIKE '%1\$s')";
		}

		$meta_field_like_sql = implode( ' OR ', $meta_field_like );
		$meta_field_like_sql = '( ' . $meta_field_like_sql . ' )';
		$search_query        = str_replace( [ "\r", "\n" ], '', $search_query );

		// Copied regex logic from $wp_query->parse_search() to get search terms.
		preg_match_all( '/".*?("|$)|((?<=[\t ",+])|^)[^\t ",+]+/', $search_query, $matches );

		$search_terms = empty( $matches[0] ) ? [] : $matches[0];

		// If the search string has only short terms, or is 10+ terms long, match it as sentence.
		if ( empty( $search_terms ) || count( $search_terms ) > 9 ) {
			$search_terms = [ $search_query ];
		}

		$searchand = empty( $clauses['where'] ) ? '' : ' AND ';
		$search    = '';

		foreach ( $search_terms as $term ) {
			$like            = '%' . $wpdb->esc_like( $term ) . '%';
			$meta_field_like = [];

			$search .= $wpdb->prepare(
				"{$searchand}(({$wpdb->posts}.post_title LIKE %s) OR ",
				$like
			);

			$search .= $wpdb->prepare(
				$meta_field_like_sql . ')',
				$like
			);
		}

		if ( ! empty( $join ) && ! empty( $search ) ) {
			$clauses['join']    .= $join;
			$clauses['where']   .= $search;
			$clauses['distinct'] = 'distinct';
		}

		return $clauses;
	}

	/**
	 * Include Grant Awards CPT in the list of CPT's that should be indexed in ES.
	 * Grant Awards are excluded by default since it is a private post type.
	 *
	 * @param array $post_types List of post types.
	 *
	 * @return array Modified list of post types.
	 */
	public function include_in_es_index( $post_types ) {
		if ( ! in_array( self::CPT_SLUG, $post_types, true ) ) {
			$post_types[] = self::CPT_SLUG;
		}

		return $post_types;
	}
}
