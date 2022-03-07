<?php
/**
 * Plugin Name:       Post Finder
 * Plugin URI:        https://github.com/10up/post-finder
 * Description:       Adds a UI for curating and ordering posts.
 * Version:           0.4.0
 * Requires at least: 
 * Requires PHP:      
 * Author:            Micah Ernst, 10up
 * Author URI:        
 * License:           GPLv2
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

 if ( ! defined( 'ABSPATH' ) ) {
	return;
}

// Useful global constants
define( 'POST_FINDER_VERSION', '0.4.0' );
define( 'POST_FINDER_URL',     plugin_dir_url( __FILE__ ) );
define( 'POST_FINDER_PATH',    dirname( __FILE__ ) . '/' );
define( 'POST_FINDER_INC',     POST_FINDER_PATH . 'includes/' );

// Load helper functions
require_once POST_FINDER_INC . 'functions/helpers.php';

// Don't load things again if this plugin was already included elsewhere
if ( ! class_exists( 'NS_Post_Finder' ) ) {
	// Include files
	require_once POST_FINDER_INC . 'classes/NS_Post_Finder.php';

	// Init
	$post_finder = new NS_Post_Finder();
	$post_finder->init();
}
