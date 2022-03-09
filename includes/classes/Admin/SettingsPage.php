<?php
/**
 * Setting Page.
 *
 * @package CaGov\Grants
 */

namespace CaGov\Grants\Admin;

use CaGov\Grants\PostTypes\Grants;

/**
 * SettingsPage Class
 */
class SettingsPage {
	/**
	 * Init.
	 *
	 * @var boolean
	 */
	public static $init = false;

	/**
	 * Settings
	 *
	 * @var Settings
	 */
	public $settings;

	/**
	 * Steps
	 *
	 * @var array
	 */
	public $steps;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->settings = new Settings();
	}

	/**
	 * Register actions and filters with WordPress API.
	 *
	 * @return void
	 */
	public function setup() {
		if ( self::$init ) {
			return;
		}
		add_action( 'admin_menu', array( $this, 'register_settings_page' ), 1 );
		add_action( 'current_screen', array( $this, 'maybe_handle_submit' ) );
		add_action( 'plugin_action_links_' . CA_GRANTS_PLUGIN_BASENAME, array( $this, 'plugin_action_link' ) );

		self::$init = true;
	}

	public function is_visible() {
		return Grants::get_published_count();
	}

	/**
	 * Register settings page.
	 *
	 * @return void
	 */
	public function register_settings_page() {
		if ( ! $this->is_visible() ) {
			return;
		}

		add_submenu_page(
			'edit.php?post_type=' . GRANTS::get_cpt_slug(),
			esc_html__( 'Settings', 'ca-grants-plugin' ),
			esc_html__( 'Settings', 'ca-grants-plugin' ),
			'manage_options',
			'settings',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Handles submitting the settings page.
	 *
	 * @return void
	 */
	public function maybe_handle_submit() {
		if ( 'ca_grants_page_settings' !== get_current_screen()->id ) {
			return;
		}

		$nonce   = filter_input( INPUT_POST, 'ca_grants_nonce', FILTER_SANITIZE_STRING );
		$reset   = filter_input( INPUT_POST, 'ca_grants_settings_submit', FILTER_SANITIZE_STRING );
		$updater = filter_input( INPUT_POST, 'ca_grants_update_token', FILTER_SANITIZE_STRING );

		if ( ! wp_verify_nonce( $nonce, 'ca_grants_settings' ) ) {
			return;
		}

		if ( $updater ) {
			$this->settings->update_setting( 'update_token', $updater );
		}

		if ( 'Reset Settings' === $reset ) {
			$this->settings->purge_settings( true );
		}
	}


	/**
	 * Render settings page.
	 *
	 * @return void
	 */
	public function render_page() {
		?>
		<div class="wrap grants-setting-page">
			<div class="grants-setting-page--header">
				<h1><?php esc_html_e( 'California State Grants Plugin Settings', 'ca-grants-plugin' ); ?></h1>
			</div>

			<form class="grants-setting-page--content" method="post">
			<?php
				wp_nonce_field( 'ca_grants_settings', 'ca_grants_nonce' );
				$this->render_intro();
				$this->render_submit_endpoint();
				$this->submit_buttons();
			?>
			</form>
		</div>
		<?php
	}


	/**
	 * Renders the wizard intro.
	 *
	 * @return void
	 */
	public function render_intro() {
		?>
		<p>
			<?php esc_html_e( 'This plugin makes it easy for state agencies using WordPress to submit grant data directly to the California State Grants Portal. The plugin uses:', 'ca-grants-plugin' ); ?>
			<ol>
				<li><?php esc_html_e( 'An endpoint where the portal can automatically fetch your grant data every 24 hours', 'ca-grants-plugin' ); ?></li>
				<li><?php esc_html_e( 'A token unique to your site that the portal will use to authenticate syncs', 'ca-grants-plugin' ); ?></li>
			</ol>
		</p>
		<?php
	}

	/**
	 * Renders the submit endpoint wizard step.
	 *
	 * @return void
	 */
	public function render_submit_endpoint() {
		$submit_endpoint_url = CA_GRANTS_PORTAL_URL . 'submit-an-endpoint';
		$remote_validation   = $this->settings->get_setting( 'remote_validation' );
		?>
		<h2><?php echo esc_html_e( 'Submit Endpoint', 'ca-grants-plugin' ); ?></h2>
		<p>
			<?php esc_html_e( 'Please follow the detailed instructions provided in the ', 'ca-grants-plugin' ); ?>
			<a href="<?php echo esc_url( CA_GRANTS_PORTAL_URL . 'part-iii-submitting-updating-and-maintaining-information/' ); ?>">
				<?php esc_html_e( 'State Grantmakers Guide', 'ca-grants-plugin' ); ?>
			</a>
			<?php esc_html_e( ' to submit an endpoint.', 'ca-grants-plugin' ); ?>
		</p>
		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th><label for="ca_grants_endpoint"><?php echo esc_html_e( 'Endpoint URL', 'ca-grants-plugin' ); ?></label></th>
					<td>
						<input name="ca_grants_endpoint" id="ca_grants_endpoint" type="text" value="<?php echo esc_attr( $this->settings->get_endpoint_url() ); ?>" class="regular-text code" disabled>
						<a href="javascript:void(0)" class="copy-clipboard" data-input-target="ca_grants_endpoint"><?php esc_html_e( 'Copy' ); ?></a>
					</td>
				</tr>
				<tr>
					<th><label for="ca_grants_auth_token"><?php echo esc_html_e( 'Authorization Token', 'ca-grants-plugin' ); ?></label></th>
					<td>
						<input name="ca_grants_auth_token" id="ca_grants_auth_token" type="text" value="<?php echo esc_attr( $this->settings->get_auth_token() ); ?>" class="regular-text code" disabled>
						<a href="javascript:void(0)" class="copy-clipboard" data-input-target="ca_grants_auth_token"><?php esc_html_e( 'Copy' ); ?></a>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Renders a contextual continue button.
	 *
	 * @return void
	 */
	public function submit_buttons() {
		?>
		<p class="submit">
			<span>
			<?php
				submit_button(
					__( 'Update Settings', 'ca-grants-plugin' ),
					'primary',
					'ca_grants_settings_submit',
					false,
					array(
						'tabindex' => '1',
					)
				);
			?>
			</span>
			<span style="margin-left:16px">
			<?php
				submit_button(
					__( 'Reset Settings', 'ca-grants-plugin' ),
					'secondary',
					'ca_grants_settings_submit',
					false,
					array(
						'tabindex' => '1',
					)
				);
			?>
			</span>
		</p>
		<p>
			<em><?php esc_html_e( 'The only time you should re-enter your endpoint URL or authorization token on the portal is if you use the Reset Settings button to generate a new token.', 'ca-grants-plugin' ); ?></em>
		</p>
		<?php
	}

	/**
	 * Plugin action link
	 *
	 * @param  string $links Links on plugin page.
	 * @return string
	 */
	public function plugin_action_link( $links ) {
		if ( ! $this->is_visible() ) {
			return $links;
		}

		$link = sprintf( '<a href="%s">%s</a>', esc_url( self::url() ), esc_html( 'Settings', 'ca-grants-plugin' ) );
		return array_merge( array( $link ), $links );
	}

	/**
	 * Returns the url of the settings page.
	 *
	 * @return string
	 */
	public static function url() {
		return add_query_arg(
			array(
				'post_type' => Grants::get_cpt_slug(),
				'page'      => 'settings',
			),
			admin_url( 'edit.php' )
		);
	}
}
