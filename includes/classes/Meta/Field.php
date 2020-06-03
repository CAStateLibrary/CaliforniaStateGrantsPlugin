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
	const API_URL = 'http://grantsportal.test';

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
		$value       = get_post_meta( get_the_ID(), $id, true );

		// Used for telephone fields
		$pattern = 'placeholder=1-555-555-5555 pattern=[0-9]{1}-[0-9]{3}-[0-9]{3}-[0-9]{4}';
		?>
		<tr>
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
					<?php echo ( 'tel' === $type ) ? esc_attr( $pattern ) : ''; ?>
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
		$value = get_post_meta( get_the_ID(), $id, true );
		?>
		<tr>
			<th>
				<label><?php echo esc_html( $name ); ?></label>
				<?php self::tooltip( $description ); ?>
			</th>
			<td>
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
		} elseif ( isset( $meta_field['fields'] ) ) {
			$fields = $meta_field['fields'];
		} else {
			$fields = '';
		}

		if ( empty( $fields ) ) {
			return;
		}

		// Get the saved data
		$value = get_post_meta( get_the_ID(), $id, true );
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
		} elseif ( isset( $meta_field['fields'] ) ) {
			$fields = $meta_field['fields'];
		} else {
			$fields = '';
		}

		if ( empty( $fields ) ) {
			return;
		}

		// Get the saved data
		$value = get_post_meta( get_the_ID(), $id, true );
		?>
		<tr>
			<th>
				<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $name ); ?></label>
				<?php self::tooltip( $description ); ?>
			</th>
			<td>
				<select name="<?php echo esc_attr( $id ); ?>" id="<?php echo esc_attr( $id ); ?>">
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
				<?php self::do_editor( $value, $id ); ?>
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
				<input type="text" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $id ); ?>[num]" value="<?php echo esc_attr( $value['num'] ); ?>"/>
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

				<input <?php checked( $value['checkbox'], 'same' ); ?> type="radio" id="<?php echo esc_attr( $id . '-same' ); ?>" name="<?php echo esc_attr( $id ); ?>[checkbox]" value="same">
				<label for="<?php echo esc_attr( $id . '-same' ); ?>"><?php esc_html_e( 'Same amount each award: ', 'ca-grants-plugin' ); ?></label>
				<input type="text" id="<?php echo esc_attr( $id ); ?>-same-amount" name="<?php echo esc_attr( $id ); ?>[same][amount]" value="<?php echo esc_attr( $value['same']['amount'] ); ?>"/>
				<br><br>

				<input <?php checked( $value['checkbox'], 'unknown' ); ?> type="radio" id="<?php echo esc_attr( $id . '-unknown' ); ?>" name="<?php echo esc_attr( $id ); ?>[checkbox]" value="unknown">
				<label for="<?php echo esc_attr( $id . '-unknown' ); ?>"><?php esc_html_e( 'Amount per award may range  between:', 'ca-grants-plugin' ); ?></label>
				<input type="text" id="<?php echo esc_attr( $id ); ?>-unknown-first" name="<?php echo esc_attr( $id ); ?>[unknown][first]" value="<?php echo esc_attr( $value['unknown']['first'] ); ?>"/>
				<?php esc_html_e( ' to ', 'ca-grants-plugin' ); ?>
				<input type="text" id="<?php echo esc_attr( $id ); ?>-unknown-second" name="<?php echo esc_attr( $id ); ?>[unknown][second]" value="<?php echo esc_attr( $value['unknown']['second'] ); ?>"/>
				<br><br>

				<input <?php checked( $value['checkbox'], 'dependant' ); ?> type="radio" id="<?php echo esc_attr( $id . '-dependant' ); ?>" name="<?php echo esc_attr( $id ); ?>[checkbox]" value="dependant">
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
				<input <?php checked( $value['checkbox'], 'exact' ); ?> type="radio" id="<?php echo esc_attr( $id . '-exactly' ); ?>" name="<?php echo esc_attr( $id ); ?>[checkbox]" value="exact">
				<label for="<?php echo esc_attr( $id . '-exactly' ); ?>"><?php esc_html_e( 'Exactly: ', 'ca-grants-plugin' ); ?></label>
				<input class="small-text" type="text" id="<?php echo esc_attr( $id ); ?>-exactly" name="<?php echo esc_attr( $id ); ?>[exact]" value="<?php echo esc_attr( $value['exact'] ); ?>"/>
				<br><br>

				<input <?php checked( $value['checkbox'], 'between' ); ?> type="radio" id="<?php echo esc_attr( $id . '-between' ); ?>" name="<?php echo esc_attr( $id ); ?>[checkbox]" value="between">
				<label for="<?php echo esc_attr( $id . '-between' ); ?>"><?php esc_html_e( 'Between', 'ca-grants-plugin' ); ?></label>
				<input type="text" id="<?php echo esc_attr( $id ); ?>-between-first" name="<?php echo esc_attr( $id ); ?>[between][low]" value="<?php echo esc_attr( $value['between']['low'] ); ?>"/>
				<?php esc_html_e( ' and ', 'ca-grants-plugin' ); ?>
				<input type="text" id="<?php echo esc_attr( $id ); ?>-between-second" name="<?php echo esc_attr( $id ); ?>[between][high]" value="<?php echo esc_attr( $value['between']['high'] ); ?>"/>
				<br><br>

				<input <?php checked( $value['checkbox'], 'dependant' ); ?> type="radio" id="<?php echo esc_attr( $id . '-dependant' ); ?>" name="<?php echo esc_attr( $id ); ?>[checkbox]" value="dependant">
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
				<input <?php checked( $value['checkbox'], 'no' ); ?> type="radio" id="<?php echo esc_attr( $id . '-no' ); ?>" name="<?php echo esc_attr( $id ); ?>" value="no">
				<label for="<?php echo esc_attr( $id . '-no' ); ?>"><?php esc_html_e( 'No', 'ca-grants-plugin' ); ?></label>
				<br>

				<input <?php checked( $value['checkbox'], 'yes' ); ?> type="radio" id="<?php echo esc_attr( $id . '-yes' ); ?>" name="<?php echo esc_attr( $id ); ?>" value="yes">
				<label for="<?php echo esc_attr( $id . '-yes' ); ?>"><?php esc_html_e( 'Yes, with matching percentage: ', 'ca-grants-plugin' ); ?></label>
				<input class="small-text" type="text" name="<?php echo esc_attr( $id ); ?>-percentage" value="<?php echo esc_attr( $value['percentage'] ); ?>"/>
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
		$description = $meta_field['description'] ?? '';

		if ( empty( $name ) || empty( $id ) ) {
			return;
		}

		// Get the saved data
		$value = get_post_meta( get_the_ID(), $id, true );
		?>
		<tr>
			<th>
				<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $name ); ?></label>
				<?php self::tooltip( $description ); ?>
			</th>
			<td>
				<input type="datetime-local" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $id ); ?>" value="<?php echo esc_attr( $value ); ?>">
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

		$type        = $meta_field['type'] ?? '';
		$name        = $meta_field['name'] ?? '';
		$id          = $meta_field['id'] ?? '';
		$description = $meta_field['description'] ?? '';

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
		<table class="form-table">
			<tbody>
				<tr>
					<th>
						<label for="<?php echo esc_attr( $id ); ?>-name"><?php esc_html_e( 'Name', 'ca-grants-plugin' ); ?></label>
					</th>
					<td>
						<input type="text" id="<?php echo esc_attr( $id ); ?>-name" name="<?php echo esc_attr( $id ); ?>[name]" value="<?php echo esc_attr( $value['name'] ); ?>" />
					</td>
				</tr>
				<tr>
					<th>
						<label for="<?php echo esc_attr( $id ); ?>-email"><?php esc_html_e( 'Email', 'ca-grants-plugin' ); ?></label>
					</th>
					<td>
						<input type="email" id="<?php echo esc_attr( $id ); ?>-name" name="<?php echo esc_attr( $id ); ?>[email]" value="<?php echo esc_attr( $value['email'] ); ?>" />
					</td>
				</tr>
				<tr>
					<th>
						<label for="<?php echo esc_attr( $id ); ?>-tel"><?php esc_html_e( 'Phone', 'ca-grants-plugin' ); ?></label>
					</th>
					<td>
						<input type="tel" id="<?php echo esc_attr( $id ); ?>-name" name="<?php echo esc_attr( $id ); ?>[tel]" value="<?php echo esc_attr( $value['tel'] ); ?>" placeholder="1-555-555-5555" pattern="[0-9]{1}-[0-9]{3}-[0-9]{3}-[0-9]{4}" />
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
		);

		// Get the saved data
		$value = wp_parse_args( get_post_meta( get_the_ID(), $id, true ), $defaults );
		?>
		<tr>
			<th>
				<label for="email_submission"><?php esc_html_e( 'Email Submission', 'csl-grants-portal' ); ?></label>
				<?php self::tooltip( $description ); ?>
			</th>
			<td>
				<input type="email" name="<?php echo esc_attr( $id ); ?>[email]" value="<?php echo esc_attr( $value['email'] ); ?>" id="email_submission">
			</td>
		</tr>

		<tr>
			<th>
				<label for="online_submission"><?php esc_html_e( 'Online submission form', 'csl-grants-portal' ); ?></label>
			</th>
			<td>
				<input type="url" name="<?php echo esc_attr( $id ); ?>[url]" value="<?php echo esc_attr( $value['url'] ); ?>" id="online_submission">
			</td>
		</tr>

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
	 * @param string $id The identifier for the type of field data needed.
	 *
	 * @return array $fields The data from the WP API
	 */
	public static function get_api_fields_by_id( $id = '' ) {
		if ( empty( $id ) ) {
			return array();
		}

		$fields_to_display = false; // wp_cache_get( $id, 'ca-grants-plugin' );

		if ( false === $fields_to_display ) {
			$api_url = trailingslashit( self::get_api_url() ) . 'wp/v2/';

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
				case 'revSources':
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

			wp_cache_set( $id, $fields_to_display, 'ca-grants-plugin', 'csl-terms', 5 * HOUR_IN_SECONDS );
		}

		return $fields_to_display;
	}

	/**
	 * Setup TinyMCE editor
	 *
	 * @param string $content The content for the editor
	 * @param string $id HTML ID attribute for the TinyMCE textarea
	 */
	public static function do_editor( $content, $id ) {
		wp_editor(
			$content,
			$id,
			[
				'media_buttons'    => false,
				'drag_drop_upload' => false,
				'teeny'            => true,
				'quicktags'        => false,
				'textarea_rows'    => 5,
			]
		);
	}
}
