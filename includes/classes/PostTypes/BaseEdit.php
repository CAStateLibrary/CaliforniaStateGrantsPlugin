<?php
/**
 * Base class on editing post type.
 *
 * @package CaGov\Grants
 */

namespace CaGov\Grants\PostTypes;

use CaGov\Grants\Admin\Settings;

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
	abstract protected function get_all_meta_fields();

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
			add_meta_box(
				static::$cpt_slug . "-submission_{$group_key}",
				$meta_group['title'],
				array( $class, 'render_metabox' ),
				static::$cpt_slug,
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
				case 'eligibility-matching-funds':
					$value = array(
						'checkbox'   => sanitize_text_field( $_POST[ $meta_field['id'] ] ),
						'percentage' => absint( $_POST[ $meta_field['id'] . '-percentage' ] ),
					);
					break;
				case 'estimated-number-awards':
					$temp_value = $_POST[ $meta_field['id'] ];

					$temp_value['checkbox'] = ( isset( $temp_value['checkbox'] ) ) ? sanitize_text_field( $temp_value['checkbox'] ) : '';

					if ( 'exact' === $temp_value['checkbox'] ) {
						$temp_value['between']['low']  = '';
						$temp_value['between']['high'] = '';
					} elseif ( 'between' === $temp_value['checkbox'] ) {
						$temp_value['exact'] = '';
					} elseif ( 'dependant' === $temp_value['checkbox'] ) {
						$temp_value['between']['low']  = '';
						$temp_value['between']['high'] = '';
						$temp_value['exact']           = '';
					}

					array_walk( $temp_value, 'sanitize_text_field' );
					$value = $temp_value;
					break;
				case 'estimated-award-amounts':
					$temp_value = $_POST[ $meta_field['id'] ];

					$temp_value['checkbox'] = ( isset( $temp_value['checkbox'] ) ) ? sanitize_text_field( $temp_value['checkbox'] ) : '';

					// Make sure the text boxes for the options not selected are empty, to avoid confusion.
					if ( 'same' === $temp_value['checkbox'] ) {
						$temp_value['unknown']['first']    = '';
						$temp_value['unknown']['second']   = '';
						$temp_value['different']['first']  = '';
						$temp_value['different']['second'] = '';
						$temp_value['different']['third']  = '';
					} elseif ( 'different' === $temp_value['checkbox'] ) {
						$temp_value['unknown']['first']  = '';
						$temp_value['unknown']['second'] = '';
						$temp_value['same']['amount']    = '';
					} elseif ( 'unknown' === $temp_value['checkbox'] ) {
						$temp_value['different']['first']  = '';
						$temp_value['different']['second'] = '';
						$temp_value['different']['third']  = '';
						$temp_value['same']['amount']      = '';
					} elseif ( 'dependant' === $temp_value['checkbox'] ) {
						$temp_value['unknown']['first']    = '';
						$temp_value['unknown']['second']   = '';
						$temp_value['different']['first']  = '';
						$temp_value['different']['second'] = '';
						$temp_value['different']['third']  = '';
						$temp_value['same']['amount']      = '';
					}

					array_walk( $temp_value, 'sanitize_text_field' );
					$value = $temp_value;
					break;
				case 'period-performance':
					$temp_value           = $_POST[ $meta_field['id'] ];
					$clean_value          = array();
					$clean_value['num']   = ( isset( $temp_value['num'] ) ) ? absint( $temp_value['num'] ) : '';
					$clean_value['units'] = ( isset( $temp_value['units'] ) ) ? sanitize_text_field( $temp_value['units'] ) : '';
					$value                = $clean_value;
					break;
				case 'electronic-submission-method':
					$temp_value           = $_POST[ $meta_field['id'] ];
					$clean_value          = array();
					$clean_value['email'] = ( isset( $temp_value['email'] ) ) ? sanitize_email( $temp_value['email'] ) : '';
					$clean_value['url']   = ( isset( $temp_value['url'] ) ) ? esc_url_raw( $temp_value['url'] ) : '';
					$value                = $clean_value;
					break;
				case 'application-deadline':
					$temp_value = $_POST[ $meta_field['id'] ];
					array_walk( $temp_value, 'sanitize_text_field' );
					$value = $temp_value;
					break;
				default:
					$value = sanitize_text_field( $_POST[ $meta_field['id'] ] );
					break;
			}

			/**
			 * Filters the post-meta value, targeted by meta-field type.
			 *
			 * The filter name is `ca_grants_post_meta_`,
			 * followed by the meta-field type.
			 *
			 * For example, using the `period-performance` meta-field:
			 * `ca_grants_post_meta_period-performance`
			 *
			 * @param mixed $value The value to filter.
			 */
			$value = apply_filters( 'ca_grants_post_meta_' . $meta_field['type'], $value );

			/**
			 * Filters the post-meta value, targeted by meta-field ID.
			 *
			 * The filter name is `ca_grants_post_meta_`,
			 * followed by the meta-field ID.
			 *
			 * For example, assuming a field ID of 1234:
			 * `ca_grants_post_meta_1234`
			 *
			 * @param mixed $value The value to filter.
			 */
			$value = apply_filters( 'ca_grants_post_meta_' . $meta_field['id'], $value );

			if ( ! empty( $post_id ) && ! empty( $value ) ) {
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

		$post_type = get_post_type_object( static::$cpt_slug );
		if ( ! $post_type || $post_type->publicly_queryable ) {
			return;
		}

		echo '<style type="text/css">#post-preview, #view-post-btn{display: none;}</style>';
	}
}
