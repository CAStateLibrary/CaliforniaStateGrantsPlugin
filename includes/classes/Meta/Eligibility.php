<?php
/**
 * Eligibility Grant Meta
 *
 * @package CaGov\Grants
 */

namespace CaGov\Grants\Meta;

use function CaGov\Grants\Core\is_portal;

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
		$fields = [];

		$fields[] = array(
			'id'          => 'applicantType',
			'name'        => __( 'Eligibility: Applicant Type', 'ca-grants-plugin' ),
			'type'        => 'checkbox',
			'source'      => is_portal() ? 'portal-api' : 'api',
			'description' => __( 'Please indicate the applicant type(s) eligible to apply for this grant opportunity.<br/><br/>Note: Non-profits do not need to have 501(c)(3) status; Public agency can be state, county, city, town, or special district, and can also be K-12, college or university affiliated; Tribal nation groups can be federally recognized and/or state recognised, or have no government recognition.<br/><br/>If you select "other", please elaborate in the applicant type notes field below.', 'ca-grants-plugin' ),
			'required'    => array( 'active' ),
		);

		if ( is_portal() ) {
			$fields[] = array(
				'id'   => 'applicantTypeSuggestion',
				'name' => __( 'Applicant Type Suggestion(s)', 'ca-grants-plugin' ),
				'type' => 'text',
			);
		}

		$fields[] = array(
			'id'          => 'applicantTypeNotes',
			'name'        => __( 'Applicant Type Notes', 'ca-grants-plugin' ),
			'type'        => 'textarea',
			'text_limit'  => 450,
			'description' => __( 'If applicable, include any clarifications or additional information regarding applicant type eligibility.', 'ca-grants-plugin' ),

		);

		$fields[] = array(
			'id'          => 'geoLimitations',
			'name'        => __( 'Eligibility: Geographic', 'ca-grants-plugin' ),
			'type'        => 'textarea',
			'text_limit'  => 450,
			'description' => __( "If applicable, provide details on any geographic requirements, limitations, or exclusions. Please add the following language if projects operated on state or federal lands are eligible to apply for the grant: 'Projects may occur on state or federal lands.'<br/><br/>Must applicants live or do business in a specified geographic area? Does the grant money have to be spent only in certain geographic areas, or are there any focused priorities, such as disadvantage communities?", 'ca-grants-plugin' ),
		);

		return $fields;
	}
}
