<?php
/**
 * Bulk Upload Page.
 *
 * @package CaGov\Grants
 */

namespace CaGov\Grants\Admin;

use CaGov\Grants\Core;
use CaGov\Grants\REST\BulkUploadEndpoint;
use CaGov\Grants\Meta\Field;
use CaGov\Grants\PostTypes\AwardUploads;
use CaGov\Grants\PostTypes\Grants as GrantsCPT;

use function CaGov\Grants\Core\is_portal;

/**
 * BulkUploadPage Class
 */
class BulkUploadPage {

	/**
	 * Page slug.
	 *
	 * @var string
	 */
	public static $page_slug = 'bulk-upload';

	/**
	 * Init.
	 *
	 * @var boolean
	 */
	public static $init = false;

	/**
	 * Admin notice messages.
	 * array(
	 *  'success' => '', // Bulk uploaded award id.
	 *  'fail' => '', // Form submission failed message.
	 * )
	 *
	 * @var array
	 */
	public $notices = array(
		'success' => null,
		'fail'    => null,
	);

	/**
	 * Register actions and filters with WordPress API.
	 *
	 * @return void
	 */
	public function setup() {
		if ( self::$init ) {
			return;
		}

		add_action( 'admin_menu', array( $this, 'register_admin_page' ), 1 );
		add_action( 'current_screen', array( $this, 'maybe_handle_submit' ) );
		add_action( 'admin_notices', array( $this, 'maybe_show_notices' ) );

		self::$init = true;
	}

	/**
	 * Check and return true if current page is supported.
	 *
	 * @return boolean
	 */
	public function is_visible() {
		return post_type_exists( AwardUploads::CPT_SLUG );
	}

	/**
	 * Register bulk upload admin page inside award upload post type.
	 *
	 * @return void
	 */
	public function register_admin_page() {
		if ( ! $this->is_visible() ) {
			return;
		}

		add_submenu_page(
			'edit.php?post_type=' . AwardUploads::CPT_SLUG,
			esc_html__( 'Bulk Upload', 'ca-grants-plugin' ),
			esc_html__( 'Bulk Upload', 'ca-grants-plugin' ),
			'manage_options',
			self::$page_slug,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Handle form submit request.
	 *
	 * @return void
	 */
	public function maybe_handle_submit() {
		if (
			'csl_award_uploads_page_bulk-upload' !== get_current_screen()->id
			|| empty( $_POST )
		) {
			return;
		}

		$nonce       = filter_input( INPUT_POST, 'ca_bulk_upload_nonce', FILTER_SANITIZE_STRING );
		$submit      = filter_input( INPUT_POST, 'ca_award_upload_submit', FILTER_SANITIZE_STRING );
		$grant_id    = filter_input( INPUT_POST, 'grantID', FILTER_SANITIZE_NUMBER_INT );
		$fiscal_year = filter_input( INPUT_POST, 'fiscalYear', FILTER_SANITIZE_STRING );
		$award_csv   = ( ! empty( $_FILES['awardCSV'] ) && ! empty( $_FILES['awardCSV']['name'] ) ) ? $_FILES['awardCSV'] : array();

		if (
		! wp_verify_nonce( $nonce, 'ca_bulk_upload_field' )
		|| __( 'Add Award Upload', 'ca-grants-plugin' ) !== $submit
		) {
			$this->notices['fail'] = esc_html__( 'Invalid form request.', 'ca-grants-plugin' );
			return;
		}

		if ( empty( $grant_id ) ) {
			$this->notices['fail'] = esc_html__( 'Grant ID not found, Please select associate grant.', 'ca-grants-plugin' );
			return;
		}

		if ( empty( $award_csv ) ) {
			$this->notices['fail'] = esc_html__( 'Award CSV file not found, Please select file.', 'ca-grants-plugin' );
			return;
		}

		$params = array(
			'grantID'    => $grant_id,
			'fiscalYear' => $fiscal_year,
		);

		$request = Core\wp_safe_remote_post_multipart( BulkUploadEndpoint::get_endpoint_url(), $params, 'awardCSV' );

		if ( is_wp_error( $request ) ) {
			$this->notices['fail'] = $request->get_error_message();
			return;
		}

		$response_code = wp_remote_retrieve_response_code( $request );
		$body          = wp_remote_retrieve_body( $request );
		$data          = (array) json_decode( $body );

		if ( 200 !== $response_code ) {
			$fail_data = empty( $data['data'] ) ? '' : $data['data'];
			$fail_data = ( empty( $fail_data ) && ! empty( $data['message'] ) ) ? $data['message'] : $fail_data;
			$fail_data = empty( $fail_data ) ? esc_html__( 'Invalid data submitted.', 'ca-grants-plugin' ) : $fail_data;

			$this->notices['fail'] = $fail_data;
			return;
		}

		if ( empty( $data['data'] ) || empty( $data['data']->awardUploadID ) ) {
			$this->notices['fail'] = esc_html__( 'Invalid data submitted.', 'ca-grants-plugin' );
			return;
		}

		// Form data successfully submitted and bulk upload is added to queue.
		$this->notices['success'] = $data['data']->awardUploadID;
		return;
	}

	/**
	 * Render bulk upload page.
	 *
	 * @return void
	 */
	public function render_page() {
		$grant_id = filter_input( INPUT_GET, 'grant_id', FILTER_SANITIZE_NUMBER_INT );

		?>
		<div class="wrap bulk-upload-page">
			<div class="bulk-upload-page--header">
				<h1><?php esc_html_e( 'Bulk Upload', 'ca-grants-plugin' ); ?></h1>
			</div>

			<?php
			if ( empty( $grant_id ) ) {
				$grant_list_link = add_query_arg(
					[
						'post_type' => GrantsCPT::get_cpt_slug(),
					],
					admin_url( 'edit.php' )
				);
				$error_notice    = sprintf(
					__( 'Grant data not found, please go to <a href="%s">grants list page</a> and select "Bulk Upload Award Data" for grant.', 'ca-grants-plugin' ),
					esc_url( $grant_list_link )
				);
				printf(
					'<div class="notice notice-error"><p>%s</p></div>',
					wp_kses_post( $error_notice )
				);
			} else {
				echo '<form class="bulk-upload-page--content" method="post" enctype="multipart/form-data">';
					wp_nonce_field( 'ca_bulk_upload_field', 'ca_bulk_upload_nonce' );
					$this->render_fields( $grant_id );
					$this->submit_button();
				echo '</form>';
			}
			?>
		</div>
			<?php
	}

	/**
	 * Render bulk upload fields.
	 *
	 * @param int $grant_id Grant post id.
	 *
	 * @return void
	 */
	public function render_fields( $grant_id ) {

		?>
		<table class="form-table" role="presentation">
			<tbody>
		<?php
		foreach ( self::get_fields( $grant_id ) as $field ) {
			Field::factory( $field );
		}
		?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Get fields for bulk upload form.
	 *
	 * @param int $grant_id Grant post id.
	 *
	 * @return array
	 */
	public static function get_fields( $grant_id ) {

		$is_closed_grant    = Core\is_closed_grant( $grant_id );
		$closed_fiscal_year = '';
		if ( $is_closed_grant ) {
			$closed_fiscal_year = Core\get_deadline_fiscal_year( $grant_id );
		}

		return array(
			array(
				'id'           => 'grantID',
				'name'         => __( 'Associated Grant', 'ca-grants-plugin' ),
				'description'  => __( 'Select the grant to enter award data for.', 'ca-grants-plugin' ),
				'type'         => 'label',
				'value_type'   => 'post-title',
				'link'         => 'post-link',
				'meta_value'   => $grant_id,
				'hidden_field' => true,
			),
			array(
				'id'           => 'fiscalYear',
				'name'         => __( 'Fiscal Year', 'ca-grants-plugin' ),
				'type'         => $closed_fiscal_year ? 'label' : 'select',
				'source'       => is_portal() ? 'portal-api' : 'api',
				'description'  => __( 'Select the Fiscal Year to import awards for.', 'ca-grants-plugin' ),
				'meta_value'   => $closed_fiscal_year,
				'hidden_field' => ! empty( $closed_fiscal_year ),
				'required'     => true,
			),
			array(
				'id'           => 'awardCSV',
				'name'         => __( 'Award CSV', 'ca-grants-plugin' ),
				'type'         => 'file',
				'description'  => __( 'Browse and select the CSV containing award data.', 'ca-grants-plugin' ),
				'required'     => true,
				'accepted-ext' => [
					'.csv',
				],
			),
		);
	}

	/**
	 * Renders submit button.
	 *
	 * @return void
	 */
	public function submit_button() {
		?>
		<p class="submit">
			<span>
		<?php
			submit_button(
				__( 'Add Award Upload', 'ca-grants-plugin' ),
				'primary',
				'ca_award_upload_submit',
				false,
				array(
					'tabindex' => '1',
				)
			);
		?>
			</span>
		</p>
			<?php
	}

	/**
	 * Admin notice callback, show notice based on $this->notice data.
	 *
	 * @return void
	 */
	public function maybe_show_notices() {
		if (
			 ! $this->is_visible()
			 || 'csl_award_uploads_page_bulk-upload' !== get_current_screen()->id
			 || ( empty( $this->notices['success'] ) && empty( $this->notices['fail'] ) )
		) {
			return;
		}

		
		if ( ! empty( $this->notices['success'] ) ) :
			$admin_url = admin_url( sprintf( 'post.php?post=%d&action=edit', esc_html( $this->notices['success'] ) ) );
			?>
		<div class="notice notice-success is-dismissible">
			<p>
				<?php esc_html_e( 'Award bulk upload added to queue successfully. ID: ', 'ca-grants-plugin' ); ?>
				<?php printf( '<a href="%1$s" target="_blank" title="%2$d">%2$d</a>', esc_url( $admin_url ), esc_html( $this->notices['success'] ) ); ?>
			</p>
		</div>
			<?php
		elseif ( ! empty( $this->notices['fail'] ) ) :
			$this->notices['fail'] = (array) $this->notices['fail'];
			?>
		<div class="notice notice-error is-dismissible">
			<?php
			if ( ! empty( $this->notices['fail'] ) && is_array( $this->notices['fail'] ) ) {
				echo '<p>';
					esc_html_e( 'Award Uploads Failed: ', 'ca-grants-plugin' );
				echo '</p>';
				echo '<p>';
					esc_html_e( 'Please correct the errors below and re-upload your file.', 'ca-grants-plugin' );
				echo '</p>';
				echo '<ul class="ul-disc">';
				foreach ( $this->notices['fail'] as $message ) {
					echo '<li>' . esc_html( $message ) . '</li>';
				}
				echo '</ul>';
			} else {
				echo '<p>';
					esc_html_e( 'Award Uploads Failed: ', 'ca-grants-plugin' );
					echo esc_html( $this->notices['fail'] );
				echo '</p>';
			}
			?>
		</div>
				<?php
		endif;
	}
}
