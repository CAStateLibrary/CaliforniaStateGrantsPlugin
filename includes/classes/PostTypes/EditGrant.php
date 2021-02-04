<?php
/**
 * Grant editing.
 *
 * @package CaGov\Grants
 */

namespace CaGov\Grants\PostTypes;

use CaGov\Grants\Admin\Settings;
use CaGov\Grants\REST\GrantsEndpoint;
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
	 * Settings.
	 *
	 * @var Settings
	 */
	public $settings;

	/**
	 * Endpoint.
	 *
	 * @var GrantsEndpoint
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
		$this->settings    = new Settings();
		$this->endpoint    = new GrantsEndpoint();
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
		if ( self::$init ) {
			return;
		}

		add_action( 'add_meta_boxes', array( $this, 'add_metaboxes' ) );
		add_action( 'save_post_' . Grants::CPT_SLUG, array( $this, 'save_post' ) );
		add_action( 'admin_notices', array( $this, 'validation_errors' ) );
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
	 * Viewing edit grant page.
	 *
	 * @return bool
	 */
	public function viewing() {
		if ( ! is_admin() || ! function_exists( 'get_current_screen' ) ) {
			return false;
		}

		$screen = get_current_screen();
		if ( $screen && Grants::CPT_SLUG === $screen->post_type && 'post' === $screen->base ) {
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

		if ( ! empty( $meta_fields ) ) {
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
						$temp_value            = $_POST[ $meta_field['id'] ];
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
						$temp_value          = $_POST[ $meta_field['id'] ];
						$temp_value['num']   = ( isset( $temp_value['num'] ) ) ? absint( $temp_value['num'] ) : '';
						$temp_value['units'] = ( isset( $temp_value['units'] ) ) ? sanitize_text_field( $temp_value['units'] ) : '';
						$value = $temp_value;
						break;
					case 'electronic-submission-method':
						$temp_value          = $_POST[ $meta_field['id'] ];
						$temp_value['email'] = ( isset( $temp_value['email'] ) ) ? sanitize_email( $temp_value['email'] ) : '';
						$temp_value['url']   = ( isset( $temp_value['url'] ) ) ? esc_url_raw( $temp_value['url'] ) : '';
						$value = $temp_value;
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

				update_post_meta( $post_id, $meta_field['id'], $value );
			}
		}

		// Remote validate if set.
		if ( $this->settings->get_setting( 'remote_validation' ) ) {
			$this->remote_validate( $post_id );
		}

		wp_cache_delete( 'grants_rest_response_' . $post_id );
	}

	/**
	 * Remote validate.
	 *
	 * @param  int $post_id The grant ID to validate.
	 * @return void
	 */
	public function remote_validate( $post_id ) {
		$shimmed_response = $this->endpoint->modify_grants_rest_response(
			new \WP_REST_Response(),
			get_post( $post_id ),
			new \WP_REST_Request()
		);

		if ( $shimmed_response instanceof \WP_REST_Response ) {
			$data = $shimmed_response->data;

			if ( is_array( $data ) ) {
				$validator = \wp_remote_post(
					CA_GRANTS_PORTAL_JSON_URL . 'grantsportal/v1/remote_validation',
					array(
						'body' => array(
							'data' => (array) $data,
						),
					)
				);
			}
		}
		$body   = \wp_remote_retrieve_body( $validator );
		$errors = json_decode( $body );

		if ( is_array( $errors ) && ! empty( $errors ) ) {
			update_post_meta( $post_id, 'validation_errors', $errors );
		} else {
			delete_post_meta( $post_id, 'validation_errors' );
		}
	}

	/**
	 * Validation errors
	 *
	 * @return void
	 */
	public function validation_errors() {
		if ( ! $this->viewing() || ! $this->settings->get_setting( 'remote_validation' ) ) {
			return;
		}

		$errors = get_post_meta( get_post()->ID, 'validation_errors', true );

		if ( empty( $errors ) ) {
			return;
		}

		$meta_fields = $this->get_all_meta_fields();
		?>
		<div class="notice notice-error">
			<p>
				<?php esc_html_e( 'The following fields will fail to validate when submitting this grant:', 'ca-grants-plugin' ); ?>
				<ul>
				<?php foreach ( $errors as $error ) : ?>
					<li>
						<code>
							<?php echo esc_html( $this->get_meta_field_display_name( $error ) ); ?>
						</code>
					</li>
				<?php endforeach; ?>
				</ul>
			</p>
		</div>
		<?php
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

		$post_type = get_post_type_object( Grants::CPT_SLUG );
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
			Meta\General::get_fields(),
			Meta\Eligibility::get_fields(),
			Meta\Funding::get_fields(),
			Meta\Dates::get_fields(),
			Meta\Contact::get_fields()
		);
	}

	/**
	 * Get meta field display name.
	 *
	 * @param  string $field_id The field id.
	 * @return string
	 */
	protected function get_meta_field_display_name( $field_id ) {
		$meta_field = array_filter(
			$this->get_all_meta_fields(),
			function( $field ) use ( $field_id ) {
				return $field['id'] === $field_id;
			}
		);

		$meta_field = ! empty( $meta_field ) ? array_shift( $meta_field ) : false;

		if ( $meta_field && isset( $meta_field['name'] ) ) {
			return $meta_field['name'];
		}

		return (string) $field_id;
	}
}
