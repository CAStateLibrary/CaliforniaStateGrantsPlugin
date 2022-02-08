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
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_rest'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => self::CPT_SLUG ),
			'rest_base'          => 'grant-awards',
			'capability_type'    => 'post',
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_icon'          => 'dashicons-awards',
			'menu_position'      => null,
			'supports'           => array( 'title', 'author' ),
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
	 * Get the number of published grants.
	 *
	 * @static
	 * @return int
	 */
	public static function get_published_count() {
		return absint( wp_count_posts( self::CPT_SLUG )->publish );
	}
}
