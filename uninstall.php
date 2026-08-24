<?php
/**
 * Remove global plugin settings on uninstall.
 *
 * Product metadata is intentionally retained so reinstalling the plugin does
 * not require administrators to remap their Pyxd products.
 *
 * @package Pyxd_Draping_For_WooCommerce
 * @author  Kevin Brent
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$kbpyxd_options = [
	'kbpyxd_company_id',
	'kbpyxd_button_label',
	'kbpyxd_button_position',
	'kbpyxd_hover_preview',
	'kbpyxd_preload',
];

foreach ( $kbpyxd_options as $kbpyxd_option ) {
	delete_option( $kbpyxd_option );
}

