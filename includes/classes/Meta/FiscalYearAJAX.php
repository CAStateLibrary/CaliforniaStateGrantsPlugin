<?php
/**
 * Fiscal Year AJAX Class.
 *
 * @package CaGov\Grants
 */

namespace CaGov\Grants\Meta;

use CAGov\Grants\Meta\Field;
use function CaGov\Grants\Helpers\FiscalYear\get_fiscal_years_query_string;

/**
 * Fiscal Year AJAX logic
 */
class FiscalYearAJAX {

	/**
	 * Init
	 *
	 * @var boolean
	 */
	public static $init = false;

	/**
	 * Setup ajax hooks.
	 *
	 * @return void
	 */
	public function setup() {
		if ( self::$init ) {
			return;
		}

		add_action( 'wp_ajax_get_fiscal_years_by_grant', [ $this, 'get_fiscal_years_by_grant' ] );
		add_action( 'wp_ajax_nopriv_get_fiscal_years_by_grant', [ $this, 'get_fiscal_years_by_grant' ] );

		self::$init = true;
	}

	/**
	 * Ajax function to get fiscal years by Grant ID
	 *
	 * @return void JSON response.
	 */
	public function get_fiscal_years_by_grant() {
		// properly verify the nonce
		if ( ! wp_verify_nonce( $_REQUEST['nonce'], 'post_finder' ) ) {
			wp_send_json_error( 'Error: Invalid nonce' );
		}

		$grant_id = intval( $_REQUEST['grantId'] );

		if ( ! $grant_id ) {
			wp_send_json_error( 'Error: Missing Grant ID' );
		}

		$options = get_fiscal_years_query_string( $grant_id );
		$fields  = Field::get_api_fields_by_id( 'fiscalYear', false, $options );

		wp_send_json_success( wp_list_pluck( $fields, 'id' ) );
	}
}
