<?php
/**
 * Base class on editing post type.
 *
 * @package CaGov\Grants
 */

namespace CaGov\Grants\PostTypes;

use CaGov\Grants\Meta\Field;
use CaGov\Grants\Admin\Settings;
use DateTime;
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
					$temp_value       = $_POST[ $meta_field['id'] ];
					$temp['checkbox'] = ( isset( $temp_value['checkbox'] ) ) ? sanitize_text_field( $temp_value['checkbox'] ) : '';

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

	/**
	 * Validate data with defined fields.
	 *
	 * @param array $data Field data to validated, key=value paired list.
	 *
	 * @return boolean|WP_Error WP_Error if any data validation fails, else return true for success.
	 */
	public function validate_fields( $data ) {
		$errors = new WP_Error();
		$fields = $this->get_all_meta_fields();

		foreach ( $fields as $field ) {
			$id = $field['id'];

			// Check if data has value for required fields.
			if ( ! empty( $field['required'] ) && ( true === $field['required'] ) && empty( $data[ $id ] ) ) {
				$errors->add(
					'validation_error',
					esc_html__( 'Missing required value for field: ', 'ca-grants-plugin' ) . esc_html( $id )
				);
				continue;
			}

			// Check if conditional requierd field have value.
			if (
				! empty( $field['visible'] )
				&& ! empty( $field['visible']['required'] )
				&& empty( $data[ $id ] )
				&& (
					( // Case: field is required only when dependent field is not equal to specific value.
						'not_equal' === $field['visible']['required']
						&& $data[ $field['visible']['fieldId'] ] !== $field['visible']['value']
					)
					||
					( // Case: field is required only when dependent field is equal to specific value.
						'equal' === $field['visible']['required']
						&& $data[ $field['visible']['fieldId'] ] === $field['visible']['value']
					)
				)
			) {
				$errors->add(
					'validation_error',
					esc_html__( 'Missing required value for field: ', 'ca-grants-plugin' ) . esc_html( $id )
				);
				continue;
			}

			// If field is not required and have empty value it's valid data, skip other checks.
			if ( empty( $data[ $id ] ) ) {
				continue;
			}

			$is_invalid = false;

			switch ( $field['type'] ) {
				case 'post-finder':
					$is_invalid = $this->validate_post_finder_field( $field, $data[ $id ] );
					break;
				case 'number':
				case 'save_to_field':
					$is_invalid = is_int( $data[ $id ] ) ? ( $data[ $id ] < 0 ) : true;
					break;
				case 'text':
				case 'textarea':
					$max_chars  = $field['maxlength'] ?: strlen( $data[ $id ] );
					$max_chars  = $field['text_limit'] ?: $max_chars;
					$is_invalid = is_string( $data[ $id ] ) ? strlen( $data[ $id ] ) > $max_chars : true;
					break;
				case 'checkbox':
				case 'select':
					if ( isset( $field['source'] ) && 'api' === $field['source'] ) {
						$api_values = Field::get_api_fields_by_id( $id );
						$field_ids  = empty( $api_values ) ? array() : wp_filter_object_list( $api_values, array(), 'and', 'id' );
						$is_invalid = ! in_array( $data[ $id ], $field_ids ) && ! in_array( sanitize_title( $data[ $id ] ), $field_ids );
					} elseif ( isset( $field['fields'] ) ) {
						$defined_values = wp_filter_object_list( $field['fields'], array(), 'and', 'id' );
						$is_invalid     = ! in_array( $data[ $id ], $defined_values ) && ! in_array( sanitize_title( $data[ $id ] ), $defined_values );
					}
					break;
				case 'datetime-local':
					$date          = new DateTime( $data[ $id ] );
					$is_valid_date = ( $date && $date->format( 'c' ) );

					if ( $is_valid_date ) {
						$max_date   = $field['max_date'] ? new DateTime( $data[ $field['max_date'] ] ) : false;
						$min_date   = $field['min_date'] ? new DateTime( $data[ $field['min_date'] ] ) : false;
						$is_invalid = $max_date ? ( $date > $max_date ) : false;
						$is_invalid = ( ! $is_invalid && $min_date ) ? ( $date < $min_date ) : false;
					} else {
						$is_invalid = true;
					}
					break;
			}

			if ( $is_invalid ) {
				$errors->add(
					'validation_error',
					esc_html__( 'Invalid value found for field: ', 'ca-grants-plugin' ) . esc_html( $id )
				);
				continue;
			}
		}

		return $errors->has_errors() ? $errors : true;
	}

	/**
	 * Validate post finder field value agaist defined field params.
	 *
	 * @param array        $field Defined field args.
	 * @param string|array $value Post finder field value.
	 *
	 * @return boolean Return true if data is invalid else false.
	 */
	public function validate_post_finder_field( $field, $value ) {
		$is_invalid = false;
		$post_type  = empty( $field['options']['args']['post_type'] ) ? 'post' : $field['options']['args']['post_type'];

		if ( is_array( $value ) ) {
			$limit      = empty( $field['options']['limit'] ) ? 10 : (int) $field['options']['limit'];
			$is_invalid = count( $value ) > $limit;

			if ( ! $is_invalid ) {
				$valid_posts = array_filter(
					$value,
					function( $id ) use ( $post_type ) {
						$post = is_int( $id ) ? get_post( $id ) : false;
						return empty( $post ) ? false : ( $post->post_type === $post_type );
					}
				);
				$is_invalid  = count( $valid_posts ) !== count( $value );
			}
		} else {
			$post       = is_int( $value ) ? get_post( $value ) : false;
			$is_invalid = empty( $post ) ? true : ( $post->post_type !== $post_type );
		}

		return $is_invalid;
	}
}
