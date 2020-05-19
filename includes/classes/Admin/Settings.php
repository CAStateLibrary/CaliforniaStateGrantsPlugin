<?php
/**
 * Plugin Settings.
 *
 * @package CaGov\Grants
 */

namespace CaGov\Grants\Admin;

use CaGov\Grants\PostTypes\Grants;

/**
 * Settings class.
 */
class Settings {
	const OPTION_NAME = 'ca_grants_settings';

	/**
	 * Init
	 *
	 * @var boolean
	 */
	public static $init = false;

	/**
	 * Constructor.
	 */
	public function __construct() {}

	/**
	 * Setup actions and filters with the WordPress API.
	 *
	 * @return void
	 */
	public function setup() {
		if ( self::$init ) {
			return;
		}

		add_action( 'admin_init', array( $this, 'register_settings' ) );

		self::$init = true;
	}

	/**
	 * Register grant plugin setting.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			self::OPTION_NAME,
			self::OPTION_NAME,
			[
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
			]
		);
	}

	/**
	 * Sanitize settings.
	 *
	 * @param  array $settings An array of settings.
	 * @return array
	 */
	public function sanitize_settings( $settings ) {
		return array_map( 'sanitize_text_field', $settings );
	}

	/**
	 * Return password policy settings
	 *
	 * @param  string $name    Setting key
	 * @param  mixed  $default A default value to return if the setting is missing.
	 * @return string
	 */
	public function get_setting( $name = '', $default = null ) {
		$settings = get_option( self::OPTION_NAME, array() );

		if ( empty( $name ) ) {
			return $settings;
		}

		return isset( $settings[ $name ] ) ? $settings[ $name ] : $default;
	}

	/**
	 * Update setting
	 *
	 * @param  string $name  The setting name.
	 * @param  mixed  $value The setting value.
	 * @return bool
	 */
	public function update_setting( $name, $value ) {
		$settings = get_option( self::OPTION_NAME, array() );

		$settings[ $name ] = $value;

		return update_option( self::OPTION_NAME, $this->sanitize_settings( $settings ) );
	}

	/**
	 * Get endpoint url.
	 *
	 * @return string
	 */
	public function get_endpoint_url() {
		return rest_url( 'wp/v2/grants' );
	}

	/**
	 * Get auth token.
	 *
	 * Creates an auth token if the setting is missing.
	 *
	 * @return string
	 */
	public function get_auth_token() {
		$token = $this->get_setting( 'auth_token', false );

		if ( ! $token ) {
			$token = wp_generate_password( 20, false, false );
			$this->update_setting( 'auth_token', $token );
		}

		return $token;
	}

	/**
	 * Is setup complete.
	 *
	 * @static
	 * @return boolean
	 */
	public static function is_setup_complete() {
		$settings = new self();

		return Grants::get_published_count() && $settings->get_setting( 'auth_token', false );
	}

	/**
	 * Purges settings.
	 *
	 * @param  bool $hard Optional. True to hard purge all settings, false for recreatable settings.
	 * @return bool
	 */
	public static function purge_settings( $hard = false ) : bool {
		if ( $hard ) {
			return delete_option( self::OPTION_NAME );
		}

		// If we're doing a soft purge, we want to persist the auth token.
		$settings = new self();
		$token    = $settings->get_setting( 'auth_token', false );

		delete_option( self::OPTION_NAME );
		$settings->update_setting( 'auth_token', $token );

		return true;
	}
}
