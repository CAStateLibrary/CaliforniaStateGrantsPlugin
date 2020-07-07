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
				'name' => __( 'Grant Open', 'ca-grants-plugin' ),
				'type' => 'datetime-local',
				'description' => __( 'Please indicate when the grant should be made available in full on the portal (Open Date).', 'ca-grants-plugin' ),
			),
			array(
				'id'   => 'closeDate',
				'name' => __( 'Grant Close', 'ca-grants-plugin' ),
				'type' => 'datetime-local',
				'description' => __( 'Please indicate when the grant should be archived on the portal (Close Date). (Archived grants are still accessible on the portal after their Close Dates, but are marked as Closed and not included in search results.)<br/><br/>If the grant does not have a Close Date, that field may be left blank. Grants without Close Dates remain active and accessible via search results until updated with a Close Date.', 'ca-grants-plugin' ),
			),
			array(
				'id'   => 'periodOfPerformance',
				'name' => __( 'Period of Performance', 'ca-grants-plugin' ),
				'type' => 'text',
				'description' => __( 'What is the total length of time that the award is available and active (i.e. do recipients have access to grant funds only within a specific timeframe)?', 'ca-grants-plugin' ),
			),
			array(
				'id'   => 'expectedAwardDate',
				'name' => __( 'Expected Award Announcement Date', 'ca-grants-plugin' ),
				'type' => 'text',
			),
			array(
				'id'   => 'deadline',
				'name' => __( 'Application Deadline', 'ca-grants-plugin' ),
				'type' => 'datetime-local',
			),
		);
	}
}
