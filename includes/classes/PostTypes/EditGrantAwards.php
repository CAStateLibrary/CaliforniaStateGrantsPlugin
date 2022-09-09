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
	 *
	 * @return void
	 */
	public function maybe_update_cleanup_data( $post_id ) {

		if (
			( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			|| ( isset( $_POST[ static::$nonce_field ] )
				 && ! wp_verify_nonce( $_POST[ static::$nonce_field ], static::$nonce_action ) )
		) {
			return;
		}

		remove_action( 'save_post_' . static::$cpt_slug, array( $this, 'maybe_update_cleanup_data' ), 11 );

		self::update_grant_award_data( $post_id );

		add_action( 'save_post_' . static::$cpt_slug, array( $this, 'maybe_update_cleanup_data' ), 11 );
	}

	/**
	 * Handles the save post action.
	 *
	 * @param integer $post_id The ID of the currently displayed post.
	 */
	public function save_post( $post_id ) {
		parent::save_post( $post_id );

		$grant_id = get_post_meta( $post_id, 'grantID', true );

		// Delete grant api rest endpoint cache.
		if ( ! empty( $grant_id ) ) {
			wp_cache_delete( 'grants_rest_response_' . $grant_id, 'ca-grants-plugin' );
		}
	}

	/**
	 * Update grant award post title and cleanup data.
	 *
	 * @param int $post_id The ID of the currently displayed post.
	 *
	 * @return void
	 */
	public static function update_grant_award_data( $post_id ) {

		if ( \CaGov\Grants\Core\is_portal() ) {
			$recipientType = wp_get_post_terms( $post_id, 'recipient-types', [ 'fields' => 'slugs' ] );
		} else {
			$recipientType = get_post_meta( $post_id, 'recipientType', true );
		}

		if (
			( is_array( $recipientType ) && in_array( 'individual', $recipientType, true ) )
			|| 'individual' === $recipientType
		) {
			$first_name = get_post_meta( $post_id, 'primaryRecipientFirstName', true ) ?: '';
			$last_name  = get_post_meta( $post_id, 'primaryRecipientLastName', true ) ?: '';
			$full_name  = $first_name . ' ' . $last_name;
			delete_post_meta( $post_id, 'primaryRecipientName' );
		} else {
			$full_name = get_post_meta( $post_id, 'primaryRecipientName', true );
			delete_post_meta( $post_id, 'primaryRecipientFirstName' );
			delete_post_meta( $post_id, 'primaryRecipientLastName' );
		}

		if ( ! empty( $full_name ) ) {
			wp_update_post(
				[
					'ID'         => $post_id,
					'post_title' => $full_name,
				]
			);
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
	public static function get_all_meta_fields() {

		return Meta\GrantAwards::get_fields();
	}
}
