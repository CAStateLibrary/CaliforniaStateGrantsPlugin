<?php
/**
 * Award Stats Meta
 *
 * @package CaGov\Grants
 */

namespace CaGov\Grants\Meta;

use CaGov\Grants\PostTypes\EditGrant;

/**
 * General Grant Data Meta Class
 */
class AwardStats {
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
		$this->description = __( 'Enter Award Stats information.', 'ca-grants-plugin' );
	}

	/**
	 * Render metabox.
	 *
	 * @return void
	 */
	public function render_metabox() {
		wp_nonce_field( EditGrant::NONCE_ACTION, EditGrant::NONCE_FIELD );
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
				'id'          => 'applicationNumber',
				'name'        => __( 'Number of Applications Submitted
				(number)', 'ca-grants-plugin' ),
				'type'        => 'number',
				'description' => __( 'Enter the total applications received for this funding opportunity.', 'ca-grants-plugin' ),
			),
			array(
				'id'          => 'grantsNumber',
				'name'        => __( 'Number of Grants Awarded
				(number)', 'ca-grants-plugin' ),
				'type'        => 'number',
				'description' => __( 'Enter the number of individual grants awarded for this grant opportunity. Please update if changes are made in the grant agreement.', 'ca-grants-plugin' ),
			),
		);
	}
}
