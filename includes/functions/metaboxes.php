<?php
/**
 * Responsible for the entire meta box of the edit page.
 */

namespace CslGrantsSubmissions\Metaboxes;

const NONCE_ACTION = 'grant-submissions-metabox';
const NONCE_FIELD  = '_grant_submission';

function setup() {
	$n = function( $ns ) {
		return __NAMESPACE__ . "\\$ns";
	};

	add_action( 'add_meta_boxes', $n( 'add_metaboxes' ) );
	add_action( 'save_post', $n( 'save_post' ) );
}

function do_editor( $content, $id ) {
	wp_editor( $content, $id, [
		'media_buttons'    => false,
		'drag_drop_upload' => false,
		'teeny'            => true,
		'quicktags'        => false,
		'textarea_rows'    => 5
	] );
}

/**
 * Adds the metaboxes.
 */
function add_metaboxes() {
	add_meta_box( 'grants-submission', __( 'Grant Information', 'csl-grants-submissions' ), __NAMESPACE__ . '\\render_metabox', \CslGrantsSubmissions\CPT\Grants\POST_TYPE );
}

/**
 * Handles the save post action.
 */
function save_post() {
	if ( ! isset( $_POST[ NONCE_FIELD ] ) || ! wp_verify_nonce( $_POST[ NONCE_FIELD ], NONCE_ACTION ) || empty( $_POST['csl_items'] ) ) {
		return;
	}

	$meta = [];
	foreach( $_POST['csl_items'] as $meta_key => $value ) {
		switch( $meta_key ) {
			case 'grant_purpose':
			case 'grant_description':
			case 'elegibility_notes':
			case 'elegibility_geographic':
			case 'revenue_source_notes':
			case 'matched_funding_notes':
				$value = wp_kses_post( $value );
				break;
			default:
				$value = sanitize_text_field( $value );
				break;
		}

		$meta[ $meta_key ] = $value;
	}
}

/**
 * Fetches grant departments from CSL and builds them into an option data set.
 *
 * @return void No return, this is an echo function.
 */
function api_grant_department_options() {

}

/**
 * Gets opportunity types from the API.
 *
 * @return void No return, this is an echo function.
 */
function api_opportunity_type_options() {
	?>
	<label><input type="radio" name="csl_items[opportunity_type]" value="grant"><?php esc_html_e( 'Grant', 'csl-grants-submissions' ); ?></label>
	<label><input type="radio" name="csl_items[opportunity_type]" value="loan"><?php esc_html_e( 'Loan', 'csl-grants-submissions' ); ?></label>
	<?php
}

/**
 * Gets applicant types from the API.
 *
 * @return void No return, this is an echo function.
 */
function api_applicant_types() {

}

function api_revenue_sources() {
	?>
	<label><input type="radio" name="csl_items[revenue_source]" value="state"><?php esc_html_e( 'State', 'csl-grants-submissions' ); ?></label>
	<label><input type="radio" name="csl_items[revenue_source]" value="federal"><?php esc_html_e( 'Federal', 'csl-grants-submissions' ); ?></label>
	<?php
}

/**
 * Gets the grant categories from the API.
 *
 * @return void No return, this is an echo function.
 */
function api_grant_categories() {
	?>
	<label for=""><input type="checkbox" name="csl_items[grant_categories][]" value="agriculture"><?php esc_html_e( 'Agriculture', 'csl-grants-submissions' ); ?></label>
	<label for=""><input type="checkbox" name="csl_items[grant_categories][]" value="arts"><?php esc_html_e( 'Arts', 'csl-grants-submissions' ); ?></label>
	<label for=""><input type="checkbox" name="csl_items[grant_categories][]" value="business-commerce"><?php esc_html_e( 'Business & Commerce', 'csl-grants-submissions' ); ?></label>
	<label for=""><input type="checkbox" name="csl_items[grant_categories][]" value="education"><?php esc_html_e( 'Education', 'csl-grants-submissions' ); ?></label>
	<?php
}

function api_disbursement_methods() {
	?>
	<label><input type="radio" name="csl_items[disbursment_method]" value="advance"><?php esc_html_e( 'Advance(s)', 'csl-grants-submissions' ); ?></label>
	<label><input type="radio" name="csl_items[disbursment_method]" value="reimbursement"><?php esc_html_e( 'Reimbursement(s)', 'csl-grants-submissions' ); ?></label>
	<label><input type="radio" name="csl_items[disbursment_method]" value="both"><?php esc_html_e( 'Both', 'csl-grants-submissions' ); ?></label>
	<?php
}


/**
 * Renders the metabox.
 */
function render_metabox() {
	wp_nonce_field( NONCE_ACTION, NONCE_FIELD );
	?>
	<p>
		<label for="grant_id"><?php esc_html_e( 'Grant ID', 'csl-grants-submissions' ); ?></label>
		<input type="text" id="grant_id" name="csl_items[grant_id]">
	</p>
	<p>
		<label for="grant_agency"><?php esc_html_e( 'Grant Agency', 'csl-grants-submissions' ); ?></label>
		<!--Actually we may not need this in the plugin, but keep here for now-->
		<select name="csl_items[grant_agency]" id="grant_agency">
			<option value=""><?php esc_html_e( 'Select One', 'csl-grants-submissions' ); ?></option>
		</select>
	</p>
	<p>
		<label for="grant_department"><?php esc_html_e( 'Grant Department', 'csl-grants-submissions' ); ?></label>
		<select name="csl_items[grant_department]" id="grant_department">
			<option value=""><?php esc_html_e( 'Select One', 'csl-grants-submissions' ); ?></option>
			<?php api_grant_department_options(); ?>
		</select>
	</p>
	<p>
		<span class="label"><?php esc_html_e( 'Opportunity Type', 'csl-grants-submissions' ); ?></span>
		<?php api_opportunity_type_options(); ?>
	</p>
	<p>
		<span class="label"><?php esc_html_e( 'Is Letter of Intent Required?', 'csl-grants-submissions' ); ?></span>
		<label><input type="radio" name="csl_items[loi_required]" value="yes"><?php esc_html_e( 'Yes', 'csl-grants-submissions' ); ?></label>
		<label><input type="radio" name="csl_items[loi_required]" value="no"><?php esc_html_e( 'No', 'csl-grants-submissions' ); ?></label>
	</p>
	<p>
		<span class="label"><?php esc_html_e( 'Grant Purpose', 'csl-grants-submissions' ); ?></span>
		<?php do_editor( '', 'grant_purpose' ); ?>
	</p>
	<p>
		<span class="label"><?php esc_html_e( 'Grant Description', 'csl-grants-submissions' ); ?></span>
		<?php do_editor( '', 'grant_description' ); ?>
	</p>
	<p>
		<span class="label"><?php esc_html_e( 'Grant Categories', 'csl-grants-submissions' ); ?></span>
		<?php api_grant_categories(); ?>
	</p>
	<p>
		<span class="label"><?php esc_html_e( 'Applicant Type', 'csl-grants-submissions' ); ?></span>
		<label><input type="checkbox" name="csl_items[applicant_type][]" value="any"><?php esc_html_e( 'Any', 'csl-grants-submissions' ); ?></label>
		<?php api_applicant_types(); ?>
	</p>
	<p>
		<span class="label"><?php esc_html_e( 'Eligibility Notes', 'csl-grants-submissions' ); ?></span>
		<?php do_editor( '', 'elegibility_notes' ); ?>
	</p>
	<p>
		<span class="label"><?php esc_html_e( 'Eligibility: Geographic', 'csl-grants-submissions' ); ?></span>
		<?php do_editor( '', 'elegibility_geographic' ); ?>
	</p>
	<p>
		<span class="label"><?php esc_html_e( 'Revenue Source', 'csl-grants-submissions' ); ?></span>
		<?php api_revenue_sources(); ?>
	</p>
	<p>
		<span class="label"><?php esc_html_e( 'Revenue Source Notes', 'csl-grants-submissions' ); ?></span>
		<?php do_editor( '', 'revenue_source_notes' ); ?>
	</p>
	<p>
		<span class="label"><?php esc_html_e( 'Is matched funding required?', 'csl-grants-submissions' ); ?></span>
		<label><input type="radio" name="csl_items[matched_funding]" value="no"><?php esc_html_e( 'No', 'csl-grants-submissions' ); ?></label>
		<label><input type="radio" name="csl_items[matched_funding]" value="yes"><?php esc_html_e( 'Yes ( please include percentage )', 'csl-grants-submissions' ); ?></label>
		<input type="number" name="csl_items[matched_fund_percentage]" value="">&percnt;
	</p>
	<p>
		<span class="label"><?php esc_html_e( 'Matched Funding Notes', 'csl-grants-submissions' ); ?></span>
		<?php do_editor( '', 'matched_funding_notes' ); ?>
	</p>
	<p>
		<label for="estimated_available_funding"><?php esc_html_e( 'Estimated Available Funding', 'csl-grants-submissions' ); ?></label>
		<input type="number" id="estimated_available_funding" name="csl_items[estimated_available_funding]" value="">
	</p>
	<p>
		<span class="label"><?php esc_html_e( 'Estimated Number of Awards', 'csl-grants-submissions' ); ?></span>
		<span class="group">
			<label>
				<input type="radio" name="csl_items[award_type][type]" value="exact"><?php esc_html_e( 'Exactly', 'csl-grants-submissions'); ?>
			</label>
			<input type="number" name="csl_items[award_type][exact][values]" value="">
		</span>
		<span class="group">
			<label>
				<input type="radio" name="csl_items[award_type][type][between]" value="between"><?php esc_html_e( 'Between', 'csl-grants-submissions'); ?>
			</label>
			<input type="number" name="csl_items[award_type][type][between][values][low]" value="">
			<?php esc_html_x( 'and', 'Exactly between X and Y', 'csl-grants-submissions' ); ?>
			<input type="number" name="csl_items[award_type][type][between][values][high]" value="">
		</span>
		<label><input type="radio" name="csl_items[award_type][type]" value="dependant"><?php esc_html_e( 'Dependent on number of submissions received, application process, etc...', 'csl-grants-submissions' ); ?></label>
		<input type="number" name="csl_items[award_type][estimated]" placeholder="<?php esc_html_e( 'Enter award amount(s)', 'csl-grants-submissions' ); ?>">
	</p>
	<p>
		<span class="label"><?php esc_html_e( 'Funds Disbursement Method', 'csl-grants-submissions' ); ?></span>
		<?php api_disbursement_methods(); ?>
	</p>
	<p>
		<span class="label"><?php esc_html_e( 'Funds Disbursement Details', 'csl-grants-submissions' ); ?></span>
		<input type="text" name="csl_items[disbursement_details]" value="">
	</p>
	<p>
		<span class="label"><?php esc_html_e( 'Grant Open/Close date', 'csl-grants-submissions' ); ?></span>
		<label for="open-date"><?php esc_html_e( 'Open Date', 'csl-grants-submissions' ); ?></label>
		<input type="datetime-local" id="open-date" name="csl_items[open_date]">
		<label for="close-date"><?php esc_html_e( 'Close Date', 'csl-grants-submissions' ); ?></label>
		<input type="datetime-local" id="close-date" name="csl_items[close_date]">
	</p>
	<p>
		<span class="label"><?php esc_html_e( 'Period of Performance', 'csl-grants-submissions' ); ?></span>
		<input type="number" name="csl_items[period_of_performance]">
		<select name="csl_items[period_of_performance_type]">
			<option value=""><?php esc_html_e( 'Select time unit' ); ?></option>
			<option value="day"><?php esc_html_e( 'Days', 'csl-grants-submissions' ); ?></option>
			<option value="week"><?php esc_html_e( 'Weeks', 'csl-grants-submissions' ); ?></option>
			<option value="month"><?php esc_html_e( 'Months', 'csl-grants-submissions' ); ?></option>
			<option value="year"><?php esc_html_e( 'Year', 'csl-grants-submissions' ); ?></option>
		</select>
	</p>
	<p>
		<label for="expected_award_date"><?php esc_html_e( 'Expected Award Announcement Date', 'csl-grants-submissions' ); ?></label>
		<input type="text" name="csl_items[expected_award_date]" id="expected_award_date" value="">
	</p>
	<p>
		<label for="application_deadline"><?php esc_html_e( 'Application Deadline', 'csl-grants-submissions' ); ?></label>
		<input type="text" name="csl_items[application_deadline]" value="" id="application_deadline">
	</p>
	<p>
		<span class="label"><?php esc_html_e( 'Electronic Application Submission Method', 'csl-grants-submissions' ); ?></span>
		<label for="email_submission"><?php esc_html_e( 'Email Submission', 'csl-grants-portal' ); ?></label>
		<input type="email" name="csl_items[submission_type][email]" value="" id="email_submission">
		<label for="online_submission"><?php esc_html_e( 'Online submission form', 'csl-grants-portal' ); ?></label>
		<input type="email" name="csl_items[submission_type][url]" value="" id="online_submission">
	</p>
	<p>
		<label for="full_grant_details"><?php esc_html_e( 'URL for Full Grant Details', 'csl-grants-submissions' ); ?></label>
		<input type="url" name="csl_items[full_grant_details]" id="full_grant_details" value="">
	</p>
	<p>
		<label for="grant_agency_url"><?php esc_html_e( 'URL of Grantmaking Agency/Department', 'csl-grants-submissions' ); ?></label>
		<input type="url" name="csl_items[grant_agency_url]" id="grant_agency_url" value="">
	</p>
	<p>
		<label for="url_for_grant_updates"><?php esc_html_e( 'URL to Subscribe to Grant Updates', 'csl-grants-submissions' ); ?></label>
		<input type="url" name="csl_items[url_for_grant_updates]" id="url_for_grant_updates" value="">
	</p>
	<p>
		<label for="url_planned_events"><?php esc_html_e( 'URL for Planned Events Information', 'csl-grants-submissions' ); ?></label>
		<input type="url" name="csl_items[url_planned_events]" id="url_planned_events" value="">
	</p>
	<p>
		<label for="primary_poc_name"><?php esc_html_e( 'Primary Point of Contact: Name', 'csl-grants-submission' ); ?></label>
		<input type="text" id="primary_poc_name" name="csl_items[contacts][primary][name]" value="">
	</p>
	<p>
		<label for="primary_poc_email"><?php esc_html_e( 'Primary Point of Contact: Email Address', 'csl-grants-submission' ); ?></label>
		<input type="email" id="primary_poc_email" name="csl_items[contacts][primary][email]" value="">
	</p>
	<p>
		<label for="primary_poc_phone"><?php esc_html_e( 'Primary Point of Contact: Phone', 'csl-grants-submission' ); ?></label>
		<input type="tel" id="primary_poc_phone" name="csl_items[contacts][primary][phone]" value="">
	</p>
	<p>
		<label for="primary_poc_name"><?php esc_html_e( 'Secondary Point of Contact: Name', 'csl-grants-submission' ); ?></label>
		<input type="text" id="primary_poc_name" name="csl_items[contacts][secondary][name]" value="">
	</p>
	<p>
		<label for="secondary_poc_email"><?php esc_html_e( 'Secondary Point of Contact: Email Address', 'csl-grants-submission' ); ?></label>
		<input type="email" id="secondary_poc_email" name="csl_items[contacts][secondary][email]" value="">
	</p>
	<p>
		<label for="secondary_poc_phone"><?php esc_html_e( 'Secondary Point of Contact: Phone', 'csl-grants-submission' ); ?></label>
		<input type="tel" id="secondary_poc_phone" name="csl_items[contacts][secondary][phone]" value="">
	</p>

	<?php
}
