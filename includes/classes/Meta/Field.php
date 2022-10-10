<?php
/**
 * Meta fields.
 *
 * @package CaGov\Grants
 */

namespace CaGov\Grants\Meta;

use CaGov\Grants\Helpers\Validators;
use DateTime;
use WP_Error;

use function CaGov\Grants\Core\is_portal;
use function CaGov\Grants\Helpers\FiscalYear\get_fiscal_years_query_string;

/**
 * Meta Field Class.
 */
class Field {
	const API_URL = 'https://www.grants.ca.gov';

	/**
	 * Init
	 *
	 * @var boolean
	 */
	public static $init = false;

	/**
	 * Setup class.
	 *
	 * @return void
	 */
	public function setup() {
		if ( self::$init ) {
			return;
		}

		self::$init = true;
	}

	/**
	 * Factory.
	 *
	 * @static
	 * @param  array $meta_field Meta field arguments.
	 * @return void
	 */
	public static function factory( $meta_field ) {
		if ( ! isset( $meta_field['type'] ) ) {
			return;
		}

		switch ( $meta_field['type'] ) {
			case 'radio':
				self::render_radio_field( $meta_field );
				break;
			case 'checkbox':
				self::render_checkbox_field( $meta_field );
				break;
			case 'textarea':
				self::render_textarea( $meta_field );
				break;
			case 'select':
				self::render_select_field( $meta_field );
				break;
			case 'point_of_contact':
				self::render_point_of_contact_input( $meta_field );
				break;
			case 'datetime-local':
				self::render_datepicker( $meta_field );
				break;
			case 'eligibility-matching-funds':
				self::render_matching_funds( $meta_field );
				break;
			case 'estimated-number-awards':
				self::render_number_awards( $meta_field );
				break;
			case 'estimated-award-amounts':
				self::render_award_amounts( $meta_field );
				break;
			case 'period-performance':
				self::render_period_performance( $meta_field );
				break;
			case 'electronic-submission-method':
				self::render_submission_method( $meta_field );
				break;
			case 'application-deadline':
				self::render_application_deadline( $meta_field );
				break;
			case 'post-finder':
				self::render_post_finder_field( $meta_field );
				break;
			case 'label':
				self::render_label_field( $meta_field );
				break;
			case 'group':
			case 'save_to_field_group':
				self::render_field_group( $meta_field );
				break;
			default:
				self::render_input_field( $meta_field );
				break;
		}
	}

	/**
	 * A function to get the API URL.
	 *
	 * @return string
	 */
	public static function get_api_url() {
		if ( defined( 'CA_GRANTS_PORTAL_JSON_URL' ) ) {
			return CA_GRANTS_PORTAL_JSON_URL;
		}

		return self::API_URL;
	}

	/**
	 * Get the current site's api url.
	 *
	 * @return string Current site api url.
	 */
	public static function get_current_site_api_url() {
		return rest_url();
	}

	/**
	 * Tooltip
	 *
	 * @param  string $content Content to display within the tooltip.
	 * @return void
	 */
	public static function tooltip( $content ) {
		if ( ! $content ) {
			return;
		}
		?>
		<span class="a11y-tip a11y-tip--no-delay">
			<a href="#!" class="a11y-tip__trigger">
				<span class="dashicons dashicons-editor-help"></span>
			</a>
			<span class="a11y-tip__help">
				<?php echo wp_kses_post( $content ); ?>
			</span>
		</span>
		<?php
	}

	/**
	 * Outputs an attribute for conditionally required inputs.
	 *
	 * @param array   $meta_field The meta field settings.
	 * @param boolean $input      Whether the element is an <input>, ([required] attribute is valid).
	 * @return void
	 */
	public static function conditional_required( $meta_field, $input = true ) {
		if ( ! isset( $meta_field['required'] ) ) {
			return;
		}

		if ( is_array( $meta_field['required'] ) ) {
			if ( array( 'active', 'forecasted' ) === $meta_field['required'] && $input ) {
				echo ' required="true" ';
			} else {
				printf( 'data-required-if="%s"', esc_attr( implode( ',', $meta_field['required'] ) ) );
			}
		} elseif ( true === $meta_field['required'] ) {
			echo ' required="true" ';
		}
	}

	/**
	 * Outputs an attribute for conditionally visible inputs.
	 *
	 * @param array $meta_field The meta field settings.
	 *
	 * @return void
	 */
	public static function conditional_visible( $meta_field ) {
		if ( ! isset( $meta_field['visible'] ) ) {
			return;
		}

		if ( is_array( $meta_field['visible'] ) ) {
			printf( 'data-visible-if="%s"', esc_attr( wp_json_encode( $meta_field['visible'] ) ) );
		}
	}

	/**
	 * Render an post finder field
	 *
	 * @param array $meta_field The meta field to render
	 */
	public static function render_post_finder_field( $meta_field = array() ) {
		if ( empty( $meta_field ) || ! is_array( $meta_field ) ) {
			return;
		}

		$default_options = array(
			// Whether to show a positional number next to each item. Makes it easy to see which position each item has. Default true.
			'show_numbers'   => true,
			// Whether to show the Recent Post select input. Default true.
			'show_recent'    => true,
			// Limit how many items can be selected. Default 10.
			'limit'          => 10,
			// Whether to include the init script for the input. Default true. If false, add custom script for select and search.
			'include_script' => true,
			// Array of arguments passed to our WP_Query instances.
			'args'           => array(),
		);

		$name = $meta_field['name'] ?? '';
		$id   = $meta_field['id'] ?? '';

		if ( isset( $meta_field['value'] ) && $meta_field['value'] > 0 ) {
			$value = $meta_field['value'];
		} else {
			$value = get_post_meta( get_the_ID(), $id, true );
		}

		$options     = $meta_field['options'] ?? array();
		$options     = wp_parse_args( $options, $default_options );
		$class       = $meta_field['class'] ?? '';
		$description = $meta_field['description'] ?? '';
		$required    = empty( $meta_field['required'] ) ? '' : 'data-post-finder=required';

		?>
		<tr class="post_finder_field <?php echo esc_attr( $class ); ?>" <?php self::conditional_visible( $meta_field ); ?>>
			<th class="<?php echo ( true === $meta_field['required'] ) ? 'required' : ''; ?>">
				<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $name ); ?></label>
				<?php self::tooltip( $description ); ?>
			</th>
			<td>
				<div class="pf_render"
					<?php echo esc_attr( $required ); ?>
				>
					<?php
					if ( function_exists( 'pf_render' ) ) {
						pf_render( $id, $value, $options );
					}
					?>
				</div>
			</td>
		</tr>
		<?php
	}

	/**
	 * Render an input field
	 *
	 * @param array $meta_field The meta field to render
	 */
	public static function render_input_field( $meta_field = array() ) {
		if ( empty( $meta_field ) || ! is_array( $meta_field ) ) {
			return;
		}

		$post_id = get_the_ID();

		if ( 'save_to_field' === $meta_field['type'] && ! empty( $meta_field['field_id'] ) ) {
			$field_id           = get_post_meta( $post_id, $meta_field['field_id'], true );
			$post_id            = $field_id ? $field_id : $post_id;
			$meta_field['type'] = 'number';
		}

		$type          = $meta_field['type'] ?? '';
		$is_number     = ( 'number' === $type );
		$name          = $meta_field['name'] ?? '';
		$description   = $meta_field['description'] ?? '';
		$id            = $meta_field['id'] ?? '';
		$field_name    = $meta_field['field_name'] ?? '';
		$field_name    = $field_name ? $field_name : $id;
		$class         = $meta_field['class'] ?? '';
		$maxlength     = $meta_field['maxlength'] ?? '';
		$default_value = $meta_field['default_value'] ?? '';
		$value         = $meta_field['meta_value'] ?? '';
		$minnumber     = isset( $meta_field['min'] ) ? sprintf( 'min=%d', absint( $meta_field['min'] ) ) : 'min=0';
		$maxnumber     = isset( $meta_field['max'] ) ? sprintf( 'max=%d', absint( $meta_field['max'] ) ) : '';
		$disabled      = empty( $meta_field['disabled'] ) || ( true !== $meta_field['disabled'] ) ? '' : 'disabled="disabled"';
		$readonly      = empty( $meta_field['readonly'] ) || ( true !== $meta_field['readonly'] ) ? '' : 'readonly="true"';
		$accept_ext    = '';

		if ( $is_number ) {
			// Keep 0 as valid value.
			$value = ( is_numeric( $value ) && 0 <= $value ) ? (int) $value : get_post_meta( $post_id, $id, true );
			$value = ( is_numeric( $value ) && 0 <= $value ) ? (int) $value : $default_value;
		} else {
			$value = $value ? $value : get_post_meta( $post_id, $id, true );
			$value = empty( $value ) ? $default_value : $value;
		}

		if ( 'file' === $meta_field['type'] && ! empty( $meta_field['accepted-ext'] ) && is_array( $meta_field['accepted-ext'] ) ) {
			$accept_ext = sprintf( 'accept=%s', implode( ',', $meta_field['accepted-ext'] ) );
		}

		// Used for telephone fields
		$pattern = 'placeholder=1-555-555-5555 pattern=[0-9]{1}-[0-9]{3}-[0-9]{3}-[0-9]{4}';
		?>
		<tr class="<?php echo esc_attr( $class ); ?>" <?php self::conditional_visible( $meta_field ); ?>>
			<th>
				<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $name ); ?></label>
				<?php self::tooltip( $description ); ?>
			</th>
			<td>
				<input
					type="<?php echo esc_attr( $type ); ?>"
					name="<?php echo esc_attr( $field_name ); ?>"
					value="<?php echo esc_attr( $value ); ?>"
					id="<?php echo esc_attr( $id ); ?>"
					<?php if ( ! empty( $maxlength ) ) : ?>
						data-maxlength="<?php echo esc_attr( $maxlength ); ?>"
					<?php endif; ?>
					<?php echo ( 'tel' === $type ) ? esc_attr( $pattern ) : ''; ?>
					<?php self::conditional_required( $meta_field ); ?>
					<?php echo esc_html( $disabled ); ?>
					<?php echo esc_html( $readonly ); ?>
					<?php echo esc_html( $accept_ext ); ?>
					<?php
					if ( 'number' === $type ) {
						echo esc_html( $minnumber );
						echo esc_html( $maxnumber );
					}
					?>
				/>
			</td>
		</tr>
		<?php
	}

	/**
	 * Render an group field set.
	 *
	 * @param array $meta_field The meta field group to render.
	 */
	public static function render_field_group( $meta_field = array() ) {

		if (
			empty( $meta_field )
			|| ! is_array( $meta_field )
			|| empty( $meta_field['fields'] )
		) {
			return;
		}

		$post_id = get_the_ID();

		if ( 'save_to_field_group' === $meta_field['type'] && ! empty( $meta_field['field_id'] ) ) {
			$field_id = get_post_meta( $post_id, $meta_field['field_id'], true );
			$post_id  = $field_id ? $field_id : $post_id;
		}

		$class         = $meta_field['class'] ?? '';
		$id            = $meta_field['id'] ?? '';
		$name          = $meta_field['name'] ?? '';
		$description   = $meta_field['description'] ?? '';
		$default_value = $meta_field['default_value'] ?? '';
		$value         = get_post_meta( $post_id, $id, true );
		$value         = empty( $value ) ? $default_value : $value;
		$add_new_label = $meta_field['add_new_label'] ?? __( 'Add new', 'ca-grants-plugin' );
		$is_multiple   = isset( $meta_field['is_multiple'] ) ? false !== $meta_field['is_multiple'] : true;

		if ( ! empty( $name ) ) :
			?>
		<tr class="form-field-group-header <?php echo esc_attr( $class ); ?>">
			<th class="<?php echo ( true === $meta_field['required'] ) ? 'required' : ''; ?>">
				<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $name ); ?></label>
				<?php
				if ( ! empty( $description ) ) {
					self::tooltip( $description );
				}
				?>
			</th>
		</tr>
			<?php
		endif;

		$index = 0;
		if ( empty( $value ) ) {
			self::render_repeater_group_fields( $index, $meta_field['fields'], $id, $is_multiple );
			$index++;
		} else {
			foreach ( $value as $field_values ) {
				self::render_repeater_group_fields( $index, $meta_field['fields'], $id, $is_multiple, $field_values );
				$index++;
			}
		}

		if ( $is_multiple ) :
			self::render_repeater_group_fields( $index, $meta_field['fields'], $id, $is_multiple, [], true );
			?>
			<tr>
				<td>
					<button class="form-field-add-new-group-button button-secondary button-large">
						<?php echo esc_html( $add_new_label ); ?>
					</button>
				</td>
			</tr>
			<?php
		endif;
	}

	/**
	 * Render repeater group fields.
	 *
	 * @param int     $index Index for using unique id attributes for field.
	 * @param array   $fields Group fields for repeater group to render.
	 * @param int     $group_id Repater field group id to store data in.
	 * @param boolean $is_multiple Flag to check if this renderer is part of repetable group field or not.
	 * @param array   $field_values Field value stored in meta.
	 * @param boolean $is_copy_field Flag to get markup for copy field or regular field.
	 *
	 * @return void
	 */
	public static function render_repeater_group_fields( $index, $fields, $group_id, $is_multiple = false, $field_values = [], $is_copy_field = false ) {

		if ( $is_copy_field ) {
			$class = 'form-field-group-wrapper-copy hidden';
		} else {
			$class = 'form-field-group-wrapper';
		}

		?>
		<tr class="<?php echo esc_attr( $class ); ?>" data-index="<?php echo esc_attr( $index ); ?>">
			<td>
				<table class="form-table field-group" role="presentation">
					<tbody>
						<?php
						foreach ( $fields as $field ) {
							if ( ! empty( $field_values ) && empty( $field['meta_value'] ) ) {
								$field['meta_value'] = $field_values[ $field['id'] ] ?? '';
							}
							if ( $is_copy_field ) {
								$field['disabled'] = true;
							}

							$field['field_name'] = $group_id . '[' . $index . '][' . $field['id'] . ']';
							$field['id']         = $field['id'] . '-' . $index;
							self::factory( $field );
						}
						?>
					</tbody>
				</table>
				<?php if ( $is_multiple ) : ?>
				<button class="form-field-remove-group-button button-secondary button-large">
					<?php echo esc_html__( 'Remove', 'ca-grants-plugin' ); ?>
				</button>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Show data for saved meta data.
	 *
	 * @param array $meta_field The meta field to render
	 */
	public static function render_label_field( $meta_field = array() ) {
		if ( empty( $meta_field ) || ! is_array( $meta_field ) ) {
			return;
		}

		$post_id = get_the_ID();

		$value_type   = $meta_field['value_type'] ?? '';
		$name         = $meta_field['name'] ?? '';
		$id           = $meta_field['id'] ?? '';
		$class        = $meta_field['class'] ?? '';
		$hidden_field = $meta_field['hidden_field'] ?? false;
		$value        = $meta_field['meta_value'] ?? '';
		$value        = $value ? $value : get_post_meta( $post_id, $id, true );
		$link         = ( 'post-link' === $meta_field['link'] ) ? get_edit_post_link( $value ) : false;
		$label_value  = $value;

		if ( ! empty( $label_value ) ) {
			switch ( $value_type ) {
				case 'post-title':
					if ( is_numeric( $label_value ) ) {
						$label_value = get_the_title( $value );
					}
					break;
				case 'attachment-url':
					if ( is_numeric( $label_value ) ) {
						$label_value = wp_get_attachment_url( $label_value );
					}
					break;
				case 'api':
					$fields      = self::get_api_fields_by_id( $id );
					$field       = wp_filter_object_list( $fields, [ 'id' => $label_value ] );
					$field       = empty( $field ) || ! is_array( $field ) ? [] : array_pop( $field );
					$label_value = empty( $field ) || empty( $field['name'] ) ? $label_value : $field['name'];
					break;
			}
		}

		?>
		<tr class="<?php echo esc_attr( $class ); ?>" <?php self::conditional_visible( $meta_field ); ?>>
			<th>
				<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $name ); ?></label>
			</th>
			<td>
				<?php if ( $hidden_field ) : ?>
					<input
						type="hidden"
						name="<?php echo esc_attr( $id ); ?>"
						value="<?php echo esc_attr( $value ); ?>"
					/>
				<?php endif; ?>
				<span
					id="<?php echo esc_attr( $id ); ?>"
				>
					<?php
					if ( ! empty( $link ) ) {
						printf( '<a href="%s" target="_blank">', esc_url( $link ) );
					}
					?>
					<?php echo esc_html( $label_value ); ?>
					<?php
					if ( ! empty( $link ) ) {
						echo '</a>';
					}
					?>
				</span>
			</td>
		</tr>
		<?php
	}

	/**
	 * Render a checkbox field
	 *
	 * @param array $meta_field The meta field to render
	 */
	public static function render_checkbox_field( $meta_field = array() ) {
		if ( empty( $meta_field ) || ! is_array( $meta_field ) ) {
			return;
		}

		$name        = $meta_field['name'] ?? '';
		$description = $meta_field['description'] ?? '';
		$id          = $meta_field['id'] ?? '';
		$field_name  = $meta_field['field_name'] ?? '';
		$field_name  = $field_name ? $field_name : $id;
		$value       = $meta_field['meta_value'] ?? '';
		$disabled    = empty( $meta_field['disabled'] ) || ( true !== $meta_field['disabled'] ) ? '' : 'disabled';

		if ( isset( $meta_field['source'] ) && 'api' === $meta_field['source'] ) {
			$fields = self::get_api_fields_by_id( $id );
		} elseif ( isset( $meta_field['source'] ) && 'portal-api' === $meta_field['source'] ) {
			$fields = self::get_api_fields_by_id( $id, true );
		} elseif ( isset( $meta_field['fields'] ) ) {
			$fields = $meta_field['fields'];
		} else {
			$fields = '';
		}

		if ( empty( $fields ) ) {
			$fields = array(
				array(
					'id'   => 'none',
					'name' => esc_html__( 'None', 'ca-grants-plugin' ),
				),
			);
		}

		$fields = self::maybe_sort_fields( $fields, $meta_field );

		// Get the saved data
		if ( empty( $value ) && isset( $meta_field['source'] ) && 'portal-api' === $meta_field['source'] ) {
			$value = self::get_value_from_taxonomy( $id, get_the_ID() );
		} elseif ( empty( $value ) ) {
			$value = get_post_meta( get_the_ID(), $id, true );
		}
		?>
		<tr class="<?php echo esc_attr( $meta_field['class'] ?? '' ); ?>" <?php self::conditional_visible( $meta_field ); ?>>
			<th class="<?php echo ( true === $meta_field['required'] ) ? 'required' : ''; ?>">
				<label><?php echo esc_html( $name ); ?></label>
				<?php self::tooltip( $description ); ?>
			</th>
			<td <?php self::conditional_required( $meta_field, false ); ?>>
			<?php foreach ( $fields as $field ) : ?>
				<?php $checked = ( in_array( $field['id'], (array) $value, true ) ) ? 'checked' : ''; ?>
				<input <?php echo esc_attr( $checked ); ?> type="checkbox" id="<?php echo esc_attr( $field['id'] ); ?>" name="<?php echo esc_attr( $field_name ); ?>[]" value="<?php echo esc_attr( $field['id'] ); ?>" <?php echo esc_html( $disabled ); ?> />
				<label for="<?php echo esc_attr( $field['id'] ); ?>"><?php echo esc_html( $field['name'] ); ?></label>
				<br>
			<?php endforeach; ?>
			<?php if ( count( $fields ) > 1 ) : ?>
				<p>
					<a href="javascript:void(0)" class="checkbox--select-all">Select All</a>
				</p>
			<?php endif; ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Render a radio field
	 *
	 * @param array $meta_field The meta field to render
	 */
	public static function render_radio_field( $meta_field = array() ) {
		if ( empty( $meta_field ) || ! is_array( $meta_field ) ) {
			return;
		}

		$name        = $meta_field['name'] ?? '';
		$description = $meta_field['description'] ?? '';
		$id          = $meta_field['id'] ?? '';
		$field_name  = $meta_field['field_name'] ?? '';
		$field_name  = $field_name ? $field_name : $id;
		$value       = $meta_field['meta_value'] ?? '';
		$disabled    = empty( $meta_field['disabled'] ) || ( true !== $meta_field['disabled'] ) ? '' : 'disabled';

		if ( empty( $name ) || empty( $id ) ) {
			return;
		}

		if ( isset( $meta_field['source'] ) && 'api' === $meta_field['source'] ) {
			$fields = self::get_api_fields_by_id( $id );
		} elseif ( isset( $meta_field['source'] ) && 'portal-api' === $meta_field['source'] ) {
			$fields = self::get_api_fields_by_id( $id, true );
		} elseif ( isset( $meta_field['fields'] ) ) {
			$fields = $meta_field['fields'];
		} else {
			$fields = '';
		}

		if ( empty( $fields ) ) {
			return;
		}

		$fields = self::maybe_sort_fields( $fields, $meta_field );

		// Get the saved data
		if ( empty( $value ) && isset( $meta_field['source'] ) && 'portal-api' === $meta_field['source'] ) {
			$value = self::get_value_from_taxonomy( $id, get_the_ID(), false );
		} elseif ( empty( $value ) ) {
			$value = get_post_meta( get_the_ID(), $id, true );
		}

		if ( empty( $value ) ) {
			$value = $meta_field['value'] ?? '';
		}
		?>
		<tr <?php self::conditional_visible( $meta_field ); ?>>
			<th class="<?php echo ( true === $meta_field['required'] ) ? 'required' : ''; ?>">
				<label for="<?php echo esc_attr( $meta_field['id'] ); ?>">
					<?php echo esc_html( $name ); ?>
				</label>

				<?php self::tooltip( $description ); ?>
			</th>
			<td>
				<fieldset>
					<legend class="screen-reader-text"><?php echo esc_html( $name ); ?></legend>
					<?php foreach ( $fields as $field ) : ?>
						<label>
							<input
								type="radio"
								id="<?php echo esc_attr( $field['id'] ); ?>"
								name="<?php echo esc_attr( $field_name ); ?>"
								value="<?php echo esc_attr( $field['id'] ); ?>"
								<?php checked( $field['id'], $value ); ?>
								<?php self::conditional_required( $meta_field ); ?>
								<?php echo esc_html( $disabled ); ?>
							/>
							<span><?php echo esc_html( $field['name'] ); ?></span>
						</label><br>
					<?php endforeach; ?>
				</fieldset>
			</td>
		</tr>
		<?php
	}

	/**
	 * Render a select field
	 *
	 * @param array $meta_field The meta field to render
	 */
	public static function render_select_field( $meta_field = array() ) {
		if ( empty( $meta_field ) || ! is_array( $meta_field ) ) {
			return;
		}

		$name          = $meta_field['name'] ?? '';
		$description   = $meta_field['description'] ?? '';
		$id            = $meta_field['id'] ?? '';
		$field_name    = $meta_field['field_name'] ?? '';
		$field_name    = $field_name ? $field_name : $id;
		$value         = $meta_field['meta_value'] ?? '';
		$default_value = $meta_field['default_value'] ?? '';
		$value         = empty( $value ) ? $default_value : $value;
		$disabled      = empty( $meta_field['disabled'] ) || ( true !== $meta_field['disabled'] ) ? '' : 'disabled';
		$options       = null;


		if ( empty( $name ) || empty( $id ) ) {
			return;
		}

		if ( 'fiscalYear' === $id ) {
			$options = get_fiscal_years_query_string();
		}

		if ( isset( $meta_field['source'] ) && 'api' === $meta_field['source'] ) {
			$fields = self::get_api_fields_by_id( $id, false, $options );
		} elseif ( isset( $meta_field['source'] ) && 'portal-api' === $meta_field['source'] ) {
			$fields = self::get_api_fields_by_id( $id, true, $options );
		} elseif ( isset( $meta_field['fields'] ) ) {
			$fields = $meta_field['fields'];
		} else {
			$fields = '';
		}

		if ( empty( $fields ) ) {
			return;
		}

		// Get the saved data
		if ( empty( $value ) && isset( $meta_field['source'] ) && 'portal-api' === $meta_field['source'] ) {
			$value = self::get_value_from_taxonomy( $id, get_the_ID(), false );
		} elseif ( empty( $value ) ) {
			$value = get_post_meta( get_the_ID(), $id, true );
		}
		?>
		<tr <?php self::conditional_visible( $meta_field ); ?>>
			<th class="<?php echo ( isset( $meta_field['required'] ) && true === $meta_field['required'] ) ? 'required' : ''; ?>">
				<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $name ); ?></label>
				<?php self::tooltip( $description ); ?>
			</th>
			<td>
				<select name="<?php echo esc_attr( $field_name ); ?>" id="<?php echo esc_attr( $id ); ?>" <?php self::conditional_required( $meta_field ); ?> <?php echo esc_html( $disabled ); ?>>
					<option <?php selected( '', $value ); ?> value=""><?php esc_html_e( 'Select One', 'ca-grants-plugin' ); ?></option>
					<?php foreach ( $fields as $field ) : ?>

					<option <?php selected( $field['id'], $value ); ?> value="<?php echo esc_attr( $field['id'] ); ?>"><?php echo esc_html( $field['name'] ); ?></option>

					<?php endforeach; ?>
				</select>
				<br/>
				<?php if ( empty( $meta_field['hide_description'] ) ) : ?>
					<span><?php echo wp_kses_post( $description ); ?></span>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Render a textarea field
	 *
	 * @param array $meta_field The meta field being rendered.
	 */
	public static function render_textarea( $meta_field = array() ) {
		if ( empty( $meta_field ) || ! is_array( $meta_field ) ) {
			return;
		}

		$name        = $meta_field['name'] ?? '';
		$id          = $meta_field['id'] ?? '';
		$field_name  = $meta_field['field_name'] ?? '';
		$field_name  = $field_name ? $field_name : $id;
		$limit       = $meta_field['text_limit'] ?? '';
		$description = $meta_field['description'] ?? '';

		if ( empty( $id ) || empty( $name ) || empty( $limit ) ) {
			return;
		}

		$limit = absint( $limit );

		// Get the saved data
		$value = $meta_field['meta_value'] ?? '';
		$value = $value ? $value : get_post_meta( get_the_ID(), $id, true );
		?>
		<tr <?php self::conditional_visible( $meta_field ); ?>>
			<th class="<?php echo ( true === $meta_field['required'] ) ? 'required' : ''; ?>">
				<label for="<?php esc_attr( $id ); ?>"><?php echo esc_html( $name ); ?></label>
				<?php self::tooltip( $description ); ?>
			</th>
			<td>
				<?php self::do_editor( $value, $field_name, $meta_field ); ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Render the custom Period of Performance field
	 *
	 * @param array $meta_field The meta field data
	 */
	public static function render_period_performance( $meta_field = array() ) {
		if ( empty( $meta_field ) || ! is_array( $meta_field ) ) {
			return;
		}

		$name        = $meta_field['name'] ?? '';
		$id          = $meta_field['id'] ?? '';
		$description = $meta_field['description'] ?? '';

		if ( empty( $name ) || empty( $id ) ) {
			return;
		}

		// default values
		$defaults = array(
			'units' => '',
			'num'   => '',
		);

		// Get the saved data
		$value = wp_parse_args( get_post_meta( get_the_ID(), $id, true ), $defaults );
		?>

		<tr>
			<th>
				<label for="<?php echo esc_attr( $id ); ?>-num-units">
					<?php echo esc_html( $name ); ?>
				</label>
				<?php self::tooltip( $description ); ?>
			</th>
			<td>
				<input type="text" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $id ); ?>[num]" value="<?php echo esc_attr( $value['num'] ); ?>" <?php self::conditional_required( $meta_field ); ?>/>
				<select name="<?php echo esc_attr( $id ); ?>[units]">
					<option <?php selected( $value['units'], 'days' ); ?> value="days"><?php esc_html_e( 'Days', 'ca-grants-plugin' ); ?></option>
					<option <?php selected( $value['units'], 'weeks' ); ?>value="weeks"><?php esc_html_e( 'Weeks', 'ca-grants-plugin' ); ?></option>
					<option <?php selected( $value['units'], 'months' ); ?>value="months"><?php esc_html_e( 'Months', 'ca-grants-plugin' ); ?></option>
					<option <?php selected( $value['units'], 'years' ); ?>value="years"><?php esc_html_e( 'Years', 'ca-grants-plugin' ); ?></option>
				</select>
			</td>
		</tr>
		<?php
	}

	/**
	 * Render the custom Award Amounts field
	 *
	 * @param array $meta_field The meta field data
	 */
	public static function render_award_amounts( $meta_field = array() ) {
		if ( empty( $meta_field ) || ! is_array( $meta_field ) ) {
			return;
		}

		$name        = $meta_field['name'] ?? '';
		$id          = $meta_field['id'] ?? '';
		$description = $meta_field['description'] ?? '';

		if ( empty( $name ) || empty( $id ) ) {
			return;
		}

		// default values
		$defaults = array(
			'checkbox' => '',
			'same'     => array(
				'amount' => '',
			),
			'unknown'  => array(
				'first'  => '',
				'second' => '',
			),
		);

		// Get the saved data
		$value = wp_parse_args( get_post_meta( get_the_ID(), $id, true ), $defaults );
		?>

		<tr>
			<th>
				<label for="<?php echo esc_attr( $id ); ?>">
					<?php echo esc_html( $name ); ?>
				</label>
				<?php self::tooltip( $description ); ?>
			</th>
			<td>

				<input <?php checked( $value['checkbox'], 'same' ); ?> type="radio" id="<?php echo esc_attr( $id . '-same' ); ?>" name="<?php echo esc_attr( $id ); ?>[checkbox]" value="same" <?php self::conditional_required( $meta_field ); ?>>
				<label for="<?php echo esc_attr( $id . '-same' ); ?>"><?php esc_html_e( 'Same amount each award: ', 'ca-grants-plugin' ); ?></label>
				<input type="number" min="0" id="<?php echo esc_attr( $id ); ?>-same-amount" name="<?php echo esc_attr( $id ); ?>[same][amount]" value="<?php echo esc_attr( $value['same']['amount'] ); ?>"/>
				<br><br>

				<input <?php checked( $value['checkbox'], 'unknown' ); ?> type="radio" id="<?php echo esc_attr( $id . '-unknown' ); ?>" name="<?php echo esc_attr( $id ); ?>[checkbox]" value="unknown" <?php self::conditional_required( $meta_field ); ?>>
				<label for="<?php echo esc_attr( $id . '-unknown' ); ?>"><?php esc_html_e( 'Amount per award may range  between:', 'ca-grants-plugin' ); ?></label>
				<input type="number" min="0" id="<?php echo esc_attr( $id ); ?>-unknown-first" name="<?php echo esc_attr( $id ); ?>[unknown][first]" value="<?php echo esc_attr( $value['unknown']['first'] ); ?>"/>
				<?php esc_html_e( ' to ', 'ca-grants-plugin' ); ?>
				<input type="number" min="0" id="<?php echo esc_attr( $id ); ?>-unknown-second" name="<?php echo esc_attr( $id ); ?>[unknown][second]" value="<?php echo esc_attr( $value['unknown']['second'] ); ?>"/>
				<br><br>

				<input <?php checked( $value['checkbox'], 'dependant' ); ?> type="radio" id="<?php echo esc_attr( $id . '-dependant' ); ?>" name="<?php echo esc_attr( $id ); ?>[checkbox]" value="dependant" <?php self::conditional_required( $meta_field ); ?>>
				<label for="<?php echo esc_attr( $id . '-dependant' ); ?>"><?php esc_html_e( 'Dependent on number of submissions received, application process, etc.', 'ca-grants-plugin' ); ?></label>

			</td>
		</tr>

		<?php
	}

	/**
	 * Render the custom Number of Awards field
	 *
	 * @param array $meta_field The meta field data
	 */
	public static function render_number_awards( $meta_field = array() ) {
		if ( empty( $meta_field ) || ! is_array( $meta_field ) ) {
			return;
		}

		$name        = $meta_field['name'] ?? '';
		$id          = $meta_field['id'] ?? '';
		$description = $meta_field['description'] ?? '';

		if ( empty( $name ) || empty( $id ) ) {
			return;
		}

		// default values
		$defaults = array(
			'checkbox' => '',
			'exact'    => '',
			'between'  => array(
				'low'  => '',
				'high' => '',
			),
		);

		// Get the saved data
		$value = wp_parse_args( get_post_meta( get_the_ID(), $id, true ), $defaults );
		?>
		<tr>
			<th>
				<label for="<?php echo esc_attr( $id ); ?>">
					<?php echo esc_html( $name ); ?>
				</label>
				<?php self::tooltip( $description ); ?>
			</th>

			<td>
				<input <?php checked( $value['checkbox'], 'exact' ); ?> type="radio" id="<?php echo esc_attr( $id . '-exactly' ); ?>" name="<?php echo esc_attr( $id ); ?>[checkbox]" value="exact" <?php self::conditional_required( $meta_field ); ?>>
				<label for="<?php echo esc_attr( $id . '-exactly' ); ?>"><?php esc_html_e( 'Exactly: ', 'ca-grants-plugin' ); ?></label>
				<input class="small-text" type="number" min="0" id="<?php echo esc_attr( $id ); ?>-exactly" name="<?php echo esc_attr( $id ); ?>[exact]" value="<?php echo esc_attr( $value['exact'] ); ?>"/>
				<br><br>

				<input <?php checked( $value['checkbox'], 'between' ); ?> type="radio" id="<?php echo esc_attr( $id . '-between' ); ?>" name="<?php echo esc_attr( $id ); ?>[checkbox]" value="between" <?php self::conditional_required( $meta_field ); ?>>
				<label for="<?php echo esc_attr( $id . '-between' ); ?>"><?php esc_html_e( 'Between', 'ca-grants-plugin' ); ?></label>
				<input type="number" min="0" id="<?php echo esc_attr( $id ); ?>-between-first" name="<?php echo esc_attr( $id ); ?>[between][low]" value="<?php echo esc_attr( $value['between']['low'] ); ?>"/>
				<?php esc_html_e( ' and ', 'ca-grants-plugin' ); ?>
				<input type="number" min="0" id="<?php echo esc_attr( $id ); ?>-between-second" name="<?php echo esc_attr( $id ); ?>[between][high]" value="<?php echo esc_attr( $value['between']['high'] ); ?>"/>
				<br><br>

				<input <?php checked( $value['checkbox'], 'dependant' ); ?> type="radio" id="<?php echo esc_attr( $id . '-dependant' ); ?>" name="<?php echo esc_attr( $id ); ?>[checkbox]" value="dependant" <?php self::conditional_required( $meta_field ); ?>>
				<label for="<?php echo esc_attr( $id . '-dependant' ); ?>"><?php esc_html_e( 'Dependent on number of submissions received, application process, etc.', 'ca-grants-plugin' ); ?></label>
			</td>
		</tr>
		<?php
	}

	/**
	 * Render the custom Matching Funds field
	 *
	 * @param array $meta_field The meta field data
	 */
	public static function render_matching_funds( $meta_field = array() ) {
		if ( empty( $meta_field ) || ! is_array( $meta_field ) ) {
			return;
		}

		$name        = $meta_field['name'] ?? '';
		$id          = $meta_field['id'] ?? '';
		$description = $meta_field['description'] ?? '';

		if ( empty( $name ) || empty( $id ) ) {
			return;
		}

		// default values
		$defaults = array(
			'checkbox'   => '',
			'percentage' => '',
		);

		// Get the saved data
		$value = wp_parse_args( get_post_meta( get_the_ID(), $id, true ), $defaults );
		?>

		<tr>
			<th>
				<label for="<?php echo esc_attr( $id ); ?>">
					<?php echo esc_html( $name ); ?>
				</label>
				<?php self::tooltip( $description ); ?>
			</th>
			<td>
				<input <?php checked( $value['checkbox'], 'no' ); ?> type="radio" id="<?php echo esc_attr( $id . '-no' ); ?>" name="<?php echo esc_attr( $id ); ?>" value="no" <?php self::conditional_required( $meta_field ); ?>>
				<label for="<?php echo esc_attr( $id . '-no' ); ?>"><?php esc_html_e( 'No', 'ca-grants-plugin' ); ?></label>
				<br>

				<input <?php checked( $value['checkbox'], 'yes' ); ?> type="radio" id="<?php echo esc_attr( $id . '-yes' ); ?>" name="<?php echo esc_attr( $id ); ?>" value="yes" <?php self::conditional_required( $meta_field ); ?>>
				<label for="<?php echo esc_attr( $id . '-yes' ); ?>"><?php esc_html_e( 'Yes, with matching percentage: ', 'ca-grants-plugin' ); ?></label>
				<input class="small-text" type="number" min="0" max="100" name="<?php echo esc_attr( $id ); ?>-percentage" value="<?php echo esc_attr( $value['percentage'] ); ?>"/>
			</td>
		</tr>

		<?php
	}

	/**
	 * Renders the custom Grant Open/Close field
	 *
	 * @param array $meta_field The meta field to render
	 */
	public static function render_datepicker( $meta_field = array() ) {
		if ( empty( $meta_field ) || ! is_array( $meta_field ) ) {
			return;
		}

		$name        = $meta_field['name'] ?? '';
		$id          = $meta_field['id'] ?? '';
		$field_name  = $meta_field['field_name'] ?? '';
		$field_name  = $field_name ? $field_name : $id;
		$class       = $meta_field['class'] ?? '';
		$description = $meta_field['description'] ?? '';
		$max_date    = empty( $meta_field['max_date'] ) ? '' : 'data-max-date-id=' . $meta_field['max_date'];
		$min_date    = empty( $meta_field['min_date'] ) ? '' : 'data-min-date-id=' . $meta_field['min_date'];
		$disabled    = empty( $meta_field['disabled'] ) || ( true !== $meta_field['disabled'] ) ? '' : 'disabled="true"';

		if ( empty( $name ) || empty( $id ) ) {
			return;
		}

		// Get the saved data
		$value = $meta_field['meta_value'] ?? '';
		$value = $value ? $value : get_post_meta( get_the_ID(), $id, true );
		$value = $value ? gmdate( 'Y-m-d\TH:i', $value ) : $value;
		?>
		<tr class="<?php echo esc_attr( $class ); ?>" <?php self::conditional_visible( $meta_field ); ?>>
			<th class="<?php echo ( true === $meta_field['required'] ) ? 'required' : ''; ?>">
				<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $name ); ?></label>
				<?php self::tooltip( $description ); ?>
			</th>
			<td>
				<input
					type="datetime-local"
					id="<?php echo esc_attr( $id ); ?>"
					name="<?php echo esc_attr( $field_name ); ?>"
					value="<?php echo esc_attr( $value ); ?>"
					<?php self::conditional_required( $meta_field ); ?>
					<?php echo esc_html( $max_date ); ?>
					<?php echo esc_html( $min_date ); ?>
					<?php echo esc_html( $disabled ); ?>
				>
			</td>
		</tr>
		<tr>
		<?php
	}

	/**
	 * Render the custom Point of Contact field
	 *
	 * @param array $meta_field The data with which to render the HTML field.
	 */
	public static function render_point_of_contact_input( $meta_field ) {
		if ( empty( $meta_field ) || ! is_array( $meta_field ) ) {
			return;
		}

		$type         = $meta_field['type'] ?? '';
		$name         = $meta_field['name'] ?? '';
		$id           = $meta_field['id'] ?? '';
		$description  = $meta_field['description'] ?? '';
		$section_note = $meta_field['section_note'] ?? '';

		// default values
		$defaults = array(
			'name'  => '',
			'email' => '',
			'tel'   => '',
		);

		// Get the saved data
		$value = wp_parse_args( get_post_meta( get_the_ID(), $id, true ), $defaults );
		?>

		<h4><?php echo esc_html( $name ); ?></h4>
		<?php if ( $section_note ) : ?>
		<p class="section--note"><?php echo wp_kses_post( $section_note ); ?></p>
		<?php endif; ?>
		<table class="form-table">
			<tbody>
				<tr>
					<th>
						<label for="<?php echo esc_attr( $id ); ?>-name"><?php esc_html_e( 'Name', 'ca-grants-plugin' ); ?></label>
					</th>
					<td>
						<?php if ( 'contactInfo' === $id ) : ?>
							<input type="text" id="<?php echo esc_attr( $id ); ?>-name" name="<?php echo esc_attr( $id ); ?>[name]" value="<?php echo esc_attr( $value['name'] ); ?>"/>
						<?php else : ?>
							<input type="text" id="<?php echo esc_attr( $id ); ?>-name" name="<?php echo esc_attr( $id ); ?>[name]" value="<?php echo esc_attr( $value['name'] ); ?>" <?php self::conditional_required( $meta_field ); ?>/>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<th>
						<label for="<?php echo esc_attr( $id ); ?>-email"><?php esc_html_e( 'Email', 'ca-grants-plugin' ); ?></label>
					</th>
					<td>
						<input type="email" id="<?php echo esc_attr( $id ); ?>-name" name="<?php echo esc_attr( $id ); ?>[email]" value="<?php echo esc_attr( $value['email'] ); ?>" <?php self::conditional_required( $meta_field ); ?>/>
					</td>
				</tr>
				<tr>
					<th>
						<label for="<?php echo esc_attr( $id ); ?>-tel"><?php esc_html_e( 'Phone', 'ca-grants-plugin' ); ?></label>
					</th>
					<td>
						<input type="tel" id="<?php echo esc_attr( $id ); ?>-name" name="<?php echo esc_attr( $id ); ?>[tel]" value="<?php echo esc_attr( $value['tel'] ); ?>" placeholder="1-555-555-5555" pattern="[0-9]{1}-[0-9]{3}-[0-9]{3}-[0-9]{4}" <?php self::conditional_required( $meta_field ); ?>/>
					</td>
				</tr>
			</tbody>
		</table>

		<?php
	}

	/**
	 * Render the custom Submission Methods field
	 *
	 * @param array $meta_field The meta field data
	 */
	public static function render_submission_method( $meta_field = array() ) {
		if ( empty( $meta_field ) || ! is_array( $meta_field ) ) {
			return;
		}

		$name        = $meta_field['name'] ?? '';
		$id          = $meta_field['id'] ?? '';
		$description = $meta_field['description'] ?? '';

		if ( empty( $name ) || empty( $id ) ) {
			return;
		}

		// default values
		$defaults = array(
			'email' => '',
			'url'   => '',
			'type'  => 'none',
		);

		// Get the saved data
		$value = wp_parse_args( get_post_meta( get_the_ID(), $id, true ), $defaults );

		// Back compat for type.
		if ( 'none' === $value['type'] && ( ! empty( $value['email'] ) || ! empty( $value['url'] ) ) ) {
			$value['type'] = ( ! empty( $value['url'] ) ) ? 'url' : 'email';
		}
		?>
		<tr>
			<th>
				<label for="electronic_submission"><?php echo esc_html( $name ); ?></label>
				<?php self::tooltip( $description ); ?>
			</th>
			<td>
				<input <?php checked( $value['type'], 'email' ); ?> type="radio" id="<?php echo esc_attr( $id . '-email' ); ?>" name="<?php echo esc_attr( $id ); ?>[type]" value="email" <?php self::conditional_required( $meta_field ); ?>>
				<label for="<?php echo esc_attr( $id . '-email' ); ?>"><?php esc_html_e( 'Email: ', 'ca-grants-plugin' ); ?></label>
				<input type="email" name="<?php echo esc_attr( $id ); ?>[email]" value="<?php echo esc_attr( $value['email'] ); ?>" id="email_submission">
				<br><br>

				<input <?php checked( $value['type'], 'url' ); ?> type="radio" id="<?php echo esc_attr( $id . '-url' ); ?>" name="<?php echo esc_attr( $id ); ?>[type]" value="url" <?php self::conditional_required( $meta_field ); ?>>
				<label for="<?php echo esc_attr( $id . '-url' ); ?>"><?php esc_html_e( 'URL: ', 'ca-grants-plugin' ); ?></label>
				<input type="url" name="<?php echo esc_attr( $id ); ?>[url]" value="<?php echo esc_attr( $value['url'] ); ?>" id="online_submission">
				<br><br>

				<input <?php checked( $value['type'], 'none' ); ?> type="radio" id="<?php echo esc_attr( $id . '-none' ); ?>" name="<?php echo esc_attr( $id ); ?>[type]" value="none" <?php self::conditional_required( $meta_field ); ?>>
				<label for="<?php echo esc_attr( $id . '-url' ); ?>"><?php esc_html_e( 'None ', 'ca-grants-plugin' ); ?></label>
			</td>
		</tr
		<?php
	}

	/**
	 * Render the custom Application Deadline field
	 *
	 * @param array $meta_field The data with which to render the HTML field.
	 */
	public static function render_application_deadline( $meta_field ) {
		if ( empty( $meta_field ) || ! is_array( $meta_field ) ) {
			return;
		}

		$type        = $meta_field['type'] ?? '';
		$name        = $meta_field['name'] ?? '';
		$id          = $meta_field['id'] ?? '';
		$description = $meta_field['description'] ?? '';

		// default values
		$defaults = array(
			'none' => '',
			'date' => '',
			'time' => '',
		);

		$meta = get_post_meta( get_the_ID(), $id, true );

		// Get the saved data
		$value = wp_parse_args( $meta['deadline'], $defaults );
		?>
		<tr>
			<th>
				<label for="<?php echo esc_attr( $id ); ?>">
					<?php echo esc_html( $name ); ?>
				</label>
				<?php self::tooltip( $description ); ?>
			</th>

			<td>
				<input <?php checked( $value['none'], 'nodeadline' ); ?> type="checkbox" id="<?php echo esc_attr( $id ); ?>-nodeadline" name="<?php echo esc_attr( $id ); ?>[deadline][none]" value="nodeadline" />
				<label for="<?php echo esc_attr( $id ); ?>-nodeadline"><?php esc_html_e( 'No Deadline', 'ca-grants-plugin' ); ?></label>
				<br><br>

				<label for="<?php echo esc_attr( $id ); ?>-date"><?php esc_html_e( 'Deadline Date', 'ca-grants-plugin' ); ?></label>
				<input class="csl-datepicker-plugin" type="text" id="<?php echo esc_attr( $id ); ?>-date" name="<?php echo esc_attr( $id ); ?>[deadline][date]" value="<?php echo esc_attr( $value['date'] ); ?>" />
				<br><br>

				<label for="<?php echo esc_attr( $id ); ?>-time"><?php esc_html_e( 'Deadline Time (optional)', 'ca-grants-plugin' ); ?></label>
				<select id="<?php echo esc_attr( $id ); ?>-time" name="<?php echo esc_attr( $id ); ?>[deadline][time]">
					<option value="none"><?php esc_html_e( 'No Deadline Time', 'ca-grants-plugin' ); ?></option>
						<?php
						for ( $time = 0; $time < 24; $time++ ) {
							if ( 0 === $time ) {
								$hour_top  = '12:00 am';
								$hour_half = '12:30 am';
							} elseif ( 12 < $time ) {
								$hour_top  = ( $time - 12 ) . ':00 pm';
								$hour_half = ( $time - 12 ) . ':30 pm';
							} else {
								$hour_top  = $time . ':00 am';
								$hour_half = $time . ':30 am';
							}
							?>

							<option <?php selected( $value['time'], $hour_top ); ?> value="<?php echo esc_attr( $hour_top ); ?>"><?php echo esc_attr( $hour_top ); ?></option>
							<option <?php selected( $value['time'], $hour_half ); ?>value="<?php echo esc_attr( $hour_half ); ?>"><?php echo esc_attr( $hour_half ); ?></option>

							<?php
						}
						?>
				</select>
			</td>
		</tr>
		<?php
	}

	/**
	 * Get fields for an HTML element from the WP API
	 *
	 * @param string $id         The identifier for the type of field data needed.
	 * @param bool   $portal_api Whether to call the API from the portal server.
	 * @param string $options    Options to append to the API url to modify the request.
	 *
	 * @return array $fields The data from the WP API
	 */
	public static function get_api_fields_by_id( $id = '', $portal_api = false, $options = '' ) {
		if ( empty( $id ) ) {
			return array();
		}

		$fields_to_display = false;
		$cache_key         = sprintf( '%s-%s-api-field-values', $id, is_portal() ? 'portal' : 'external' );
		$fields_to_display = wp_cache_get( $cache_key, 'ca-grants-plugin-api-field-values' );

		if ( false === $fields_to_display ) {
			if ( $portal_api ) {
				$api_url = trailingslashit( self::get_current_site_api_url() ) . 'wp/v2/';
			} else {
				$api_url = trailingslashit( self::get_api_url() ) . 'wp/v2/';
			}

			// Check if it's a serialised data field, with incremental suffix ids.
			preg_match( '/[-]\d+$/', $id, $matches );
			if ( ! empty( $matches ) ) {
				$id = preg_replace( '/[-]\d+$/', '', $id, 1 );
			}

			switch ( $id ) {
				case 'grantCategories':
					$api_url .= 'grant_categories?per_page=100';
					break;
				case 'applicantType':
					$api_url .= 'applicant_type';
					break;
				case 'disbursementMethod':
					$api_url .= 'disbursement_method';
					break;
				case 'opportunityType':
					$api_url .= 'opportunity_types';
					break;
				case 'fundingSource':
					$api_url .= 'revenue_sources';
					break;
				case 'fiscalYear':
				case 'csl_fiscal_year':
					$api_url .= 'fiscal-year?orderby=name&order=desc&per_page=100&' . $options;
					break;
				case 'recipientType':
					$api_url .= 'recipient-types';
					break;
				case 'countiesServed':
					$api_url .= 'counties?per_page=100';
					break;
				default:
					$api_url = null;
					break;
			}

			if ( is_null( $api_url ) ) {
				return array();
			}

			$request = wp_remote_get( $api_url );

			if ( is_wp_error( $request ) ) {
				return array();
			}

			$response_code = wp_remote_retrieve_response_code( $request );
			if ( 200 !== $response_code ) {
				return array();
			}

			$response = json_decode( wp_remote_retrieve_body( $request ) );
			if ( empty( $response ) ) {
				return array();
			}

			$fields_to_display = array();

			foreach ( $response as $field ) {
				if ( ! isset( $field->name ) || ! isset( $field->slug ) ) {
					continue;
				}

				$fields_to_display[] = array(
					'name' => sanitize_text_field( $field->name ),
					'id'   => sanitize_text_field( $field->slug ),
				);
			}

			wp_cache_set( $cache_key, $fields_to_display, 'ca-grants-plugin-api-field-values', 60 );
		}

		return $fields_to_display;
	}

	/**
	 * Setup TinyMCE editor
	 *
	 * @param string $content The content for the editor
	 * @param string $id      HTML ID attribute for the TinyMCE textarea
	 * @param string $args    WP Editor arguments.
	 */
	public static function do_editor( $content, $id, $args = array() ) {
		$settings = wp_parse_args(
			$args,
			self::get_wysiwyg_settings()
		);
		$class    = 'wysiwyg';
		$required = ( isset( $settings['required'] ) && $settings['required'] );

		if ( $required ) {
			$class .= ' wysiwyg--required';
		}

		?>
		<div
			class="<?php echo esc_attr( $class ); ?>"
			data-characters-limit="<?php echo esc_attr( $settings['text_limit'] ); ?>"
			<?php self::conditional_required( $args, false ); ?>
		>
			<?php wp_editor( $content, $id, $settings ); ?>
		</div>
		<?php
	}

	/** Get WYSIWYG settings for grant form.
	 *
	 * @return array
	 */
	public static function get_wysiwyg_settings() {

		$toolbar = array(
			'bold',
			'italic',
			'underline',
			'separator',
			'alignleft',
			'aligncenter',
			'alignright',
			'separator',
			'link',
			'unlink',
			'undo',
			'redo',
		);

		$settings = array(
			'media_buttons' => false,
			'quicktags'     => false,
			'tinymce'       => array(
				'toolbar1' => join( ',', $toolbar ),
				'toolbar2' => '',
				'toolbar3' => '',
			),
			'text_limit'    => -1,
			'textarea_rows' => 5,
		);

		return $settings;
	}

	/**
	 * Applies sorting to the radio options of some meta fields.
	 *
	 * @param array $fields     The fields to sort.
	 * @param array $meta_field Meta field data.
	 * @return array
	 */
	public static function maybe_sort_fields( $fields, $meta_field ) {
		switch ( $meta_field['id'] ) {
			case 'fundingSource':
				$order    = array( 'State', 'Federal', 'Both', 'Other' );
				$index_of = function( $name ) use ( $order ) {
					return array_search( $name, $order, true );
				};
				usort(
					$fields,
					function( $a, $b ) use ( $index_of ) {
						return ( $index_of( $a['name'] ) < $index_of( $b['name'] ) ) ? -1 : 1;
					}
				);
				return $fields;
			case 'applicantType':
				// Move 'other' to the bottom of the list.
				foreach ( $fields as $index => $field ) {
					if ( isset( $field['id'] ) && 'other' === $field['id'] ) {
						unset( $fields[ $index ] );
						array_push( $fields, $field );
					}
				}
				return $fields;
			default:
				return $fields;
		}
	}

	/**
	 * Sanitize meta data based on field attributes and save it to respective post.
	 *
	 * @param array $meta_fields Meta fields to save in post.
	 * @param int   $post_id Post id to store meta data.
	 * @param array $data Meta values for fields.
	 *
	 * @return void
	 */
	public static function sanitize_and_save_fields( $meta_fields, $post_id, $data ) {
		foreach ( $meta_fields as $meta_field ) {
			$value = array();

			if ( 'label' === $meta_field['type'] ) {
				continue;
			}

			// If a text or textarea field is an empty string, delete the post meta entirely.
			$is_empty_text = ( 'text' === $meta_field['type'] || 'textarea' === $meta_field['type'] ) && isset( $data[ $meta_field['id'] ] ) && empty( trim( $data[ $meta_field['id'] ] ) );

			if ( ! isset( $data[ $meta_field['id'] ] ) || $is_empty_text ) {
				delete_post_meta( $post_id, $meta_field['id'] );
				continue;
			}

			switch ( $meta_field['type'] ) {
				case 'checkbox':
					if ( isset( $meta_field['source'] ) && 'portal-api' === $meta_field['source'] ) {
						self::set_taxonomy_terms( $data[ $meta_field['id'] ], $meta_field['id'], $post_id );
					} elseif ( ! empty( $data[ $meta_field['id'] ] ) && is_array( $data[ $meta_field['id'] ] ) ) {
						$value = $data[ $meta_field['id'] ];
						array_walk( $value, 'sanitize_text_field' );
					} else {
						$value = sanitize_text_field( $data[ $meta_field['id'] ] );
					}
					break;
				case 'radio':
				case 'select':
					if ( isset( $meta_field['source'] ) && 'portal-api' === $meta_field['source'] ) {
						self::set_taxonomy_terms( $data[ $meta_field['id'] ], $meta_field['id'], $post_id );
					} else {
						$value = sanitize_text_field( $data[ $meta_field['id'] ] );
					}
					break;
				case 'email':
					$value = sanitize_email( $data[ $meta_field['id'] ] );
					break;
				case 'url':
					$value = esc_url_raw( $data[ $meta_field['id'] ] );
					break;
				case 'number':
					$value = absint( $data[ $meta_field['id'] ] );
					break;
				case 'datetime-local':
					$date          = new DateTime( $data[ $meta_field['id'] ] );
					$is_valid_date = ( $date && $date->format( 'c' ) );
					$max_date      = ! empty( $meta_field['max_date'] ) ? new DateTime( $data[ $meta_field['max_date'] ] ) : false;
					$min_date      = ! empty( $meta_field['min_date'] ) ? new DateTime( $data[ $meta_field['min_date'] ] ) : false;

					if ( $is_valid_date && $max_date instanceof DateTime ) {
						$is_valid_date = $date <= $max_date;
					}

					if ( $is_valid_date && $min_date instanceof DateTime ) {
						$is_valid_date = $date >= $min_date;
					}

					if ( $is_valid_date ) {
						$value = strtotime( $data[ $meta_field['id'] ] );
					}
					break;
				case 'textarea':
					$value = wp_kses_post( $data[ $meta_field['id'] ] );
					break;
				case 'group':
					if ( ! empty( $meta_field['sanitize_callback'] ) ) {
						$value = call_user_func( $meta_field['sanitize_callback'], $data[ $meta_field['id'] ] );
					} else {
						$value = array_filter( $data[ $meta_field['id'] ], 'array_filter' );
					}
					break;
				case 'save_to_field_group':
					$field_post_id = absint( $data[ $meta_field['field_id'] ] );
					if ( ! empty( $meta_field['sanitize_callback'] ) ) {
						$group_data = call_user_func( $meta_field['sanitize_callback'], $data[ $meta_field['id'] ] );
					} else {
						$group_data = array_filter( $data[ $meta_field['id'] ], 'array_filter' );
					}
					update_post_meta( $field_post_id, $meta_field['id'], $group_data );
					break;
				case 'save_to_field':
					$field_post_id = absint( $data[ $meta_field['field_id'] ] );
					update_post_meta( $field_post_id, $meta_field['id'], sanitize_text_field( $data[ $meta_field['id'] ] ) );
					break;
				case 'point_of_contact':
					$temp_value = $data[ $meta_field['id'] ];
					array_walk( $temp_value, 'sanitize_text_field' );
					$value = $temp_value;
					break;
				case 'eligibility-matching-funds':
					$value = array(
						'checkbox'   => sanitize_text_field( $data[ $meta_field['id'] ] ),
						'percentage' => absint( $data[ $meta_field['id'] . '-percentage' ] ),
					);
					break;
				case 'estimated-number-awards':
					$temp_value = $data[ $meta_field['id'] ];

					if ( 'exact' === $temp_value['checkbox'] ) {
						unset( $temp_value['between'] );
					} elseif ( 'between' === $temp_value['checkbox'] ) {
						unset( $temp_value['exact'] );
					} elseif ( 'dependant' === $temp_value['checkbox'] ) {
						unset( $temp_value['between'], $temp_value['exact'] );
					}

					array_walk( $temp_value, 'sanitize_text_field' );
					$value = $temp_value;
					break;
				case 'estimated-award-amounts':
					$temp_value = $data[ $meta_field['id'] ];

					// Make sure the text boxes for the options not selected are empty, to avoid confusion.
					if ( 'same' === $temp_value['checkbox'] ) {
						unset( $temp_value['unknown'] );
					} elseif ( 'unknown' === $temp_value['checkbox'] ) {
						unset( $temp_value['same'] );
					} elseif ( 'dependant' === $temp_value['checkbox'] ) {
						unset( $temp_value['same'], $temp_value['unknown'] );
					}

					array_walk( $temp_value, 'sanitize_text_field' );
					$value = $temp_value;
					break;
				case 'period-performance':
					$temp_value           = $data[ $meta_field['id'] ];
					$clean_value          = array();
					$clean_value['num']   = ( isset( $temp_value['num'] ) ) ? absint( $temp_value['num'] ) : '';
					$clean_value['units'] = ( isset( $temp_value['units'] ) ) ? sanitize_text_field( $temp_value['units'] ) : '';
					$value                = $clean_value;
					break;
				case 'electronic-submission-method':
					$temp_value  = $data[ $meta_field['id'] ];
					$clean_value = array();
					if ( 'email' === $temp_value['type'] ) {
						$clean_value['email'] = ( isset( $temp_value['email'] ) ) ? sanitize_email( $temp_value['email'] ) : '';
					} elseif ( 'url' === $temp_value['type'] ) {
						$clean_value['url'] = ( isset( $temp_value['url'] ) ) ? esc_url_raw( $temp_value['url'] ) : '';
					}
					$value = $clean_value;
					break;
				case 'application-deadline':
					$temp_value = $data[ $meta_field['id'] ];
					array_walk( $temp_value, 'sanitize_text_field' );
					$value = $temp_value;
					break;
				default:
					$value = sanitize_text_field( $data[ $meta_field['id'] ] );
					break;
			}

			/**
			 * Filters the post-meta value, targeted by meta-field type.
			 *
			 * The filter name is `ca_grants_post_meta_`,
			 * followed by the meta-field type.
			 *
			 * For example, using the `period-performance` meta-field:
			 * `ca_grants_post_meta_period-performance`
			 *
			 * @param mixed $value The value to filter.
			 */
			$value = apply_filters( 'ca_grants_post_meta_' . $meta_field['type'], $value );

			/**
			 * Filters the post-meta value, targeted by meta-field ID.
			 *
			 * The filter name is `ca_grants_post_meta_`,
			 * followed by the meta-field ID.
			 *
			 * For example, assuming a field ID of 1234:
			 * `ca_grants_post_meta_1234`
			 *
			 * @param mixed $value The value to filter.
			 */
			$value = apply_filters( 'ca_grants_post_meta_' . $meta_field['id'], $value );

			// Allow 0 to be saved if the field type is a number.
			$is_numeric_zero = 'number' === $meta_field['type'] && ( 0 === $value || '0' === $value );

			if ( ! empty( $post_id ) && ( ! empty( $value ) || $is_numeric_zero ) ) {
				update_post_meta( $post_id, $meta_field['id'], $value );
			} else if ( ! empty( $post_id ) && empty( $value ) && ! $is_numeric_zero ) {
				delete_post_meta( $post_id, $meta_field['id'] );
			}
		}
	}

	/**
	 * Validate fields and maybe get errors for validation.
	 *
	 * @param array $fields Meta fields.
	 * @param array $data Meta field values.
	 *
	 * @return WP_Error
	 */
	public static function maybe_get_field_errors( $fields, $data ) {
		$errors = new WP_Error();

		foreach ( $fields as $field ) {
			$id              = $field['id'];
			$is_numeric_zero = ( 'number' === $field['type'] && isset( $data[ $id ] ) && 0 === $data[ $id ] );

			// Check if data has value for required fields.
			if ( ! empty( $field['required'] ) && ( true === $field['required'] ) && empty( $data[ $id ] ) && ! $is_numeric_zero ) {
				$errors->add(
					'validation_error',
					esc_html__( 'Missing required value for field: ', 'ca-grants-plugin' ) . esc_html( $id )
				);
				continue;
			}

			// Check if conditional requierd field have value.
			if (
				! empty( $field['visible'] )
				&& ! empty( $field['visible']['required'] )
				&& empty( $data[ $id ] )
				&& ! $is_numeric_zero
				&& (
					( // Case: field is required only when dependent field is not equal to specific value.
						'not_equal' === $field['visible']['compare']
						&& (
							strtolower( $data[ $field['visible']['fieldId'] ] ) !== strtolower( $field['visible']['value'] )
							|| sanitize_title( $data[ $field['visible']['fieldId'] ] ) !== sanitize_title( $field['visible']['value'] )
						)
					)
					||
					( // Case: field is required only when dependent field is equal to specific value.
						'equal' === $field['visible']['compare']
						&& (
							strtolower( $data[ $field['visible']['fieldId'] ] ) === strtolower( $field['visible']['value'] )
							|| sanitize_title( $data[ $field['visible']['fieldId'] ] ) === sanitize_title( $field['visible']['value'] )
						)
					)
				)
			) {
				$errors->add(
					'validation_error',
					esc_html__( 'Missing required value for field: ', 'ca-grants-plugin' ) . esc_html( $id )
				);
				continue;
			}

			if ( empty( $is_invalid ) && empty( $data[ $id ] ) && 'fiscalYear' === $field['id'] && empty( $data['grantID'] ) ) {
				$errors->add(
					'validation_error',
					esc_html__( 'Dependent grantID value not found for field: ', 'ca-grants-plugin' ) . esc_html( $id )
				);
				continue;
			} elseif ( empty( $is_invalid ) && empty( $data[ $id ] ) && 'fiscalYear' === $field['id'] && ! empty( $data['grantID'] ) ) {
				$grant_id      = $data['grantID'];
				$is_forecasted = get_post_meta( $grant_id, 'isForecasted', true );
				$is_active     = 'active' === $is_forecasted;
				$deadline      = get_post_meta( $grant_id, 'deadline', true );
				$is_invalid    = ( $is_active && empty( $deadline ) );

				if ( $is_active && empty( $deadline ) ) {
					$errors->add(
						'validation_error',
						esc_html__( 'The associated grant is ongoing, Please add value for field: ', 'ca-grants-plugin' ) . esc_html( $id )
					);
					continue;
				}
			} elseif ( empty( $is_invalid ) && 'countiesServed' === $id ) {
				if ( is_array( $data[ $id ] ) ) {
					foreach ( $data[ $id ] as $term ) {
						if ( ! empty( $term ) && ! term_exists( $term, 'counties' ) ) {
							$is_invalid = true;
						}
					}
				} else {
					if ( ! empty( $data[ $id ] ) && ! term_exists( $data[ $id ], 'counties' ) ) {
						$is_invalid = true;
					}
				}
				if ( $is_invalid ) {
					$errors->add(
						'validation_error',
						esc_html__( 'Invalid value found for field: ', 'ca-grants-plugin' ) . esc_html( $id )
					);
				}
			}

			// If field is not required and have empty value it's valid data, skip other checks.
			if ( empty( $data[ $id ] ) ) {
				continue;
			}

			$is_invalid = false;

			switch ( $field['type'] ) {
				case 'post-finder':
					$is_invalid = self::validate_post_finder_field( $field, $data[ $id ] );
					break;
				case 'number':
				case 'save_to_field':
					$save_to_value = is_numeric( $data[ $id ] ) ? (int) $data[ $id ] : 0;
					$is_invalid    = Validators\validate_int( $save_to_value ) ? ( $save_to_value <= 0 ) : true;
					break;
				case 'text':
				case 'textarea':
					$max_chars = empty( $field['maxlength'] ) ? strlen( $data[ $id ] ) : $field['maxlength'];

					if ( isset( $field['text_limit'] ) && ! empty( $field['text_limit'] ) ) {
						$max_chars = $field['text_limit'];
					}

					$is_invalid = ! Validators\validate_string( $data[ $id ], $max_chars );
					break;
				case 'checkbox':
				case 'select':
					if ( isset( $field['source'] ) && in_array( $field['source'], [ 'api', 'portal-api' ], true ) ) {
						$api_values = self::get_api_fields_by_id( $id, 'portal-api' === $field['source'] );
						$field_ids  = empty( $api_values ) ? array() : wp_filter_object_list( $api_values, array(), 'and', 'id' );

						if ( empty( $data[ $id ] ) ) {
							$values = [];
						} else if ( is_string( $data[ $id ] ) ) {
							$values = explode( ',', $data[ $id ] );
						} else {
							$values = (array) $data[ $id ];
						}

						$values     = array_map( 'sanitize_title', $values );
						$is_invalid = ! empty( array_diff( $values, $field_ids ) );
					} elseif ( isset( $field['fields'] ) ) {
						$defined_values = wp_filter_object_list( $field['fields'], array(), 'and', 'id' );
						$is_invalid     = ! in_array( $data[ $id ], $defined_values, true ) && ! in_array( sanitize_title( $data[ $id ] ), $defined_values, true );
					}
					break;
				case 'datetime-local':
					$date          = new DateTime( $data[ $id ] );
					$is_valid_date = ( $date && $date->format( 'c' ) );

					if ( $is_valid_date ) {
						$max_date   = ! empty( $field['max_date'] ) ? new DateTime( $data[ $field['max_date'] ] ) : false;
						$min_date   = ! empty( $field['min_date'] ) ? new DateTime( $data[ $field['min_date'] ] ) : false;
						$is_invalid = $max_date ? ( $date > $max_date ) : false;
						$is_invalid = ( ! $is_invalid && $min_date ) ? ( $date < $min_date ) : false;
					} else {
						$is_invalid = true;
					}
					break;
			}

			if ( $is_invalid ) {
				$errors->add(
					'validation_error',
					esc_html__( 'Invalid value found for field: ', 'ca-grants-plugin' ) . esc_html( $id )
				);
				continue;
			}
		}

		return $errors;
	}

	/**
	 * Validate post finder field value agaist defined field params.
	 *
	 * @param array        $field Defined field args.
	 * @param string|array $value Post finder field value.
	 *
	 * @return boolean Return true if data is invalid else false.
	 */
	public static function validate_post_finder_field( $field, $value ) {
		$is_invalid = false;
		$post_type  = empty( $field['options']['args']['post_type'] ) ? 'post' : $field['options']['args']['post_type'];

		if ( is_array( $value ) ) {
			$limit      = empty( $field['options']['limit'] ) ? 10 : (int) $field['options']['limit'];
			$is_invalid = count( $value ) > $limit;

			if ( ! $is_invalid ) {
				$valid_posts = array_filter(
					$value,
					function( $id ) use ( $post_type ) {
						$post = is_int( $id ) ? get_post( $id ) : false;
						return empty( $post ) ? false : ( $post->post_type === $post_type );
					}
				);
				$is_invalid  = count( $valid_posts ) !== count( $value );
			}
		} else {
			$post       = is_int( $value ) ? get_post( $value ) : false;
			$is_invalid = empty( $post ) ? true : ( $post->post_type !== $post_type );
		}

		return $is_invalid;
	}

	/**
	 * Get value from taxonomy.
	 *
	 * @param string $id    Field id.
	 * @param int    $post_id   Post ID.
	 * @param bool   $multi Whether to return an array or string.
	 * @param string $return_value Field to get from taxonomy term.
	 *
	 * @return array|string Value. Will be array if multi is set to true.
	 */
	public static function get_value_from_taxonomy( $id, $post_id = 0, $multi = true, $return_value = 'slugs' ) {

		if ( empty( $post_id ) ) {
			$post_id = get_the_ID();
		}

		$value = wp_get_post_terms( $post_id, self::get_taxonmy_from_field_id( $id ), [ 'fields' => $return_value ] );

		if ( empty( $value ) || is_wp_error( $value ) ) {
			if ( $multi ) {
				return [];
			}

			return '';
		}

		if ( $multi ) {
			return $value;
		}

		return $value[0];
	}

	/**
	 * Set taxonomy terms to post.
	 *
	 * @param string|array $value Taxonomy term slug or list of slug.
	 * @param string       $id Field id to identify taxonomy.
	 * @param int          $post_id Post id to assign the taxonomy term.
	 *
	 * @return boolean Return true for sucess term assignd else fail.
	 */
	protected static function set_taxonomy_terms( $value, $id, $post_id ) {

		if ( empty( $value ) ) {
			return false;
		}

		$taxonomy = self::get_taxonmy_from_field_id( $id );

		if ( is_array( $value ) ) {
			array_walk( $value, 'sanitize_text_field' );
			foreach ( $value as $key => $term ) {
				if ( ! term_exists( $term, $taxonomy ) ) {
					unset( $value[ $key ] );
				}
			}
		} else {
			$value = sanitize_text_field( $value );
			if ( ! term_exists( $value, $taxonomy ) ) {
				$value = null;
			}
		}

		// recheck, array may be empty as well
		if ( empty( $value ) ) {
			return false;
		}

		$terms = wp_set_object_terms( $post_id, $value, $taxonomy );

		return is_wp_error( $terms ) ? false : true;
	}

	/**
	 * Get the taxonomy based on form id.
	 *
	 * @param string $id Field id.
	 *
	 * @return string Taxonomy name.
	 */
	protected static function get_taxonmy_from_field_id( $id ) {
		$field_id_to_taxonomy_map = [
			'grantCategories'    => 'grant_categories',
			'applicantType'      => 'applicant_type',
			'disbursementMethod' => 'disbursement_method', // Keep both disbursementMethod and fundingMethod for now due to differences between the portal and plugin.
			'fundingMethod'      => 'disbursement_method', // Keep both disbursementMethod and fundingMethod for now due to differences between the portal and plugin.
			'opportunityType'    => 'opportunity_types',
			'fundingSource'      => 'revenue_sources',
			'fiscalYear'         => 'fiscal-year',
			'recipientType'      => 'recipient-types',
			'countiesServed'     => 'counties',
		];

		return $field_id_to_taxonomy_map[ $id ] ?? '';
	}

}
