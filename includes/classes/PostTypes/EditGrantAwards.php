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
class EditGrantAwards {
	const NONCE_ACTION = 'grant-awards-submissions-metabox';
	const NONCE_FIELD  = '_grant_awards_submission';

	/**
	 * Init
	 *
	 * @var boolean
	 */
	public static $init = false;

	/**
	 * Settings.
	 *
	 * @var Settings
	 */
	public $settings;

	/**
	 * Endpoint.
	 * TODO: Modify endpoint as part of E-3.5.1.
	 *
	 * @var GrantsAwardEndpoint
	 */
	public $endpoint;

	/**
	 * Meta groups
	 *
	 * @var array
	 */
	public $meta_groups;

	/**
	 * Constructor.
	 */
	public function __construct() {
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
	public function setup() {
		if ( self::$init ) {
			return;
		}

		add_action( 'add_meta_boxes', array( $this, 'add_metaboxes' ) );
		add_action( 'save_post_' . GrantAwards::CPT_SLUG, array( $this, 'save_post' ) );
		add_action( 'save_post_' . GrantAwards::CPT_SLUG, array( $this, 'save_post_title' ), 11 );
		add_action( 'admin_head', array( $this, 'maybe_hide_preview' ) );

		self::$init = true;
	}

	/**
	 * Add metaboxes.
	 *
	 * @return void
	 */
	public function add_metaboxes() {
		foreach ( $this->meta_groups as $group_key => $meta_group ) {
			$class = new $meta_group['class']();
			add_meta_box(
				"grants-awards-submission_{$group_key}",
				$meta_group['title'],
				array( $class, 'render_metabox' ),
				GrantAwards::CPT_SLUG,
				'normal',
				'high'
			);
		}
	}

	/**
	 * Viewing edit grant page.
	 *
	 * @return bool
	 */
	public function viewing() {
		if ( ! is_admin() || ! function_exists( 'get_current_screen' ) ) {
			return false;
		}

		$screen = get_current_screen();
		if ( $screen && GrantAwards::CPT_SLUG === $screen->post_type && 'post' === $screen->base ) {
			return true;
		}

		return false;
	}

	/**
	 * Handles the save post action.
	 *
	 * @param integer $post_id The ID of the currently displayed post.
	 */
	public function save_post( $post_id ) {
		if ( ! isset( $_POST[ self::NONCE_FIELD ] ) || ! wp_verify_nonce( $_POST[ self::NONCE_FIELD ], self::NONCE_ACTION ) ) {
			return;
		}

		$meta_fields = $this->get_all_meta_fields();

		if ( empty( $meta_fields ) ) {
			return;
		}

		foreach ( $meta_fields as $meta_field ) {
			$value = array();

			if ( empty( $_POST[ $meta_field['id'] ] ) ) {
				delete_post_meta( $post_id, $meta_field['id'] );
				continue;
			}

			switch ( $meta_field['type'] ) {
				case 'checkbox':
					$temp_value = $_POST[ $meta_field['id'] ];
					array_walk( $temp_value, 'sanitize_text_field' );
					$value = $temp_value;
					break;
				case 'email':
					$value = sanitize_email( $_POST[ $meta_field['id'] ] );
					break;
				case 'url':
					$value = esc_url_raw( $_POST[ $meta_field['id'] ] );
					break;
				case 'number':
					$value = absint( $_POST[ $meta_field['id'] ] );
					break;
				case 'textarea':
					$value = wp_kses_post( $_POST[ $meta_field['id'] ] );
					break;
				case 'save_to_field':
					$grant_id = absint( $_POST[ $meta_field['field_id'] ] );
					update_post_meta( $grant_id, $meta_field['id'], sanitize_text_field( $_POST[ $meta_field['id'] ] ) );
					break;
				case 'point_of_contact':
					$temp_value = $_POST[ $meta_field['id'] ];
					array_walk( $temp_value, 'sanitize_text_field' );
					$value = $temp_value;
					break;
				default:
					$value = sanitize_text_field( $_POST[ $meta_field['id'] ] );
					break;
			}

			if ( ! empty( $post_id ) ) {
				update_post_meta( $post_id, $meta_field['id'], $value );
			}
		}
	}

	/**
	 * Save post title based on Recipient Type value.
	 *
	 * @param int $post_id The ID of the currently displayed post.
	 */
	public function save_post_title( $post_id ) {
		if ( ! isset( $_POST[ self::NONCE_FIELD ] ) || ! wp_verify_nonce( $_POST[ self::NONCE_FIELD ], self::NONCE_ACTION ) ) {
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
			remove_action( 'save_post_' . GrantAwards::CPT_SLUG, array( $this, 'save_post' ) );
			remove_action( 'save_post_' . GrantAwards::CPT_SLUG, array( $this, 'save_post_title' ), 11 );
			wp_update_post(
				[
					'ID'         => $post_id,
					'post_title' => $full_name,
				]
			);
		}
	}

	/**
	 * Maybe hide preview button.
	 *
	 * @return void
	 */
	public function maybe_hide_preview() {
		if ( ! $this->viewing() ) {
			return;
		}

		$post_type = get_post_type_object( GrantAwards::CPT_SLUG );
		if ( ! $post_type || $post_type->public ) {
			return;
		}

		echo '<style type="text/css">#post-preview, #view-post-btn{display: none;}</style>';
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
