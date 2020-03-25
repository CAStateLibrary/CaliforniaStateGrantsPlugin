<?php
/**
 * Handles all functionality related to the grants custom post type.
 *
 * @package CslGrantsSubmissions
 */

namespace CslGrantsSubmissions\CPT\Grants;

use CslGrantsSubmissions\Metaboxes;
use WP_REST_Response;

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

	add_filter( 'rest_prepare_csl_grants', $n( 'modify_grants_rest_response' ), 10, 3 );
	add_filter( 'rest_csl_grants_query', $n( 'modify_grants_rest_params' ), 10, 2 );
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

/**
 * Ensure the Grants REST API returns all Grants
 *
 * @param array           $args The params used in the request
 * @param WP_REST_Request $request The Post Type object
 *
 * @return array The modified query params
 */
function modify_grants_rest_params( $args, $request ) {
	$args['nopaging'] = true;
	return $args;
}

/**
 * Modify the REST response for the Grants Post Type
 *
 * @param WP_REST_Response $response The response object
 * @param WP_Post          $post The post object
 * @param WP_REST_Request  $request The request object
 *
 * @return WP_REST_Response The modified response
 */
function modify_grants_rest_response( $response, $post, $request ) {
	$new_response = wp_cache_get( 'grants_rest_response_' . $post->ID );
	if ( false === $new_response ) {
		// Fields that aren't needed in the REST response
		$blacklisted_fields = array(
			'grant-hash',
			'applications-submitted',
			'matchingFundsNotes',
			'disbursementMethodNotes',
			'administrative-secondary-contact',
		);

		$metafields = Metaboxes\get_meta_fields();
		$new_data   = array();

		// Add Grant Categories Taxonomy Data to REST output
		$new_data['grantCategories'] = array();
		$grant_categories            = get_the_terms( $post->ID, 'grant-categories' );

		if ( false !== $grant_categories && ! is_wp_error( $grant_categories ) ) {
			foreach ( $grant_categories as $grant_category ) {
				$new_data['grantCategories'][] = $grant_category->name;
			}
		}

		// Add Applicant Types Taxonomy Data to REST output
		$new_data['applicantType'] = array(
			'type'  => array(),
			'notes' => '',
		);

		$applicant_types = get_the_terms( $post->ID, 'applicant-types' );
		if ( false !== $applicant_types && ! is_wp_error( $applicant_types ) ) {
			foreach ( $applicant_types as $applicant_type ) {
				$new_data['applicantType']['type'][] = $applicant_type->name;
			}
		}

		// Add Revenue Sources Taxonomy Data to REST output
		$new_data['revSources'] = array(
			'type'  => array(),
			'notes' => '',
		);

		$revenue_sources = get_the_terms( $post->ID, 'revenue-sources' );
		if ( false !== $revenue_sources && ! is_wp_error( $revenue_sources ) ) {
			foreach ( $revenue_sources as $revenue_source ) {
				$new_data['revSources']['type'][] = $revenue_source->name;
			}
		}

		// Modify the output for the remaining post meta
		if ( ! empty( $metafields ) ) {
			foreach ( $metafields as $metafield_key => $metafield_data ) {
				// Skip meta fields we don't want in the REST response
				if ( in_array( $metafield_data['id'], $blacklisted_fields, true ) ) {
					continue;
				}

				// Get the metadata for this post
				$metadata = get_post_meta( $post->ID, $metafield_data['id'], true );

				// Some fields need special handling
				switch ( $metafield_data['id'] ) {
					case 'estimatedAwards':
						if ( 'exact' === $metadata['checkbox'] ) {
							$new_data['estimatedAwards'] = array(
								'exact' => $metadata['exact'],
							);
						} elseif ( 'between' === $metadata['checkbox'] ) {
							$new_data['estimatedAwards'] = array(
								'between' => array(
									$metadata['between']['low'],
									$metadata['between']['high'],
								),
							);
						} elseif ( 'dependant' === $metadata['checkbox'] ) {
							$new_data['estimatedAwards'] = array(
								'dependent' => esc_html__( 'Dependant on number of awards.', 'grantsportal' ),
							);
						}

						break;
					case 'isForecasted':
						$new_data['isForecasted'] = ( 'forecasted' === $metadata ) ? true : false;
						break;

					case 'loiRequired':
						$new_data['loiRequired'] = ( 'yes' === $metadata ) ? true : false;
						break;

					case 'matchingFunds':
						$notes = get_post_meta( $post->ID, 'matchingFundsNotes', true );

						$new_data['matchingFunds'] = array(
							'required' => ( 'yes' === $metadata['checkbox'] ) ? true : false,
							'notes'    => $notes,
						);

						break;

					case 'disbursementMethod':
						$notes = get_post_meta( $post->ID, 'disbursementMethodNotes', true );

						$new_data['disbursementMethod'] = array(
							'type'  => $metadata,
							'notes' => $notes,
						);

						break;

					case 'estimatedAmounts':
						if ( 'same' === $metadata['checkbox'] ) {
							$new_data['estimatedAmounts'] = array(
								'same' => $metadata['same']['amount'],
							);
						} elseif ( 'different' === $metadata['checkbox'] ) {
							$new_data['estimatedAmounts'] = array(
								'diff' => array(
									$metadata['different']['first'],
									$metadata['different']['second'],
									$metadata['different']['third'],
								),
							);
						} elseif ( 'unknown' === $metadata['checkbox'] ) {
							$new_data['estimatedAmounts'] = array(
								'range' => array(
									$metadata['unknown']['first'],
									$metadata['unknown']['second'],
								),
							);
						} elseif ( 'dependant' === $metadata['checkbox'] ) {
							$new_data['estimatedAmounts'] = array(
								'unknown' => true,
							);
						}

						break;

					case 'administrative-primary-contact':
						$metadata['primary']             = true;
						$secondary_contact               = get_post_meta( $post->ID, 'administrative-secondary-contact', true );
						$new_data['internalContactInfo'] = array(
							$metadata,
							$secondary_contact,
						);

						break;

					case 'deadline':
						if ( isset( $metadata['deadline']['none'] ) && 'nodeadline' === $metadata['deadline']['none'] ) {
							$new_data['deadline'] = '';
						} else {
							$new_data['deadline'] = $metadata['deadline']['date'];
							if ( 'none' !== $metadata['deadline']['time'] ) {
								$new_data['deadline'] .= ' ' . $metadata['deadline']['time'];
							}
						}

						break;

					default:
						$new_data[ $metafield_data['id'] ] = $metadata;
						break;
				}
			}
		}

		// Set up a custom api response
		$new_response = new WP_REST_Response();
		$new_response->set_data( $new_data );
		$new_response->set_status( 200 );
		$new_response->set_headers(
			array(
				'Content-Type'  => 'application/json',
				'last_modified' => $post->post_modified,
				'Cache-Control' => 'max-age=' . WEEK_IN_SECONDS,
			)
		);

		wp_cache_set( 'grants_rest_response_' . $post->ID, $new_response, '', WEEK_IN_SECONDS );
	}

	return $new_response;
}
