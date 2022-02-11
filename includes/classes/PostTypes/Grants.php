<?php
/**
 * Post Type: Grant
 *
 * @package CaGov\Grants
 */

namespace CaGov\Grants\PostTypes;

/**
 * Grants post type class.
 */
class Grants {
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
		add_filter( 'use_block_editor_for_post_type', array( $this, 'disable_block_editor' ), 10, 2 );

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
			'description'        => __( 'California State Grants.', 'ca-grants-plugin' ),
			'public'             => false,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_rest'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'grants' ),
			'rest_base'          => 'grants',
			'capability_type'    => 'post',
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_icon'          => 'dashicons-awards',
			'menu_position'      => null,
			'supports'           => array( 'title', 'author' ),
		);
		
		/**
		 * Filter the California Grants post type arguments.
		 *
		 * @param array $args The post type arguments.
		 */
		$args = apply_filters( 'ca_grants_post_type_args', $args );
		
		register_post_type( self::get_cpt_slug(), $args );
	}
	
	/**
	 * Returns the custom post-type slug used when registering
	 * the grants post-type.
	 *
	 * @return string The post-type slug.
	 */
	public static function get_cpt_slug() {
		$cpt_slug = 'ca_grants';
		
		/**
		 * Filters the slug used in registration
		 * of the grants post-type.
		 * 
		 * @param $cpt_slug string The filtered post-type slug.
		 */
		return apply_filters( 'ca_grants_cpt_slug', $cpt_slug );
	}
	
	/**
	 * Get grant post type labels.
	 *
	 * @return array
	 */
	public function get_labels() {
		return array(
			'name'               => _x( 'CA Grants', 'post type general name', 'ca-grants-plugin' ),
			'singular_name'      => _x( 'CA Grant', 'post type singular name', 'ca-grants-plugin' ),
			'menu_name'          => _x( 'CA Grants', 'admin menu', 'ca-grants-plugin' ),
			'name_admin_bar'     => _x( 'CA Grant', 'add new on admin bar', 'ca-grants-plugin' ),
			'add_new'            => _x( 'Add New', 'grant', 'ca-grants-plugin' ),
			'add_new_item'       => __( 'Add New CA Grant', 'ca-grants-plugin' ),
			'new_item'           => __( 'New CA Grant', 'ca-grants-plugin' ),
			'edit_item'          => __( 'Edit CA Grant', 'ca-grants-plugin' ),
			'view_item'          => __( 'View CA Grant', 'ca-grants-plugin' ),
			'all_items'          => __( 'All CA Grants', 'ca-grants-plugin' ),
			'search_items'       => __( 'Search CA Grants', 'ca-grants-plugin' ),
			'parent_item_colon'  => __( 'Parent CA Grants:', 'ca-grants-plugin' ),
			'not_found'          => __( 'No grants found.', 'ca-grants-plugin' ),
			'not_found_in_trash' => __( 'No grants found in Trash.', 'ca-grants-plugin' ),
		);
	}

	/**
	 * Disables the block editor for this post type.
	 *
	 * @param bool   $use Whether to use the block editor
	 * @param string $post_type The current post type
	 *
	 * @return bool
	 */
	public function disable_block_editor( $use, $post_type ) {
		if ( self::get_cpt_slug() === $post_type ) {
			return false;
		}

		return $use;
	}

	/**
	 * Get the number of published grants.
	 *
	 * @static
	 * @return int
	 */
	public static function get_published_count() {
		return absint( wp_count_posts( self::get_cpt_slug() )->publish );
	}
}
