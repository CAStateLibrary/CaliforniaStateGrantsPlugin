<?php
/**
 * Grant Awards Endpoint
 *
 * @package CaGov\Grants
 */

namespace CaGov\Grants\REST;

use CaGov\Grants\PostTypes\Grants;
use CaGov\Grants\PostTypes\GrantAwards;
use CaGov\Grants\Meta;
use WP_REST_Response;
use WP_Rest_Request;
use WP_Error;
use WP_Http;

/**
 * GrantsEndpoint Class.
 */
class GrantAwardsEndpoint extends BaseEndpoint {

	/**
	 * Rest url Slug.
	 *
	 * @var string
	 */
	public static $rest_slug = 'grant-awards';

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

		add_filter( 'rest_' . GrantAwards::CPT_SLUG . '_collection_params', array( $this, 'modify_collection_params' ) );
		add_filter( 'rest_request_before_callbacks', array( $this, 'grant_id_present_rest_request' ), 10, 3 );
		add_filter( 'rest_prepare_' . GrantAwards::CPT_SLUG, array( $this, 'modify_grants_rest_response' ), 10, 2 );
		add_filter( 'rest_' . GrantAwards::CPT_SLUG . '_query', array( $this, 'modify_grants_rest_params' ), 10, 2 );

		self::$init = true;
	}

	/**
	 * Modify collection parameters for the grant awards post type REST controller.
	 *
	 * @param array $query_params JSON Schema-formatted collection parameters.
	 *
	 * @return array
	 */
	public function modify_collection_params( $query_params ) {

		$query_params['grant_id'] = array(
			'description'       => __( 'Grant id to get list of awards associated with this grant.', 'ca-grants-plugin' ),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'required'          => true,
		);

		$query_params['fiscal_year'] = array(
			'description' => __( 'Fiscal Year Taxonomy slug, to get all grant awards within provided fiscal year for the grant. i.e 2020-2021', 'ca-grants-plugin' ),
			'type'        => 'string',
		);

		$query_params['orderby'] = array(
			'description' => __( 'Sort collection by post attribute.', 'ca-grants-plugin' ),
			'type'        => 'string',
			'default'     => 'date',
			'enum'        => array(
				'date', // Post date
				'name', // Post Title
				'project', // Project Title
				'amount', // Total Award Amount
				'start_date', // Beginning Date of Grant-Funded Project
				'end_date', // End Date of Grant-Funded Project
			),
		);

		return $query_params;
	}

	/**
	 * Authenticate the REST Requests
	 *
	 * @param  \WP_HTTP_Response|WP_Error $response Result to send.
	 * @param  array                      $handler  Route handler used.
	 * @param  \WP_REST_Request           $request  Request used to generate response.
	 * @return \WP_HTTP_Response|WP_Error           WP_HTTP_Response if authentication succeeded,
	 *                                              WP_Error otherwise.
	 */
	public function grant_id_present_rest_request( $response, $handler, $request ) {
		if ( 0 !== strpos( $request->get_route(), '/wp/v2/' . self::$rest_slug ) ) {
			return $response;
		}

		$grant_id = sanitize_text_field( $request->get_param( 'grant_id' ) );

		if ( empty( $grant_id ) ) {
			return new WP_Error(
				'empty_grant_id',
				__( 'Grant id must be provided in query args.', 'ca-grants-plugin' ),
				array(
					'status' => WP_Http::BAD_REQUEST,
				)
			);
		}

		$grant = get_post( $grant_id );

		if ( empty( $grant ) || Grants::get_cpt_slug() !== $grant->post_type ) {
			return new WP_Error(
				'invalid_grant_id',
				__( 'Invalid grant id found.', 'ca-grants-plugin' ),
				array(
					'status' => WP_Http::NOT_FOUND,
				)
			);
		}

		return $response;
	}

	/**
	 * Ensure the Grant Awards REST API returns all Grants
	 *
	 * @param array           $args    The params used in the request.
	 * @param WP_REST_Request $request The post type object.
	 *
	 * @return array The modified query params
	 */
	public function modify_grants_rest_params( $args, $request ) {
		$grant_id      = sanitize_text_field( $request->get_param( 'grant_id' ) );
		$fiscal_year   = sanitize_text_field( $request->get_param( 'fiscal_year' ) );
		$orderby       = sanitize_text_field( $request->get_param( 'orderby' ) );
		$override_args = array(
			'orderby' => 'date',
		);

		if ( ! empty( $grant_id ) ) {
			$override_args['meta_query'][] = array(
				'key'     => 'grantID',
				'value'   => $grant_id,
				'compare' => '=',
			);
		}

		if ( ! empty( $fiscal_year ) ) {
			$override_args['meta_query'][] = array(
				'key'     => 'fiscalYear',
				'value'   => $fiscal_year,
				'compare' => '=',
			);
		}

		if ( ! empty( $orderby ) ) {
			$orderby_mappings = array(
				'name'       => 'title', // Post Title
				'project'    => 'projectTitle', // Project Title
				'amount'     => 'totalAwardAmount', // Total Award Amount
				'start_date' => 'grantFundedStartDate', // Beginning Date of Grant-Funded Project
				'end_date'   => 'grantFundedEndDate', // End Date of Grant-Funded Project
			);

			if ( 'name' === $orderby ) {
				$override_args['orderby'] = $orderby_mappings[ $orderby ];
			} elseif ( 'amount' === $orderby ) {
				$override_args['orderby']  = 'meta_value_num';
				$override_args['meta_key'] = $orderby_mappings[ $orderby ];
			} else {
				$override_args['orderby']  = 'meta_value';
				$override_args['meta_key'] = $orderby_mappings[ $orderby ];
			}
		}

		return wp_parse_args( $override_args, $args );
	}

	/**
	 * Modify the REST response for the Grant Awards Post Type
	 *
	 * @param WP_REST_Response $response The response object.
	 * @param \WP_Post         $post     The post object.
	 *
	 * @return \WP_REST_Response The modified response
	 */
	public function modify_grants_rest_response( $response, $post ) {
		// TODO: Add custom cache for grant award response, with respecting params like orderby.

		// Fields that aren't needed in the REST response
		$blacklisted_fields = array(
			'applicationsSubmitted',
			'grantsAwarded',
		);

		$metafields = array_merge(
			Meta\GrantAwards::get_fields(),
		);

		$new_data     = array(
			'grantAwardTitle' => get_the_title( $post->ID ),
			'uniqueID'        => $post->ID,
		);
		$new_response = new WP_REST_Response();
		$new_response->set_status( 200 );
		$new_response->set_headers(
			array(
				'Content-Type'  => 'application/json',
				'last_modified' => $post->post_modified,
				'Cache-Control' => 'max-age=' . WEEK_IN_SECONDS,
			)
		);

		if ( empty( $metafields ) ) {
			$new_response->set_data( $new_data );
			return $new_response;
		}

		foreach ( $metafields as $metafield_data ) {
			// Skip meta fields we don't want in the REST response
			if ( in_array( $metafield_data['id'], $blacklisted_fields, true ) ) {
				continue;
			}

			// Get the metadata for this post
			$metadata = get_post_meta( $post->ID, $metafield_data['id'], true );

			// Some fields need special handling
			switch ( $metafield_data['type'] ) {

				case 'post-finder':
				case 'number':
					$new_data[ $metafield_data['id'] ] = absint( $metadata );
					break;
				case 'textarea':
					$new_data[ $metafield_data['id'] ] = apply_filters( 'the_content', $metadata );
					break;
				default:
					$new_data[ $metafield_data['id'] ] = $metadata;
					break;
			}
		}

		$new_response->set_data( $new_data );

		return $new_response;
	}
}
