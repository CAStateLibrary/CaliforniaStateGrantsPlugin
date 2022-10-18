<?php
/**
 * Funding Grant Meta
 *
 * @package CaGov\Grants
 */

namespace CaGov\Grants\Meta;

use function CaGov\Grants\Core\is_portal;

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
		$fields = [];

		$fields[] = array(
			'id'          => 'fundingSource',
			'name'        => __( 'Funding Source', 'ca-grants-plugin' ),
			'type'        => 'radio',
			'source'      => is_portal() ? 'portal-api' : 'api',
			'description' => __( 'If you select "Other", please elaborate in the funding source notes field below.', 'ca-grants-plugin' ),
			'required'    => array( 'active' ),
		);

		$fields[] = array(
			'id'          => 'revenueSourceNotes',
			'name'        => __( 'Funding Source Notes', 'ca-grants-plugin' ),
			'type'        => 'textarea',
			'text_limit'  => 450,
			'description' => __( 'Please specify information that may be helpful to applicants, such as proposition number, bond name/number, federal grant program, etc.', 'ca-grants-plugin' ),
		);

		$fields[] = array(
			'id'          => 'matchingFunds',
			'name'        => __( 'Eligibility: Matching Funds', 'ca-grants-plugin' ),
			'type'        => 'eligibility-matching-funds',
			'required'    => array( 'active' ),
			'description' => __( 'Choose “Yes” or “No.” Certain grant programs do not require match if an eligible applicant meets certain requirements (budget, geography, etc.). When a department uses a sliding scale, it is not uncommon for certain categories of applicants to have no match requirements. In this case, indicate that no match is required and then provide more information in the Matching Funding Notes to indicate the range of match requirements. Follow guidelines created by your department or agency to determine if your program includes matched funding.', 'ca-grants-plugin' ),
		);

		$fields[] = array(
			'id'          => 'matchingFundsNotes',
			'name'        => __( 'Matching Funds Notes', 'ca-grants-plugin' ),
			'type'        => 'textarea',
			'text_limit'  => 450,
			'description' => __( 'Please provide any additional details and/or exceptions, or requirements around acceptable matched funding sources (e.g. public or private funding only). If your grant has no requirement for matching funds, but gives preference based on applications that have them, please indicate this in your Grant Description.', 'ca-grants-plugin' ),
		);

		$fields[] = array(
			'id'          => 'totalEstimatedFunding',
			'name'        => __( 'Total Estimated Available Funding', 'ca-grants-plugin' ),
			'description' => __( 'If you know the exact amount of funding available, select “Exact Funding Amount” and input the dollar amount available for this grant opportunity. Only input numbers; do not use punctuation such as commas or periods. If funding has not yet been determined by your agency, select “Other Funding Amount,” and provide notes.', 'ca-grants-plugin' ),
			'type'        => 'radio',
			'value'       => 'exactFundingAmount',
			'fields'      => array(
				array(
					'id'   => 'exactFundingAmount',
					'name' => __( 'Exact Funding Amount', 'ca-grants-plugin' ),
				),
				array(
					'id'   => 'fundingAmountNotes',
					'name' => __( 'Other Funding Amount', 'ca-grants-plugin' ),
				),
			),
			'required' => array( 'active' ),
		);

		$fields[] = array(
			'id'      => 'estimatedAvailableFunds',
			'name'    => __( 'Available Funding Amount', 'ca-grants-plugin' ),
			'type'    => 'number',
			'visible' => array(
				'fieldId'  => 'totalEstimatedFunding',
				'value'    => 'exactFundingAmount',
				'compare'  => 'equal',
				'required' => true,
			),
		);

		$fields[] = array(
			'id'         => 'estimatedAvailableFundNotes',
			'name'       => __( 'Available Funding Notes', 'ca-grants-plugin' ),
			'type'       => 'textarea',
			'text_limit' => 450,
			'visible'    => array(
				'fieldId'  => 'totalEstimatedFunding',
				'value'    => 'fundingAmountNotes',
				'compare'  => 'equal',
				'required' => true,
			),
		);

		$fields[] = array(
			'id'          => 'estimatedAwards',
			'name'        => __( 'Estimated Number of Awards', 'ca-grants-plugin' ),
			'type'        => 'estimated-number-awards',
			'required'    => array( 'active' ),
			'description' => __( 'Please enter a number only; no letters, commas, or special characters. Refer to the data dictionary in the State Grantmaker’s Guide for additional information.', 'ca-grants-plugin' ),
		);

		$fields[] = array(
			'id'          => 'estimatedAmounts',
			'name'        => __( 'Estimated Award Amounts', 'ca-grants-plugin' ),
			'type'        => 'estimated-award-amounts',
			'required'    => array( 'active' ),
			'description' => __( 'Please enter a number only; no letters, commas, or special characters. Refer to the data dictionary in the State Grantmaker\'s Guide for additional information.', 'ca-grants-plugin' ),
		);

		$fields[] = array(
			'id'          => 'disbursementMethod',
			'name'        => __( 'Funding Method', 'ca-grants-plugin' ),
			'type'        => 'radio',
			'source'      => is_portal() ? 'portal-api' : 'api',
			'description' => __( 'If you select "Other", please elaborate in the funding method notes field below.', 'ca-grants-plugin' ),
			'required'    => array( 'active' ),
		);

		$fields[] = array(
			'id'          => 'disbursementMethodNotes',
			'name'        => __( 'Funding Method Notes', 'ca-grants-plugin' ),
			'type'        => 'textarea',
			'text_limit'  => 450,
			'description' => __( 'Use this field to include 1-2 sentences providing details on the funding method.', 'ca-grants-plugin' ),
		);

		return $fields;
	}
}
