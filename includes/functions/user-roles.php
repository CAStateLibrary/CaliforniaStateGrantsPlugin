<?php
/**
 * Responsible for the entire meta box of the edit page.
 *
 * @package CaGov\Grants
 */

namespace CaGov\Grants\User_Roles;

const CSL_USERS_VERSION = 1;

const GRANT_EDITOR_ROLE = array(
	'slug' => 'grant-editor',
	'caps' => array(
		'edit_grant',
		'edit_grants',
		'edit_published_grants',
		'edit_others_grants',
		'edit_private_grants',
		'read',
		'read_grant',
		'read_private_grants',
		'delete_grant',
		'delete_grants',
		'delete_published_grants',
		'delete_others_grants',
		'delete_private_grants',
		'publish_grants',
		'upload_files',
		'assign_grants_terms',
		'manage_grants_terms',
		'edit_grants_terms',
		'delete_grants_terms',
	),
);

const GRANT_CONTRIBUTOR_ROLE = array(
	'slug' => 'grant-contributor',
	'caps' => array(
		'edit_grant',
		'edit_grants',
		'edit_published_grants',
		'read',
		'read_grant',
		'delete_grant',
		'delete_grants',
		'delete_published_grants',
		'publish_grants',
		'upload_files',
	),
);

const CUSTOM_CORE_CAPS = array(
	'edit_grant',
	'edit_grants',
	'edit_private_grants',
	'edit_published_grants',
	'edit_others_grants',
	'read',
	'read_grant',
	'read_private_grants',
	'delete_grant',
	'delete_grants',
	'delete_private_grants',
	'delete_published_grants',
	'delete_others_grants',
	'publish_grants',
	'assign_grants_terms',
	'manage_grants_terms',
	'edit_grants_terms',
	'delete_grants_terms',
);

/**
 * Run setup hooks/filters
 */
function setup() {
	$n = function( $ns ) {
		return __NAMESPACE__ . "\\$ns";
	};

	add_action( 'add_meta_boxes', $n( 'add_metaboxes' ) );
	add_action( 'save_post', $n( 'save_post' ) );

	// Add restriction for CA.gov email addresses
	add_action( 'registration_errors', $n( 'restrict_email_address' ), 10, 3 );

	// Add meta field to user profile
	add_action( 'show_user_profile', $n( 'add_user_agency_field' ) );
	add_action( 'edit_user_profile', $n( 'add_user_agency_field' ) );
	add_action( 'user_new_form', $n( 'add_user_agency_field' ) );
	add_action( 'user_register', $n( 'save_agency_meta_field' ) );
	add_action( 'profile_update', $n( 'save_agency_meta_field' ) );

	// Add/update custom user roles and permissions
	$current_version = get_option( 'cslgrantssub_user_version' );

	// If the version isn't set, or doesn't match the class version, then run the user role updates.
	if ( false === $current_version || CSL_USERS_VERSION !== $current_version ) {
		add_action( 'init', $n( 'add_grant_contributor' ) );
		add_action( 'init', $n( 'add_grant_editor' ) );
		add_action( 'init', $n( 'update_core_role_permissions' ) );

		update_option( 'cslgrantssub_user_version', CSL_USERS_VERSION, false );
	}
}

/**
 * Restrict user registrations to ca.gov domain addresses.
 *
 * @param WP_Error $errors A WP_Error object containing existing errors.
 * @param string   $username A sanitized username
 * @param string   $user_email The user's email
 */
function restrict_email_address( $errors, $username, $user_email ) {
	if ( 0 === preg_match( '/\w+(\.ca\.gov|\@ca\.gov)$/', $user_email ) ) {
		$errors->add( 'invalid_emamil', __( '<strong>ERROR:</strong> You must register with a California Government (ca.gov) email address.', 'grantsportal' ) );
	}

	return $errors;
}

/**
 * Add an Agency field to the User Profile
 *
 * @param WP_User $user The WP_User object of the user being edited/added.
 */
function add_user_agency_field( $user ) {
	$input_disabled = ( current_user_can( 'edit_users' ) ) ? '' : 'disabled';
	$agency         = get_user_meta( $user->ID, 'csl_agency', true );
	?>

	<h2><?php esc_html_e( 'Agency', 'grantsportal' ); ?></h2>
	<table class="form-table" role="presentation">
		<tbody>
			<tr>
				<td>
					<?php wp_nonce_field( 'save_agency_data', 'save_agency_data_nonce' ); ?>
					<input id="agency" class="regular-text" name="agency" type="text" value="<?php echo esc_html( $agency ); ?>" <?php echo esc_attr( $input_disabled ); ?>/>
				</td>
			</tr>
		</tbody>
	</table>

	<?php
}

/**
 * Save the Agency meta field when a user profile is registered or updated.
 *
 * @param integer $user_id The user_id of the user being edited.
 */
function save_agency_meta_field( $user_id ) {
	if (
		isset( $_POST['save_agency_data_nonce'] ) &&
		wp_verify_nonce( $_POST['save_agency_data_nonce'], 'save_agency_data' ) &&
		current_user_can( 'edit_users' )
	) {
		update_user_meta( $user_id, 'csl_agency', sanitize_text_field( $_POST['agency'] ) );
	}
}

/**
 * Add a new role named Grant Contributor
 */
function add_grant_contributor() {
	$role = get_role( GRANT_CONTRIBUTOR_ROLE['slug'] );
	if ( is_null( $role ) ) {
		add_role( GRANT_CONTRIBUTOR_ROLE['slug'], esc_html__( 'Grant Contributor', 'grantsportal' ) );
		$role = get_role( GRANT_CONTRIBUTOR_ROLE['slug'] );
	}

	update_role( $role, GRANT_CONTRIBUTOR_ROLE['caps'] );
}

/**
 * Add a new role named Grant Contributor
 */
function add_grant_editor() {
	$role = get_role( GRANT_EDITOR_ROLE['slug'] );
	if ( is_null( $role ) ) {
		add_role( GRANT_EDITOR_ROLE['slug'], esc_html__( 'Grant Editor', 'grantsportal' ) );
		$role = get_role( GRANT_EDITOR_ROLE['slug'] );
	}

	update_role( $role, GRANT_EDITOR_ROLE['caps'] );
}

/**
 * Update Core administrator and editor roles with permissions to the Grants Post Type
 */
function update_core_role_permissions() {
	$admin_role = get_role( 'administrator' );
	if ( ! is_null( $admin_role ) ) {
		update_role( $admin_role, CUSTOM_CORE_CAPS );
	}

	$editor_role = get_role( 'editor' );
	if ( ! is_null( $editor_role ) ) {
		update_role( $editor_role, CUSTOM_CORE_CAPS );
	}
}

/**
 * Updates the capabilities for the given role.
 *
 * @param WP_Role $role The WP_Role object to update.
 * @param array   $capabilities An array of new capabilities to add to the $role.
 */
function update_role( $role, $capabilities ) {
	foreach ( $capabilities as $capability ) {
		if ( ! $role->has_cap( $capability ) ) {
			$role->add_cap( $capability );
		}
	}
}
