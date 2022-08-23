<?php
/**
 * Post Type: Award Uploads
 *
 * @package CaGov\Grants
 */

namespace CaGov\Grants\PostTypes;

use CaGov\Grants\Cron\BulkAwardImport;
use CaGov\Grants\PostTypes\EditGrantAwards;
use CaGov\Grants\Admin\BulkUploadPage;
use WP_Error;

/**
 * Award Uploads post type class.
 */
class AwardUploads {

	const CPT_SLUG = 'csl_award_uploads';

	/**
	 * Init
	 *
	 * @var boolean
	 */
	public static $init = false;

	/**
	 * CSV File header info for award upload csv file.
	 * Mapping: [ "CSV Header Title" => "Grant Award Meta Key" ]
	 *
	 * @return array List of csv header.
	 */
	public static function get_csv_header_mapping() {

		return array(
			'Project Title'                           => 'projectTitle',
			'Recipient Type *'                        => 'recipientType',
			'Individual Recipient First Name*'        => 'primaryRecipientFirstName',
			'Individual Recipient Last Name*'         => 'primaryRecipientLastName',
			'Primary Recipient Name*'                 => 'primaryRecipientName',
			'Sub-recipients?*'                        => 'secondaryRecipients',
			'Total Award Amount*'                     => 'totalAwardAmount',
			'Matching Funding Amount*'                => 'matchingFundingAmount',
			'Award Amount Notes'                      => 'awardAmountNotes',
			'Beginning Date of Grant-Funded Project*' => 'grantFundedStartDate',
			'End Date of Grant-Funded Project*'       => 'grantFundedEndDate',
			'Project Abstract*'                       => 'projectAbstract',
			'Geographic Location Served*'             => 'geoLocationServed',
			'Counties Served*'                        => 'countiesServed',
			'Geographic Location Served Notes'        => 'geoServedNotes',
		);
	}

	/**
	 * Replace field id with csv mapped column name.
	 *
	 * @param string $text String to search replace field id with csv column name.
	 *
	 * @return string
	 */
	public static function maybe_replace_column_name( $text ) {

		foreach ( self::get_csv_header_mapping() as $csv_column => $field_id ) {
			$text = str_replace( $field_id, $csv_column, $text );
		}

		return $text;
	}

	/**
	 * Validate temp uploaded file.
	 *
	 * @param array|string $csv_file Temp uploaded file data OR file path.
	 * @param array        $data Award uploads grantID and fiscalYear data.
	 *
	 * @return boolean|WP_Error Return WP_Error on empty or invalid file, else return true.
	 */
	public static function validate_csv_file( $csv_file, $data ) {
		$file_path = is_array( $csv_file ) ? $csv_file['tmp_name'] : $csv_file;

		if ( ! file_exists( $file_path ) ) {
			// 'File content not found'
			return new WP_Error(
				'validate_csv_file_not_found',
				__( 'File not found.', 'ca-grants-plugin' ),
				array( 'status' => 500 )
			);
		}

		$csv_headers = array_keys( self::get_csv_header_mapping() );
		$file_handle = fopen( $file_path, 'r' );

		if ( feof( $file_handle ) ) {
			// 'File content not found'
			return new WP_Error(
				'validate_csv_empty_file',
				__( 'File content not found.', 'ca-grants-plugin' ),
				array( 'status' => 500 )
			);
		}

		$headers = fgetcsv( $file_handle, 4096 );
		fclose( $file_handle );
		// Trim whitespaces.
		$headers = array_map( 'trim', $headers );
		$headers = array_map( '\CaGov\Grants\Core\trim_byte_order_mark', $headers );
		$headers = array_filter( $headers );

		$diff = array_diff( $csv_headers, $headers );

		if ( ! empty( $diff ) ) {
			// Header miss match.
			return new WP_Error(
				'validate_csv_missmatch_header',
				__( 'Invalid CSV: File header mismatch. Please use a valid CSV file.', 'ca-grants-plugin' ),
				array( 'status' => 500 )
			);
		}

		$row_errors = self::validate_csv_rows( $file_path, $data );

		if ( is_wp_error( $row_errors ) && $row_errors->has_errors() ) {
			return $row_errors;
		}

		return true;
	}

	/**
	 * Validate csv row data.
	 *
	 * @param string $csv_file CSV file path to read and validate data.
	 * @param array  $data Award uploads grantID and fiscalYear data.
	 *
	 * @return WP_Error
	 */
	public static function validate_csv_rows( $csv_file, $data ) {
		$csv_errors = new WP_Error();
		$csv_data   = self::read_csv( $csv_file );

		if ( empty( $csv_data ) ) {
			return new WP_Error(
				'validation_error',
				esc_html__( 'Invalid CSV: No row found. Please use a valid CSV file.', 'ca-grants-plugin' )
			);
		}

		foreach ( $csv_data as $row_index => $row ) {
			// Note: this two value required for bulk upload but won't be part of csv file. Adding this value for validation only.
			$row['grantID']    = $data['grantID'] ? (int) $data['grantID'] : 0;
			$row['fiscalYear'] = $data['fiscalYear'] ?: '';

			$edit_award_class = new EditGrantAwards();
			$validated_data   = $edit_award_class->validate_fields( $row );

			if ( is_wp_error( $validated_data ) && $validated_data->has_errors() ) {
				$messages = $validated_data->get_error_messages();
				foreach ( $messages as $message ) {
					$message = self::maybe_replace_column_name( $message );
					$csv_errors->add(
						'csv_errors',
						'ROW #' . ( $row_index + 2 ) . ' | ' . $message // Row index + 2 for header column and skip 0 as index valule.
					);
				}
			}
		}

		return $csv_errors;
	}

	/**
	 * Read csv file and return data as an array.
	 *
	 * @param string $csv_file csv file name.
	 *
	 * @return array
	 */
	public static function read_csv( $csv_file ) {
		$file_handle = fopen( $csv_file, 'r' );

		$data = [];

		if ( feof( $file_handle ) ) {
			return [];
		}

		$csv_header_mapping = self::get_csv_header_mapping();
		$headers            = fgetcsv( $file_handle, 4096 );
		$headers            = array_map( 'trim', $headers );
		$headers            = array_map( '\CaGov\Grants\Core\trim_byte_order_mark', $headers );
		$headers            = array_filter( $headers );
		$headers            = array_map(
			function( $header ) use ( $csv_header_mapping ) {
				$header = trim( $header );
				return isset( $csv_header_mapping[ $header ] ) ? $csv_header_mapping[ $header ] : $header;
			},
			$headers
		);

		while ( ! feof( $file_handle ) ) {
			$row_data       = fgetcsv( $file_handle, 4096 );
			$row_assoc_data = [];

			// Skip if whole row is empty.
			if ( empty( $row_data ) || empty( implode( '', $row_data ) ) ) {
				continue;
			}

			foreach ( $row_data as $key => $value ) {
				// Do not get data for columns which are not in header column.
				if ( ! isset( $headers[ $key ] ) ) {
					continue;
				}

				$value = \CaGov\Grants\Core\convert_smart_quotes( $value );

				switch ( $headers[ $key ] ) {
					case 'recipientType':
					case 'secondaryRecipients':
					case 'geoLocationServed':
						$value = sanitize_title( $value );
						break;
					case 'totalAwardAmount':
					case 'matchingFundingAmount':
						$value = (int) preg_replace( '/[^0-9-.]+/', '', $value );
						break;
					case 'grantFundedStartDate':
					case 'grantFundedEndDate':
						$value = $value ? date_format( date_create( $value ), 'Y-m-d\TH:i:s' ) : $value;
						break;
					case 'countiesServed':
						$value = explode( ',', $value );
						$value = array_map( 'sanitize_title', $value );
						$value = array_filter( $value ); // Remove empty data.
						break;
				}

				$row_assoc_data[ $headers[ $key ] ] = $value;
			}

			$data[] = $row_assoc_data;
		}

		fclose( $file_handle );

		return $data;
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

		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'init', array( $this, 'register_post_status' ) );
		add_action( 'admin_head', array( $this, 'remove_submenu_pages' ) );
		add_action( 'load-post-new.php', array( $this, 'redirect_add_new_to_bulk_upload' ) );

		// Post edit screen.
		add_action( 'admin_footer-post.php', array( $this, 'append_post_status_list' ) );

		// Quick post edit screen.
		add_action( 'admin_footer-edit.php', array( $this, 'append_post_status_list' ) );

		add_filter( 'display_post_states', array( $this, 'display_failed_post_states' ), 10, 2 );

		// Schedual bulk award import.
		add_action( 'publish_' . self::CPT_SLUG, [ $this, 'trigger_bulk_award_import' ], 10, 2 );

		self::$init = true;
	}

	/**
	 * Trigger bulk award import on publish.
	 *
	 * @param int      $award_upload_id Post ID.
	 * @param \WP_Post $post            Post object.
	 */
	public function trigger_bulk_award_import( $award_upload_id, $award_upload ) {

		$result = wp_schedule_single_event(
			time(),
			BulkAwardImport::$hourly_check_job,
			array(
				'posts' => [ $award_upload ],
			),
			true
		);

		if ( true !== $result ) {
			wp_update_post(
				array(
					'ID'          => $award_upload_id,
					'post_status' => 'csl_failed',
				)
			);
			return;
		}
	}

	/**
	 * Register award uploads post type.
	 *
	 * @return void
	 */
	public function register_post_type() {
		$args = array(
			'labels'             => $this->get_labels(),
			'description'        => __( 'California State Award Uploads.', 'ca-grants-plugin' ),
			'public'             => true,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_rest'       => false,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'award-uploads' ),
			'rest_base'          => 'award-uploads',
			'capability_type'    => 'post',
			'has_archive'        => false,
			'hierarchical'       => false,
			'menu_icon'          => 'dashicons-upload',
			'menu_position'      => null,
			'supports'           => array( 'title', 'author' ),
		);

		// Do not create any post from UI/Admin, but should be able to create from REST API.
		if ( is_admin() ) {
			$args['capabilities'] = array(
				'create_posts' => 'do_not_allow',
			);
			$args['map_meta_cap'] = true;
		}

		/**
		 * Filter the California Grants Awards post type arguments.
		 *
		 * @param array $args The post type arguments.
		 */
		$args = apply_filters( 'csl_grant_awards_post_type_args', $args );

		register_post_type( self::CPT_SLUG, $args );
	}

	/**
	 * Remove add new sub menu from post type.
	 *
	 * @return void
	 */
	public function remove_submenu_pages() {
		remove_submenu_page( 'edit.php?post_type=' . self::CPT_SLUG, 'post-new.php?post_type=' . self::CPT_SLUG );
		remove_submenu_page( 'edit.php?post_type=' . self::CPT_SLUG, BulkUploadPage::$page_slug );
	}

	/**
	 * Redirect add new page to bulk upload.
	 *
	 * @return void
	 */
	public function redirect_add_new_to_bulk_upload() {
		$current_screen = get_current_screen();

		// Check if current page is add award upload page.
		if (
			empty( $current_screen )
			|| 'add' !== $current_screen->action
			|| self::CPT_SLUG !== $current_screen->post_type
		) {
			return;
		}

		$url = admin_url(
			sprintf(
				'edit.php?post_type=%s&page=%s',
				self::CPT_SLUG,
				BulkUploadPage::$page_slug
			)
		);
		wp_redirect( $url );
		exit;
	}

	/**
	 * Get grant post type labels.
	 *
	 * @return array
	 */
	public function get_labels() {
		return array(
			'name'               => _x( 'Award Uploads', 'post type general name', 'ca-grants-plugin' ),
			'singular_name'      => _x( 'Award Upload', 'post type singular name', 'ca-grants-plugin' ),
			'menu_name'          => _x( 'Award Uploads', 'admin menu', 'ca-grants-plugin' ),
			'name_admin_bar'     => _x( 'Award Upload', 'add new on admin bar', 'ca-grants-plugin' ),
			'add_new'            => _x( 'Bulk Upload', 'Award Upload', 'ca-grants-plugin' ),
			'add_new_item'       => __( 'Add New Award Upload', 'ca-grants-plugin' ),
			'new_item'           => __( 'New Award Upload', 'ca-grants-plugin' ),
			'edit_item'          => __( 'Edit Award Upload', 'ca-grants-plugin' ),
			'view_item'          => __( 'View Award Upload', 'ca-grants-plugin' ),
			'all_items'          => __( 'All Award Uploads', 'ca-grants-plugin' ),
			'search_items'       => __( 'Search Award Uploads', 'ca-grants-plugin' ),
			'parent_item_colon'  => __( 'Parent Award Uploads:', 'ca-grants-plugin' ),
			'not_found'          => __( 'No grants found.', 'ca-grants-plugin' ),
			'not_found_in_trash' => __( 'No grants found in Trash.', 'ca-grants-plugin' ),
		);
	}

	/**
	 * Register award uploads post status.
	 *
	 * @return void
	 */
	public function register_post_status() {

		$args = array(
			'label'                     => _x( 'Failed', 'post', 'ca-grants-plugin' ),
			'public'                    => true,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop( 'Failed (%s)', 'Failed (%s)', 'ca-grants-plugin' ),
		);

		register_post_status( 'csl_failed', $args );
	}

	/**
	 * Add custom post status.
	 *
	 * @return void
	 */
	public function append_post_status_list() {
		global $post;

		if ( ! is_a( $post, \WP_Post::class ) ) {
			return;
		}

		if ( static::CPT_SLUG !== $post->post_type ) {
			return;
		}

		$failed_option = sprintf(
			'<option value=\"csl_failed\" %s>Failed</option>',
			selected( $post->post_status, 'csl_failed', false )
		);

		echo '<script>';
		echo 'jQuery(document).ready(function($){';
			printf( '$("select#post_status").append("%s");', $failed_option );
			// Inline edit status for quick edit screen.
			printf( '$(".inline-edit-status select[name=\"_status\"]").append("%s");', $failed_option );
		if ( 'csl_failed' === $post->post_status ) {
			printf( '$("#post-status-display").text("Failed");' );
		}
		echo '});';
		echo '</script>';
	}

	/**
	 * Add failed post status to display status list.
	 *
	 * @param string[] $post_states An array of post display states.
	 * @param WP_Post  $post        The current post object.
	 *
	 * @return string[]
	 */
	public function display_failed_post_states( $post_states, $post ) {

		if ( isset( $_REQUEST['post_status'] ) ) {
			$post_status = $_REQUEST['post_status'];
		} else {
			$post_status = '';
		}

		if ( 'csl_failed' === $post->post_status && 'csl_failed' !== $post_status ) {
			$post_states['csl_failed'] = _x( 'Failed', 'post status', 'ca-grants-plugin' );
		}

		return $post_states;
	}
}
