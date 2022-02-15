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
class WelcomePage {
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
		$this->settings_page = new SettingsPage();
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
		add_action( 'admin_menu', array( $this, 'register_welcome_page' ), 1 );
		add_action( 'plugin_action_links_' . CA_GRANTS_PLUGIN_BASENAME, array( $this, 'plugin_action_link' ) );

		self::$init = true;
	}

	/**
	 * Is visible.
	 *
	 * @return boolean
	 */
	public function is_visible() {
		return ! $this->settings_page->is_visible();
	}

	/**
	 * Register settings page.
	 *
	 * @return void
	 */
	public function register_welcome_page() {
		if ( ! $this->is_visible() ) {
			return;
		}

		add_submenu_page(
			'edit.php?post_type=' . GRANTS::get_cpt_slug(),
			esc_html__( 'California State Grants Plugin', 'ca-grants-plugin' ),
			esc_html__( 'Getting Started', 'ca-grants-plugin' ),
			'manage_options',
			'welcome',
			array( $this, 'render_page' ),
			0
		);
	}

	/**
	 * Render welcome page.
	 *
	 * @return void
	 */
	public function render_page() {
		?>
		<div class="wrap grants-setting-page">
			<div class="grants-setting-page--header">
				<h1><?php esc_html_e( 'California State Grants Plugin', 'ca-grants-plugin' ); ?></h1>
			</div>
			<?php $this->settings_page->render_intro(); ?>
			<p>
				<?php esc_html_e( 'Once you have created your first grant using the plugin, you will have access to a Settings screen providing your system-generated endpoint URL and authorization token, which you will then provide to the grants portal in order to submit your data.', 'ca-grants-plugin' ); ?>
			</p>
			<p>
			<?php
				printf(
					'%s <a href="%s">%s</a>',
					esc_html__( 'Ready to', 'ca-grants-plugin' ),
					esc_url( admin_url( 'post-new.php?post_type=' . Grants::get_cpt_slug() ) ),
					esc_html__( 'add your first grant?', 'ca-grants-plugin' )
				);
			?>
			</p>
		</div>
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

		$link = sprintf( '<a href="%s">%s</a>', esc_url( self::url() ), esc_html( 'Getting Started', 'ca-grants-plugin' ) );
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
				'page'      => 'welcome',
			),
			admin_url( 'edit.php' )
		);
	}
}
