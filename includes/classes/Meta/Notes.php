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
class Notes {
	/**
	 * Constructor.
	 */
	public function __construct() {}

	/**
	 * Render metabox.
	 *
	 * @return void
	 */
	public function render_metabox() {
		?>

		<table class="form-table --side" role="presentation">
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
		$fields = array();

		if ( is_portal() ) {
			$fields[] = [
				'id'         => 'applicationNotes',
				'name'       => __( 'Application Notes', 'ca-grants-plugin' ),
				'type'       => 'textarea',
				'text_limit' => '150',
			];
		}

		return $fields;
	}
}
