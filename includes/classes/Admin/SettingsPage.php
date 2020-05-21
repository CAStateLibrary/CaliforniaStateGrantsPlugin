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
		$this->settings    = new Settings();
		$this->steps       = array(
			'intro',
			'add_grant',
			'submit_endpoint',
			'complete',
		);
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

	/**
	 * Register settings page.
	 *
	 * @return void
	 */
	public function register_settings_page() {
		add_submenu_page(
			'edit.php?post_type=' . GRANTS::CPT_SLUG,
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

		$step  = filter_input( INPUT_POST, 'wizard_step', FILTER_SANITIZE_STRING );
		$nonce = filter_input( INPUT_POST, 'ca_grants_nonce', FILTER_SANITIZE_STRING );
		$reset = filter_input( INPUT_POST, 'ca_grants_settings_submit', FILTER_SANITIZE_STRING );

		if ( ! in_array( $step, $this->steps, true ) || ! wp_verify_nonce( $nonce, $step ) ) {
			return;
		}

		if ( 'Reset Settings' === $reset ) {
			$this->settings->purge_settings( true );
			$this->settings->update_setting( 'wizard_step', $this->steps[0] );
			return;
		}

		$this->settings->update_setting( 'wizard_step', $this->get_next_step( $step ) );
	}

	/**
	 * Get current step.
	 *
	 * @return string
	 */
	public function get_current_step() {
		return $this->settings->get_setting( 'wizard_step', $this->steps[0] );
	}

	/**
	 * Get next step.
	 *
	 * @param  string $step Optional. The step to start from.
	 * @return string
	 */
	public function get_next_step( $step = null ) {
		if ( ! $step ) {
			$step = $this->get_current_step();
		}
		$current = array_search( $step, $this->steps, true );
		$max     = count( $this->steps ) - 1;

		return $this->steps[ min( ++$current, $max ) ];
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
				<?php wp_nonce_field( $this->get_current_step(), 'ca_grants_nonce' ); ?>
				<input type="hidden" name="wizard_step" value="<?php echo esc_attr( $this->get_current_step() ); ?>" />

				<div class="grants-setting-page--wizard">
					<?php $this->render_wizard(); ?>
				</div>

				<?php $this->continue_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Renders the wizard up to the current step.
	 *
	 * @return void
	 */
	public function render_wizard() {
		$current_step = $this->get_current_step();

		foreach ( $this->steps as $step ) {
			call_user_func( array( $this, 'render_wizard_' . $step ) );

			if ( $step === $current_step ) {
				break;
			}
		}
	}

	/**
	 * Renders the wizard intro.
	 *
	 * @return void
	 */
	public function render_wizard_intro() {
		?>
		<h2><?php echo esc_html_e( 'Getting Started', 'ca-grants-plugin' ); ?></h2>
		<p>
			<?php esc_html_e( 'The California State Grants Portal offers this plugin to make it easy for state agencies and departments using WordPress to create grants and syncronize them with the central portal. To get started using this plugin, we create an endpoint where the Grants Portal can fetch your grant data and a unique token for your site. The Grants Portal will use this token to authenticate with this site when it attempts to syncronize with the grants you create.', 'ca-grants-plugin' ); ?>
		</p>
		<?php
	}

	/**
	 * Renders the add a grant wizard step.
	 *
	 * @return void
	 */
	public function render_wizard_add_grant() {
		?>
		<h2><?php echo esc_html_e( 'Add Grants', 'ca-grants-plugin' ); ?></h2>
		<?php if ( Grants::get_published_count() ) : ?>
		<p>
			<?php
			echo esc_html(
				sprintf(
					/* translators: %d is the number of published grants */
					_n(
						'The Grants Portal will sync the %d grant that you have published.',
						'The Grants Portal will sync the %d grants that you have published.',
						Grants::get_published_count(),
						'ca-grants-plugin'
					),
					Grants::get_published_count()
				)
			);
			?>
		</p>
		<?php else : ?>
		<p>
			<?php esc_html_e( 'Time to add your first grant!', 'ca-grants-plugin' ); ?>
			<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . Grants::CPT_SLUG ) ); ?>">
				<?php esc_html_e( 'Click here to add a grant.', 'ca-grants-plugin' ); ?>
			</a>
		</p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Renders the submit endpoint wizard step.
	 *
	 * @return void
	 */
	public function render_wizard_submit_endpoint() {
		$submit_endpoint_url = CA_GRANTS_PORTAL_URL . 'submit-an-endpoint';
		?>
		<h2><?php echo esc_html_e( 'Submit Endpoint', 'ca-grants-plugin' ); ?></h2>
		<p>
			<?php esc_html_e( 'Enter the following details when registering your endpoint on the', 'ca-grants-plugin' ); ?>
			<a href="<?php echo esc_url( $submit_endpoint_url ); ?>">
				<?php echo esc_html_e( 'Grants Portal:', 'ca-grants-plugin' ); ?>
			</a>
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
	 * Renders the completed wizard step.
	 *
	 * @return void
	 */
	public function render_wizard_complete() {
		?>
		<p>
			<?php esc_html_e( 'Setup is complete. The Grants Portal will periodically fetch your published grant data. Newly published grants will be automatically synced with the portal the next time the Portal fetches your grant data.  If you need to re-run installation, for example if you need to reset your authorization token, you can reset the plugin settings below.', 'ca-grants-plugin' ); ?>
		</p>
		<?php
	}

	/**
	 * Renders a contextual continue button.
	 *
	 * @return void
	 */
	public function continue_button() {
		if ( 'complete' === $this->get_current_step() ) {
			submit_button(
				__( 'Reset Settings', 'ca-grants-plugin' ),
				'primary',
				'ca_grants_settings_submit',
				true,
				array(
					'tabindex' => '1',
				)
			);
			return;
		}

		if ( 'add_grant' === $this->get_current_step() && ! Grants::get_published_count() ) {
			echo sprintf(
				'<a href="%s" class="button button-primary">%s</a>',
				esc_url( admin_url( 'post-new.php?post_type=' . Grants::CPT_SLUG ) ),
				esc_html__( 'Add a grant', 'ca-grants-plugin' )
			);
			return;
		}

		submit_button(
			__( 'Continue', 'ca-grants-plugin' ),
			'primary',
			'ca_grants_settings_submit',
			true,
			array(
				'tabindex' => '1',
			)
		);
	}

	/**
	 * Plugin action link
	 *
	 * @param  string $links Links on plugin page.
	 * @return string
	 */
	public function plugin_action_link( $links ) {
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
				'post_type' => Grants::CPT_SLUG,
				'page'      => 'settings',
			),
			admin_url( 'edit.php' )
		);
	}
}
