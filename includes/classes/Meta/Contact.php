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
				'id'          => 'electronicSubmission',
				'name'        => __( 'Electronic Submission', 'ca-grants-plugin' ),
				'type'        => 'electronic-submission-method',
				'description' => __( 'The Grant Information Act of 2018 requires each state agency, on or before July 1, 2020, to provide for the acceptance of electronic applications for any grant administered by the state agency, as appropriate.', 'ca-grants-plugin' ),
				'required' => array( 'active' ),
			),
			array(
				'id'       => 'details',
				'name'     => __( 'Grant Details URL', 'ca-grants-plugin' ),
				'type'     => 'url',
				'required' => array( 'active' ),
			),
			array(
				'id'          => 'agencyURL',
				'name'        => __( 'Grantmaking Agency/Department URL', 'ca-grants-plugin' ),
				'type'        => 'url',
				'required'    => array( 'active' ),
				'description' => __( 'Please provide a link to the page on your department’s website where applicants may find further details about this grant opportunity (e.g. the full RFP, or additional information).', 'ca-grants-plugin' ),
			),
			array(
				'id'          => 'subscribe',
				'name'        => __( 'Grant Updates Subscribe URL', 'ca-grants-plugin' ),
				'type'        => 'url',
				'description' => __( 'Please provide a link to your department’s website where applicants may learn more about it.', 'ca-grants-plugin' ),
			),
			array(
				'id'          => 'events',
				'name'        => __( 'Planned Events Information URL', 'ca-grants-plugin' ),
				'type'        => 'url',
				'description' => __( 'If there are any known events — workshops, public forums, meetings — planned for this grant, please provide a link to the page where applicants can find event details.', 'ca-grants-plugin' ),
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
				'id'           => 'contactInfo',
				'name'         => __( 'Public Point of Contact', 'ca-grants-plugin' ),
				'type'         => 'point_of_contact',
				'required'     => array( 'active' ),
				'section_note' => __( 'AB2252 requires grantmakers to provide a valid email address and/or phone number that applicants may use to make inquiries or request information about the grant opportunity. Please enter the phone number in the following format: 1-555-555-5555.', 'ca-grants-plugin' ),
			),
			array(
				'id'           => 'adminPrimaryContact',
				'name'         => __( 'Administrative Primary Point of Contact', 'ca-grants-plugin' ),
				'type'         => 'point_of_contact',
				'required'     => array( 'active' ),
				'section_note' => __( 'This contact information is for <strong>internal use only</strong> (i.e. not public-facing). It will only be used when a representative from the California Grants Portal (CGP) needs to contact the grantmaker for administrative purposes.<br /><br />Please provide <strong>two different</strong> points of contact.', 'ca-grants-plugin' ),
			),
			array(
				'id'           => 'adminSecondaryContact',
				'name'         => __( 'Administrative Secondary Point of Contact', 'ca-grants-plugin' ),
				'type'         => 'point_of_contact',
				'required'     => array( 'active' ),
				'section_note' => __( 'This contact information is for <strong>internal use only</strong> (i.e. not public-facing). It will only be used when a representative from the California Grants Portal (CGP) need to contact a secondary grantmaker for administrative purposes. Please enter the phone number in the following format: 1-555-555-5555.', 'ca-grants-plugin' ),
			),
		);
	}
}
