<?php
/**
 * Contact Grant Meta
 *
 * @package CaGov\Grants
 */

namespace CaGov\Grants\Meta;

/**
 * Contact Grant Data Meta Class
 */
class Contact {
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
		$this->description = __( 'Enter grant contact and link information.', 'ca-grants-plugin' );
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
			foreach ( self::get_standard_fields() as $field ) {
				Field::factory( $field );
			}
			?>
			</tbody>
		</table>

		<?php
		foreach ( self::get_contact_fields() as $field ) {
			Field::factory( $field );
		}
	}

	/**
	 * Get fields.
	 *
	 * @static
	 * @return array
	 */
	public static function get_fields() {
		return array_merge( self::get_standard_fields(), self::get_contact_fields() );
	}

	/**
	 * Get standard fields.
	 *
	 * @return array
	 */
	public static function get_standard_fields() {
		return array(
			array(
				'id'   => 'electronicSubmission',
				'name' => __( 'Submission Email', 'csl-grants-submission' ),
				'type' => 'electronic-submission-method',
			),
			array(
				'id'   => 'grantDetailsURL',
				'name' => __( 'Grant Details URL', 'csl-grants-submission' ),
				'type' => 'url',
			),
			array(
				'id'   => 'grantMakingAgencyURL',
				'name' => __( 'Grantmaking Agency/Department URL', 'csl-grants-submission' ),
				'type' => 'url',
			),
			array(
				'id'   => 'grantUpdatesURL',
				'name' => __( 'Grant Updates Subscribe URL', 'csl-grants-submission' ),
				'type' => 'url',
			),
			array(
				'id'   => 'plannedEventsURL',
				'name' => __( 'Planned Events Information URL', 'csl-grants-submission' ),
				'type' => 'url',
			),
		);
	}

	/**
	 * Get contact fields.
	 *
	 * @return array
	 */
	public static function get_contact_fields() {
		return array(
			array(
				'id'   => 'contactInfo',
				'name' => __( 'Public Point of Contact', 'csl-grants-submission' ),
				'type' => 'point_of_contact',
			),
			array(
				'id'   => 'adminPrimaryContact',
				'name' => __( 'Administrative Primary Point of Contact', 'csl-grants-submission' ),
				'type' => 'point_of_contact',
			),
			array(
				'id'   => 'adminSecondaryContact',
				'name' => __( 'Administrative Secondary Point of Contact', 'csl-grants-submission' ),
				'type' => 'point_of_contact',
			),
		);
	}
}
