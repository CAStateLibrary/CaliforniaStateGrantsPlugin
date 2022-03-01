<?php
/**
 * Meta fields.
 *
 * @package CaGov\Grants
 */

namespace CaGov\Grants\Meta;

/**
 * Meta Field Class.
 */
class Field {
	const API_URL = 'https://www.grants.ca.gov';

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
		return get_site_url( null, '/wp-json/' );
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
		}
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

		$type        = $meta_field['type'] ?? '';
		$name        = $meta_field['name'] ?? '';
		$description = $meta_field['description'] ?? '';
		$id          = $meta_field['id'] ?? '';
		$class       = $meta_field['class'] ?? '';
		$maxlength   = $meta_field['maxlength'] ?? '';
		$value       = get_post_meta( get_the_ID(), $id, true );
		$minnumber   = isset( $meta_field['min'] ) ? sprintf( 'min=%d', absint( $meta_field['min'] ) ) : '';
		$maxnumber   = isset( $meta_field['max'] ) ? sprintf( 'max=%d', absint( $meta_field['max'] ) ) : '';

		// Used for telephone fields
		$pattern = 'placeholder=1-555-555-5555 pattern=[0-9]{1}-[0-9]{3}-[0-9]{3}-[0-9]{4}';
		?>
		<tr class="<?php echo esc_attr( $class ); ?>">
			<th>
				<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $name ); ?></label>
				<?php self::tooltip( $description ); ?>
			</th>
			<td>
				<input
					type="<?php echo esc_attr( $type ); ?>"
					name="<?php echo esc_attr( $id ); ?>"
					value="<?php echo esc_attr( $value ); ?>"
					id="<?php echo esc_attr( $id ); ?>"
					maxlength="<?php echo esc_attr( $maxlength ); ?>"
					<?php echo ( 'tel' === $type ) ? esc_attr( $pattern ) : ''; ?>
					<?php self::conditional_required( $meta_field ); ?>
					<?php echo esc_html( $minnumber ); ?>
					<?php echo esc_html( $maxnumber ); ?>
				/>
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

		// Get the saved data
		if ( isset( $meta_field['source'] ) && 'portal-api' === $meta_field['source'] ) {
			$value = self::get_value_from_taxonomy( $id );
		} else {
			$value = get_post_meta( get_the_ID(), $id, true );
		}
		?>
		<tr>
			<th>
				<label><?php echo esc_html( $name ); ?></label>
				<?php self::tooltip( $description ); ?>
			</th>
			<td <?php self::conditional_required( $meta_field, false ); ?>>
			<?php foreach ( $fields as $field ) : ?>
				<?php $checked = ( in_array( $field['id'], (array) $value, true ) ) ? 'checked' : ''; ?>
				<input <?php echo esc_attr( $checked ); ?> type="checkbox" id="<?php echo esc_attr( $field['id'] ); ?>" name="<?php echo esc_attr( $id ); ?>[]" value="<?php echo esc_attr( $field['id'] ); ?>"/>
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
		if ( isset( $meta_field['source'] ) && 'portal-api' === $meta_field['source'] ) {
			$value = self::get_value_from_taxonomy( $id, false );
		} else {
			$value = get_post_meta( get_the_ID(), $id, true );
		}
		?>
		<tr>
			<th>
				<?php echo esc_html( $name ); ?>
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
								name="<?php echo esc_attr( $id ); ?>"
								value="<?php echo esc_attr( $field['id'] ); ?>"
								<?php checked( $field['id'], $value ); ?>
								<?php self::conditional_required( $meta_field ); ?>
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
	 * Render a radio field
	 *
	 * @param array $meta_field The meta field to render
	 */
	public static function render_select_field( $meta_field = array() ) {
		if ( empty( $meta_field ) || ! is_array( $meta_field ) ) {
			return;
		}

		$name        = $meta_field['name'] ?? '';
		$description = $meta_field['description'] ?? '';
		$id          = $meta_field['id'] ?? '';

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

		// Get the saved data
		if ( isset( $meta_field['source'] ) && 'portal-api' === $meta_field['source'] ) {
			$value = self::get_value_from_taxonomy( $id, false );
		} else {
			$value = get_post_meta( get_the_ID(), $id, true );
		}
		?>
		<tr>
			<th>
				<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $name ); ?></label>
				<?php self::tooltip( $description ); ?>
			</th>
			<td>
				<select name="<?php echo esc_attr( $id ); ?>" id="<?php echo esc_attr( $id ); ?>" <?php self::conditional_required( $meta_field ); ?>>
					<option <?php selected( '', $value ); ?> value=""><?php esc_html_e( 'Select One', 'ca-grants-plugin' ); ?></option>
					<?php foreach ( $fields as $field ) : ?>

					<option <?php selected( $field['id'], $value ); ?> value="<?php echo esc_attr( $field['id'] ); ?>"><?php echo esc_html( $field['name'] ); ?></option>

					<?php endforeach; ?>
				</select>
				<span><?php echo esc_html( $description ); ?></span>
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
		$limit       = $meta_field['text_limit'] ?? '';
		$description = $meta_field['description'] ?? '';

		if ( empty( $id ) || empty( $name ) || empty( $limit ) ) {
			return;
		}

		$limit = absint( $limit );

		// Get the saved data
		$value = get_post_meta( get_the_ID(), $id, true );
		?>
		<tr>
			<th>
				<label for="<?php esc_attr( $id ); ?>"><?php echo esc_html( $name ); ?></label>
				<?php self::tooltip( $description ); ?>
			</th>
			<td>
				<?php self::do_editor( $value, $id, $meta_field ); ?>
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
			'checkbox'  => '',
			'same'      => array(
				'amount' => '',
			),
			'different' => array(
				'first'  => '',
				'second' => '',
				'third'  => '',
			),
			'unknown'   => array(
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
				<input type="number" id="<?php echo esc_attr( $id ); ?>-same-amount" name="<?php echo esc_attr( $id ); ?>[same][amount]" value="<?php echo esc_attr( $value['same']['amount'] ); ?>"/>
				<br><br>

				<input <?php checked( $value['checkbox'], 'different' ); ?> type="radio" id="<?php echo esc_attr( $id . '-different' ); ?>" name="<?php echo esc_attr( $id ); ?>[checkbox]" value="different" <?php self::conditional_required( $meta_field ); ?>>
				<label for="<?php echo esc_attr( $id . '-different' ); ?>"><?php esc_html_e( 'Different amount each award:', 'ca-grants-plugin' ); ?></label>
				<?php esc_html_e( ' First ', 'ca-grants-plugin' ); ?>
				<input type="number" id="<?php echo esc_attr( $id ); ?>-different-first" name="<?php echo esc_attr( $id ); ?>[different][first]" value="<?php echo esc_attr( $value['different']['first'] ); ?>"/>
				<?php esc_html_e( ' Second ', 'ca-grants-plugin' ); ?>
				<input type="number" id="<?php echo esc_attr( $id ); ?>-different-second" name="<?php echo esc_attr( $id ); ?>[different][second]" value="<?php echo esc_attr( $value['different']['second'] ); ?>"/>
				<?php esc_html_e( ' Third ', 'ca-grants-plugin' ); ?>
				<input type="number" id="<?php echo esc_attr( $id ); ?>-different-third" name="<?php echo esc_attr( $id ); ?>[different][third]" value="<?php echo esc_attr( $value['different']['third'] ); ?>"/>
				<br><br>

				<input <?php checked( $value['checkbox'], 'unknown' ); ?> type="radio" id="<?php echo esc_attr( $id . '-unknown' ); ?>" name="<?php echo esc_attr( $id ); ?>[checkbox]" value="unknown" <?php self::conditional_required( $meta_field ); ?>>
				<label for="<?php echo esc_attr( $id . '-unknown' ); ?>"><?php esc_html_e( 'Amount per award may range  between:', 'ca-grants-plugin' ); ?></label>
				<input type="number" id="<?php echo esc_attr( $id ); ?>-unknown-first" name="<?php echo esc_attr( $id ); ?>[unknown][first]" value="<?php echo esc_attr( $value['unknown']['first'] ); ?>"/>
				<?php esc_html_e( ' to ', 'ca-grants-plugin' ); ?>
				<input type="number" id="<?php echo esc_attr( $id ); ?>-unknown-second" name="<?php echo esc_attr( $id ); ?>[unknown][second]" value="<?php echo esc_attr( $value['unknown']['second'] ); ?>"/>
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
				<input class="small-text" type="number" id="<?php echo esc_attr( $id ); ?>-exactly" name="<?php echo esc_attr( $id ); ?>[exact]" value="<?php echo esc_attr( $value['exact'] ); ?>"/>
				<br><br>

				<input <?php checked( $value['checkbox'], 'between' ); ?> type="radio" id="<?php echo esc_attr( $id . '-between' ); ?>" name="<?php echo esc_attr( $id ); ?>[checkbox]" value="between" <?php self::conditional_required( $meta_field ); ?>>
				<label for="<?php echo esc_attr( $id . '-between' ); ?>"><?php esc_html_e( 'Between', 'ca-grants-plugin' ); ?></label>
				<input type="number" id="<?php echo esc_attr( $id ); ?>-between-first" name="<?php echo esc_attr( $id ); ?>[between][low]" value="<?php echo esc_attr( $value['between']['low'] ); ?>"/>
				<?php esc_html_e( ' and ', 'ca-grants-plugin' ); ?>
				<input type="number" id="<?php echo esc_attr( $id ); ?>-between-second" name="<?php echo esc_attr( $id ); ?>[between][high]" value="<?php echo esc_attr( $value['between']['high'] ); ?>"/>
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
				<input class="small-text" type="number" max="100" name="<?php echo esc_attr( $id ); ?>-percentage" value="<?php echo esc_attr( $value['percentage'] ); ?>"/>
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
		$class       = $meta_field['class'] ?? '';
		$description = $meta_field['description'] ?? '';

		if ( empty( $name ) || empty( $id ) ) {
			return;
		}

		// Get the saved data
		$value = get_post_meta( get_the_ID(), $id, true );
		?>
		<tr class="<?php echo esc_attr( $class ); ?>">
			<th>
				<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $name ); ?></label>
				<?php self::tooltip( $description ); ?>
			</th>
			<td>
				<input
					type="datetime-local"
					id="<?php echo esc_attr( $id ); ?>"
					name="<?php echo esc_attr( $id ); ?>"
					value="<?php echo esc_attr( $value ); ?>"
					<?php self::conditional_required( $meta_field ); ?>
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
						<?php if ( $id == 'contactInfo' ) : ?>
							<input type="text" id="<?php echo esc_attr( $id ); ?>-name" name="<?php echo esc_attr( $id ); ?>[name]" value="<?php echo esc_attr( $value['name'] ); ?>"/>
						<?php else: ?>
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
	 *
	 * @return array $fields The data from the WP API
	 */
	public static function get_api_fields_by_id( $id = '', $portal_api = false ) {
		if ( empty( $id ) ) {
			return array();
		}

		$fields_to_display = false;
		if ( ! $portal_api ) {
			// Retrieve from cache only if it is not portal api.
			$fields_to_display = wp_cache_get( $id, 'ca-grants-plugin' );
		}

		if ( false === $fields_to_display ) {
			if ( $portal_api ) {
				$api_url = trailingslashit( self::get_current_site_api_url() ) . 'wp/v2/';
			} else {
				$api_url = trailingslashit( self::get_api_url() ) . 'wp/v2/';
			}

			switch ( $id ) {
				case 'grantCategories':
					$api_url .= 'grant_categories?per_page=100';
					break;
				case 'applicantType':
					$api_url .= 'applicant_type';
					break;
				case 'fundingMethod':
					$api_url .= 'disbursement_method';
					break;
				case 'opportunityType':
					$api_url .= 'opportunity_types';
					break;
				case 'fundingSource':
					$api_url .= 'revenue_sources';
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

			if ( ! $portal_api ) {
				wp_cache_set( $id, $fields_to_display, 'ca-grants-plugin', 'csl-terms', 5 * HOUR_IN_SECONDS );
			}
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
			default:
				return $fields;
		}
	}

	/**
	 * Get value from taxonomy.
	 *
	 * @param string $id    Field id.
	 * @param bool   $multi Whether to return an array or string.
	 *
	 * @return array|string Value. Will be array if multi is set to true.
	 */
	protected static function get_value_from_taxonomy( $id, $multi = true ) {
		$value = wp_get_post_terms( get_the_ID(), self::get_taxonmy_from_field_id( $id ), [ 'fields' => 'slugs' ] );
		if ( empty( $value) || is_wp_error( $value ) ) {
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
	 * Get the taxonomy based on form id.
	 *
	 * @param string $id Field id.
	 *
	 * @return string Taxonomy name.
	 */
	protected static function get_taxonmy_from_field_id( $id ) {
		$field_id_to_taxonomy_map = [
				'grantCategories' => 'grant_categories',
				'applicantType'   => 'applicant_type',
				'fundingMethod'   => 'disbursement_method',
				'opportunityType' => 'opportunity_types',
				'fundingSource'   => 'revenue_sources',
		];

		return $field_id_to_taxonomy_map[ $id ] ?? '';
	}
}
