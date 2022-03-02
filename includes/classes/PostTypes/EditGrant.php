<?php
/**
 * Grant editing.
 *
 * @package CaGov\Grants
 */

namespace CaGov\Grants\PostTypes;

use CaGov\Grants\REST\GrantsEndpoint;
use CaGov\Grants\Meta;

/**
 * Edit grant class.
 */
class EditGrant extends BaseEdit {

	/**
	 * Init
	 *
	 * @var boolean
	 */
	public static $init = false;

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
				'class' => 'CaGov\\Grants\\Meta\\AwardStats',
				'title' => __( 'Award Stats', 'ca-grants-plugin' ),
			),
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
	public function setup( $cpt_slug = '' ) {
		if ( static::$init ) {
			return;
		}

		if ( empty( $cpt_slug ) ) {
			$cpt_slug = Grants::get_cpt_slug();
		}

		parent::setup( $cpt_slug );

		add_action( 'admin_notices', array( $this, 'validation_errors' ) );

		$this->endpoint = new GrantsEndpoint();
		static::$init     = true;
	}

	/**
	 * Handles the save post action.
	 *
	 * @param integer $post_id The ID of the currently displayed post.
	 */
	public function save_post( $post_id ) {

		parent::save_post( $post_id );

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
	 * Get all meta fields.
	 *
	 * @return array
	 */
	protected function get_all_meta_fields() {
		return array_merge(
			Meta\AwardStats::get_fields(),
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
