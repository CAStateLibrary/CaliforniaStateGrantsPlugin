<?php
/**
 * Plugin Name: California State Grants
 * Plugin URI:
 * Description: This plugin provides a WordPress dashboard interface to input California State Grant information and facilitate syncing that data with the California State Grants Portal.
 * Version:     0.1.0
 * Author:      10up
 * Author URI:  https://10up.com
 * Text Domain: csl-grants-submissions
 * Domain Path: /languages
 *
 * @package CslGrantsSubmissions
 */

// Useful global constants.
define( 'CSL_GRANTS_SUBMISSIONS_VERSION', '0.1.0' );
define( 'CSL_GRANTS_SUBMISSIONS_URL', plugin_dir_url( __FILE__ ) );
define( 'CSL_GRANTS_SUBMISSIONS_PATH', plugin_dir_path( __FILE__ ) );
define( 'CSL_GRANTS_SUBMISSIONS_INC', CSL_GRANTS_SUBMISSIONS_PATH . 'includes/' );

// Include files.
require_once CSL_GRANTS_SUBMISSIONS_INC . 'functions/core.php';
require_once CSL_GRANTS_SUBMISSIONS_INC . 'functions/cpt-grants.php';

// Require Composer autoloader if it exists.
if ( file_exists( CSL_GRANTS_SUBMISSIONS_PATH . '/vendor/autoload.php' ) ) {
	require_once CSL_GRANTS_SUBMISSIONS_PATH . 'vendor/autoload.php';
} else {
	// No composer, autoload our own classes.
	spl_autoload_register(
		function( $class ) {
			$sanitized_class = str_replace( array( 'CaGov\Grants\\', '\\' ), array( '', '/' ), $class );
			$file            = CSL_GRANTS_SUBMISSIONS_INC . '/classes/' . $sanitized_class . '.php';
			if ( file_exists( $file ) ) {
				require_once $file;
			}
		}
	);
}

// Activation/Deactivation.
register_activation_hook( __FILE__, '\CslGrantsSubmissions\Core\activate' );
register_deactivation_hook( __FILE__, '\CslGrantsSubmissions\Core\deactivate' );

// Bootstrap files.
CslGrantsSubmissions\Core\setup();

// Setup Post Type.
$grants = new CaGov\Grants\PostTypes\Grants();
$grants->setup();
$edit_grant = new CaGov\Grants\PostTypes\EditGrant();
$edit_grant->setup();

// Setup Settings.
$settings = new CaGov\Grants\Admin\Settings();
$settings->setup();
$settings_page = new CaGov\Grants\Admin\SettingsPage();
$settings_page->setup();
