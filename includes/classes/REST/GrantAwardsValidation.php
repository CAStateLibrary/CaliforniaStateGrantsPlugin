<?php
/**
 * Grant Awards Field Data Validation Endpoint.
 *
 * @package CaGov\Grants
 */

namespace CaGov\Grants\REST;

use CaGov\Grants\PostTypes\AwardUploads;
use CaGov\Grants\PostTypes\EditGrantAwards;
use CaGov\Grants\Meta\GrantAwards;
use WP_REST_Controller;
use WP_REST_Server;
use WP_Rest_Request;
use function CaGov\Grants\Core\get_rest_namespace;

/**
 * Class GrantAwardsValidation
 */
class GrantAwardsValidation extends WP_REST_Controller {

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
		$this->namespace = get_rest_namespace();
		$this->post_type = AwardUploads::CPT_SLUG;
		$this->rest_base = 'grant-awards-validation';
	}

	/**
	 * Get endpoint url.
	 *
	 * @return string
	 */
	public static function get_endpoint_url() {
		return rest_url( get_rest_namespace() . 'grant-awards-validation' );
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
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'remote_validate' ),
					'permission_callback' => '__return_true',
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
				),
				'schema' => array( $this, 'get_schema' ),
			)
		);
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

		$metafields  = GrantAwards::get_fields();
		$schema_args = array();

		foreach ( $metafields as $meta ) {

			switch ( $meta['type'] ) {
				case 'post-finder':
				case 'save_to_field':
				case 'number':
					$type = 'integer';
					break;
				default:
					$type = 'string';
					break;
			}

			$schema_args[ $meta['id'] ] = array(
				'description' => isset( $meta['name'] ) ? $meta['name'] : '',
				'type'        => $type,
				'context'     => array( 'edit' ),
			);
		}

		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => $this->post_type,
			'type'       => 'object',
			'properties' => $schema_args,
		);

		$this->schema = $schema;

		return $this->schema;
	}

	/**
	 * Validate requested params.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return object Response object on success, or WP_Error object on failure.
	 */
	public function remote_validate( $request ) {
		$data = $request->get_params();

		if ( empty( $data ) ) {
			return wp_send_json_error( esc_html__( 'No data found to validate.', 'ca-grants-plugin' ), 400 );
		}

		$edit_award_class = new EditGrantAwards();
		$validated_data   = $edit_award_class->validate_fields( $data );

		if ( is_wp_error( $validated_data ) ) {
			return wp_send_json_error( $validated_data, 500 );
		}

		return wp_send_json_success( $data );
	}
}
