<?php
/**
 * Responsible for the entire meta box of the edit page.
 *
 * @package CslGrantsSubmissions
 */

namespace CslGrantsSubmissions\Metaboxes;

const NONCE_ACTION = 'grant-submissions-metabox';
const NONCE_FIELD  = '_grant_submission';
const API_URL      = 'https://cslportaldev.10uplabs.com';

/**
 * Run setup hooks/filters
 */
function setup() {
	$n = function( $ns ) {
		return __NAMESPACE__ . "\\$ns";
	};

	add_action( 'add_meta_boxes', $n( 'add_metaboxes' ) );
	add_action( 'save_post', $n( 'save_post' ) );
}

/**
 * Setup TinyMCE editor
 *
 * @param string $content The content for the editor
 * @param string $id HTML ID attribute for the TinyMCE textarea
 */
function do_editor( $content, $id ) {
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

/**
 * Adds the metaboxes.
 */
function add_metaboxes() {
	add_meta_box( 'grants-submission', __( 'Grant Information', 'csl-grants-submissions' ), __NAMESPACE__ . '\\render_metabox', \CslGrantsSubmissions\CPT\Grants\POST_TYPE );
}

/**
 * Handles the save post action.
 *
 * @param integer $post_id The ID of the currently displayed post.
 */
function save_post( $post_id ) {
	if ( ! isset( $_POST[ NONCE_FIELD ] ) || ! wp_verify_nonce( $_POST[ NONCE_FIELD ], NONCE_ACTION ) ) {
		return;
	}

	$meta_fields = get_meta_fields();

	if ( ! empty( $meta_fields ) ) {
		foreach ( $meta_fields as $meta_field ) {
			$value = array();

			switch ( $meta_field['type'] ) {
				case 'email':
					$value = sanitize_email( $_POST[ $meta_field['id'] ] );
					break;
				case 'url':
					$value = esc_url_raw( $_POST[ $meta_field['id'] ] );
					break;
				case 'number':
					$value = absint( $_POST[ $meta_field['id'] ] );
					break;
				case 'textarea':
					$value = wp_kses_post( $_POST[ $meta_field['id'] ] );
					break;
				case 'point_of_contact':
					$value = $_POST[ $meta_field['id'] ];
					array_walk( $value, 'sanitize_text_field' );
					break;
				case 'datetime-local':
					$value = $_POST[ $meta_field['id'] ];
					array_walk( $value, 'sanitize_text_field' );
					break;
				case 'eligibility-matching-funds':
					$value = array(
						'checkbox'   => sanitize_text_field( $_POST[ $meta_field['id'] ] ),
						'percentage' => absint( $_POST[ $meta_field['id'] . '-percentage' ] ),
					);
					break;
				case 'estimated-number-awards':
					$value = $_POST[ $meta_field['id'] ];

					if ( 'exact' === $value['checkbox'] ) {
						$value['between']['low']  = '';
						$value['between']['high'] = '';
					} elseif ( 'between' === $value['checkbox'] ) {
						$value['exact'] = '';
					} elseif ( 'dependant' === $value['checkbox'] ) {
						$value['between']['low']  = '';
						$value['between']['high'] = '';
						$value['exact']           = '';
					}

					array_walk( $value, 'sanitize_text_field' );
					break;
				case 'estimated-award-amounts':
					$value            = $_POST[ $meta_field['id'] ];
					$temp['checkbox'] = ( isset( $temp['checkbox'] ) ) ? sanitize_text_field( $temp['checkbox'] ) : '';

					// Make sure the text boxes for the options not selected are empty, to avoid confusion.
					if ( 'same' === $temp['checkbox'] ) {
						$value['unknown']['first']    = '';
						$value['unknown']['second']   = '';
						$value['different']['first']  = '';
						$value['different']['second'] = '';
						$value['different']['third']  = '';
					} elseif ( 'different' === $temp['checkbox'] ) {
						$value['unknown']['first']  = '';
						$value['unknown']['second'] = '';
						$value['same']['amount']    = '';
					} elseif ( 'unknown' === $temp['checkbox'] ) {
						$value['different']['first']  = '';
						$value['different']['second'] = '';
						$value['different']['third']  = '';
						$value['same']['amount']      = '';
					} elseif ( 'dependant' === $temp['checkbox'] ) {
						$value['unknown']['first']    = '';
						$value['unknown']['second']   = '';
						$value['different']['first']  = '';
						$value['different']['second'] = '';
						$value['different']['third']  = '';
						$value['same']['amount']      = '';
					}

					array_walk( $value, 'sanitize_text_field' );
					break;
				case 'period-performance':
					$value          = $_POST[ $meta_field['id'] ];
					$value['num']   = ( isset( $value['num'] ) ) ? absint( $value['num'] ) : '';
					$value['units'] = ( isset( $value['units'] ) ) ? sanitize_text_field( $value['units'] ) : '';
					break;
				case 'electronic-submission-method':
					$value          = $_POST[ $meta_field['id'] ];
					$value['email'] = ( isset( $value['email'] ) ) ? sanitize_email( $value['email'] ) : '';
					$value['url']   = ( isset( $value['url'] ) ) ? esc_url_raw( $value['url'] ) : '';
					break;
				case 'application-deadline':
					$value = $_POST[ $meta_field['id'] ];
					array_walk( $value, 'sanitize_text_field' );
					break;
				default:
					$value = sanitize_text_field( $_POST[ $meta_field['id'] ] );
					break;
			}

			update_post_meta( $post_id, $meta_field['id'], $value );
		}
	}
}

/**
 * Render the metabox
 */
function render_metabox() {
	$meta_fields = get_meta_fields();

	if ( empty( $meta_fields ) ) {
		return;
	}

	wp_nonce_field( NONCE_ACTION, NONCE_FIELD );

	foreach ( $meta_fields as $meta_field ) {
		// If the field type isn't set, pass over this entry
		if ( ! isset( $meta_field['type'] ) ) {
			continue;
		}

		switch ( $meta_field['type'] ) {
			case 'radio':
				render_radio_field( $meta_field );
				break;
			case 'checkbox':
				render_checkbox_field( $meta_field );
				break;
			case 'textarea':
				render_textarea( $meta_field );
				break;
			case 'select':
				render_select_field( $meta_field );
				break;
			case 'point_of_contact':
				render_point_of_contact_input( $meta_field );
				break;
			case 'datetime-local':
				render_datepicker( $meta_field );
				break;
			case 'eligibility-matching-funds':
				render_matching_funds( $meta_field );
				break;
			case 'estimated-number-awards':
				render_number_awards( $meta_field );
				break;
			case 'estimated-award-amounts':
				render_award_amounts( $meta_field );
				break;
			case 'period-performance':
				render_period_performance( $meta_field );
				break;
			case 'electronic-submission-method':
				render_submission_method( $meta_field );
				break;
			case 'application-deadline':
				render_application_deadline( $meta_field );
				break;
			default:
				render_input_field( $meta_field );
				break;
		}
	}
}

/**
 * Returns an array of the required meta fields
 *
 * @return array The meta fields.
 */
function get_meta_fields() {
	return array(
		array(
			'id'         => 'grant-title',
			'name'       => __( 'Grant Title', 'csl-grants-submission' ),
			'type'       => 'textarea',
			'text_limit' => 105,
		),
		array(
			'id'   => 'grant-id',
			'name' => __( 'Grant ID', 'csl-grants-submission' ),
			'type' => 'text',
		),
		array(
			'id'     => 'grant-type',
			'name'   => __( 'Grant Type', 'csl-grants-submission' ),
			'type'   => 'radio',
			'fields' => array(
				array(
					'id'   => 'forecasted',
					'name' => __( 'Forcasted', 'csl-grants-submission' ),
				),
				array(
					'id'   => 'active',
					'name' => __( 'Active', 'csl-grants-submission' ),
				),
			),
		),
		array(
			'id'     => 'grantmaking-agency',
			'name'   => __( 'Grantmaking Agency/Department', 'csl-grants-submission' ),
			'type'   => 'select',
			'source' => 'api',
		),
		array(
			'id'     => 'opportunity-type',
			'name'   => __( 'Opportunity Type', 'csl-grants-submission' ),
			'type'   => 'radio',
			'source' => 'api',
		),
		array(
			'id'     => 'relevant-categories',
			'name'   => __( 'Relevant Categories', 'csl-grants-submission' ),
			'type'   => 'select',
			'source' => 'api',
			'multi'  => true,
		),
		array(
			'id'   => 'category-suggestion',
			'name' => __( 'Category Suggestion(s)', 'grantsportal' ),
			'type' => 'text',
		),
		array(
			'id'         => 'purpose',
			'name'       => __( 'Purpose', 'csl-grants-submission' ),
			'type'       => 'textarea',
			'text_limit' => 450,
		),
		array(
			'id'         => 'description',
			'name'       => __( 'Description', 'csl-grants-submission' ),
			'type'       => 'textarea',
			'text_limit' => 3200,
		),
		array(
			'id'     => 'required-loi',
			'name'   => __( 'Required LOI', 'csl-grants-submission' ),
			'type'   => 'radio',
			'fields' => array(
				array(
					'id'   => 'yes',
					'name' => __( 'Yes', 'csl-grants-submission' ),
				),
				array(
					'id'   => 'no',
					'name' => __( 'No', 'csl-grants-submission' ),
				),
			),
		),
		array(
			'id'     => 'eligibility-applicant-type',
			'name'   => __( 'Eligibility: Applicant Type', 'csl-grants-submission' ),
			'type'   => 'checkbox',
			'source' => 'api',
		),
		array(
			'id'         => 'applicant-type-notes',
			'name'       => __( 'Applicant Type Notes', 'csl-grants-submission' ),
			'type'       => 'textarea',
			'text_limit' => 250,
		),
		array(
			'id'         => 'eligibility-geographic',
			'name'       => __( 'Eligibility: Geographic', 'csl-grants-submission' ),
			'type'       => 'textarea',
			'text_limit' => 450,
		),
		array(
			'id'     => 'revenue-source',
			'name'   => __( 'Revenue Source', 'csl-grants-submission' ),
			'type'   => 'radio',
			'source' => 'api',
		),
		array(
			'id'         => 'revenue-source-notes',
			'name'       => __( 'Revenu Source Notes', 'csl-grants-submission' ),
			'type'       => 'textarea',
			'text_limit' => 200,
		),
		array(
			'id'   => 'eligibility-matching-funds',
			'name' => __( 'Eligibility: Matching Funds', 'csl-grants-submission' ),
			'type' => 'eligibility-matching-funds',
		),
		array(
			'id'         => 'matching-funds-notes',
			'name'       => __( 'Matching Funds Notes', 'csl-grants-submission' ),
			'type'       => 'textarea',
			'text_limit' => 300,
		),
		array(
			'id'   => 'total-estimated-available-funding',
			'name' => __( 'Total Estimated Available Funding', 'csl-grants-submission' ),
			'type' => 'number',
		),
		array(
			'id'   => 'estimated-number-awards',
			'name' => __( 'Estimated Number of Awards', 'csl-grants-submission' ),
			'type' => 'estimated-number-awards',
		),
		array(
			'id'   => 'estimated-award-amounts',
			'name' => __( 'Estimated Award Amounts', 'csl-grants-submission' ),
			'type' => 'estimated-award-amounts',
		),
		array(
			'id'     => 'funds-disbursement-methods',
			'name'   => __( 'Funds Disbursement Methods', 'csl-grants-submission' ),
			'type'   => 'radio',
			'fields' => array(
				array(
					'id'   => 'advance',
					'name' => __( 'Advance(s)', 'grantsportal' ),
				),
				array(
					'id'   => 'reimbursement',
					'name' => __( 'Reimbursement(s)', 'grantsportal' ),
				),
				array(
					'id'   => 'combination',
					'name' => __( 'Combination of Advance(s) and Reimbursement(s)', 'grantsportal' ),
				),
			),
		),
		array(
			'id'   => 'funds-disbursement-details',
			'name' => __( 'Funds Disbursement Details', 'csl-grants-submission' ),
			'type' => 'textarea',
		),
		array(
			'id'   => 'grant-open-close',
			'name' => __( 'Grant Open/Close', 'csl-grants-submission' ),
			'type' => 'datetime-local',
		),
		array(
			'id'   => 'period-performance',
			'name' => __( 'Period of Performance', 'csl-grants-submission' ),
			'type' => 'period-performance',
		),
		array(
			'id'   => 'expected-award-date',
			'name' => __( 'Expected Award Announcement Date', 'csl-grants-submission' ),
			'type' => 'text',
		),
		array(
			'id'   => 'application-deadline',
			'name' => __( 'Application Deadline', 'csl-grants-submission' ),
			'type' => 'application-deadline',
		),
		array(
			'id'   => 'electronic-submission-method',
			'name' => __( 'Electronic Application Submission Method', 'csl-grants-submission' ),
			'type' => 'electronic-submission-method',
		),
		array(
			'id'   => 'grant-details-url',
			'name' => __( 'Grant Details URL', 'csl-grants-submission' ),
			'type' => 'url',
		),
		array(
			'id'   => 'grantmaking-agency-url',
			'name' => __( 'Grantmaking Agency/Department URL', 'csl-grants-submission' ),
			'type' => 'url',
		),
		array(
			'id'   => 'grant-updates-url',
			'name' => __( 'Grant Updates Subscribe URL', 'csl-grants-submission' ),
			'type' => 'url',
		),
		array(
			'id'   => 'planned-events-url',
			'name' => __( 'Planned Events Information URL', 'csl-grants-submission' ),
			'type' => 'url',
		),
		array(
			'id'   => 'public-poc',
			'name' => __( 'Public Point of Contact', 'csl-grants-submission' ),
			'type' => 'point_of_contact',
		),
		array(
			'id'   => 'administrative-primary-contact',
			'name' => __( 'Administrative Primary Point of Contact', 'csl-grants-submission' ),
			'type' => 'point_of_contact',
		),
		array(
			'id'   => 'administrative-secondary-contact',
			'name' => __( 'Administrative Secondary Point of Contact', 'csl-grants-submission' ),
			'type' => 'point_of_contact',
		),
	);
}

/**
 * Render an input field
 *
 * @param array $meta_field The meta field to render
 */
function render_input_field( $meta_field = array() ) {
	if ( empty( $meta_field ) || ! is_array( $meta_field ) ) {
		return;
	}

	$type        = $meta_field['type'] ?? '';
	$name        = $meta_field['name'] ?? '';
	$description = $meta_field['description'] ?? '';
	$id          = $meta_field['id'] ?? '';

	// Get the saved data
	$value = get_post_meta( get_the_ID(), $id, true );

	// Used for telephone fields
	$pattern = 'placeholder=1-555-555-5555 pattern=[0-9]{1}-[0-9]{3}-[0-9]{3}-[0-9]{4}';
	?>

	<p><strong><label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $name ); ?></label></strong></p>
	<p><?php echo esc_html( $description ); ?></p>
	<p><input type="<?php echo esc_attr( $type ); ?>" <?php echo ( 'tel' === $type ) ? esc_attr( $pattern ) : ''; ?> id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $id ); ?>" value="<?php echo esc_attr( $value ); ?>" /></p>

	<?php
}

/**
 * Render a checkbox field
 *
 * @param array $meta_field The meta field to render
 */
function render_checkbox_field( $meta_field = array() ) {
	if ( empty( $meta_field ) || ! is_array( $meta_field ) ) {
		return;
	}

	$name        = $meta_field['name'] ?? '';
	$description = $meta_field['description'] ?? '';
	$id          = $meta_field['id'] ?? '';

	// Get the saved data
	$value = get_post_meta( get_the_ID(), $id, true );
	?>

	<p><strong><label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $name ); ?></label></strong></p>
	<p><?php echo esc_html( $description ); ?></p>
	<p><input <?php checked( $value, 'on' ); ?> type="checkbox" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $id ); ?>" value="on" /></p>

	<?php
}

/**
 * Render a radio field
 *
 * @param array $meta_field The meta field to render
 */
function render_radio_field( $meta_field = array() ) {
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
		$fields = get_api_fields_by_id( $id );
	} elseif ( isset( $meta_fields['fields'] ) ) {
		$fields = $meta_fields['fields'];
	} else {
		$fields = '';
	}

	if ( empty( $fields ) ) {
		return;
	}

	// Get the saved data
	$value = get_post_meta( get_the_ID(), $id, true );
	?>

	<p><strong><label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $name ); ?></label></strong></p>
	<p><?php echo esc_html( $description ); ?></p>
	<p>
		<input <?php checked( 'none', $value ); ?> type="radio" id="none" name="<?php echo esc_attr( $id ); ?>" value="none"/>
		<label for="none"><?php esc_html_e( 'None', 'csl-grants-submissions' ); ?></label>
	</p>

	<?php foreach ( $fields as $field ) : ?>

		<p>
			<input <?php checked( $field['id'], $value ); ?> type="radio" id="<?php echo esc_attr( $field['id'] ); ?>" name="<?php echo esc_attr( $id ); ?>" value="<?php echo esc_attr( $field['id'] ); ?>"/>
			<label for="<?php echo esc_attr( $field['id'] ); ?>"><?php echo esc_html( $field['name'] ); ?></label>
		</p>

	<?php endforeach; ?>
	<?php
}

/**
 * Render a radio field
 *
 * @param array $meta_field The meta field to render
 */
function render_select_field( $meta_field = array() ) {
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
		$fields = get_api_fields_by_id( $id );
	} elseif ( isset( $meta_fields['fields'] ) ) {
		$fields = $meta_fields['fields'];
	} else {
		$fields = '';
	}

	if ( empty( $fields ) ) {
		return;
	}

	// Get the saved data
	$value = get_post_meta( get_the_ID(), $id, true );
	?>

	<p><strong><label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $name ); ?></label></strong></p>
	<p><?php echo esc_html( $description ); ?></p>
	<p>
		<select name="<?php echo esc_attr( $id ); ?>" id="<?php echo esc_attr( $id ); ?>">
			<option <?php selected( '', $value ); ?> value=""><?php esc_html_e( 'Select One', 'csl-grants-submissions' ); ?></option>
			<?php foreach ( $fields as $field ) : ?>

			<option <?php selected( $field['id'], $value ); ?> value="<?php echo esc_attr( $field['id'] ); ?>"><?php echo esc_html( $field['name'] ); ?></option>

			<?php endforeach; ?>
		</select>
	</p>

	<?php
}

/**
 * Render a textarea field
 *
 * @param array $meta_field The meta field being rendered.
 */
function render_textarea( $meta_field = array() ) {
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

	<p><strong><label for="<?php esc_attr( $id ); ?>"><?php echo esc_html( $name ); ?></label></strong></p>
	<p><?php echo esc_html( $description ); ?></p>
	<p><?php do_editor( $value, $id ); ?></p>

	<?php
}

/**
 * Render the custom Submission Methods field
 *
 * @param array $meta_field The meta field data
 */
function render_submission_method( $meta_field = array() ) {
	if ( empty( $meta_field ) || ! is_array( $meta_field ) ) {
		return;
	}

	$name = $meta_field['name'] ?? '';
	$id   = $meta_field['id'] ?? '';

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

	<table class="table-object">
		<tr>
			<th>
				<?php echo esc_html( $name ); ?>
			</th>
		</tr>
		<tr>
			<td>
				<table class="table-object">
					<tr>
						<td>
							<label for="email_submission"><?php esc_html_e( 'Email Submission', 'csl-grants-portal' ); ?></label>
							<input type="email" name="<?php echo esc_attr( $id ); ?>[email]" value="<?php echo esc_attr( $value['email'] ); ?>" id="email_submission">
						</td>
					</tr>
					<tr>
						<td>
							<label for="online_submission"><?php esc_html_e( 'Online submission form', 'csl-grants-portal' ); ?></label>
							<input type="url" name="<?php echo esc_attr( $id ); ?>[url]" value="<?php echo esc_attr( $value['url'] ); ?>" id="online_submission">
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>

	<?php
}

/**
 * Render the custom Period of Performance field
 *
 * @param array $meta_field The meta field data
 */
function render_period_performance( $meta_field = array() ) {
	if ( empty( $meta_field ) || ! is_array( $meta_field ) ) {
		return;
	}

	$name = $meta_field['name'] ?? '';
	$id   = $meta_field['id'] ?? '';

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

	<table class="table-object">
		<tr>
			<th>
				<label for="<?php echo esc_attr( $id ); ?>-num-units">
					<?php echo esc_html( $name ); ?>
				</label>
			</th>
		</tr>
		<tr>
			<td>
				<table class="table-object">
					<tr>
						<td>
							<input type="text" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $id ); ?>[num]" value="<?php echo esc_attr( $value['num'] ); ?>"/>
							<select name="<?php echo esc_attr( $id ); ?>[units]">
								<option <?php selected( $value['units'], 'days' ); ?> value="days"><?php esc_html_e( 'Days', 'csl-grants-submission' ); ?></option>
								<option <?php selected( $value['units'], 'weeks' ); ?>value="weeks"><?php esc_html_e( 'Weeks', 'csl-grants-submission' ); ?></option>
								<option <?php selected( $value['units'], 'months' ); ?>value="months"><?php esc_html_e( 'Months', 'csl-grants-submission' ); ?></option>
								<option <?php selected( $value['units'], 'years' ); ?>value="years"><?php esc_html_e( 'Years', 'csl-grants-submission' ); ?></option>
							</select>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>

	<?php
}

/**
 * Render the custom Award Amounts field
 *
 * @param array $meta_field The meta field data
 */
function render_award_amounts( $meta_field = array() ) {
	if ( empty( $meta_field ) || ! is_array( $meta_field ) ) {
		return;
	}

	$name = $meta_field['name'] ?? '';
	$id   = $meta_field['id'] ?? '';

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

	<table class="table-object">
		<tr>
			<th>
				<label for="<?php echo esc_attr( $id ); ?>">
					<?php echo esc_html( $name ); ?>
				</label>
			</th>
		</tr>
		<tr>
			<td>
				<table class="table-object">
					<tr>
						<td>
							<input <?php checked( $value['checkbox'], 'same' ); ?> type="radio" id="<?php echo esc_attr( $id . '-same' ); ?>" name="<?php echo esc_attr( $id ); ?>[checkbox]" value="same">
							<label for="<?php echo esc_attr( $id . '-same' ); ?>"><?php esc_html_e( 'Same amount each award: ', 'csl-grants-submission' ); ?></label>
							<input class="widefat" type="text" id="<?php echo esc_attr( $id ); ?>-same-amount" name="<?php echo esc_attr( $id ); ?>[same][amount]" value="<?php echo esc_attr( $value['same']['amount'] ); ?>"/>
						</td>
					</tr>

					<tr>
						<td>
							<input <?php checked( $value['checkbox'], 'different' ); ?> type="radio" id="<?php echo esc_attr( $id . '-different' ); ?>" name="<?php echo esc_attr( $id ); ?>[checkbox]" value="different">
							<label for="<?php echo esc_attr( $id . '-different' ); ?>"><?php esc_html_e( 'Different amount each award', 'csl-grants-submission' ); ?></label>
							<input type="text" id="<?php echo esc_attr( $id ); ?>-different-first" name="<?php echo esc_attr( $id ); ?>[different][first]" value="<?php echo esc_attr( $value['different']['first'] ); ?>"/>
							<input type="text" id="<?php echo esc_attr( $id ); ?>-different-second" name="<?php echo esc_attr( $id ); ?>[different][second]" value="<?php echo esc_attr( $value['different']['second'] ); ?>"/>
							<input type="text" id="<?php echo esc_attr( $id ); ?>-different-third" name="<?php echo esc_attr( $id ); ?>[different][third]" value="<?php echo esc_attr( $value['different']['third'] ); ?>"/>
						</td>
					</tr>

					<tr>
						<td>
							<input <?php checked( $value['checkbox'], 'unknown' ); ?> type="radio" id="<?php echo esc_attr( $id . '-unknown' ); ?>" name="<?php echo esc_attr( $id ); ?>[checkbox]" value="unknown">
							<label for="<?php echo esc_attr( $id . '-unknown' ); ?>"><?php esc_html_e( 'Unknown; amount per award may range  between:', 'csl-grants-submission' ); ?></label>
							<input type="text" id="<?php echo esc_attr( $id ); ?>-unknown-first" name="<?php echo esc_attr( $id ); ?>[unknown][first]" value="<?php echo esc_attr( $value['unknown']['first'] ); ?>"/>
							<?php esc_html_e( ' to ', 'csl-grants-submission' ); ?>
							<input type="text" id="<?php echo esc_attr( $id ); ?>-unknown-second" name="<?php echo esc_attr( $id ); ?>[unknown][second]" value="<?php echo esc_attr( $value['unknown']['second'] ); ?>"/>
						</td>
					</tr>

					<tr>
						<td>
							<input <?php checked( $value['checkbox'], 'dependant' ); ?> type="radio" id="<?php echo esc_attr( $id . '-dependant' ); ?>" name="<?php echo esc_attr( $id ); ?>[checkbox]" value="dependant">
							<label for="<?php echo esc_attr( $id . '-dependant' ); ?>"><?php esc_html_e( 'Dependant on number of submissions received, application process, etc.', 'csl-grants-submission' ); ?></label>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>

	<?php
}

/**
 * Render the custom Number of Awards field
 *
 * @param array $meta_field The meta field data
 */
function render_number_awards( $meta_field = array() ) {
	if ( empty( $meta_field ) || ! is_array( $meta_field ) ) {
		return;
	}

	$name = $meta_field['name'] ?? '';
	$id   = $meta_field['id'] ?? '';

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

	<table class="table-object">
		<tr>
			<th>
				<label for="<?php echo esc_attr( $id ); ?>">
					<?php echo esc_html( $name ); ?>
				</label>
			</th>
		</tr>
		<tr>
			<td>
				<table class="table-object">
					<tr>
						<td>
							<input <?php checked( $value['checkbox'], 'exact' ); ?> type="radio" id="<?php echo esc_attr( $id . '-exactly' ); ?>" name="<?php echo esc_attr( $id ); ?>[checkbox]" value="exact">
							<label for="<?php echo esc_attr( $id . '-exactly' ); ?>"><?php esc_html_e( 'Exactly: ', 'csl-grants-submission' ); ?></label>
							<input class="widefat" type="text" id="<?php echo esc_attr( $id ); ?>-exactly" name="<?php echo esc_attr( $id ); ?>[exact]" value="<?php echo esc_attr( $value['exact'] ); ?>"/>
						</td>
					</tr>

					<tr>
						<td>
							<input <?php checked( $value['checkbox'], 'between' ); ?> type="radio" id="<?php echo esc_attr( $id . '-between' ); ?>" name="<?php echo esc_attr( $id ); ?>[checkbox]" value="between">
							<label for="<?php echo esc_attr( $id . '-between' ); ?>"><?php esc_html_e( 'Between', 'csl-grants-submission' ); ?></label>
							<input type="text" id="<?php echo esc_attr( $id ); ?>-between-first" name="<?php echo esc_attr( $id ); ?>[between][low]" value="<?php echo esc_attr( $value['between']['low'] ); ?>"/>
							<?php esc_html_e( ' and ', 'csl-grants-submission' ); ?>
							<input type="text" id="<?php echo esc_attr( $id ); ?>-between-second" name="<?php echo esc_attr( $id ); ?>[between][high]" value="<?php echo esc_attr( $value['between']['high'] ); ?>"/>
						</td>
					</tr>

					<tr>
						<td>
							<input <?php checked( $value['checkbox'], 'dependant' ); ?> type="radio" id="<?php echo esc_attr( $id . '-dependant' ); ?>" name="<?php echo esc_attr( $id ); ?>[checkbox]" value="dependant">
							<label for="<?php echo esc_attr( $id . '-dependant' ); ?>"><?php esc_html_e( 'Dependant on number of submissions received, application process, etc.', 'csl-grants-submission' ); ?></label>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>

	<?php
}

/**
 * Render the custom Matching Funds field
 *
 * @param array $meta_field The meta field data
 */
function render_matching_funds( $meta_field = array() ) {
	if ( empty( $meta_field ) || ! is_array( $meta_field ) ) {
		return;
	}

	$name = $meta_field['name'] ?? '';
	$id   = $meta_field['id'] ?? '';

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

	<table class="table-object">
		<tr>
			<th>
				<label for="<?php echo esc_attr( $id ); ?>">
					<?php echo esc_html( $name ); ?>
				</label>
			</th>
		</tr>
		<tr>
			<td>
				<table class="table-object">
					<tr>
						<td>
							<input <?php checked( $value['checkbox'], 'no' ); ?> type="radio" id="<?php echo esc_attr( $id . '-no' ); ?>" name="<?php echo esc_attr( $id ); ?>" value="no">
							<label for="<?php echo esc_attr( $id . '-no' ); ?>"><?php esc_html_e( 'No', 'csl-grants-submission' ); ?></label>
						</td>
					</tr>

					<tr>
						<td>
							<input <?php checked( $value['checkbox'], 'yes' ); ?> type="radio" id="<?php echo esc_attr( $id . '-yes' ); ?>" name="<?php echo esc_attr( $id ); ?>" value="yes">
							<label for="<?php echo esc_attr( $id . '-yes' ); ?>"><?php esc_html_e( 'Yes, with matching percentage: ', 'csl-grants-submission' ); ?></label>
							<input class="widefat" type="text" name="<?php echo esc_attr( $id ); ?>-percentage" value="<?php echo esc_attr( $value['percentage'] ); ?>"/>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>

	<?php
}

/**
 * Renders the custom Grant Open/Close field
 *
 * @param array $meta_field The meta field to render
 */
function render_datepicker( $meta_field = array() ) {
	if ( empty( $meta_field ) || ! is_array( $meta_field ) ) {
		return;
	}

	$name = $meta_field['name'] ?? '';
	$id   = $meta_field['id'] ?? '';

	if ( empty( $name ) || empty( $id ) ) {
		return;
	}

	// default values
	$defaults = array(
		'open'  => '',
		'close' => '',
	);

	// Get the saved data
	$value = wp_parse_args( get_post_meta( get_the_ID(), $id, true ), $defaults );
	?>

	<table class="table-object">
		<tr>
			<th>
				<?php echo esc_html( $name ); ?>
			</th>
		</tr>
		<tr>
			<td>
				<label for="open-date"><?php esc_html_e( 'Open Date', 'csl-grants-submissions' ); ?></label>
				<input type="datetime-local" id="open-date" name="<?php echo esc_attr( $id ); ?>[open]" value="<?php echo esc_attr( $value['open'] ); ?>">
			</td>
		</tr>
		<tr>
			<td>
				<label for="close-date"><?php esc_html_e( 'Close Date', 'csl-grants-submissions' ); ?></label>
				<input type="datetime-local" id="close-date" name="<?php echo esc_attr( $id ); ?>[close]" value="<?php echo esc_attr( $value['close'] ); ?>">
			</td>
		</tr>
	</table>

	<?php
}

/**
 * Render the custom Point of Contact field
 *
 * @param array $meta_field The data with which to render the HTML field.
 */
function render_point_of_contact_input( $meta_field ) {
	if ( empty( $meta_field ) || ! is_array( $meta_field ) ) {
		return;
	}

	$type = $meta_field['type'] ?? '';
	$name = $meta_field['name'] ?? '';
	$id   = $meta_field['id'] ?? '';

	// default values
	$defaults = array(
		'name'  => '',
		'email' => '',
		'tel'   => '',
	);

	// Get the saved data
	$value = wp_parse_args( get_post_meta( get_the_ID(), $id, true ), $defaults );
	?>

	<table class="table-object">
		<tr>
			<th>
				<label for="<?php echo esc_attr( $id ); ?>">
					<?php echo esc_html( $name ); ?>
				</label>
			</th>
		</tr>
		<tr>
			<td>
				<table class="table-object">
					<tr>
						<td>
							<label for="<?php echo esc_attr( $id ); ?>-name"><?php esc_html_e( 'Name', 'csl-grants-submissions' ); ?></label>
							<input type="text" id="<?php echo esc_attr( $id ); ?>-name" name="<?php echo esc_attr( $id ); ?>[name]" value="<?php echo esc_attr( $value['name'] ); ?>" />
						</td>
					</tr>
					<tr>
						<td>
							<label for="<?php echo esc_attr( $id ); ?>-email"><?php esc_html_e( 'Email', 'csl-grants-submissions' ); ?></label>
							<input type="email" id="<?php echo esc_attr( $id ); ?>-name" name="<?php echo esc_attr( $id ); ?>[email]" value="<?php echo esc_attr( $value['email'] ); ?>" />
						</td>
					</tr>
					<tr>
						<td>
							<label for="<?php echo esc_attr( $id ); ?>-tel"><?php esc_html_e( 'Phone', 'csl-grants-submissions' ); ?></label>
							<input type="tel" id="<?php echo esc_attr( $id ); ?>-name" name="<?php echo esc_attr( $id ); ?>[tel]" value="<?php echo esc_attr( $value['tel'] ); ?>" placeholder="1-555-555-5555" pattern="[0-9]{1}-[0-9]{3}-[0-9]{3}-[0-9]{4}" />
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>

	<?php
}

/**
 * Render the custom Application Deadline field
 *
 * @param array $meta_field The data with which to render the HTML field.
 */
function render_application_deadline( $meta_field ) {
	if ( empty( $meta_field ) || ! is_array( $meta_field ) ) {
		return;
	}

	$type = $meta_field['type'] ?? '';
	$name = $meta_field['name'] ?? '';
	$id   = $meta_field['id'] ?? '';

	// default values
	$defaults = array(
		'deadline' => array(
			'none' => '',
			'date' => '',
			'time' => '',
		),
	);

	// Get the saved data
	$value = wp_parse_args( get_post_meta( get_the_ID(), $id, true ), $defaults );
	?>

	<table class="table-object">
		<tr>
			<th>
				<label for="<?php echo esc_attr( $id ); ?>">
					<?php echo esc_html( $name ); ?>
				</label>
			</th>
		</tr>
		<tr>
			<td>
				<table class="table-object">
					<tr>
						<td>
							<input <?php checked( $value['deadline']['none'], 'nodeadline' ); ?> type="checkbox" id="<?php echo esc_attr( $id ); ?>-nodeadline" name="<?php echo esc_attr( $id ); ?>[deadline][none]" value="nodeadline" />
							<label for="<?php echo esc_attr( $id ); ?>-nodeadline"><?php esc_html_e( 'No Deadline', 'csl-grants-submissions' ); ?></label>
						</td>
					</tr>
					<tr>
						<td>
							<label for="<?php echo esc_attr( $id ); ?>-date"><?php esc_html_e( 'Deadline Date', 'csl-grants-submissions' ); ?></label>
							<input class="csl-datepicker" type="text" id="<?php echo esc_attr( $id ); ?>-date" name="<?php echo esc_attr( $id ); ?>[deadline][date]" value="<?php echo esc_attr( $value['deadline']['date'] ); ?>" />
						</td>
					</tr>
					<tr>
						<td>
							<label for="<?php echo esc_attr( $id ); ?>-time"><?php esc_html_e( 'Deadline Time (optional)', 'csl-grants-submissions' ); ?></label>
								<select id="<?php echo esc_attr( $id ); ?>-time" name="<?php echo esc_attr( $id ); ?>[deadline][time]">
									<option value="none"><?php esc_html_e( 'No Deadline Time', 'csl-grants-submissions' ); ?></option>
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

											<option <?php selected( $value['deadline']['time'], $hour_top ); ?> value="<?php echo esc_attr( $hour_top ); ?>"><?php echo esc_attr( $hour_top ); ?></option>
											<option <?php selected( $value['deadline']['time'], $hour_half ); ?>value="<?php echo esc_attr( $hour_half ); ?>"><?php echo esc_attr( $hour_half ); ?></option>

											<?php
										}
										?>
								</select>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>

	<?php
}

/**
 * Get fields for an HTML element from the WP API
 *
 * @param string $id The identifier for the type of field data needed.
 *
 * @return array $fields The data from the WP API
 */
function get_api_fields_by_id( $id = '' ) {
	if ( empty( $id ) ) {
		return;
	}

	$fields_to_display = wp_cache_get( $id, 'csl-grants-submissions' );

	if ( false === $fields_to_display ) {
		$api_url = trailingslashit( API_URL ) . 'wp-json/wp/v2/';

		switch ( $id ) {
			case 'relevant-categories':
				$api_url .= 'grant-categories';
				break;
			case 'grantmaking-agency':
				$api_url .= 'agencies';
				break;
			case 'eligibility-applicant-type':
				$api_url .= 'applicant-types';
				break;
			case 'funds-disbursement-methods':
				$api_url .= 'disbursement-methods';
				break;
			case 'opportunity-type':
				$api_url .= 'opportunity-types';
				break;
			case 'revenue-source':
				$api_url .= 'revenue-sources';
				break;
			default:
				$api_url = null;
				break;
		}

		if ( is_null( $api_url ) ) {
			return;
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

		wp_cache_set( $id, $fields_to_display, 'csl-grants-submissions' );
	}

	return $fields_to_display;
}
