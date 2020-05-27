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
				'id'          => 'grantID',
				'name'        => __( 'Grant ID', 'ca-grants-plugin' ),
				'type'        => 'text',
				'description' => __( 'The identifier (typically numbers, letters or a combination of letters or numbers) that the state department uses to identify the grant. If your department does not use a Grant ID system, leave this field blank.', 'ca-grants-plugin' ),
			),
			array(
				'id'          => 'isForecasted',
				'name'        => __( 'Grant Type', 'ca-grants-plugin' ),
				'type'        => 'radio',
				'description' => __( 'Todo: Add helper text.', 'ca-grants-plugin' ),
				'fields'      => array(
					array(
						'id'   => 'forecasted',
						'name' => __( 'Forcasted', 'ca-grants-plugin' ),
					),
					array(
						'id'   => 'active',
						'name' => __( 'Active', 'ca-grants-plugin' ),
					),
				),
			),
			array(
				'id'          => 'opportunityType',
				'name'        => __( 'Opportunity Type', 'ca-grants-plugin' ),
				'type'        => 'radio',
				'description' => __( 'Todo: Add helper text.', 'ca-grants-plugin' ),
				'source'      => 'api',

			),
			array(
				'id'          => 'loiRequired',
				'name'        => __( 'Letter of Intent Required', 'ca-grants-plugin' ),
				'type'        => 'radio',
				'description' => __( 'Todo: Add helper text.', 'ca-grants-plugin' ),
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
			),
			array(
				'id'          => 'grantCategories',
				'name'        => __( 'Relevant Categories', 'ca-grants-plugin' ),
				'type'        => 'checkbox',
				'description' => __( 'Todo: Add helper text.', 'ca-grants-plugin' ),
				'source'      => 'api',
				'multi'       => true,
			),
			array(
				'id'          => 'categorySuggestions',
				'name'        => __( 'Category Suggestion(s)', 'ca-grants-plugin' ),
				'type'        => 'text',
				'description' => __( 'Todo: Add helper text.', 'ca-grants-plugin' ),
			),
			array(
				'id'          => 'purpose',
				'name'        => __( 'Purpose', 'ca-grants-plugin' ),
				'type'        => 'textarea',
				'description' => __( 'Todo: Add helper text.', 'ca-grants-plugin' ),
				'text_limit'  => 450,
			),
			array(
				'id'          => 'description',
				'name'        => __( 'Description', 'ca-grants-plugin' ),
				'type'        => 'textarea',
				'description' => __( 'Todo: Add helper text.', 'ca-grants-plugin' ),
				'text_limit'  => 3200,
			),
		);
	}
}
