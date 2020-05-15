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
				'id'     => 'applicantType',
				'name'   => __( 'Eligibility: Applicant Type', 'csl-grants-submission' ),
				'type'   => 'checkbox',
				'source' => 'api',
			),
			array(
				'id'         => 'applicantTypeNotes',
				'name'       => __( 'Applicant Type Notes', 'csl-grants-submission' ),
				'type'       => 'textarea',
				'text_limit' => 250,
			),
			array(
				'id'         => 'geoLimitations',
				'name'       => __( 'Eligibility: Geographic', 'csl-grants-submission' ),
				'type'       => 'textarea',
				'text_limit' => 450,
			),
		);
	}
}
