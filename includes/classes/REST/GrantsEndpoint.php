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
		add_action( 'rest_api_init', array( $this, 'register_rest_additional_fields' ) );

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

			$metafields = array_merge(
				Meta\AwardStats::get_fields(),
				Meta\General::get_fields(),
				Meta\Eligibility::get_fields(),
				Meta\Funding::get_fields(),
				Meta\Dates::get_fields(),
				Meta\Contact::get_fields()
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

						case 'fundingMethod':
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

			$grant_awards_url = add_query_arg(
				array(
					'grant_id' => $post->ID,
				),
				rest_url( 'wp/v2/grant-awards' )
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

			wp_cache_set( 'grants_rest_response_' . $post->ID, $new_response );
		}

		return $new_response;
	}

	/**
	 * Register additional meta fields for endpoint.
	 *
	 * @return void
	 */
	public function register_rest_additional_fields() {

		$additional_meta = Meta\AwardStats::get_fields();

		foreach ( $additional_meta as $meta ) {
			register_rest_field(
				Grants::get_cpt_slug(),
				$meta['id'],
				[
					'schema'          => [
						'description' => $meta['name'],
						'type'        => ( 'number' === $meta['type'] ) ? 'integer' : 'string',
						'context'     => [ 'edit' ],
					],
					'update_callback' => array( $this, 'rest_fields_update_callback' ),
				]
			);
		}
	}

	/**
	 * Update callback for requested data/fields.
	 *
	 * @param mixed   $value Value provided from API to update.
	 * @param WP_Post $post Post object for which data is updated.
	 * @param string  $field_name Field name to update data.
	 *
	 * @return WP_Error|Boolean Return WP_Error if validation fail else return true.
	 */
	public function rest_fields_update_callback( $value, $post, $field_name ) {
		$additional_meta = Meta\AwardStats::get_fields();
		$current_field   = wp_filter_object_list( $additional_meta, array( 'id' => $field_name ) );
		$validate_field  = Meta\Field::maybe_get_field_errors( $current_field, array( $field_name => $value ) );

		if ( is_wp_error( $validate_field ) && $validate_field->has_errors() ) {
			return $validate_field;
		}

		Meta\Field::sanitize_and_save_fields( $current_field, $post->ID, array( $field_name => $value ) );

		return true;
	}
}
