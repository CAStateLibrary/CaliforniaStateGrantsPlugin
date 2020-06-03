<?php
/**
 * Eligibility Grant Meta
 *
 * @package CaGov\Grants
 */

namespace CaGov\Grants\Meta;

/**
 * Eligibility Grant Data Meta Class
 */
class Eligibility {
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
		$this->description = __( 'Enter grant eligibility information.', 'ca-grants-plugin' );
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
		return array(
			array(
				'id'          => 'applicantType',
				'name'        => __( 'Eligibility: Applicant Type', 'ca-grants-plugin' ),
				'type'        => 'checkbox',
				'source'      => 'api',
				'description' => __( 'Please indicate the applicant type(s) eligible to apply for this grant opportunity.<br/><br/>Note: Non-profits do not need to have 501(c)(3) status; Public agency can be state, county, city, town, or special district, and can also be K-12, college or university affiliated; Tribal nation groups can be federally recognized and/or state recognised, or have no government recognition.', 'ca-grants-plugin' ),
			),
			array(
				'id'          => 'applicantTypeNotes',
				'name'        => __( 'Applicant Type Notes', 'ca-grants-plugin' ),
				'type'        => 'textarea',
				'text_limit'  => 250,
				'description' => __( 'If applicable, include any clarifications or additional information regarding applicant type eligibility.', 'ca-grants-plugin' ),

			),
			array(
				'id'          => 'geoLimitations',
				'name'        => __( 'Eligibility: Geographic', 'ca-grants-plugin' ),
				'type'        => 'textarea',
				'text_limit'  => 450,
				'description' => __( 'If applicable, provide details on any geographic requirements, limitations, or exclusions.<br/><br/>Must applicants live or do business in a specified geographic area? Does the grant money have to be spent only in certain geographic areas, or are there any focused priorities, such as disadvantage communities?', 'ca-grants-plugin' ),
			),
		);
	}
}
