<?php
/**
 * Plugin Name: Pyxd Draping for WooCommerce
 * Plugin URI:  https://kevinbrent.com/
 * Description: Adds the Pyxd Draping fabric visualizer to selected WooCommerce products.
 * Version:     1.0.0
 * Author:      Kevin Brent
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: pyxd-draping-for-woocommerce
 * Domain Path: /languages
 * Requires at least: 6.4
 * Requires PHP: 7.4
 * WC requires at least: 8.0
 * WC tested up to: 11.0
 *
 * @package Pyxd_Draping_For_WooCommerce
 * @author  Kevin Brent
 */

namespace KevinBrent\PyxdDraping;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'KBPYXD_VERSION', '1.0.0' );
define( 'KBPYXD_PLUGIN_FILE', __FILE__ );
define( 'KBPYXD_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'KBPYXD_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once KBPYXD_PLUGIN_PATH . 'includes/class-plugin.php';

add_action( 'plugins_loaded', [ Plugin::class, 'instance' ] );

