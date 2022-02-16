<?php
/**
 * Award Uploads CPT Meta
 *
 * @package CaGov\Grants
 */

namespace CaGov\Grants\Meta;

use CaGov\Grants\PostTypes\EditAwardUploads;
use CaGov\Grants\PostTypes\Grants as GrantsCPT;

/**
 * General Grant Data Meta Class
 */
class AwardUploads {

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
		$this->description = __( 'Enter award uploads information.', 'ca-grants-plugin' );
	}

	/**
	 * Render metabox.
	 *
	 * @return void
	 */
	public function render_metabox() {
		wp_nonce_field( EditAwardUploads::$nonce_action, EditAwardUploads::$nonce_field );
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
				'id'          => 'csl_grant_id',
				'name'        => __( 'Associated Grant', 'ca-grants-plugin' ),
				'type'        => 'post-finder',
				'description' => __( 'Select the grant to enter award data for.', 'ca-grants-plugin' ),
				'required'    => true,
				'options'     => array(
					'show_numbers'   => false,
					'show_recent'    => false,
					'limit'          => 1,
					'include_script' => true,
					'args'           => array(
						'post_type' => GrantsCPT::get_cpt_slug(),
					),
				),
			),
			array(
				'id'          => 'applicationsSubmitted',
				'name'        => __( 'Number of Applications Submitted', 'ca-grants-plugin' ),
				'type'        => 'save_to_field',
				'field_id'    => 'csl_grant_id',
				'description' => __( 'Enter the total applications received for this funding opportunity.', 'ca-grants-plugin' ),
				'required'    => true,
			),
			array(
				'id'          => 'grantsAwarded',
				'name'        => __( 'Number of Grants Awarded', 'ca-grants-plugin' ),
				'type'        => 'save_to_field',
				'field_id'    => 'csl_grant_id',
				'description' => __( 'Enter the number of individual grants awarded for this grant opportunity. Please update if changes are made in the grant agreement.', 'ca-grants-plugin' ),
				'required'    => true,
			),
			array(
				'id'          => 'csl_fiscal_year',
				'name'        => __( 'Fiscal Year', 'ca-grants-plugin' ),
				'type'        => 'select',
				'source'      => 'api',
				'description' => __( 'Select the Fiscal Year to import awards for.', 'ca-grants-plugin' ),
				'required'    => true,
			),
			array(
				'id'          => 'csl_award_csv',
				'name'        => __( 'Award CSV', 'ca-grants-plugin' ),
				'type'        => 'file',
				'description' => __( 'Browse and select the CSV containing award data.', 'ca-grants-plugin' ),
				'required'    => true,
			),
		);
	}
}
