<?php
/**
 * Award Uploads editing.
 *
 * @package CaGov\Grants
 */

namespace CaGov\Grants\PostTypes;

use CaGov\Grants\Meta;

/**
 * Edit award uploads class.
 */
class EditAwardUploads extends BaseEdit {

	/**
	 * Init
	 *
	 * @var boolean
	 */
	public static $init = false;

	/**
	 * Meta groups.
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
		$this->meta_groups = array(
			'grantAwards' => array(
				'class' => 'CaGov\\Grants\\Meta\\AwardUploads',
				'title' => __( 'Award Uploads', 'ca-grants-plugin' ),
			),
		);
	}

	/**
	 * Setup actions and filters with the WordPress API.
	 *
	 * @return void
	 */
	public function setup( $cpt_slug = AwardUploads::CPT_SLUG ) {
		if ( self::$init ) {
			return;
		}

		parent::setup( $cpt_slug );

		self::$init = true;
	}

	/**
	 * Get all meta fields.
	 *
	 * @return array
	 */
	public static function get_all_meta_fields() {
		return Meta\AwardUploads::get_fields();
	}

}
