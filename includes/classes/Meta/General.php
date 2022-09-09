<?php
/**
 * General Grant Meta
 *
 * @package CaGov\Grants
 */

namespace CaGov\Grants\Meta;

use CaGov\Grants\PostTypes\EditGrant;
use function CaGov\Grants\Core\is_portal;

/**
 * General Grant Data Meta Class
 */
class General {
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
		wp_nonce_field( EditGrant::$nonce_action, EditGrant::$nonce_field );
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
		$fields = array(
			array(
				'id'          => 'grantID',
				'name'        => __( 'Grant ID', 'ca-grants-plugin' ),
				'type'        => 'text',
				'description' => __( 'The identifier (typically numbers, letters or a combination of letters or numbers) that the state department uses to identify the grant. If your department does not use a Grant ID system, leave this field blank.', 'ca-grants-plugin' ),
			),
		);

		if ( is_portal() ) {
			$fields[] = array(
				'id'   => 'uniqueID',
				'name' => __( 'Unique ID', 'ca-grants-plugin' ),
				'type' => 'text',
			);
		}

		$fields[] = array(
			'id'          => 'isForecasted',
			'name'        => __( 'Grant Type', 'ca-grants-plugin' ),
			'type'        => 'radio',
			'description' => __( '<strong>Forecasted:</strong> A grant opportunity that is planned but not yet open. Not all data elements are required so you do not need to have the program details finalized. Grantmaking agencies are not required to post forecasted grants, though they are helpful for grantseekers.<br /><strong>Active:</strong> A grant opportunity that is currently open to application submissions. Grantmaking agencies must post active grants on the Grants Portal as soon as they become available.', 'ca-grants-plugin' ),
			'fields'   => array(
				array(
					'id'   => 'forecasted',
					'name' => __( 'Forecasted', 'ca-grants-plugin' ),
				),
				array(
					'id'   => 'active',
					'name' => __( 'Active', 'ca-grants-plugin' ),
				),
			),
			'required' => array( 'active', 'forecasted' ),
		);

		$fields[] = array(
			'id'          => 'opportunityType',
			'name'        => __( 'Opportunity Type', 'ca-grants-plugin' ),
			'type'        => 'checkbox',
			'source'      => is_portal() ? 'portal-api' : 'api',
			'required'    => array( 'active', 'forecasted' ),
			'description' => __( 'Choose whether this is a grant, loan, or both a grant and a loan.', 'ca-grants-plugin' ),
		);

		$fields[] = array(
			'id'          => 'loiRequired',
			'name'        => __( 'Letter of Intent Required', 'ca-grants-plugin' ),
			'type'        => 'radio',
			'description' => __( 'A Letter of Intent, Letter of Inquiry, Concept Paper, or similar document is a summary of a grant proposal, which grantmakers then use to determine whether applicants should submit a full applications. Select whether a Letter of Intent or similar document is required as the first step in the application process.', 'ca-grants-plugin' ),
			'fields'      => array(
				array(
					'id'   => 'yes',
					'name' => __( 'Yes', 'ca-grants-plugin' ),
				),
				array(
					'id'   => 'no',
					'name' => __( 'No', 'ca-grants-plugin' ),
				),
			),
			'required'    => array( 'active' ),
		);

		$fields[] = array(
			'id'          => 'grantCategories',
			'name'        => __( 'Relevant Categories', 'ca-grants-plugin' ),
			'type'        => 'checkbox',
			'description' => __( 'Please indicate the category (or categories) this grant opportunity belongs in. Categorizing your grant will allow users to narrow their search to find grants covering specific topics or areas of focus relevant to their needs.<br/><br/>If your grant doesn’t fit into any of the categories presented, please select Uncategorized, and suggest a category in Category Suggestion(s) below.', 'ca-grants-plugin' ),
			'source'      => is_portal() ? 'portal-api' : 'api',
			'multi'       => true,
			'required'    => array( 'active' ),
		);

		$fields[] = array(
			'id'          => 'categorySuggestions',
			'name'        => __( 'Category Suggestion(s)', 'ca-grants-plugin' ),
			'type'        => 'text',
			'description' => __( 'If you selected Uncategorized from the categories list above (or just have a suggestion for a category addition), please tell us the category you would like to see added to the list. (CA Grants Portal will add categories at its discretion; there is no guarantee a suggestion will be added.)', 'ca-grants-plugin' ),
		);

		$fields[] = array(
			'id'          => 'purpose',
			'name'        => __( 'Purpose', 'ca-grants-plugin' ),
			'type'        => 'textarea',
			'description' => __( 'Please provide a brief outline of the goals and intended outcomes of this grant opportunity. In most cases, this information should already be available in your department’s documentation. If this is not the case, consider the following prompt, "At a high-level, what does your department hope to achieve as a result of this grant program?"', 'ca-grants-plugin' ),
			'text_limit'  => 450,
			'required'    => array( 'active' ),
		);

		$fields[] = array(
			'id'          => 'description',
			'name'        => __( 'Description', 'ca-grants-plugin' ),
			'type'        => 'textarea',
			'description' => __( 'Please provide an overview of the grant opportunity. This may include information such as: project such as the project scope, types of projects to be funded, allowable activities, eligibility exclusions, priority communities, or other types of priority, such as amount of match, grant award announcement mechanism, and/or past/average award size. If this grant opportunity uses a Letter of Intent process, please explain that in this section.<br/><br/>Please also list any keywords that grant seekers might use to find this grant. For example, if this grant is specifically designated for certain geographies or for disadvantaged communities, list these words throughout your description for grantseekers to be able to search them in the filter.', 'ca-grants-plugin' ),
			'text_limit'  => 3200,
			'required'    => array( 'active', 'forecasted' ),
		);

		return $fields;
	}
}
