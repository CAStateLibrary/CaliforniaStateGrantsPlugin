<?php
/**
 * Plugin Name: CslGrantsSubmissions
 * Plugin URI:
 * Description:
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
require_once CSL_GRANTS_SUBMISSIONS_INC . 'functions/metaboxes.php';

// Activation/Deactivation.
register_activation_hook( __FILE__, '\CslGrantsSubmissions\Core\activate' );
register_deactivation_hook( __FILE__, '\CslGrantsSubmissions\Core\deactivate' );

// Bootstrap.
CslGrantsSubmissions\Core\setup();
CslGrantsSubmissions\CPT\Grants\setup();
CslGrantsSubmissions\Metaboxes\setup();

// Require Composer autoloader if it exists.
if ( file_exists( CSL_GRANTS_SUBMISSIONS_PATH . '/vendor/autoload.php' ) ) {
	require_once CSL_GRANTS_SUBMISSIONS_PATH . 'vendor/autoload.php';
}
