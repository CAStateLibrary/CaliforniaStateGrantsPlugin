<?php
/**
 * Grants Endpoint
 *
 * @package CaGov\Grants
 */

namespace CaGov\Grants\REST;

use CaGov\Grants\PostTypes\Grants;
use CaGov\Grants\PostTypes\GrantAwards;
use CaGov\Grants\Meta;
use CaGov\Grants\Admin\Settings;
use WP_REST_Response;
use WP_Rest_Request;
use WP_Error;
use WP_Http;

/**
 * GrantsEndpoint Class.
 */
class GrantAwardsEndpoint {
	/**
	 * Init
	 *
	 * @var boolean
	 */
	public static $init = false;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->settings = new Settings();
	}

	/**
	 * Setup actions and filters with the WordPress API.
	 *
	 * @return void
	 */
	public function setup() {
		if ( self::$init ) {
			return;
		}

		add_filter( 'rest_' . GrantAwards::CPT_SLUG . '_collection_params', array( $this, 'modify_collection_params' ) );
		add_filter( 'rest_request_before_callbacks', array( $this, 'authenticate_rest_request' ), 10, 3 );
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
			'default'     => 'name',
			'enum'        => array(
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
	public function authenticate_rest_request( $response, $handler, $request ) {
		if ( 0 !== strpos( $request->get_route(), '/wp/v2/grant-awards' ) ) {
			return $response;
		}

		// Ensure authorization header is present.
		$auth_header_present = $this->auth_header_present( $response, $request );
		if ( is_wp_error( $auth_header_present ) ) {
			return $auth_header_present;
		}

		// Ensure bearer token is valid.
		$token_valid = $this->auth_token_valid( $response, $request );
		if ( is_wp_error( $token_valid ) ) {
			return $token_valid;
		}

		// Authorization successful.
		return $response;
	}

	/**
	 * Auth header present.
	 *
	 * @param  mixed           $response The current response object.
	 * @param  WP_REST_Request $request  The current request object.
	 * @return mixed                     Response if successful, WP_Error otherwise.
	 */
	protected function auth_header_present( $response, WP_REST_Request $request ) {
		$auth_header = $request->get_header( 'X-CaGov-Token' );
		if ( empty( $auth_header ) ) {
			return new WP_Error(
				'empty_auth_header',
				__( 'An authorization header must be provided.', 'ca-grants-plugin' ),
				array(
					'status' => WP_Http::BAD_REQUEST,
				)
			);
		}
		return $response;
	}

	/**
	 * Auth token valid.
	 *
	 * @param  mixed           $response The current response object.
	 * @param  WP_REST_Request $request  The current request object.
	 * @return mixed                     Response if successful, WP_Error otherwise.
	 */
	protected function auth_token_valid( $response, WP_REST_Request $request ) {
		$auth_header  = $request->get_header( 'X-CaGov-Token' );
		$auth_token   = sanitize_text_field( $auth_header );
		$stored_token = sha1( $this->settings->get_auth_token() );
		if ( empty( $stored_token ) || $stored_token !== $auth_token ) {
			return new WP_Error(
				'invalid_auth',
				__( 'The authorization token does not match.', 'ca-grants-plugin' ),
				array(
					'status' => WP_Http::UNAUTHORIZED,
				)
			);
		}
		return $response;
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
		if ( 0 !== strpos( $request->get_route(), '/wp/v2/grant-awards' ) ) {
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

		if ( empty( $grant ) || Grants::CPT_SLUG !== $grant->post_type ) {
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
	 * Ensure the Grants REST API returns all Grants
	 *
	 * @param array           $args    The params used in the request.
	 * @param WP_REST_Request $request The post type object.
	 *
	 * @return array The modified query params
	 */
	public function modify_grants_rest_params( $args, $request ) {
		$grant_id      = sanitize_text_field( $request->get_param( 'grant_id' ) );
		$fiscal_year   = sanitize_text_field( $request->get_param( 'fiscal_year' ) );
		$override_args = array();

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
		$new_response = wp_cache_get( 'grant_award_rest_response_' . $post->ID );

		if ( false !== $new_response ) {
			return $new_response;
		}

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

		wp_cache_set( 'grant_award_rest_response_' . $post->ID, $new_response );

		return $new_response;
	}
}
