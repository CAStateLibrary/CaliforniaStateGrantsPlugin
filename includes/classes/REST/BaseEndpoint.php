<?php
/**
 * Base Endpoint
 *
 * @package CaGov\Grants
 */

namespace CaGov\Grants\REST;

use CaGov\Grants\Admin\Settings;
use WP_Rest_Request;
use WP_Error;
use WP_Http;

use function CaGov\Grants\Core\is_portal;

/**
 * BaseEndpoint Class.
 */
class BaseEndpoint {

	/**
	 * Rest url Slug.
	 *
	 * @var string
	 */
	public static $rest_slug;

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
		// Add request validation if the plugin is installed on an external site.
		if ( true !== is_portal() ) {
			add_filter( 'rest_request_before_callbacks', array( $this, 'authenticate_rest_request' ), 10, 3 );
		}
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
		if ( 0 !== strpos( $request->get_route(), '/wp/v2/' . self::$rest_slug ) ) {
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
}
