<?php
/**
 * Award Uploads CPT Meta
 *
 * @package CaGov\Grants
 */

namespace CaGov\Grants\Meta;

use CaGov\Grants\PostTypes\EditAwardUploads;

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
		$this->description = __( 'Information about this upload:', 'ca-grants-plugin' );
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
				'id'         => 'csl_grant_id',
				'name'       => __( 'Associated Grant:', 'ca-grants-plugin' ),
				'type'       => 'label',
				'value_type' => 'post-title',
				'link'       => 'post-link',
			),
			array(
				'id'         => 'csl_award_csv',
				'name'       => __( 'Award Data:', 'ca-grants-plugin' ),
				'type'       => 'label',
				'value_type' => 'attachment-url',
			),
			array(
				'id'         => 'csl_fiscal_year',
				'name'       => __( 'Fiscal Year:', 'ca-grants-plugin' ),
				'type'       => 'label',
				'value_type' => 'api',
			),
			array(
				'id'   => 'csl_award_count',
				'name' => __( 'Award Count:', 'ca-grants-plugin' ),
				'type' => 'label',
			),
			array(
				'id'   => 'csl_imported_awards',
				'name' => __( 'Imported Awards', 'ca-grants-plugin' ),
				'type' => 'label',
			),
		);
	}
}
