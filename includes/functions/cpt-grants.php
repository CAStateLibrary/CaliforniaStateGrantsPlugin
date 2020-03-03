<?php
/**
 * Handles all functionality related to the grants custom post type.
 *
 * @package CslGrantsSubmissions
 */

namespace CslGrantsSubmissions\CPT\Grants;

/**
 * Defines the post type slug.
 */
const POST_TYPE = 'csl_grants';

/**
 * Sets up the file.
 */
function setup() {
	$n = function( $fn ) {
		return __NAMESPACE__ . '\\' . $fn;
	};

	add_action( 'init', $n( 'register' ) );
	add_filter( 'use_block_editor_for_post_type', $n( 'disable_block_editor' ), 10, 2 );
};

/**
 * Disables the block editor for this post type.
 *
 * @param bool $use
 * @param string $post_type
 *
 * @return bool
 */
function disable_block_editor( $use, $post_type ) {
	if ( POST_TYPE === $post_type ) {
		return false;
	}

	return $use;
}

/**
 * Registers the post type.
 */
function register() {
	$labels = array(
		'name'               => _x( 'Grants', 'post type general name', 'csl-grants-submissions' ),
		'singular_name'      => _x( 'Grant', 'post type singular name', 'csl-grants-submissions' ),
		'menu_name'          => _x( 'Grants', 'admin menu', 'csl-grants-submissions' ),
		'name_admin_bar'     => _x( 'Grant', 'add new on admin bar', 'csl-grants-submissions' ),
		'add_new'            => _x( 'Add New', 'grant', 'csl-grants-submissions' ),
		'add_new_item'       => __( 'Add New Grant', 'csl-grants-submissions' ),
		'new_item'           => __( 'New Grant', 'csl-grants-submissions' ),
		'edit_item'          => __( 'Edit Grant', 'csl-grants-submissions' ),
		'view_item'          => __( 'View Grant', 'csl-grants-submissions' ),
		'all_items'          => __( 'All Grants', 'csl-grants-submissions' ),
		'search_items'       => __( 'Search Grants', 'csl-grants-submissions' ),
		'parent_item_colon'  => __( 'Parent Grants:', 'csl-grants-submissions' ),
		'not_found'          => __( 'No grants found.', 'csl-grants-submissions' ),
		'not_found_in_trash' => __( 'No grants found in Trash.', 'csl-grants-submissions' )
	);

	$args = array(
		'labels'             => $labels,
		'description'        => __( 'Description.', 'csl-grants-submissions' ),
		'public'             => true,
		'publicly_queryable' => true,
		'show_ui'            => true,
		'show_in_menu'       => true,
		'show_in_rest'       => true,
		'query_var'          => true,
		'rewrite'            => array( 'slug' => 'csl-grant' ),
		'capability_type'    => 'post',
		'has_archive'        => true,
		'hierarchical'       => false,
		'menu_position'      => null,
		'supports'           => array( 'title', 'editor', 'author' )
	);

	register_post_type( POST_TYPE, $args );
}
