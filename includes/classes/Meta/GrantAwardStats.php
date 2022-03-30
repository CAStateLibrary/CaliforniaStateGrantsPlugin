<?php
/**
 * Grant Award Stats Meta
 *
 * @package CaGov\Grants
 */

namespace CaGov\Grants\Meta;

use CaGov\Grants\Meta\AwardStats;
use function CaGov\Grants\Core\is_portal;
use CaGov\Grants\Core;

/**
 * Grant Award Stats Meta Class
 */
class GrantAwardStats {

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
		$this->description = __( 'Keep this information about the grant up to date as new applications are submitted and grants are awarded.', 'ca-grants-plugin' );
	}

	/**
	 * Function to handle logic for showing metabox.
	 *
	 * @return boolean
	 */
	public static function is_visible() {
		$grant_id = get_post_meta( get_the_ID(), 'grantID', true );
		if ( empty( $grant_id ) ) {
			return false;
		}

		return Core\is_ongoing_grant( $grant_id ) || Core\is_closed_grant( $grant_id );
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

		$fiscal_year = '';

		if ( is_admin() ) {
			if ( ! self::is_visible() ) {
				return array();
			}

			$grant_id = get_post_meta( get_the_ID(), 'grantID', true );
			$deadline = get_post_meta( $grant_id, 'deadline', true );

			if ( ! empty( $deadline ) ) {
				$fiscal_year = Core\get_fiscal_year( gmdate( 'Y-m-d H:m:s', $deadline ) );
			}
		}

		$fields = array(
			array(
				'id'               => 'fiscalYear',
				'name'             => __( 'Fiscal Year', 'ca-grants-plugin' ),
				'type'             => $fiscal_year ? 'text' : 'select',
				'source'           => is_portal() ? 'portal-api' : 'api',
				'description'      => __( 'Select the fiscal year to save the number of applications and grants awarded for.', 'ca-grants-plugin' ),
				'hide_description' => true,
				'meta_value'       => $fiscal_year,
				'readonly'         => ( ! empty( $fiscal_year ) ),
			),
			array(
				'id'          => 'applicationsSubmitted',
				'name'        => __( 'Number of Applications Submitted', 'ca-grants-plugin' ),
				'type'        => 'number',
				'description' => __( 'Enter the total applications received for this funding opportunity.', 'ca-grants-plugin' ),
				'min'         => 0,
			),
			array(
				'id'          => 'grantsAwarded',
				'name'        => __( 'Number of Grants Awarded', 'ca-grants-plugin' ),
				'type'        => 'number',
				'description' => __( 'Enter the number of individual grants awarded for this grant opportunity. Please update if changes are made in the grant agreement.', 'ca-grants-plugin' ),
				'min'         => 0,
			),
		);

		return array(
			array(
				'id'                => 'awardStats',
				'type'              => 'save_to_field_group',
				'field_id'          => 'grantID',
				'serialize'         => true,
				'add_new_label'     => __( 'Add Fiscal Year', 'ca-grants-plugin' ),
				'is_multiple'       => empty( $fiscal_year ),
				'fields'            => $fields,
				'sanitize_callback' => [ AwardStats::class, 'sanitize_award_stats_data' ],
			),
		);
	}
}
