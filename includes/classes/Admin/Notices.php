<?php
/**
 * Plugin Admin Notices.
 *
 * @package CaGov\Grants
 */

namespace CaGov\Grants\Admin;

use CaGov\Grants\PostTypes\Grants;

/**
 * Notices class.
 */
class Notices {
	/**
	 * Init
	 *
	 * @var boolean
	 */
	public static $init = false;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->settings = new Settings();
	}

	/**
	 * Setup actions and filters with the WordPress API.
	 *
	 * @return void
	 */
	public function setup() {
		if ( self::$init ) {
			return;
		}

		add_action( 'admin_notices', array( $this, 'maybe_prompt_setup' ) );
		add_action( 'admin_notices', array( $this, 'maybe_prompt_after_first_grant' ) );

		self::$init = true;
	}

	/**
	 * Prompt user to run intial setup.
	 *
	 * @return void
	 */
	public function maybe_prompt_setup() {
		if ( $this->settings->get_setting( 'auth_token' ) || Grants::get_published_count() || ! $this->can_notify_user() ) {
			return;
		}
		?>
		<div class="notice notice-warn">
			<p>
				<?php esc_html_e( 'Thanks for installing the California State Grants Plugin.', 'ca-grants-plugin' ); ?>
				<a href="<?php echo esc_url( SettingsPage::url() ); ?>">
					<?php esc_html_e( 'Click here to begin setup', 'ca-grants-plugin' ); ?>
				</a>
				<?php esc_html_e( ' and get started publishing grants to the Grants Portal.', 'ca-grants-plugin' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Prompt user to continue setup after publishing first grant.
	 *
	 * @return void
	 */
	public function maybe_prompt_after_first_grant() {
		if ( $this->settings->get_setting( 'auth_token' ) || ! Grants::get_published_count() || ! $this->can_notify_user() ) {
			return;
		}
		?>
		<div class="notice notice-warn">
			<p>
				<?php esc_html_e( 'You have published a grant, but you still need to continue with the setup to have the California State Grants Portal syncronize your grant data.', 'ca-grants-plugin' ); ?>
				<a href="<?php echo esc_url( SettingsPage::url() ); ?>">
					<?php esc_html_e( 'Click here to continue with setup.', 'ca-grants-plugin' ); ?>
				</a>
			</p>
		</div>
		<?php
	}

	/**
	 * Can notify user.
	 *
	 * @return boolean
	 */
	public function can_notify_user() {
		return current_user_can( 'manage_options' );
	}
}
