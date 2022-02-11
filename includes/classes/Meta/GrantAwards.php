<?php
/**
 * Grant Awards Meta
 *
 * @package CaGov\Grants
 */

namespace CaGov\Grants\Meta;

use CaGov\Grants\PostTypes\EditGrantAwards;

/**
 * General Grant Data Meta Class
 */
class GrantAwards {

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
		$this->description = __( 'Enter general grant information.', 'ca-grants-plugin' );
	}

	/**
	 * Render metabox.
	 *
	 * @return void
	 */
	public function render_metabox() {
		wp_nonce_field( EditGrantAwards::NONCE_ACTION, EditGrantAwards::NONCE_FIELD );
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
			array( // TODO: Use post finder and make it select field to get grants.
				'id'          => 'grantID',
				'name'        => __( 'Associated Grant', 'ca-grants-plugin' ),
				'type'        => 'text',
				'description' => __( 'Select the grant to enter award data for.', 'ca-grants-plugin' ),
				'required'    => true,
			),
			array( // TODO: Save this field value to selected grant id meta.
				'id'          => 'applicationsSubmitted',
				'name'        => __( 'Number of Applications Submitted', 'ca-grants-plugin' ),
				'type'        => 'number',
				'description' => __( 'Enter the total applications received for this funding opportunity.', 'ca-grants-plugin' ),
				'required'    => true,
			),
			array( // TODO: Save this field value to selected grant id meta.
				'id'          => 'grantsAwarded',
				'name'        => __( 'Number of Grants Awarded', 'ca-grants-plugin' ),
				'type'        => 'number',
				'description' => __( 'Enter the number of individual grants awarded for this grant opportunity. Please update if changes are made in the grant agreement.', 'ca-grants-plugin' ),
				'required'    => true,
			),
			array(
				'id'          => 'fiscalYear',
				'name'        => __( 'Fiscal Year', 'ca-grants-plugin' ),
				'type'        => 'select',
				'source'      => 'api',
				'description' => __( 'Select the Fiscal Year this award belongs to.', 'ca-grants-plugin' ),
				'required'    => true,
			),
			array(
				'id'          => 'projectTitle',
				'name'        => __( 'Project Title', 'ca-grants-plugin' ),
				'type'        => 'text',
				'description' => __( 'The project title must match the title provided in the application.', 'ca-grants-plugin' ),
				'maxlength'   => 300,
				'required'    => true,
			),
			array(
				'id'          => 'recipientType',
				'name'        => __( 'Recipient Type', 'ca-grants-plugin' ),
				'type'        => 'select',
				'source'      => 'api',
				'description' => __( 'Indicate the recipient type, or the recipient type of the primary awardee if multiple awardees.<br/>
				<ol>
					<li>Business: A for-profit sole proprietorship, partnership, corporation, or other type of business.</li>
					<li>Individual: A person receiving on their own behalf (i.e., not on behalf of a company, organization, institution, or government).</li>
					<li>Nonprofit Organization: Any nonprofit (501(c)(3) or others) or tax-exempt organization, including private schools and private universities.</li>
					<li>Public Agency: Counties, cities, special districts, public K12 or higher education institutions, or any other government entity.</li>
					<li>Tribal Government: Federally recognized Tribes located in California and non-federally recognized Tribes located in California with an established government structure.</li>
				</ol>', 'ca-grants-plugin' ),
				'required'    => true,
			),
			array( // TODO: This field is only visible if “Individual” is not chosen as the “Recipient Type”.
				'id'          => 'primeryRecipientName',
				'name'        => __( 'Primary Recipient Name', 'ca-grants-plugin' ),
				'type'        => 'text',
				'description' => __( 'Provide the award recipient’s name (legal name of the principal investigator, project lead, or institution name), or the name of the primary awardee if multiple recipients. Please update this if changes are made in the grant agreement.', 'ca-grants-plugin' ),
				'maxlength'   => 100,
				'required'    => true,
			),
			array( // TODO: This field is only visible if “Individual” is chosen as the “Recipient Type”.
				'id'          => 'primeryRecipientFirstName',
				'name'        => __( 'Primary Recipient’s First Name', 'ca-grants-plugin' ),
				'type'        => 'text',
				'description' => __( 'Provide the award recipient’s first name, or the first name of the primary awardee if multiple recipients. Please update this if changes are made in the grant agreement.', 'ca-grants-plugin' ),
				'maxlength'   => 100,
				// TODO: Required if “Individual” is chosen as the “Recipient Type”
				'required'    => true,
			),
			array( // TODO: This field is only visible if “Individual” is chosen as the “Recipient Type”.
				'id'          => 'primeryRecipientLastName',
				'name'        => __( 'Primary Recipient’s Last Name', 'ca-grants-plugin' ),
				'type'        => 'text',
				'description' => __( 'Provide the award recipient’s last name, or the last name of the primary awardee if multiple recipients. Please update this if changes are made in the grant agreement.', 'ca-grants-plugin' ),
				'maxlength'   => 100,
				// TODO: Required if “Individual” is chosen as the “Recipient Type”
				'required'    => true,
			),
			array(
				'id'          => 'secondaryRecipients',
				'name'        => __( 'Secondary Recipients?', 'ca-grants-plugin' ),
				'type'        => 'select',
				'description' => __( 'Indicate if additional recipients (e.g. sub, secondary, or co-recipients/grantees) were listed in the application. ', 'ca-grants-plugin' ),
				'maxlength'   => 100,
				'required'    => true,
				'fields'   => array(
					array(
						'id'   => 'yes',
						'name' => __( 'Yes', 'ca-grants-plugin' ),
					),
					array(
						'id'   => 'no',
						'name' => __( 'No', 'ca-grants-plugin' ),
					),
				),
			),
			array(
				'id'          => 'totalAwardAmount',
				'name'        => __( 'Total Award Amount', 'ca-grants-plugin' ),
				'type'        => 'number',
				'description' => __( 'Enter the dollar amount awarded to the recipient, as listed on the grant agreement - think of this as the "grant cost" rather than the "project cost". Note: Please update if the award amount gets augmented in the grant agreement (however, updates are not required if award amount changes are not reflected in an updated agreement).', 'ca-grants-plugin' ),
				'required'    => true,
			),
			array(
				'id'          => 'matchingFundingAmount',
				'name'        => __( 'Matching Funding Amount', 'ca-grants-plugin' ),
				'type'        => 'number',
				'description' => __( 'If partial or full matching is requested or required by the agreement, enter the matched funding dollar amount. If no matched funding is requested or required, enter "0".  Please update if changes are made in the grant agreement.', 'ca-grants-plugin' ),
				'required'    => true,
			),
			array(
				'id'          => 'awardAmountNotes',
				'name'        => __( 'Award Amount Notes', 'ca-grants-plugin' ),
				'type'        => 'textarea',
				'description' => __( 'Provide any additional details, including: exceptions, amount or source (state, federal, in-kind, etc.) limitations, and matching funding percentage, if applicable.', 'ca-grants-plugin' ),
				'text_limit'  => 300,
			),
			array( // TODO: Validation value should be <= the end date ( grantFundedEndDate ).
				'id'          => 'grantFundedStartDate',
				'name'        => __( 'Beginning Date of Grant-Funded Project', 'ca-grants-plugin' ),
				'type'        => 'datetime-local',
				'description' => __( 'Provide the start date per the grant agreement.  For grants that are one-time spending opportunities (such as a lump sum payment), the date the grant was awarded serves as both start and end dates. Please update if changes are made in the grant agreement.', 'ca-grants-plugin' ),
				'required'    => true,
			),
			array(
				'id'          => 'grantFundedEndDate',
				'name'        => __( 'End Date of Grant-Funded Project', 'ca-grants-plugin' ),
				'type'        => 'datetime-local',
				'description' => __( 'Provide the expected close date per the grant agreement. For grants that are one-time spending opportunities (such as a lump sum payment), the date the grant was awarded serves as both start and end dates. Please update if changes are made in the grant agreement.', 'ca-grants-plugin' ),
				'required'    => true,
			),
			array(
				'id'          => 'projectAbstract',
				'name'        => __( 'Project Abstract', 'ca-grants-plugin' ),
				'type'        => 'textarea',
				'description' => __( 'Provide a brief outline of the project purpose, priorities, scope of this opportunity, and grant beneficiaries. Beneficiaries include any communities, persons, or entities that benefit from this funding. Please update if changes are made in the grant agreement.', 'ca-grants-plugin' ),
				'text_limit'  => 600,
				'required'    => true,
			),
			array(
				'id'          => 'geoLocationServed',
				'name'        => __( 'Geographic Location Served', 'ca-grants-plugin' ),
				'type'        => 'select',
				'description' => __( 'Select the geographic location the grant serves. This should be the area served by the funds.<br>Options are as follows:
				<ol>
					<li>County (select multiple if applicable)</li>
					<li>Statewide (as defined by your department or agency)</li>
					<li>Out-of-state</li>
				</ol>
				Please update if changes are made in the grant agreement.', 'ca-grants-plugin' ),
				'required'    => true,
				'fields'   => array(
					array(
						'id'   => 'county',
						'name' => __( 'County', 'ca-grants-plugin' ),
					),
					array(
						'id'   => 'statewide',
						'name' => __( 'Statewide', 'ca-grants-plugin' ),
					),
					array(
						'id'   => 'out-of-state',
						'name' => __( 'Out-of-state', 'ca-grants-plugin' ),
					),
				),
			),
			array( // TODO: [Verify] Required if “County” was selected as the “Geographic Location Served”
				'id'          => 'countiesServed',
				'name'        => __( 'Counties Served', 'ca-grants-plugin' ),
				'type'        => 'checkbox',
				'source'      => 'api',
				'description' => __( 'If "County" is selected in the Geographic Location Served field, select all relevant California counties.', 'ca-grants-plugin' ),
				'required'    => array( 'county' ),
			),
			array(
				'id'          => 'geoServedNotes',
				'name'        => __( 'Geographic Location Served Notes', 'ca-grants-plugin' ),
				'type'        => 'textarea',
				'description' => __( 'Provide details on geographic locations served by this grant opportunity, with emphasis on priority communities, underserved areas, or communities impacted by and benefiting from the funding. ', 'ca-grants-plugin' ),
				'text_limit'  => 300,
			),
		);
	}
}
