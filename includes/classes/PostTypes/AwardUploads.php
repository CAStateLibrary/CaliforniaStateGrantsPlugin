<?php
/**
 * Post Type: Award Uploads
 *
 * @package CaGov\Grants
 */

namespace CaGov\Grants\PostTypes;

/**
 * Award Uploads post type class.
 */
class AwardUploads {

	const CPT_SLUG = 'csl_award_uploads';

	/**
	 * Init
	 *
	 * @var boolean
	 */
	public static $init = false;

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
		add_action( 'init', array( $this, 'register_post_status' ) );

		// Post edit screen.
		add_action( 'admin_footer-post.php', array( $this, 'append_post_status_list' ) );

		// Quick post edit screen.
		add_action( 'admin_footer-edit.php', array( $this, 'append_post_status_list' ) );

		self::$init = true;
	}

	/**
	 * Register award uploads post type.
	 *
	 * @return void
	 */
	public function register_post_type() {
		$args = array(
			'labels'             => $this->get_labels(),
			'description'        => __( 'California State Award Uploads.', 'ca-grants-plugin' ),
			'public'             => true,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_rest'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'award-uploads' ),
			'rest_base'          => 'award-uploads',
			'capability_type'    => 'post',
			'has_archive'        => false,
			'hierarchical'       => false,
			'menu_icon'          => 'dashicons-upload',
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
			'name'               => _x( 'Award Uploads', 'post type general name', 'ca-grants-plugin' ),
			'singular_name'      => _x( 'Award Upload', 'post type singular name', 'ca-grants-plugin' ),
			'menu_name'          => _x( 'Award Uploads', 'admin menu', 'ca-grants-plugin' ),
			'name_admin_bar'     => _x( 'Award Upload', 'add new on admin bar', 'ca-grants-plugin' ),
			'add_new'            => _x( 'Add New', 'Award Upload', 'ca-grants-plugin' ),
			'add_new_item'       => __( 'Add New Award Upload', 'ca-grants-plugin' ),
			'new_item'           => __( 'New Award Upload', 'ca-grants-plugin' ),
			'edit_item'          => __( 'Edit Award Upload', 'ca-grants-plugin' ),
			'view_item'          => __( 'View Award Upload', 'ca-grants-plugin' ),
			'all_items'          => __( 'All Award Uploads', 'ca-grants-plugin' ),
			'search_items'       => __( 'Search Award Uploads', 'ca-grants-plugin' ),
			'parent_item_colon'  => __( 'Parent Award Uploads:', 'ca-grants-plugin' ),
			'not_found'          => __( 'No grants found.', 'ca-grants-plugin' ),
			'not_found_in_trash' => __( 'No grants found in Trash.', 'ca-grants-plugin' ),
		);
	}

	/**
	 * Register award uploads post status.
	 *
	 * @return void
	 */
	public function register_post_status() {

		$args = array(
			'label'                     => _x( 'Failed', 'post', 'ca-grants-plugin' ),
			'public'                    => true,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop( 'Failed (%s)', 'Failed (%s)', 'ca-grants-plugin' ),
		);

		register_post_status( 'csl_failed', $args );
	}

	/**
	 * Add custom post status.
	 *
	 * @return void
	 */
	public function append_post_status_list() {
		global $post;

		if ( static::CPT_SLUG !== $post->post_type ) {
			return;
		}

		$failed_option = sprintf(
			'<option value=\"csl_failed\" %s>Failed</option>',
			selected( $post->post_status, 'csl_failed', false )
		);

		echo '<script>';
		echo 'jQuery(document).ready(function($){';
			printf( '$("select#post_status").append("%s");', $failed_option );
			// Inline edit status for quick edit screen.
			printf( '$(".inline-edit-status select[name=\"_status\"]").append("%s");', $failed_option );
		if ( 'csl_failed' === $post->post_status ) {
			printf( '$("#post-status-display").text("Failed");' );
		}
		echo '});';
		echo '</script>';
	}
}
