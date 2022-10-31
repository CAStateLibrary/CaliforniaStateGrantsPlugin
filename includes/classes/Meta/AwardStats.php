<?php
/**
 * Award Stats Meta
 *
 * @package CaGov\Grants
 */

namespace CaGov\Grants\Meta;

use function CaGov\Grants\Core\is_portal;
use CaGov\Grants\Core;
use WP_Error;

/**
 * General Grant Data Meta Class
 */
class AwardStats {
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
		$grant_id = get_the_ID();
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

			$post_id  = get_the_ID();
			$deadline = get_post_meta( $post_id, 'deadline', true );

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
				'id'            => 'applicationsSubmitted',
				'name'          => __( 'Number of Applications Submitted', 'ca-grants-plugin' ),
				'type'          => 'number',
				'description'   => __( 'Enter the total applications received for this funding opportunity.', 'ca-grants-plugin' ),
				'min'           => 0,
			),
			array(
				'id'            => 'grantsAwarded',
				'name'          => __( 'Number of Grants Awarded', 'ca-grants-plugin' ),
				'type'          => 'number',
				'description'   => __( 'Enter the number of individual grants awarded for this grant opportunity. Please update if changes are made in the grant agreement.', 'ca-grants-plugin' ),
				'min'           => 0,
			),
		);

		$group_fields = array(
			array(
				'id'                => 'awardStats',
				'type'              => 'group',
				'serialize'         => true,
				'add_new_label'     => __( 'Add Fiscal Year', 'ca-grants-plugin' ),
				'is_multiple'       => empty( $fiscal_year ),
				'fields'            => $fields,
				'sanitize_callback' => [ __CLASS__, 'sanitize_award_stats_data' ],
			),
		);

		return $group_fields;
	}

	/**
	 * Sanitize award stats data for grant.
	 *
	 * @param array $value Award stats data.
	 *
	 * @return array Sanitized award stats data.
	 */
	public static function sanitize_award_stats_data( $value ) {
		// Remove any empty data.
		$value       = array_filter( $value, 'array_filter' );
		$unique_data = [];

		foreach ( $value as $award_stats ) {
			if ( ! isset( $award_stats['fiscalYear'], $award_stats['applicationsSubmitted'], $award_stats['grantsAwarded'] ) ) {
				continue;
			}

			if ( '' === $award_stats['fiscalYear'] || '' === $award_stats['applicationsSubmitted'] ) {
				continue;
			}

			$unique_data[ $award_stats['fiscalYear'] ] = [
				'fiscalYear'            => sanitize_text_field( $award_stats['fiscalYear'] ),
				'applicationsSubmitted' => absint( $award_stats['applicationsSubmitted'] ),
				'grantsAwarded'         => absint( $award_stats['grantsAwarded'] ),
			];
		}

		return array_values( $unique_data );
	}

	/**
	 * Validate award stats data for grant and send error if invalid data found.
	 *
	 * @param array $award_stats Grant award stats data.
	 * @param int   $grant_id Grant post ID.
	 *
	 * @return WP_Error|boolean
	 */
	public static function get_validation_errors( $award_stats, $grant_id ) {
		$errors = new WP_Error();

		if ( empty( $award_stats ) || ! is_array( $award_stats ) ) {
			$errors->add(
				'validation_error',
				esc_html__( 'Invalid or empty awardStats data, please check and try again.', 'ca-grants-plugin' )
			);
			return $errors;
		}

		$isForecasted = get_post_meta( $grant_id, 'isForecasted', true );

		if ( empty( $isForecasted ) ) {
			$errors->add(
				'validation_error',
				esc_html__( 'Grant type is not defined, awardStats value can\'t be accepted. Please update grant type and try again.', 'ca-grants-plugin' )
			);
			return $errors;
		}

		$is_ongoing_grant = Core\is_ongoing_grant( $grant_id );
		$deadline         = get_post_meta( $grant_id, 'deadline', true );
		$fiscal_year      = empty( $deadline ) ? '' : Core\get_fiscal_year( gmdate( 'Y-m-d H:m:s', $deadline ) );

		if (
			! $is_ongoing_grant
			&& ! empty( $fiscal_year )
			&& ! empty( $award_stats['fiscalYear'] )
			&& $fiscal_year !== $award_stats['fiscalYear']
		) {
			$errors->add(
				'validation_error',
				esc_html__( 'Invalid fiscalYear found in awardStats data: Grant is closed and fiscal year should match with deadline date.', 'ca-grants-plugin' )
			);
			return $errors;
		}

		return true;
	}
}
