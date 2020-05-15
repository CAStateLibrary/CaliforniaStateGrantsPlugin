<?php
/**
 * Funding Grant Meta
 *
 * @package CaGov\Grants
 */

namespace CaGov\Grants\Meta;

/**
 * Funding Grant Data Meta Class
 */
class Funding {
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
		$this->description = __( 'Enter grant funding information.', 'ca-grants-plugin' );
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
				'id'     => 'revSources',
				'name'   => __( 'Revenue Source', 'csl-grants-submission' ),
				'type'   => 'radio',
				'source' => 'api',
			),
			array(
				'id'         => 'revenueSourceNotes',
				'name'       => __( 'Revenue Source Notes', 'csl-grants-submission' ),
				'type'       => 'textarea',
				'text_limit' => 200,
			),
			array(
				'id'   => 'matchingFunds',
				'name' => __( 'Eligibility: Matching Funds', 'csl-grants-submission' ),
				'type' => 'eligibility-matching-funds',
			),
			array(
				'id'         => 'matchingFundsNotes',
				'name'       => __( 'Matching Funds Notes', 'csl-grants-submission' ),
				'type'       => 'textarea',
				'text_limit' => 300,
			),
			array(
				'id'   => 'estimatedAvailableFunds',
				'name' => __( 'Total Estimated Available Funding', 'csl-grants-submission' ),
				'type' => 'number',
			),
			array(
				'id'   => 'estimatedAwards',
				'name' => __( 'Estimated Number of Awards', 'csl-grants-submission' ),
				'type' => 'estimated-number-awards',
			),
			array(
				'id'   => 'estimatedAmounts',
				'name' => __( 'Estimated Award Amounts', 'csl-grants-submission' ),
				'type' => 'estimated-award-amounts',
			),
			array(
				'id'     => 'disbursementMethod',
				'name'   => __( 'Funds Disbursement Methods', 'csl-grants-submission' ),
				'type'   => 'radio',
				'source' => 'api',
			),
			array(
				'id'   => 'disbursementMethodNotes',
				'name' => __( 'Funds Disbursement Details', 'csl-grants-submission' ),
				'type' => 'textarea',
			),
		);
	}
}
