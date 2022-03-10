<?php
/**
 * Core plugin functionality.
 *
 * @package CaGov\Grants
 */

namespace CaGov\Grants\Core;

use CaGov\Grants\Admin\Settings;
use CaGov\Grants\Helpers\Validators;
use CaGov\Grants\PostTypes\GrantAwards;
use DateTime;
use WP_Query;
use \WP_Error as WP_Error;

/**
 * Default setup routine
 *
 * @return void
 */
function setup() {
	$n = function( $function ) {
		return __NAMESPACE__ . "\\$function";
	};

	add_action( 'init', $n( 'i18n' ) );
	add_action( 'init', $n( 'init' ) );
	add_action( 'wp_enqueue_scripts', $n( 'scripts' ) );
	add_action( 'wp_enqueue_scripts', $n( 'styles' ) );
	add_action( 'admin_enqueue_scripts', $n( 'admin_scripts' ) );
	add_action( 'admin_enqueue_scripts', $n( 'admin_styles' ) );
	add_action(
		'post_edit_form_tag',
		function() {
			echo ' class="form--validate"';
		}
	);

	add_action( 'tiny_mce_before_init', $n( 'tiny_mce_before_init' ) );

	// Hook to allow async or defer on asset loading.
	add_filter( 'script_loader_tag', $n( 'script_loader_tag' ), 10, 2 );

	do_action( 'csl_grants_submissions_loaded' );
}

/**
 * Registers the default textdomain.
 *
 * @return void
 */
function i18n() {
	$locale = apply_filters( 'plugin_locale', get_locale(), 'ca-grants-plugin' );
	load_textdomain( 'ca-grants-plugin', WP_LANG_DIR . '/ca-grants-plugin/ca-grants-plugin-' . $locale . '.mo' );
	load_plugin_textdomain( 'ca-grants-plugin', false, plugin_basename( CA_GRANTS_PATH ) . '/languages/' );
}

/**
 * Initializes the plugin and fires an action other plugins can hook into.
 *
 * @return void
 */
function init() {
	do_action( 'csl_grants_submissions_init' );
}

/**
 * Activate the plugin
 *
 * @return void
 */
function activate() {
	// First load the init scripts in case any rewrite functionality is being loaded
	init();
	flush_rewrite_rules();
}

/**
 * Deactivate the plugin
 *
 * Uninstall routines should be in uninstall.php
 *
 * @return void
 */
function deactivate() {
	Settings::purge_settings();
}


/**
 * The list of knows contexts for enqueuing scripts/styles.
 *
 * @return array
 */
function get_enqueue_contexts() {
	return [ 'admin', 'frontend', 'shared' ];
}

/**
 * Generate an URL to a script, taking into account whether SCRIPT_DEBUG is enabled.
 *
 * @param string $script Script file name (no .js extension)
 * @param string $context Context for the script ('admin', 'frontend', or 'shared')
 *
 * @return string|WP_Error URL
 */
function script_url( $script, $context ) {

	if ( ! in_array( $context, get_enqueue_contexts(), true ) ) {
		return new WP_Error( 'invalid_enqueue_context', 'Invalid $context specified in CaGov\Grants script loader.' );
	}

	return CA_GRANTS_URL . "dist/js/${script}.js";

}

/**
 * Generate an URL to a stylesheet, taking into account whether SCRIPT_DEBUG is enabled.
 *
 * @param string $stylesheet Stylesheet file name (no .css extension)
 * @param string $context Context for the script ('admin', 'frontend', or 'shared')
 *
 * @return string URL
 */
function style_url( $stylesheet, $context ) {

	if ( ! in_array( $context, get_enqueue_contexts(), true ) ) {
		return new WP_Error( 'invalid_enqueue_context', 'Invalid $context specified in CaGov\Grants stylesheet loader.' );
	}

	return CA_GRANTS_URL . "dist/css/${stylesheet}.css";

}

/**
 * Enqueue scripts for front-end.
 *
 * @return void
 */
function scripts() {

	wp_enqueue_script(
		'csl_grants_submissions_shared',
		script_url( 'shared', 'shared' ),
		[],
		CA_GRANTS_VERSION,
		true
	);

	wp_enqueue_script(
		'csl_grants_submissions_frontend',
		script_url( 'frontend', 'frontend' ),
		[],
		CA_GRANTS_VERSION,
		true
	);

}

/**
 * Enqueue scripts for admin.
 *
 * @return void
 */
function admin_scripts() {

	wp_enqueue_script(
		'csl_grants_submissions_shared',
		script_url( 'shared', 'shared' ),
		[],
		CA_GRANTS_VERSION,
		true
	);

	wp_enqueue_script(
		'csl_grants_submissions_admin',
		script_url( 'admin', 'admin' ),
		[],
		CA_GRANTS_VERSION,
		true
	);

	wp_enqueue_script( 'jquery-ui-datepicker' );
}

/**
 * Enqueue styles for front-end.
 *
 * @return void
 */
function styles() {

	wp_enqueue_style(
		'csl_grants_submissions_shared',
		style_url( 'shared-style', 'shared' ),
		[],
		CA_GRANTS_VERSION
	);

	if ( is_admin() ) {
		wp_enqueue_style(
			'csl_grants_submissions_admin',
			style_url( 'admin-style', 'admin' ),
			[],
			CA_GRANTS_VERSION
		);
	} else {
		wp_enqueue_style(
			'csl_grants_submissions_frontend',
			style_url( 'style', 'frontend' ),
			[],
			CA_GRANTS_VERSION
		);
	}
}

/**
 * Enqueue styles for admin.
 *
 * @return void
 */
function admin_styles() {

	wp_enqueue_style(
		'csl_grants_submissions_shared',
		style_url( 'shared-style', 'shared' ),
		[],
		CA_GRANTS_VERSION
	);

	wp_enqueue_style(
		'csl_grants_submissions_admin',
		style_url( 'admin-style', 'admin' ),
		[],
		CA_GRANTS_VERSION
	);

	wp_enqueue_style(
		'jquery_datepicker_css',
		style_url( 'jquery-ui', 'shared' ),
		array(),
		CA_GRANTS_VERSION
	);
}

/**
 * Add async/defer attributes to enqueued scripts that have the specified script_execution flag.
 *
 * @link https://core.trac.wordpress.org/ticket/12009
 * @param string $tag    The script tag.
 * @param string $handle The script handle.
 * @return string
 */
function script_loader_tag( $tag, $handle ) {
	$script_execution = wp_scripts()->get_data( $handle, 'script_execution' );

	if ( ! $script_execution ) {
		return $tag;
	}

	if ( 'async' !== $script_execution && 'defer' !== $script_execution ) {
		return $tag; // _doing_it_wrong()?
	}

	// Abort adding async/defer for scripts that have this script as a dependency. _doing_it_wrong()?
	foreach ( wp_scripts()->registered as $script ) {
		if ( in_array( $handle, $script->deps, true ) ) {
			return $tag;
		}
	}

	// Add the attribute if it hasn't already been added.
	if ( ! preg_match( ":\s$script_execution(=|>|\s):", $tag ) ) {
		$tag = preg_replace( ':(?=></script>):', " $script_execution", $tag, 1 );
	}

	return $tag;
}

/**
 * Filters the TinyMCE config before init.
 *
 * @param array $mce_init An array with TinyMCE config.
 * @return array
 */
function tiny_mce_before_init( $mce_init ) {

	$mce_init['setup'] = "function(editor) {
		console.log( 'setting up editor' );
		editor.on('keyup', function( event ) {
			window.parent.postMessage( JSON.stringify({
				type: 'editor.keyup',
				dataId: event.target.getAttribute( 'data-id' ),
				textContent: event.target.textContent
			}), '*' );
		});
	}";

	return $mce_init;
}

/**
 * Checks for definition of the `CSL_IS_PORTAL` constant,
 * which indicates whether the environment is the CSL portal site.
 *
 * @note This function is used to enable/disable core CA Grants plugin behavior,
 *       and will not function on any other WordPress installation.
 *
 * @return boolean true if `CSL_IS_PORTAL` constant is defined, and has a boolean value of `true`.
 */
function is_portal() {
	if ( defined( 'CSL_IS_PORTAL' ) && true === CSL_IS_PORTAL ) {
		return true;
	}
	return false;
}

/**
 * Gets the REST namespace for the custom endpoints.
 *
 * @return string
 */
function get_rest_namespace() {
	return 'ca-grants/v1';
}

/**
 * Retrieve the raw response from a safe HTTP request using the POST method with multipart form submission.
 * This one supports sending files in $_FILES data.
 *
 * Note: WP Core currently doesn't support direct file upload or multipart form submission from http request.
 * Core ref links:
 * https://github.com/WordPress/Requests/pull/313 ( Once this PR is merged we can update or remove this function )
 * https://core.trac.wordpress.org/ticket/35388
 *
 * @param string $url  URL to retrieve.
 * @param array  $args Body POST args to send in request.
 * @param string $file_name Form submitted filename to access from global $_FILES data.
 *
 * @return array|WP_Error The response or WP_Error on failure.
 */
function wp_safe_remote_post_multipart( $url, $args, $file_name ) {

	$file = $_FILES[ $file_name ] ?? array();

	if ( empty( $file ) || empty( $file['tmp_name'] ) ) {
		return new WP_Error(
			'request_empty_filename',
			__( 'Filename not found or invalid to access from $_FILES data.', 'ca-grants-plugin' )
		);
	}

	$boundary = sha1( time() );
	$payload  = '';

	// First, add the standard POST fields:
	foreach ( $args as $key => $value ) {
		$payload .= '--' . $boundary;
		$payload .= "\r\n";
		$payload .= 'Content-Disposition: form-data; name="' . $key . '"' . "\r\n\r\n";
		$payload .= $value;
		$payload .= "\r\n";
	}

	$payload .= '--' . $boundary;
	$payload .= "\r\n";
	$payload .= 'Content-Disposition: form-data; name="' . $file_name . '"; filename="' . $file['name'] . '"' . "\r\n";
	$payload .= 'Content-Type: ' . $file['type'] . "\r\n";
	$payload .= 'Content-Transfer-Encoding: binary' . "\r\n";
	$payload .= "\r\n";
	$payload .= file_get_contents( $file['tmp_name'] );
	$payload .= "\r\n";

	$payload .= '--' . $boundary . '--';
	$payload .= "\r\n\r\n";

	$post_args = array(
		'cookies'   => wp_unslash( $_COOKIE ),
		'headers'   => array(
			'Content-type'        => "multipart/form-data; boundary=$boundary",
			'Content-Disposition' => 'form-data; filename="' . $file['name'] . '"',
			'X-WP-Nonce'          => wp_create_nonce( 'wp_rest' ),
			'Content-Length'      => strlen( $payload ),
		),
		'sslverify' => false,
		'body'      => $payload,
	);

	return wp_safe_remote_post( $url, $post_args );
}

/**
 * Helper function to check if grant have atleast one award data.
 *
 * @param int $grant_id Grant ID.
 *
 * @return boolean
 */
function has_grant_awards( $grant_id ) {

	$query_args = array(
		'post_type'              => GrantAwards::CPT_SLUG,
		'post_status'            => 'publish',
		'posts_per_page'         => 1,
		'no_found_rows'          => true,
		'orderby'                => 'date',
		'order'                  => 'order',
		'update_post_term_cache' => false,
		'meta_key'               => 'grantID',
		'meta_value'             => $grant_id,
	);

	$posts = new WP_Query( $query_args );

	return ! empty( $posts->posts );
}

/**
 * Check if given grant is ongoing grant or not.
 *
 * @param int $grant_id Grant ID
 *
 * @return boolean Return true for ongoing grant and false if its closed.
 */
function is_ongoing_grant( $grant_id ) {
	$isForecasted = get_post_meta( $grant_id, 'isForecasted', true );

	return ! empty( empty( $isForecasted ) ) && 'active' === $isForecasted;
}

/**
 * Get fiscal year for given datetime. ( default return current date fiscal year )
 *
 * Identify fiscal year based on given date and return value.
 * i.e if given date is 10th Feb 2022 the fiscal year
 * would be 2021-2022 since the date is < 1st July 2022 and > 30th July 2021
 * if date would have been > 1st July 2022 we would check next year 30th July date as end date.
 *
 * @return string Fiscal year. i.e `2021-2022`
 */
function get_fiscal_year( $datetime = 'now' ) {
	$current_date = new DateTime( $datetime, wp_timezone() );
	$current_year = $current_date->format( 'Y' );

	$fiscal_start_date = DateTime::createFromFormat( 'Y-m-d H:i:s', $current_year . '-07-01 00:00:00' );

	// Check if previous fiscal year is yet to be ended. i.e current date is 10th-Feb-2022 so fiscal start year would be 2021 as it's < 1st-July-2022.
	if ( $current_date < $fiscal_start_date ) {
		$prev_year         = ( $current_year - 1 );
		$fiscal_end_date   = $fiscal_start_date;
		$fiscal_start_date = DateTime::createFromFormat( 'Y-m-d H:i:s', $prev_year . '-07-01 00:00:00' );
	} else {
		$next_year       = ( $current_year + 1 );
		$fiscal_end_date = DateTime::createFromFormat( 'Y-m-d H:i:s', $next_year . '-06-30 00:00:00' );
	}

	return $fiscal_start_date->format( 'Y' ) . '-' . $fiscal_end_date->format( 'Y' );
}
