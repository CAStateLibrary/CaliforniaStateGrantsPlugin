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

		add_action( 'save_post_' . static::$cpt_slug, array( $this, 'save_post_title' ), 11 );

		static::$init = true;
	}

	/**
	 * Save post title based on Recipient Type value.
	 *
	 * @param int $post_id The ID of the currently displayed post.
	 */
	public function save_post_title( $post_id ) {
		if ( ! isset( $_POST[ static::$nonce_field ] ) || ! wp_verify_nonce( $_POST[ static::$nonce_field ], static::$nonce_action ) ) {
			return;
		}

		$recipientType = get_post_meta( $post_id, 'recipientType', true );

		if ( 'individual' === $recipientType ) {
			$first_name = get_post_meta( $post_id, 'primeryRecipientFirstName', true ) ?: '';
			$last_name  = get_post_meta( $post_id, 'primeryRecipientLastName', true ) ?: '';
			$full_name  = $first_name . ' ' . $last_name;
		} else {
			$full_name = get_post_meta( $post_id, 'primeryRecipientName', true );
		}

		if ( ! empty( $full_name ) ) {
			remove_action( 'save_post_' . static::$cpt_slug, array( $this, 'save_post_title' ), 11 );
			wp_update_post(
				[
					'ID'         => $post_id,
					'post_title' => $full_name,
				]
			);
			add_action( 'save_post_' . static::$cpt_slug, array( $this, 'save_post_title' ), 11 );
		}
	}

	/**
	 * Get all meta fields.
	 *
	 * @return array
	 */
	protected function get_all_meta_fields() {
		return array_merge(
			Meta\GrantAwards::get_fields(),
		);
	}
}
