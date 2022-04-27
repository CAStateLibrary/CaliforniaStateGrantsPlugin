<?php
/**
 * Grant editing.
 *
 * @package CaGov\Grants
 */

namespace CaGov\Grants\PostTypes;

use CaGov\Grants\Meta;

use function CaGov\Grants\Core\is_portal;

/**
 * Edit grant class.
 */
class EditGrant extends BaseEdit {

	/**
	 * Init
	 *
	 * @var boolean
	 */
	public static $init = false;

	/**
	 * Meta groups
	 *
	 * @var array
	 */
	public $meta_groups = array();

	/**
	 * CPT Slug for edit screen.
	 *
	 * @var string
	 */
	public static $cpt_slug = '';

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct();
		$this->meta_groups = array(
			'award-stats' => array(
				'class' => 'CaGov\\Grants\\Meta\\AwardStats',
				'title' => __( 'Award Stats', 'ca-grants-plugin' ),
			),
			'general'     => array(
				'class' => 'CaGov\\Grants\\Meta\General',
				'title' => __( 'General Grant Information', 'ca-grants-plugin' ),
			),
			'eligibility' => array(
				'class' => 'CaGov\\Grants\\Meta\Eligibility',
				'title' => __( 'Grant Eligibility Details', 'ca-grants-plugin' ),
			),
			'funding'     => array(
				'class' => 'CaGov\\Grants\\Meta\Funding',
				'title' => __( 'Grant Funding Details', 'ca-grants-plugin' ),
			),
			'dates'       => array(
				'class' => 'CaGov\\Grants\\Meta\Dates',
				'title' => __( 'Grant Dates &amp; Deadlines', 'ca-grants-plugin' ),
			),
			'contact'     => array(
				'class' => 'CaGov\\Grants\\Meta\Contact',
				'title' => __( 'Grant Contacts and Links', 'ca-grants-plugin' ),
			),
		);

		if ( ! is_portal() ) {
			$this->meta_groups['grant-consent'] = array(
				'class' => 'CaGov\\Grants\\Meta\\GrantConsent',
				'title' => __( 'Grant Publish Consent', 'ca-grants-plugin' ),
			);
		}
	}

	/**
	 * Setup actions and filters with the WordPress API.
	 *
	 * @return void
	 */
	public function setup( $cpt_slug = '' ) {
		if ( static::$init ) {
			return;
		}

		if ( empty( $cpt_slug ) ) {
			$cpt_slug = Grants::get_cpt_slug();
		}

		parent::setup( $cpt_slug );

		static::$init     = true;
	}

	/**
	 * Handles the save post action.
	 *
	 * @param integer $post_id The ID of the currently displayed post.
	 */
	public function save_post( $post_id ) {
		parent::save_post( $post_id );

		wp_cache_delete( 'grants_rest_response_' . $post_id );
	}

	/**
	 * Get all meta fields.
	 *
	 * @return array
	 */
	public static function get_all_meta_fields() {

		$consent_fields = is_portal() ? [] : Meta\GrantConsent::get_fields();

		return array_merge(
			Meta\AwardStats::get_fields(),
			Meta\General::get_fields(),
			Meta\Eligibility::get_fields(),
			Meta\Funding::get_fields(),
			Meta\Dates::get_fields(),
			Meta\Contact::get_fields(),
			$consent_fields
		);
	}
}
