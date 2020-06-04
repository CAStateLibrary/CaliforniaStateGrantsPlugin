<?php
/**
 * Plugin Name: California State Grants
 * Plugin URI:  https://github.com/10up/ca-grants-plugin
 * Description: This plugin provides a WordPress dashboard interface to input California State Grant information and facilitate syncing that data with the California State Grants Portal.
 * Version:     0.2.0
 * Author:      10up
 * Author URI:  https://10up.com
 * Text Domain: ca-grants-plugin
 * Domain Path: /languages
 *
 * @package CaGov\Grants
 */

// Useful global constants.
define( 'CA_GRANTS_VERSION', '0.2.0' );
define( 'CA_GRANTS_URL', plugin_dir_url( __FILE__ ) );
define( 'CA_GRANTS_PATH', plugin_dir_path( __FILE__ ) );
define( 'CA_GRANTS_INC', CA_GRANTS_PATH . 'includes/' );
define( 'CA_GRANTS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'CA_GRANTS_SOURCE_REPO', 'https://github.com/10up/ca-grants-plugin' );

if ( ! defined( 'CA_GRANTS_PORTAL_URL' ) ) {
	define( 'CA_GRANTS_PORTAL_URL', 'https://castlg-stage.10upmanaged.com/' );
}

if ( ! defined( 'CA_GRANTS_PORTAL_JSON_URL' ) ) {
	define( 'CA_GRANTS_PORTAL_JSON_URL', CA_GRANTS_PORTAL_URL . 'wp-json/' );
}

// Include files.
require_once CA_GRANTS_INC . 'functions/core.php';

// Require Composer autoloader if it exists.
if ( file_exists( CA_GRANTS_PATH . '/vendor/autoload.php' ) ) {
	require_once CA_GRANTS_PATH . 'vendor/autoload.php';
} else {
	// No composer, autoload our own classes.
	spl_autoload_register( 'ca_grants_plugin_autoload' );
}

// Activation/Deactivation.
register_activation_hook( __FILE__, '\CaGov\Grants\Core\activate' );
register_deactivation_hook( __FILE__, '\CaGov\Grants\Core\deactivate' );

// Bootstrap files.
CaGov\Grants\Core\setup();

/**
 * Plugin autoload callback
 *
 * @param  string $class The class to autoload.
 * @return void
 */
function ca_grants_plugin_autoload( $class ) {
	$sanitized_class = str_replace( array( 'CaGov\Grants\\', '\\' ), array( '', '/' ), $class );
	$file            = CA_GRANTS_INC . '/classes/' . $sanitized_class . '.php';
	if ( file_exists( $file ) ) {
		require_once $file;
	}
}

/**
 * Plugin updater.
 */
function ca_grants_enable_updates() {
	if ( ! class_exists( 'Puc_v4_Factory' ) ) {
		return;
	}

	$plugin_settings = get_option( CaGov\Grants\Admin\Settings::OPTION_NAME );
	$updater         = Puc_v4_Factory::buildUpdateChecker(
		CA_GRANTS_SOURCE_REPO,
		__FILE__,
		'ca-grants-plugin'
	);

	if ( is_array( $plugin_settings ) && isset( $plugin_settings['update_token'] ) ) {
		$updater->setAuthentication( $plugin_settings['update_token'] );
	}

	$updater->setBranch( 'master' );

	return $updater;
}

/**
 * Plugin setup.
 *
 * @return array Array of intialized class instances.
 */
function ca_grants_plugin_setup() {
	$classes = array(
		'CaGov\Grants\PostTypes\Grants',
		'CaGov\Grants\PostTypes\EditGrant',
		'CaGov\Grants\Admin\Settings',
		'CaGov\Grants\Admin\SettingsPage',
		'CaGov\Grants\Admin\WelcomePage',
		'CaGov\Grants\Admin\Notices',
		'CaGov\Grants\REST\GrantsEndpoint',
	);

	return array_map(
		function( $class ) {
			$instance = new $class();
			$instance->setup();
			return $instance;
		},
		$classes
	);
}

// Setup the plugin.
ca_grants_plugin_setup();

// Enable updates.
ca_grants_enable_updates();
