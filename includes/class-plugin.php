<?php
/**
 * Main plugin controller.
 *
 * @package Pyxd_Draping_For_WooCommerce
 * @author  Kevin Brent
 */

namespace KevinBrent\PyxdDraping;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Coordinates the plugin's admin and storefront integrations.
 */
final class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Get the plugin instance.
	 *
	 * @return Plugin
	 */
	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initialize the plugin.
	 */
	private function __construct() {
		add_action( 'before_woocommerce_init', [ $this, 'declare_compatibility' ] );

		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action( 'admin_notices', [ $this, 'woocommerce_missing_notice' ] );
			return;
		}

		require_once KBPYXD_PLUGIN_PATH . 'includes/class-admin.php';
		require_once KBPYXD_PLUGIN_PATH . 'includes/class-frontend.php';

		Admin::register();
		Frontend::register();

		add_action( 'init', [ $this, 'load_textdomain' ] );
	}

	/**
	 * Declare compatibility with WooCommerce features.
	 *
	 * @return void
	 */
	public function declare_compatibility(): void {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'custom_order_tables',
				KBPYXD_PLUGIN_FILE,
				true
			);
		}
	}

	/**
	 * Load translations.
	 *
	 * @return void
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain(
			'pyxd-draping-for-woocommerce',
			false,
			dirname( plugin_basename( KBPYXD_PLUGIN_FILE ) ) . '/languages'
		);
	}

	/**
	 * Display the missing WooCommerce dependency notice.
	 *
	 * @return void
	 */
	public function woocommerce_missing_notice(): void {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		printf(
			'<div class="notice notice-error"><p>%s</p></div>',
			esc_html__( 'Pyxd Draping for WooCommerce requires WooCommerce to be installed and active.', 'pyxd-draping-for-woocommerce' )
		);
	}
}

