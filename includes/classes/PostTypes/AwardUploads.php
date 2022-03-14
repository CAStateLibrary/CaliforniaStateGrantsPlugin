<?php
/**
 * Post Type: Award Uploads
 *
 * @package CaGov\Grants
 */

namespace CaGov\Grants\PostTypes;

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

		// TODO: Remove "*" if not needed.
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
	 * Validate temp uploaded file.
	 *
	 * @param array|string $csv_file Temp uploaded file data OR file path.
	 *
	 * @return boolean|WP_Error Return WP_Error on empty or invalid file, else return true.
	 */
	public static function validate_csv_file( $csv_file ) {
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

		$diff = array_diff( $csv_headers, $headers );

		if ( ! empty( $diff ) ) {
			// Header miss match.
			return new WP_Error(
				'validate_csv_missmatch_header',
				__( 'Invalid CSV: File header miss-matached. Please use valid csv file.', 'ca-grants-plugin' ),
				array( 'status' => 500 )
			);
		}

		return true;
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
		add_action( 'admin_menu', array( $this, 'remove_submenu_pages' ), 11 );
		add_action( 'load-post-new.php', array( $this, 'redirect_add_new_to_bulk_upload' ) );

		// Post edit screen.
		add_action( 'admin_footer-post.php', array( $this, 'append_post_status_list' ) );

		// Quick post edit screen.
		add_action( 'admin_footer-edit.php', array( $this, 'append_post_status_list' ) );

		add_filter( 'display_post_states', array( $this, 'display_failed_post_states' ), 10, 2 );

		self::$init = true;
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
			'capabilities'       => array(
				'create_posts' => 'do_not_allow',
			),
			'map_meta_cap'       => true,
		);

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
