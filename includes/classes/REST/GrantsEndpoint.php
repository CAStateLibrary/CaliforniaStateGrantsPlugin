<?php
/**
 * Grants Endpoint
 *
 * @package CaGov\Grants
 */

namespace CaGov\Grants\REST;

use CaGov\Grants\PostTypes\Grants;
use CaGov\Grants\Core;
use CaGov\Grants\Meta;
use WP_REST_Response;
use WP_Rest_Request;

/**
 * GrantsEndpoint Class.
 */
class GrantsEndpoint extends BaseEndpoint {

	/**
	 * Rest url Slug.
	 *
	 * @var string
	 */
	public static $rest_slug = 'grants';

	/**
	 * Setup actions and filters with the WordPress API.
	 *
	 * @return void
	 */
	public function setup() {
		if ( self::$init ) {
			return;
		}

		parent::setup();

		add_filter( 'rest_prepare_' . Grants::get_cpt_slug(), array( $this, 'modify_grants_rest_response' ), 10, 3 );
		add_filter( 'rest_' . Grants::get_cpt_slug() . '_query', array( $this, 'modify_grants_rest_params' ), 10, 2 );

		self::$init = true;
	}

	/**
	 * Ensure the Grants REST API returns all Grants
	 *
	 * @param array           $args    The params used in the request.
	 * @param WP_REST_Request $request The post type object.
	 *
	 * @return array The modified query params
	 */
	public function modify_grants_rest_params( $args, $request ) {
		$args['nopaging'] = true;
		return $args;
	}

	/**
	 * Modify the REST response for the Grants Post Type
	 *
	 * @param WP_REST_Response $response The response object.
	 * @param \WP_Post         $post     The post object.
	 * @param \WP_REST_Request $request  The request object.
	 *
	 * @return \WP_REST_Response The modified response
	 */
	public function modify_grants_rest_response( $response, $post, $request ) {
		$new_response = wp_cache_get( 'grants_rest_response_' . $post->ID, 'ca-grants-plugin' );

		if ( false === $new_response ) {
			// Fields that aren't needed in the REST response
			$blacklisted_fields = array(
				'grant-hash',
				'applications-submitted',
				'matchingFundsNotes',
				'disbursementMethodNotes',
				'adminSecondaryContact',
			);

			$metafields = array_merge(
				Meta\AwardStats::get_fields(),
				Meta\General::get_fields(),
				Meta\Eligibility::get_fields(),
				Meta\Funding::get_fields(),
				Meta\Dates::get_fields(),
				Meta\Contact::get_fields(),
				Meta\Notes::get_fields()
			);

			$new_data = array(
				'grantTitle' => get_the_title( $post->ID ),
				'uniqueID'   => strval( $post->ID ),
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
						case 'estimatedAvailableFunds':
							$new_data['estimatedAvailableFunds'] = absint( $metadata );
							break;
						case 'estimatedAwards':
							if ( 'exact' === $metadata['checkbox'] ) {
								$new_data['estimatedAwards'] = array(
									'exact' => absint( $metadata['exact'] ),
								);
							} elseif ( 'between' === $metadata['checkbox'] ) {
								$new_data['estimatedAwards'] = array(
									'between' => array(
										absint( $metadata['between']['low'] ),
										absint( $metadata['between']['high'] ),
									),
								);
							} elseif ( 'dependant' === $metadata['checkbox'] ) {
								$new_data['estimatedAwards'] = array(
									'dependent' => esc_html__( 'Dependant on number of awards.', 'ca-grants-plugin' ),
								);
							}
							break;

						case 'loiRequired':
							$new_data['loiRequired'] = ( 'yes' === $metadata ) ? true : false;
							break;

						case 'matchingFunds':
							$notes = get_post_meta( $post->ID, 'matchingFundsNotes', true );

							$new_data['matchingFunds'] = array(
								'required' => ( 'yes' === $metadata['checkbox'] ) ? true : false,
								'percent'  => absint( $metadata['percentage'] ?? 0 ),
								'notes'    => $notes,
							);

							break;

						case 'disbursementMethod':
							$notes = get_post_meta( $post->ID, 'disbursementMethodNotes', true );

							$new_data['fundingMethod'] = array(
								'type'  => $metadata,
								'notes' => $notes,
							);

							break;

						case 'estimatedAmounts':
							if ( 'same' === $metadata['checkbox'] ) {
								$new_data['estimatedAmounts'] = array(
									'same' => absint( $metadata['same']['amount'] ),
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
										absint( $metadata['unknown']['first'] ),
										absint( $metadata['unknown']['second'] ),
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

						case 'fundingSource':
							$notes                             = get_post_meta( $post->ID, 'revenueSourceNotes', true );
							$new_data[ $metafield_data['id'] ] = array(
								'type'  => $metadata,
								'notes' => $notes,
							);
							break;

						case 'deadline_hold':
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
							if ( 'number' === $metafield_data['type'] ) {
								$new_data[ $metafield_data['id'] ] = absint( $metadata );
							} else {
								$new_data[ $metafield_data['id'] ] = $metadata;
							}
							break;
					}
				}
			}

			$grant_award_url_base = rest_url( 'wp/v2/grant-awards' );

			if (
				defined( 'CA_HTTP_AUTH_USER' ) &&
				! empty( CA_HTTP_AUTH_USER ) &&
				defined( 'CA_HTTP_AUTH_PASSWORD' ) &&
				! empty( CA_HTTP_AUTH_PASSWORD )
			 ) {
				$auth_string = sprintf( '%s:%s@', CA_HTTP_AUTH_USER, CA_HTTP_AUTH_PASSWORD );
				$grant_award_url_base = str_replace( '://', '://' . $auth_string, $grant_award_url_base );
			}

			$grant_awards_url = add_query_arg(
				array(
					'grant_id' => $post->ID,
				),
				$grant_award_url_base
			);

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

			if ( Core\has_grant_awards( $post->ID ) ) {
				$new_response->add_link( 'award', $grant_awards_url );
			}

			wp_cache_set( 'grants_rest_response_' . $post->ID, $new_response, 'ca-grants-plugin', DAY_IN_SECONDS );
		}

		return $new_response;
	}
}
