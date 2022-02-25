<?php
/**
 * Grant Award Stats Meta
 *
 * @package CaGov\Grants
 */

namespace CaGov\Grants\Meta;

/**
 * Grant Award Stats Meta Class
 */
class GrantAwardStats {

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
		$this->description = __( 'Keep this information about the grant up to date as new applications are submitted and grants are awarded.', 'ca-grants-plugin' );
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
				'id'          => 'applicationsSubmitted',
				'name'        => __( 'Number of Applications Submitted', 'ca-grants-plugin' ),
				'type'        => 'save_to_field',
				'field_id'    => 'grantID',
				'description' => __( 'Enter the total applications received for this funding opportunity.', 'ca-grants-plugin' ),
				'required'    => true,
			),
			array(
				'id'          => 'grantsAwarded',
				'name'        => __( 'Number of Grants Awarded', 'ca-grants-plugin' ),
				'type'        => 'save_to_field',
				'field_id'    => 'grantID',
				'description' => __( 'Enter the number of individual grants awarded for this grant opportunity. Please update if changes are made in the grant agreement.', 'ca-grants-plugin' ),
				'required'    => true,
			),
		);
	}
}
