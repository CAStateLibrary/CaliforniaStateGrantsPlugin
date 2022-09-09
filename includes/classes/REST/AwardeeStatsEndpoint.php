<?php
/**
 * Awardee stats update endpoint.
 *
 * @package CaGov\Grants
 */

namespace CaGov\Grants\REST;

use CaGov\Grants\Core;
use CaGov\Grants\Meta;
use CaGov\Grants\PostTypes\Grants;
use WP_REST_Controller;
use WP_REST_Server;
use WP_REST_Response;
use WP_Rest_Request;
use WP_Error;

/**
 * Class AwardeeStatsEndpoint
 */
class AwardeeStatsEndpoint extends WP_REST_Controller {

	/**
	 * Init
	 *
	 * @var boolean
	 */
	public static $init = false;

	/**
	 * Namespace for the endpoint.
	 *
	 * @var string
	 */
	public $namespace;

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type;

	/**
	 * Cached results of get_item_schema.
	 *
	 * @var array
	 */
	protected $schema;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->namespace = Core\get_rest_namespace();
		$this->post_type = Grants::get_cpt_slug();
		$this->rest_base = 'awardee-stats';
	}

	/**
	 * Get endpoint url.
	 *
	 * @return string
	 */
	public static function get_endpoint_url() {
		return rest_url( Core\get_rest_namespace() . 'awardee-stats' );
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

		add_action( 'rest_api_init', array( $this, 'register_routes' ), 11 );

		self::$init = true;
	}

	/**
	 * Register Routes for Custom Endpoint Request.
	 */
	public function register_routes() {

		register_rest_route(
			$this->namespace,
			$this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
				),
				'schema' => array( $this, 'get_schema' ),
			)
		);
	}

	/**
	 * Checks if a given request has access to update a post.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return true|WP_Error True if the request has access to update items, WP_Error object otherwise.
	 */
	public function update_item_permissions_check( $request ) {
		$current_user_id = get_current_user_id();

		if ( empty( $current_user_id ) ) {
			return new WP_Error(
				'rest_user_not_found',
				__( 'Sorry, you are not allowed to perform this operation without user authentication.', 'ca-grants-plugin' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		$grant_id = empty( $request['grantID'] ) ? 0 : $request['grantID'];
		$post     = $this->get_post( $grant_id );

		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$post_type = get_post_type_object( $this->post_type );

		if ( empty( $post_type ) || ! current_user_can( 'edit_post', $post->ID ) ) {
			return new WP_Error(
				'rest_cannot_edit',
				__( 'Sorry, you are not allowed to edit awardee stats as this user.', 'ca-grants-plugin' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Get the post, if the ID is valid.
	 *
	 * @since 4.7.2
	 *
	 * @param int $id Supplied ID.
	 * @return WP_Post|WP_Error Post object if ID is valid, WP_Error otherwise.
	 */
	protected function get_post( $id ) {
		$error = new WP_Error(
			'rest_invalid_grant_id',
			__( 'Invalid grantID found. Please provide valid data and try again.', 'ca-grants-plugin' ),
			array( 'status' => 404 )
		);

		if ( (int) $id <= 0 ) {
			return $error;
		}

		$post = get_post( (int) $id );
		if ( empty( $post ) || empty( $post->ID ) || $this->post_type !== $post->post_type ) {
			return $error;
		}

		return $post;
	}

	/**
	 * Retrieves the post's schema, conforming to JSON Schema.
	 *
	 * @return array Item schema data.
	 */
	public function get_item_schema() {

		if ( $this->schema ) {
			return $this->schema;
		}

		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => $this->post_type,
			'type'       => 'object',
			'properties' => array(
				'grantID'               => array(
					'description' => __( 'Grant ID to update awardee stats data for.', 'ca-grants-plugin' ),
					'type'        => 'integer',
					'context'     => array( 'edit' ),
				),
				'applicationsSubmitted' => array(
					'description' => __( 'The total applications received for this funding opportunity.', 'ca-grants-plugin' ),
					'type'        => 'integer',
					'context'     => array( 'edit' ),
				),
				'grantsAwarded'         => array(
					'description' => __( 'The number of individual grants awarded for this grant opportunity.', 'ca-grants-plugin' ),
					'type'        => 'integer',
					'context'     => array( 'edit' ),
				),
				'fiscalYear'            => array(
					'description' => __( 'The Fiscal Year to import awards for.', 'ca-grants-plugin' ),
					'type'        => 'string',
					'context'     => array( 'edit' ),
				),
			),
		);

		$this->schema = $schema;

		return $this->schema;
	}

	/**
	 * Get all params values.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return array|WP_Error Return param value array if required value exists else WP_Error if any one if empty.
	 */
	public function get_param_values( $request ) {

		$required_params = array(
			'grantID'               => $request->get_param( 'grantID' ),
			'applicationsSubmitted' => $request->get_param( 'applicationsSubmitted' ),
			'grantsAwarded'         => $request->get_param( 'grantsAwarded' ),
			'fiscalYear'            => $request->get_param( 'fiscalYear' ),
		);

		$empty_params = array_keys(
			array_filter(
				$required_params,
				function( $key ) use ( $request ) {
						return ! $request->has_param( $key );
				},
				ARRAY_FILTER_USE_KEY
			)
		);

		if ( ! empty( $empty_params ) ) {
			return new WP_Error(
				'rest_missing_param',
				__( 'Missing parameter(s): ', 'ca-grants-plugin' ) . implode( ', ', $empty_params ),
				array( 'status' => 400 )
			);
		}

		// Negative value.
		if ( (int) $required_params['grantsAwarded'] < 0 ) {
			return new WP_Error(
				'rest_invalid_value',
				__( 'Invalid value for param: grantsAwarded. Please add non negative value.', 'ca-grants-plugin' ),
				array( 'status' => 400 )
			);
		}

		// Negative value.
		if ( (int) $required_params['applicationsSubmitted'] < 0 ) {
			return new WP_Error(
				'rest_invalid_value',
				__( 'Invalid value for param: applicationsSubmitted. Please add non negative value.', 'ca-grants-plugin' ),
				array( 'status' => 400 )
			);
		}

		return $required_params;
	}

	/**
	 * Update awardee stats data for grant.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function update_item( $request ) {
		$request_params = $this->get_param_values( $request );

		if ( is_wp_error( $request_params ) ) {
			return wp_send_json_error( $request_params->get_error_messages(), 400 );
		}

		$grant_id         = $request_params['grantID'];
		$award_stats_data = [
			'fiscalYear'            => $request_params['fiscalYear'],
			'applicationsSubmitted' => $request_params['applicationsSubmitted'],
			'grantsAwarded'         => $request_params['grantsAwarded'],
		];

		$validate_field = Meta\AwardStats::get_validation_errors( $award_stats_data, $grant_id );

		if ( is_wp_error( $validate_field ) && $validate_field->has_errors() ) {
			return $validate_field;
		}

		$existing_values  = Core\is_ongoing_grant( $grant_id ) ? get_post_meta( $grant_id, 'awardStats', true ) : [];
		$award_stats_data = empty( $existing_values ) ? [ $award_stats_data ] : array_merge( $existing_values, [ $award_stats_data ] );
		$award_stats_data = Meta\AwardStats::sanitize_award_stats_data( $award_stats_data );

		$args = array(
			'ID'          => $grant_id,
			'meta_input'  => array(
				'awardStats' => $award_stats_data,
			),
		);

		$grant_update_id = wp_update_post( $args );

		if ( empty( $grant_update_id ) || is_wp_error( $grant_update_id ) ) {
			return new WP_Error(
				'rest_update_failed',
				__( 'Updating awardee stats data operation failed. Please check data and try again.', 'ca-grants-plugin' ),
				array( 'status' => 400 )
			);
		}

		return wp_send_json_success( array( 'grantID' => $grant_update_id ), 200 );
	}
}
