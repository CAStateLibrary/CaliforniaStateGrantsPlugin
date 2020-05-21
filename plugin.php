<?php
/**
 * Plugin Name: California State Grants
 * Plugin URI:  https://github.com/10up/ca-grants-plugin
 * Description: This plugin provides a WordPress dashboard interface to input California State Grant information and facilitate syncing that data with the California State Grants Portal.
 * Version:     1.0.0-beta1
 * Author:      10up
 * Author URI:  https://10up.com
 * Text Domain: ca-grants-plugin
 * Domain Path: /languages
 *
 * @package CaGov\Grants
 */

// Useful global constants.
define( 'CA_GRANTS_VERSION', '1.0.0-beta1' );
define( 'CA_GRANTS_URL', plugin_dir_url( __FILE__ ) );
define( 'CA_GRANTS_PATH', plugin_dir_path( __FILE__ ) );
define( 'CA_GRANTS_INC', CA_GRANTS_PATH . 'includes/' );
define( 'CA_GRANTS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

if ( ! defined( 'CA_GRANTS_PORTAL_URL' ) ) {
	define( 'CA_GRANTS_PORTAL_URL', '//castlg-stage.10upmanaged.com/' );
}

if ( ! defined( 'CA_GRANTS_PORTAL_JSON_URL' ) ) {
	define( 'CA_GRANTS_PORTAL_JSON_URL', CA_GRANTS_PORTAL_URL . 'wp-json/' );
}

// Include files.
require_once CA_GRANTS_INC . 'functions/core.php';
require_once CA_GRANTS_INC . 'functions/cpt-grants.php';

// Require Composer autoloader if it exists.
if ( file_exists( CA_GRANTS_PATH . '/vendor/autoload.php' ) ) {
	require_once CA_GRANTS_PATH . 'vendor/autoload.php';
} else {
	// No composer, autoload our own classes.
	spl_autoload_register(
		function( $class ) {
			$sanitized_class = str_replace( array( 'CaGov\Grants\\', '\\' ), array( '', '/' ), $class );
			$file            = CA_GRANTS_INC . '/classes/' . $sanitized_class . '.php';
			if ( file_exists( $file ) ) {
				require_once $file;
			}
		}
	);
}

// Activation/Deactivation.
register_activation_hook( __FILE__, '\CaGov\Grants\Core\activate' );
register_deactivation_hook( __FILE__, '\CaGov\Grants\Core\deactivate' );

// Bootstrap files.
CaGov\Grants\Core\setup();
CaGov\Grants\REST\setup();

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
$notices = new CaGov\Grants\Admin\Notices();
$notices->setup();
