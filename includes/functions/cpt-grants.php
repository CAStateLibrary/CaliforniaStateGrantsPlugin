<?php
/**
 * Handles all functionality related to the grants custom post type.
 *
 * @package CaGov\Grants
 */

namespace CaGov\Grants\CPT\Grants;

use CaGov\Grants\Core;
use CaGov\Grants\Metaboxes;
use WP_REST_Response;
use WP_Error;
use WP_Http;

/**
 * Defines the post type slug.
 */
const POST_TYPE = 'ca_grants';

/**
 * Sets up the file.
 */
function setup() {
	$n = function( $fn ) {
		return __NAMESPACE__ . '\\' . $fn;
	};

	add_filter( 'rest_prepare_ca_grants', $n( 'modify_grants_rest_response' ), 10, 3 );
	add_filter( 'rest_ca_grants_query', $n( 'modify_grants_rest_params' ), 10, 2 );

	add_filter( 'rest_request_before_callbacks', $n( 'authenticate_rest_request' ), 10, 3 );
};

/**
 * Authenticate the REST Requests
 *
 * @param \WP_HTTP_Response|WP_Error $response Result to send
 * @param array                      $handler Route handler used
 * @param \WP_REST_Request           $request Request used to generate $response
 *
 * @return \WP_HTTP_Response|WP_Error WP_HTTP_Response if authentication succeeded, WP_Error otherwise
 */
function authenticate_rest_request( $response, $handler, $request ) {
	$authorization_header = $request->get_header( 'authorization' );
	if ( 0 !== strpos( '/wp/v2/grants', $request->get_route() ) ) {
		return $response;
	}

	if ( empty( $authorization_header ) ) {
		return new WP_Error( 'empty_auth_header', __( 'An authorization header must be provided.', 'csl-grants-submissions' ), array( 'status' => WP_Http::BAD_REQUEST ) );
	}

	$received_token = sanitize_text_field( $authorization_header );
	$stored_token   = sha1( Core\get_grants_token() );

	if ( empty( $stored_token ) || $stored_token !== $received_token ) {
		return new WP_Error( 'invalid_auth', __( 'The authorization token does not match.', 'csl-grants-submissions' ), array( 'status' => WP_Http::UNAUTHORIZED ) );
	}

	// If we get here, authorization has passed and we can return the data.
	return $response;
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
 * @param \WP_Post         $post The post object
 * @param \WP_REST_Request $request The request object
 *
 * @return \WP_REST_Response The modified response
 */
function modify_grants_rest_response( $response, $post, $request ) {
	$new_response = wp_cache_get( 'grants_rest_response_' . $post->ID );
	$new_response = false;
	if ( false === $new_response ) {
		// Fields that aren't needed in the REST response
		$blacklisted_fields = array(
			'grant-hash',
			'applications-submitted',
			'matchingFundsNotes',
			'disbursementMethodNotes',
			'adminSecondaryContact',
		);

		$metafields = Metaboxes\get_meta_fields();
		$new_data   = array(
			'grantTitle' => get_the_title( $post->ID ),
			'uniqueID'   => $post->ID,
		);

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
							'percent'  => absint( $metadata['percent'] ?? 0 ),
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

					case 'grantCategories':
						$new_data[ $metafield_data['id'] ] = (array) $metadata;
						break;

					case 'adminPrimaryContact':
						$metadata['primary']             = true;
						$secondary_contact               = get_post_meta( $post->ID, 'adminSecondaryContact', true );
						$new_data['internalContactInfo'] = array(
							$metadata,
							$secondary_contact,
						);

						break;

					case 'applicantType':
						$notes                             = get_post_meta( $post->ID, 'applicantTypeNotes', true );
						$new_data[ $metafield_data['id'] ] = array(
							'type'  => $metadata,
							'notes' => $notes,
						);
						break;

					case 'revSources':
						$notes                             = get_post_meta( $post->ID, 'revenueSourceNotes', true );
						$new_data[ $metafield_data['id'] ] = array(
							'type'  => $metadata,
							'notes' => $notes,
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
					case 'matchingFundsNotes':
					case 'revenueSourceNotes':
					case 'applicantTypeNotes':
					case 'disbursementMethodNotes':
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

		wp_cache_set( 'grants_rest_response_' . $post->ID, $new_response );
	}

	return $new_response;
}
