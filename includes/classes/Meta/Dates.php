<?php
/**
 * Date and Deadlines Grant Meta
 *
 * @package CaGov\Grants
 */

namespace CaGov\Grants\Meta;

use function CaGov\Grants\Core\is_portal;

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
		$fields = [];

		if ( ! is_portal() ) {
			$fields[] = [
				'id'          => 'openDate',
				'name'        => __( 'Publish Date', 'ca-grants-plugin' ),
				'type'        => 'datetime-local',
				'description' => __( 'Please indicate when the grant should be made available in full on the portal (Open Date).', 'ca-grants-plugin' ),
				'required'    => [ 'active', 'forecasted' ],
			];
		}

		$fields[] = [
			'id'          => 'anticipatedOpenDate',
			'class'       => 'onlyForecasted',
			'name'        => __( 'Anticipated Open Date', 'ca-grants-plugin' ),
			'type'        => 'text',
			'description' => __( 'For <strong>forecasted</strong> grants only. You can use things like "Q1" or "Summer 2020"', 'ca-grants-plugin' ),
		];

		$fields[] = [
			'id'          => 'periodOfPerformance',
			'name'        => __( 'Period of Performance', 'ca-grants-plugin' ),
			'type'        => 'text',
			'description' => __( 'What is the total length of time that the award is available and active (i.e. do recipients have access to grant funds only within a specific timeframe)? <strong>The max character limit is 20 characters.</strong>', 'ca-grants-plugin' ),
			'maxlength'   => 20,
			'required'    => [ 'active' ],
		];

		$fields[] = [
			'id'          => 'expectedAwardDate',
			'name'        => __( 'Expected Award Announcement Date', 'ca-grants-plugin' ),
			'type'        => 'text',
			'description' => __( '<strong>The max character limit is 20 characters.</strong>', 'ca-grants-plugin' ),
			'maxlength'   => 20,
			'required'    => [ 'active' ],
		];

		$fields[] = [
			'id'          => 'deadline',
			'class'       => 'onlyActive',
			'name'        => __( 'Application Deadline', 'ca-grants-plugin' ),
			'type'        => 'datetime-local',
			'description' => __( "Leave empty if this opportunity has no deadline.<br/><br/>Please enter the time in 24 hour format (e.g. 14:30). When grants close at midnight, the grant details page will display 00:00, which can be confusing for users. If possible, use '23:59' (11:59pm) for clarity.", 'ca-grants-plugin' ),
		];

		return $fields;
	}
}
