<?php
/**
 * Bulk Upload Awards Endpoint.
 *
 * @package CaGov\Grants
 */

namespace CaGov\Grants\REST;

use CaGov\Grants\PostTypes\Grants;
use CaGov\Grants\PostTypes\AwardUploads;
use WP_REST_Controller;
use WP_REST_Server;
use WP_REST_Response;
use WP_Rest_Request;
use WP_Error;
use function CaGov\Grants\Core\get_rest_namespace;
use function CaGov\Grants\Core\is_portal;

/**
 * Class BulkUploadEndpoint
 */
class BulkUploadEndpoint extends WP_REST_Controller {

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
		$this->rest_base = 'bulk-uploads';
	}

	/**
	 * Get endpoint url.
	 *
	 * @return string
	 */
	public static function get_endpoint_url() {
		return rest_url( get_rest_namespace() . '/bulk-uploads' );
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
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
				),
				'schema' => array( $this, 'get_schema' ),
			)
		);
	}

	/**
	 * Checks if a given request has access to create a post.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return true|WP_Error True if the request has access to create items, WP_Error object otherwise.
	 */
	public function create_item_permissions_check( $request ) {

		$current_user_id = get_current_user_id();

		if ( empty( $current_user_id ) ) {
			return new WP_Error(
				'rest_user_not_found',
				__( 'Sorry, you are not allowed to perform this operation without user.', 'ca-grants-plugin' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		$grant = get_post( $request->get_param( 'grantID' ) );

		if ( empty( $grant ) || $grant->post_type !== Grants::get_cpt_slug() ) {
			return new WP_Error(
				'rest_invalid_grant_id',
				__( 'Invalid grantID found. Please provide valid data and try again.', 'ca-grants-plugin' ),
				array( 'status' => 400 )
			);
		}

		$grant_post_type_obj = get_post_type_object( Grants::get_cpt_slug() );
		$edit_cap            = ! empty( $grant_post_type_obj->cap->edit_post ) ? $grant_post_type_obj->cap->edit_post : 'edit_post';

		if ( ! current_user_can( $edit_cap, $grant->ID ) ) {
			return new WP_Error(
				'rest_cannot_edit_others',
				__( 'Sorry, you are not allowed to create upload award as this user.', 'ca-grants-plugin' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		if ( ! is_portal() && ! current_user_can( 'upload_files' ) ) {
			return new WP_Error(
				'rest_cannot_create',
				__( 'Sorry, you are not allowed to upload media on this site.', 'ca-grants-plugin' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
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
					'description' => __( 'Grant ID to upload award data for.', 'ca-grants-plugin' ),
					'type'        => 'integer',
					'context'     => array( 'edit' ),
					'readonly'    => true,
				),
				'applicationsSubmitted' => array(
					'description' => __( 'The total applications received for this funding opportunity.', 'ca-grants-plugin' ),
					'type'        => 'integer',
					'context'     => array( 'edit' ),
					'readonly'    => true,
				),
				'grantsAwarded'         => array(
					'description' => __( 'The number of individual grants awarded for this grant opportunity.', 'ca-grants-plugin' ),
					'type'        => 'integer',
					'context'     => array( 'edit' ),
					'readonly'    => true,
				),
				'fiscalYear'            => array(
					'description' => __( 'The Fiscal Year to import awards for.', 'ca-grants-plugin' ),
					'type'        => 'string',
					'context'     => array( 'edit' ),
					'readonly'    => true,
				),
				'awardCSV'              => array(
					'description' => __( 'The CSV containing award data.', 'ca-grants-plugin' ),
					'type'        => 'file',
					'context'     => array( 'edit' ),
					'readonly'    => true,
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

		$files = $request->get_file_params();

		$required_params = array(
			'grantID'  => $request->get_param( 'grantID' ),
			'awardCSV' => empty( $files['awardCSV'] ) ? array() : $files['awardCSV'],
		);

		$empty_params = array_keys(
			array_filter(
				$required_params,
				function( $param ) {
					return empty( $param );
				}
			)
		);

		if ( ! empty( $empty_params ) ) {
			return new WP_Error(
				'rest_missing_param',
				__( 'Missing parameter(s): ', 'ca-grants-plugin' ) . implode( ', ', $empty_params ),
				array( 'status' => 400 )
			);
		}

		$additional_params = array(
			'applicationsSubmitted' => $request->get_param( 'applicationsSubmitted' ),
			'grantsAwarded'         => $request->get_param( 'grantsAwarded' ),
			'fiscalYear'            => $request->get_param( 'fiscalYear' ),
		);

		return array_merge( $required_params, $additional_params );
	}

	/**
	 * Creates a single Award Upload.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function create_item( $request ) {
		$request_params = $this->get_param_values( $request );

		if ( is_wp_error( $request_params ) ) {
			return wp_send_json_error( $request_params->get_error_messages(), 400 );
		}

		$headers = $request->get_headers();
		$file    = $this->upload_from_file( $request_params, $headers );

		if ( is_wp_error( $file ) ) {
			return wp_send_json_error( $file->get_error_messages(), 400 );
		}

		$current_user_id = get_current_user_id();
		$name            = wp_basename( $file['file'] );
		$name_parts      = pathinfo( $name );
		$name            = trim( substr( $name, 0, -( 1 + strlen( $name_parts['extension'] ) ) ) );

		$attachment = array(
			'post_author'    => $current_user_id,
			'post_mime_type' => $file['type'] ?: '',
			'guid'           => $file['url'] ?: '',
			'post_title'     => preg_replace( '/\.[^.]+$/', '', $name ),
		);

		// Insert bulk upload csv file.
		$attachment_id = wp_insert_attachment( $attachment, $file['file'], 0, true, false );

		if ( is_wp_error( $attachment_id ) ) {
			return wp_send_json_error( $attachment_id->get_error_messages(), 400 );
		}

		$meta_params_mapping = array(
			'grantID'    => 'csl_grant_id',
			'awardCSV'   => 'csl_award_csv',
			'fiscalYear' => 'csl_fiscal_year',
		);

		$meta = array();

		foreach ( $request_params as $key => $value ) {
			if ( ! isset( $meta_params_mapping[ $key ] ) ) {
				continue;
			}

			$meta[ $meta_params_mapping[ $key ] ] = $value;
		}

		// Update csv file id.
		$meta['csl_award_csv'] = $attachment_id;

		$args = array(
			'post_author' => $current_user_id,
			'post_title'  => get_the_title( $meta['csl_grant_id'] ),
			'post_type'   => $this->post_type,
			'post_status' => 'pending',
			'meta_input'  => array_filter( $meta ),
		);

		$award_upload_id = wp_insert_post( $args );

		if ( is_wp_error( $award_upload_id ) ) {
			// Delete attachment csv if post insert operatoin fails.
			wp_delete_attachment( $attachment_id );
			return wp_send_json_error( $award_upload_id->get_error_messages(), 404 );
		}

		// Store "Number of Applications Submitted" to provided grant ID.
		if ( ! empty( $request_params['applicationsSubmitted'] ) ) {
			update_post_meta( $meta['csl_grant_id'], 'applicationsSubmitted', $request_params['applicationsSubmitted'] );
		}

		// Store "Number of Grants Awarded" to provided grant ID.
		if ( ! empty( $request_params['grantsAwarded'] ) ) {
			update_post_meta( $meta['csl_grant_id'], 'grantsAwarded', $request_params['grantsAwarded'] );
		}

		// Update attachment post parent.
		wp_update_post(
			array(
				'ID'          => $attachment_id,
				'post_parent' => $award_upload_id,
				'meta_input'  => [
					'bulkUploadAttachment' => true, // Keeping a flag to identify attachment is from bulk upload.
				]
			)
		);

		return wp_send_json_success( array( 'awardUploadID' => $award_upload_id ), 200 );
	}

	/**
	 * Handles an upload via multipart/form-data ($_FILES) in REST request.
	 *
	 * @param array $data Award uploads grantID and fiscalYear data with awardCSV $_FILES data.
	 * @param array $headers HTTP headers from the request.
	 *
	 * @return array|WP_Error Data from wp_handle_upload().
	 */
	protected function upload_from_file( $data, $headers ) {
		$file = $data['awardCSV'] ?: '';

		if ( empty( $file ) ) {
			return new WP_Error(
				'rest_upload_no_data',
				__( 'No data supplied.', 'ca-grants-plugin' ),
				array( 'status' => 400 )
			);
		}

		// Verify hash, if given.
		if ( ! empty( $headers['content_md5'] ) ) {
			$content_md5 = array_shift( $headers['content_md5'] );
			$expected    = trim( $content_md5 );
			$actual      = md5_file( $file['tmp_name'] );

			if ( $expected !== $actual ) {
				return new WP_Error(
					'rest_upload_hash_mismatch',
					__( 'Content hash did not match expected.', 'ca-grants-plugin' ),
					array( 'status' => 412 )
				);
			}
		}

		// Validate file before uploading it.
		if (
			defined( 'CSL_INVALID_BULK_UPLOAD_CSV_TEST_FILENAME' ) &&
			! empty( $file['name'] ) &&
			CSL_INVALID_BULK_UPLOAD_CSV_TEST_FILENAME === $file['name']
		) {
			// Bypass validation to test the failure notice sent out when an upload is created but the awards fail to import for some reason.
			$validate_file = true;
		} else {
			$validate_file = AwardUploads::validate_csv_file( $file, $data );
		}

		if ( is_wp_error( $validate_file ) ) {
			return $validate_file;
		}

		// Pass off to WP to handle the actual upload.
		$overrides = array(
			'test_form' => false,
			'mimes'     => array(
				'csv' => 'text/csv',
			),
		);

		if ( ! function_exists( 'wp_handle_upload' ) ) {
			// Include filesystem functions to get access to wp_handle_upload().
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		// Override uploads dir path to `uploads/awards`.
		add_filter( 'upload_dir', array( $this, 'update_uploads_dir_path' ) );

		$file = wp_handle_upload( $file, $overrides );

		remove_filter( 'upload_dir', array( $this, 'update_uploads_dir_path' ) );

		if ( isset( $file['error'] ) ) {
			return new WP_Error(
				'rest_upload_unknown_error',
				$file['error'],
				array( 'status' => 500 )
			);
		}

		return $file;
	}

	/**
	 * Update uploads directory path for bulk upload awards csv file.
	 *
	 * @param array $uploads Uploads directory data.
	 *
	 * @return array uploads directory path data.
	 */
	public function update_uploads_dir_path( $uploads ) {

		$awards_basedir = trailingslashit( $uploads['basedir'] ) . 'awards';
		$awards_baseurl = trailingslashit( $uploads['baseurl'] ) . 'awards';

		$override_uploads = array(
			'path'    => $awards_basedir,
			'url'     => $awards_baseurl,
			'basedir' => $awards_basedir,
			'baseurl' => $awards_baseurl,
		);

		return wp_parse_args( $override_uploads, $uploads );
	}
}
