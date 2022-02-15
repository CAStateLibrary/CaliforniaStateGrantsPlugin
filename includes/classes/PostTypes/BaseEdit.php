<?php
/**
 * Base class on editing post type.
 *
 * @package CaGov\Grants
 */

namespace CaGov\Grants\PostTypes;

/**
 * Edit Base class.
 */
abstract class BaseEdit {

	/**
	 * Nonce field name.
	 *
	 * @var string
	 */
	public static $nonce_field = '-submissions-metabox';

	/**
	 * Nonce action name.
	 *
	 * @var string
	 */
	public static $nonce_action = '-submission';

	/**
	 * CPT Slug for edit screen.
	 *
	 * @var string
	 */
	public static $cpt_slug;

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
	public $meta_groups;

	/**
	 * Constructor.
	 */
	public function __construct() {}

	/**
	 * Get all meta fields.
	 *
	 * @return array
	 */
	abstract protected function get_all_meta_fields();

	/**
	 * Setup actions and filters with the WordPress API.
	 *
	 * @return void
	 */
	public function setup( $cpt_slug ) {

		self::$cpt_slug     = $cpt_slug;
		self::$nonce_field  = $cpt_slug . self::$nonce_field;
		self::$nonce_action = $cpt_slug . self::$nonce_action;

		add_action( 'add_meta_boxes', array( $this, 'add_metaboxes' ) );
		add_action( 'save_post_' . self::$cpt_slug, array( $this, 'save_post' ) );
		add_action( 'admin_head', array( $this, 'maybe_hide_preview' ) );
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
				self::$cpt_slug . "-submission_{$group_key}",
				$meta_group['title'],
				array( $class, 'render_metabox' ),
				self::$cpt_slug,
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
		if ( $screen && self::$cpt_slug === $screen->post_type && 'post' === $screen->base ) {
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
		if ( ! isset( $_POST[ self::$nonce_field ] ) || ! wp_verify_nonce( $_POST[ self::$nonce_field ], self::$nonce_action ) ) {
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
					$field_post_id = absint( $_POST[ $meta_field['field_id'] ] );
					update_post_meta( $field_post_id, $meta_field['id'], sanitize_text_field( $_POST[ $meta_field['id'] ] ) );
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
	 * Maybe hide preview button.
	 *
	 * @return void
	 */
	public function maybe_hide_preview() {
		if ( ! $this->viewing() ) {
			return;
		}

		$post_type = get_post_type_object( self::$cpt_slug );
		if ( ! $post_type || $post_type->public ) {
			return;
		}

		echo '<style type="text/css">#post-preview, #view-post-btn{display: none;}</style>';
	}
}
