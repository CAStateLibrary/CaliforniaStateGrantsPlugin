<?php
/**
 * Award Stats Meta
 *
 * @package CaGov\Grants
 */

namespace CaGov\Grants\Meta;

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
	 * Render metabox.
	 *
	 * @return void
	 */
	public function render_metabox() {
		$post_id      = get_the_ID();
		$isForecasted = get_post_meta( $post_id, 'isForecasted', true );

		// Do not show this metabox if grant data is not saved.
		if ( empty( $isForecasted ) ) {
			return;
		}

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
		$fiscal_year      = '';
		$is_ongoing_grant = true;

		if ( is_admin() ) {
			$post_id      = get_the_ID();
			$isForecasted = get_post_meta( $post_id, 'isForecasted', true );

			if ( empty( $isForecasted ) ) {
				return array();
			}

			$is_ongoing_grant = Core\is_ongoing_grant( $post_id );
			$deadline         = get_post_meta( $post_id, 'deadline', true );

			if ( ! $is_ongoing_grant && ! empty( $deadline ) ) {
				$fiscal_year = Core\get_fiscal_year( gmdate( 'Y-m-d H:m:s', $deadline ) );
			}
		}

		$fields = array(
			array(
				'id'            => 'fiscalYear',
				'name'          => __( 'Fiscal Year', 'ca-grants-plugin' ),
				'type'          => $fiscal_year ? 'text' : 'select',
				'source'        => 'api',
				'description'   => __( 'Select the fiscal year in which this grant opportunity closed for applications.', 'ca-grants-plugin' ),
				'default_value' => $fiscal_year,
				'readonly'      => ( ! $is_ongoing_grant ),
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

		$group_fields = array(
			array(
				'id'            => 'awardStats',
				'name'          => __( 'Grant award stats data:', 'ca-grants-plugin' ),
				'type'          => 'group',
				'description'   => __( 'Grant award stats data:', 'ca-grants-plugin' ),
				'serialize'     => true,
				'add_new_label' => __( 'Add Fiscal Year', 'ca-grants-plugin' ),
				'is_multiple'   => Core\is_ongoing_grant( $post_id ),
				'fields'        => $fields,
			),
		);

		return $group_fields;
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

		// We only need one fiscal year data for closed grants.
		if ( ! $is_ongoing_grant && count( $award_stats ) > 1 ) {
			$errors->add(
				'validation_error',
				esc_html__( 'Invalid awardStats data: grant is closed and can have only one fiscal year data. Multiple entry found.', 'ca-grants-plugin' )
			);
			return $errors;
		}

		$deadline    = get_post_meta( $grant_id, 'deadline', true );
		$fiscal_year = empty( $deadline ) ? '' : Core\get_fiscal_year( gmdate( 'Y-m-d H:m:s', $deadline ) );

		if (
			! $is_ongoing_grant
			&& ! empty( $fiscal_year )
			&& ! empty( $award_stats[0]['fiscalYear'] )
			&& $fiscal_year !== $award_stats[0]['fiscalYear']
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
