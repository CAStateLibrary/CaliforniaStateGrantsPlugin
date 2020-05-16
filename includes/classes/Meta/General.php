<?php
/**
 * General Grant Meta
 *
 * @package CaGov\Grants
 */

namespace CaGov\Grants\Meta;

use CaGov\Grants\PostTypes\EditGrant;

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
				'id'   => 'grantID',
				'name' => __( 'Grant ID', 'csl-grants-submission' ),
				'type' => 'text',
				'description' => __( 'The title of the grant opportunity. This should be identical to the name for the grant opportunity on your own website, RFP/A, etc.', 'ca-grants-plugin' ),
			),
			array(
				'id'     => 'isForecasted',
				'name'   => __( 'Grant Type', 'csl-grants-submission' ),
				'type'   => 'radio',
				'fields' => array(
					array(
						'id'   => 'forecasted',
						'name' => __( 'Forcasted', 'csl-grants-submission' ),
					),
					array(
						'id'   => 'active',
						'name' => __( 'Active', 'csl-grants-submission' ),
					),
				),
			),
			array(
				'id'     => 'opportunityType',
				'name'   => __( 'Opportunity Type', 'csl-grants-submission' ),
				'type'   => 'radio',
				'source' => 'api',
			),
			array(
				'id'     => 'grantCategories',
				'name'   => __( 'Relevant Categories', 'csl-grants-submission' ),
				'type'   => 'select',
				'source' => 'api',
				'multi'  => true,
			),
			array(
				'id'   => 'categorySuggestions',
				'name' => __( 'Category Suggestion(s)', 'grantsportal' ),
				'type' => 'text',
			),
			array(
				'id'         => 'purpose',
				'name'       => __( 'Purpose', 'csl-grants-submission' ),
				'type'       => 'textarea',
				'text_limit' => 450,
			),
			array(
				'id'         => 'description',
				'name'       => __( 'Description', 'csl-grants-submission' ),
				'type'       => 'textarea',
				'text_limit' => 3200,
			),
		);
	}
}
