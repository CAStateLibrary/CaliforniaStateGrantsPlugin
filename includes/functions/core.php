<?php
/**
 * Core plugin functionality.
 *
 * @package CaGov\Grants
 */

namespace CaGov\Grants\Core;

use CaGov\Grants\Admin\Settings;

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
		'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css',
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
