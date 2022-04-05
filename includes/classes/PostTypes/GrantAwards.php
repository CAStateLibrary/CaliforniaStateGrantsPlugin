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

		self::$init = true;
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

		$grant_id    = filter_input( INPUT_GET, 'grant_id', FILTER_VALIDATE_INT ) ?: 0;
		$grant_title = get_the_title( $grant_id );

		if ( empty( $grant_id ) || empty( $grant_title ) ) {
			return;
		}

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

	/**
	 * Filter wires stories for WP_Query post list view.
	 *
	 * @param WP_Query $wp_query WP_Query object.
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

		$grant_id = filter_input( INPUT_GET, 'grant_id', FILTER_VALIDATE_INT );

		if ( empty( $grant_id ) ) {
			return;
		}

		$wp_query->set( 'meta_key', 'grantID' );
		$wp_query->set( 'meta_value', $grant_id );
		$wp_query->set( 'meta_compare', '=' );
	}
}
