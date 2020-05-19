<?php
/**
 * Grant editing.
 *
 * @package CaGov\Grants
 */

namespace CaGov\Grants\PostTypes;

use CaGov\Grants\PostTypes\Grant;
use CaGov\Grants\Meta;

/**
 * Edit grant class.
 */
class EditGrant {
	const NONCE_ACTION = 'grant-submissions-metabox';
	const NONCE_FIELD  = '_grant_submission';

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
	public function __construct() {
		$this->meta_groups = array(
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
	}

	/**
	 * Setup actions and filters with the WordPress API.
	 *
	 * @return void
	 */
	public function setup() {
		add_action( 'add_meta_boxes', array( $this, 'add_metaboxes' ) );
		add_action( 'save_post', array( $this, 'save_post' ) );
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
				"grants-submission_{$group_key}",
				$meta_group['title'],
				array( $class, 'render_metabox' ),
				Grants::CPT_SLUG,
				'normal',
				'high'
			);
		}
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

		$meta_fields = array_merge(
			Meta\General::get_fields(),
			Meta\Eligibility::get_fields(),
			Meta\Funding::get_fields(),
			Meta\Dates::get_fields(),
			Meta\Contact::get_fields()
		);

		if ( ! empty( $meta_fields ) ) {
			foreach ( $meta_fields as $meta_field ) {
				$value = array();

				if ( empty( $_POST[ $meta_field['id'] ] ) ) {
					delete_post_meta( $post_id, $meta_field['id'] );
					continue;
				}

				switch ( $meta_field['type'] ) {
					case 'checkbox':
						$value = $_POST[ $meta_field['id'] ];
						array_walk( $value, 'sanitize_text_field' );
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
					case 'point_of_contact':
						$value = $_POST[ $meta_field['id'] ];
						array_walk( $value, 'sanitize_text_field' );
						break;
					case 'eligibility-matching-funds':
						$value = array(
							'checkbox'   => sanitize_text_field( $_POST[ $meta_field['id'] ] ),
							'percentage' => absint( $_POST[ $meta_field['id'] . '-percentage' ] ),
						);
						break;
					case 'estimated-number-awards':
						$value = $_POST[ $meta_field['id'] ];

						if ( 'exact' === $value['checkbox'] ) {
							$value['between']['low']  = '';
							$value['between']['high'] = '';
						} elseif ( 'between' === $value['checkbox'] ) {
							$value['exact'] = '';
						} elseif ( 'dependant' === $value['checkbox'] ) {
							$value['between']['low']  = '';
							$value['between']['high'] = '';
							$value['exact']           = '';
						}

						array_walk( $value, 'sanitize_text_field' );
						break;
					case 'estimated-award-amounts':
						$value            = $_POST[ $meta_field['id'] ];
						$temp['checkbox'] = ( isset( $value['checkbox'] ) ) ? sanitize_text_field( $value['checkbox'] ) : '';

						// Make sure the text boxes for the options not selected are empty, to avoid confusion.
						if ( 'same' === $value['checkbox'] ) {
							$value['unknown']['first']    = '';
							$value['unknown']['second']   = '';
							$value['different']['first']  = '';
							$value['different']['second'] = '';
							$value['different']['third']  = '';
						} elseif ( 'different' === $value['checkbox'] ) {
							$value['unknown']['first']  = '';
							$value['unknown']['second'] = '';
							$value['same']['amount']    = '';
						} elseif ( 'unknown' === $value['checkbox'] ) {
							$value['different']['first']  = '';
							$value['different']['second'] = '';
							$value['different']['third']  = '';
							$value['same']['amount']      = '';
						} elseif ( 'dependant' === $value['checkbox'] ) {
							$value['unknown']['first']    = '';
							$value['unknown']['second']   = '';
							$value['different']['first']  = '';
							$value['different']['second'] = '';
							$value['different']['third']  = '';
							$value['same']['amount']      = '';
						}

						array_walk( $value, 'sanitize_text_field' );
						break;
					case 'period-performance':
						$value          = $_POST[ $meta_field['id'] ];
						$value['num']   = ( isset( $value['num'] ) ) ? absint( $value['num'] ) : '';
						$value['units'] = ( isset( $value['units'] ) ) ? sanitize_text_field( $value['units'] ) : '';
						break;
					case 'electronic-submission-method':
						$value          = $_POST[ $meta_field['id'] ];
						$value['email'] = ( isset( $value['email'] ) ) ? sanitize_email( $value['email'] ) : '';
						$value['url']   = ( isset( $value['url'] ) ) ? esc_url_raw( $value['url'] ) : '';
						break;
					case 'application-deadline':
						$value = $_POST[ $meta_field['id'] ];
						array_walk( $value, 'sanitize_text_field' );
						break;
					default:
						$value = sanitize_text_field( $_POST[ $meta_field['id'] ] );
						break;
				}

				update_post_meta( $post_id, $meta_field['id'], $value );
			}
		}

		wp_cache_delete( 'grants_rest_response_' . $post_id );
	}
}