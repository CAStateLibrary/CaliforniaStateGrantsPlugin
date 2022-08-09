<?php
/**
 * Base class on editing post type.
 *
 * @package CaGov\Grants
 */

namespace CaGov\Grants\PostTypes;

use CaGov\Grants\Meta\Field;
use CaGov\Grants\Admin\Settings;
use WP_Error;

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
	public static $cpt_slug = '';

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
	 * Settings.
	 *
	 * @var Settings
	 */
	public $settings;

	/**
	 * Constructor.
	 */
	public function __construct() {}

	/**
	 * Get all meta fields.
	 *
	 * @return array
	 */
	abstract public static function get_all_meta_fields();

	/**
	 * Setup actions and filters with the WordPress API.
	 *
	 * @return void
	 */
	public function setup( $cpt_slug = '' ) {

		static::$cpt_slug     = $cpt_slug;
		static::$nonce_field  = '_' . $cpt_slug . static::$nonce_field;
		static::$nonce_action = $cpt_slug . static::$nonce_action;
		$this->settings       = new Settings();

		add_action( 'add_meta_boxes_' . $cpt_slug, array( $this, 'add_metaboxes' ) );
		add_action( 'save_post_' . $cpt_slug, array( $this, 'save_post' ) );
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

			if ( method_exists( $class, 'is_visible' ) && ! $class->is_visible() ) {
				continue;
			}

			add_meta_box(
				static::$cpt_slug . "-submission_{$group_key}",
				$meta_group['title'],
				array( $class, 'render_metabox' ),
				static::$cpt_slug,
				$meta_group['context'] ?? 'normal',
				$meta_group['priority'] ?? 'high'
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

		if ( $screen && static::$cpt_slug === $screen->post_type && 'post' === $screen->base ) {
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
		if ( ! isset( $_POST[ static::$nonce_field ] ) || ! wp_verify_nonce( $_POST[ static::$nonce_field ], static::$nonce_action ) ) {
			return;
		}

		$meta_fields = $this->get_all_meta_fields();

		if ( empty( $meta_fields ) ) {
			return;
		}

		Field::sanitize_and_save_fields( $meta_fields, $post_id, $_POST );
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

		$post_type = get_post_type_object( static::$cpt_slug );
		if ( ! $post_type || $post_type->publicly_queryable ) {
			return;
		}

		echo '<style type="text/css">#post-preview, #view-post-btn{display: none;}</style>';
	}

	/**
	 * Validate data with defined fields.
	 *
	 * @param array $data Field data to validated, key=value paired list.
	 *
	 * @return boolean|WP_Error WP_Error if any data validation fails, else return true for success.
	 */
	public function validate_fields( $data ) {

		$fields = $this->get_all_meta_fields();
		$errors = Field::maybe_get_field_errors( $fields, $data );

		return $errors->has_errors() ? $errors : true;
	}
}
