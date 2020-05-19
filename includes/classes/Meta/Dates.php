<?php
/**
 * Date and Deadlines Grant Meta
 *
 * @package CaGov\Grants
 */

namespace CaGov\Grants\Meta;

/**
 * Dates and Deadlines Grant Data Meta Class
 */
class Dates {
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
		$this->description = __( 'Enter grant date &amp; deadline information.', 'ca-grants-plugin' );
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
				'id'   => 'openDate',
				'name' => __( 'Grant Open', 'csl-grants-submission' ),
				'type' => 'datetime-local',
			),
			array(
				'id'   => 'closeDate',
				'name' => __( 'Grant Close', 'csl-grants-submission' ),
				'type' => 'datetime-local',
			),
			array(
				'id'   => 'periodOfPerformance',
				'name' => __( 'Period of Performance', 'csl-grants-submission' ),
				'type' => 'period-performance',
			),
			array(
				'id'   => 'expectedAwardDate',
				'name' => __( 'Expected Award Announcement Date', 'csl-grants-submission' ),
				'type' => 'text',
			),
			array(
				'id'   => 'deadline',
				'name' => __( 'Application Deadline', 'csl-grants-submission' ),
				'type' => 'datetime-local',
			),
		);
	}
}
