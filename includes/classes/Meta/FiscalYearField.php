<?php
/**
 * Meta fields.
 *
 * @package CaGov\Grants
 */

namespace CaGov\Grants\Meta;

/**
 * Meta Field Class.
 */
class FiscalYearField extends Field {
	const API_URL = 'https://www.grants.ca.gov';

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
			error_log( 'fiscal year field already initialized' );
			return;
		}
		error_log( 'FiscalYearField::setup()' );

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
		error_log( 'Get Fiscal Year By Grant' );

		// properly verify the nonce
		// if ( ! wp_verify_nonce( $_REQUEST['nonce'] ) ) {
		// exit( 'No naughty business please' );
		// }

		$grant_id = $_REQUEST['grantID'];
		$options  = get_fiscal_years( $grant_id );
		$fields   = parent::get_api_fields_by_id( 'fiscalYear', false, $options );

		wp_send_json( $fields );
	}
}
