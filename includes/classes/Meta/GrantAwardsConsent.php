<?php
/**
 * Grant Awards Publish/Update consent metabox.
 *
 * @package CaGov\Grants
 */

namespace CaGov\Grants\Meta;

/**
 * Grant awards consent metabox class.
 */
class GrantAwardsConsent {
	/**
	 * Description
	 *
	 * @var string
	 */
	public $description;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->description = '';
	}

	/**
	 * Render metabox.
	 *
	 * @return void
	 */
	public function render_metabox() {
		if ( $this->description ) {
			echo '<p class="grants-metabox-description">' . esc_html( $this->description ) . '</p>';
		}
		?>

		<table class="form-table" role="presentation">
			<tbody>
			<?php
			foreach ( self::get_fields() as $field ) {
				Field::factory( $field );
			}
			?>
			</tbody>
		</table>

		<?php
	}

	/**
	 * Get fields.
	 *
	 * @static
	 * @return array
	 */
	public static function get_fields() {

		$fields = array(
			array(
				'id'     => 'grantConsent',
				'name'   => __( 'Grant Publish/Update Consent', 'ca-grants-plugin' ),
				'type'   => 'checkbox',
				'fields' => array(
					array(
						'id'   => 'consent-checkbox',
						'name' => __( 'I understand that the above information will be posted to the California Grants Portal in its entirety once submitted.', 'grantsportal' ),
					),
				),
			),
		);

		return $fields;
	}
}
