<?php
/**
 * Grant Awards editing.
 *
 * @package CaGov\Grants
 */

namespace CaGov\Grants\PostTypes;

use CaGov\Grants\Meta;

/**
 * Edit grant awards class.
 */
class EditGrantAwards extends BaseEdit {

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
		parent::__construct();
		$this->meta_groups = array(
			'award-stats' => array(
				'class' => 'CaGov\\Grants\\Meta\\GrantAwardStats',
				'title' => __( 'Grant Award Stats', 'ca-grants-plugin' ),
			),
			'grantAwards' => array(
				'class' => 'CaGov\\Grants\\Meta\\GrantAwards',
				'title' => __( 'Grant Awards', 'ca-grants-plugin' ),
			),
		);
	}

	/**
	 * Setup actions and filters with the WordPress API.
	 *
	 * @return void
	 */
	public function setup( $cpt_slug = GrantAwards::CPT_SLUG ) {
		if ( static::$init ) {
			return;
		}

		parent::setup( $cpt_slug );

		add_action( 'save_post_' . static::$cpt_slug, array( $this, 'maybe_update_cleanup_data' ), 11 );

		static::$init = true;
	}

	/**
	 * Save post title based on Recipient Type value.
	 * Cleanup country data based on geoLocationServed value.
	 *
	 * @param int $post_id The ID of the currently displayed post.
	 */
	public function maybe_update_cleanup_data( $post_id ) {

		if (
			( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			|| ( isset( $_POST[ static::$nonce_field ] )
				 && ! wp_verify_nonce( $_POST[ static::$nonce_field ], static::$nonce_action ) )
		) {
			return;
		}

		$geoLocationServed = get_post_meta( $post_id, 'geoLocationServed', true );

		if ( 'county' !== $geoLocationServed ) {
			delete_post_meta( $post_id, 'countiesServed' );
		}
	}

	/**
	 * Get all meta fields.
	 *
	 * @return array
	 */
	protected function get_all_meta_fields() {
		return array_merge(
			Meta\GrantAwardStats::get_fields(),
			Meta\GrantAwards::get_fields()
		);
	}
}
