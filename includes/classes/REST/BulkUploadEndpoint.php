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
	 * CSV File header info.
	 *
	 * @return array List of csv header.
	 */
	public function get_csv_header() {

		// TODO: Remove "*" if not needed.
		return array(
			'Project Title',
			'Recipient Type *',
			'Indivudal Recipient First Name*',
			'Individual Recipient Last Name*',
			'"Other" Recipient Type*',
			'Primary Recipient Name*',
			'Sub-recipients?',
			'Total Award Amount*',
			'Matching Funding Amount*',
			'Award Amount Notes',
			'Beginning Date of Grant-Funded Project*',
			'End Date of Grant-Funded Project*',
			'Project Abstract*',
			'Geographic Location Served*',
			'Counties Served*',
			'Geographic Location Served Notes',
		);
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

		$post_type = get_post_type_object( $this->post_type );

		if ( ! current_user_can( $post_type->cap->edit_others_posts ) ) {
			return new WP_Error(
				'rest_cannot_edit_others',
				__( 'Sorry, you are not allowed to create upload award as this user.', 'ca-grants-plugin' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		if ( ! current_user_can( $post_type->cap->create_posts ) ) {
			return new WP_Error(
				'rest_cannot_create',
				__( 'Sorry, you are not allowed to create posts as this user.', 'ca-grants-plugin' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		if ( ! current_user_can( 'upload_files' ) ) {
			return new WP_Error(
				'rest_cannot_create',
				__( 'Sorry, you are not allowed to upload media on this site.', 'ca-grants-plugin' ),
				array( 'status' => 400 )
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

		$grant = get_post( $required_params['grantID'] );

		if ( empty( $grant ) || $grant->post_type !== Grants::get_cpt_slug() ) {
			return new WP_Error(
				'rest_invalid_grant_id',
				__( 'Invalid grantID found. Please provide valid data and try again.', 'ca-grants-plugin' ) . implode( ', ', $empty_params ),
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
			return wp_send_json_error( $request_params, 400 );
		}

		$headers = $request->get_headers();
		$file    = $this->upload_from_file( $request_params['awardCSV'], $headers );

		if ( is_wp_error( $file ) ) {
			return wp_send_json_error( $file, 400 );
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
			return wp_send_json_error( $attachment_id, 400 );
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
			return wp_send_json_error( $award_upload_id, 404 );
		}

		// Store "Number of Applications Submitted" to provided grant ID.
		if ( ! empty( $meta['applicationsSubmitted'] ) ) {
			update_post_meta( $meta['csl_grant_id'], 'applicationsSubmitted', $meta['applicationsSubmitted'] );
		}

		// Store "Number of Grants Awarded" to provided grant ID.
		if ( ! empty( $meta['grantsAwarded'] ) ) {
			update_post_meta( $meta['csl_grant_id'], 'grantsAwarded', $meta['grantsAwarded'] );
		}

		// Update attachment post parent.
		wp_update_post(
			array(
				'ID'          => $attachment_id,
				'post_parent' => $award_upload_id,
			)
		);

		return wp_send_json_success( array( 'awardUploadID' => $award_upload_id ), 200 );
	}

	/**
	 * Handles an upload via multipart/form-data ($_FILES) in REST request.
	 *
	 * @param array $file   Data from the `$_FILES` superglobal.
	 * @param array $headers HTTP headers from the request.
	 *
	 * @return array|WP_Error Data from wp_handle_upload().
	 */
	protected function upload_from_file( $file, $headers ) {
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
		$validate_file = $this->validate_csv_file( $file );

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

	/**
	 * Validate temp uploaded file.
	 *
	 * @param array $csv_file Temp uploaded file data.
	 *
	 * @return boolean|WP_Error Return WP_Error on empty or invalid file, else return true.
	 */
	public function validate_csv_file( $csv_file ) {
		$csv_headers = $this->get_csv_header();
		$file_handle = fopen( $csv_file['tmp_name'], 'r' );

		if ( feof( $file_handle ) ) {
			// 'File content not found'
			return new WP_Error(
				'rest_empty_csv_file',
				__( 'File content not found.', 'ca-grants-plugin' ),
				array( 'status' => 500 )
			);
		}

		$headers = fgetcsv( $file_handle, 4096 );
		fclose( $file_handle );

		$diff = array_diff( $csv_headers, $headers );

		if ( ! empty( $diff ) ) {
			// Header miss match.
			return new WP_Error(
				'rest_missmatch_csv_header',
				__( 'Invalid CSV: File header miss-matached. Please use valid csv file.', 'ca-grants-plugin' ),
				array( 'status' => 500 )
			);
		}

		return true;
	}
}
