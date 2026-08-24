<?php
/**
 * WooCommerce storefront integration.
 *
 * @package Pyxd_Draping_For_WooCommerce
 * @author  Kevin Brent
 */

namespace KevinBrent\PyxdDraping;

use WC_Product;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Loads assets and renders the Pyxd modal trigger on product pages.
 */
final class Frontend {

	/**
	 * Current eligible product.
	 *
	 * @var WC_Product|null
	 */
	private static $product = null;

	/**
	 * Register storefront hooks.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'wp', [ self::class, 'register_product_hooks' ] );
	}

	/**
	 * Register hooks only when the current product is eligible.
	 *
	 * @return void
	 */
	public static function register_product_hooks(): void {
		if ( ! is_product() ) {
			return;
		}

		$product = wc_get_product( get_queried_object_id() );

		if ( ! $product instanceof WC_Product || 'yes' !== $product->get_meta( '_kbpyxd_enabled', true ) ) {
			return;
		}

		$company_id = trim( (string) get_option( 'kbpyxd_company_id', '' ) );
		$flexible_id = self::get_flexible_id( $product );

		if ( '' === $company_id || '' === $flexible_id ) {
			return;
		}

		self::$product = $product;

		add_action( 'wp_enqueue_scripts', [ self::class, 'enqueue_assets' ] );

		$position = (string) get_option( 'kbpyxd_button_position', 'after_form' );
		$hooks    = [
			'before_form'  => 'woocommerce_before_add_to_cart_form',
			'after_button' => 'woocommerce_after_add_to_cart_button',
			'after_form'   => 'woocommerce_after_add_to_cart_form',
		];
		$hook     = isset( $hooks[ $position ] ) ? $hooks[ $position ] : $hooks['after_form'];

		add_action( $hook, [ self::class, 'render_button' ], 20 );
	}

	/**
	 * Enqueue the plugin's standalone frontend assets and configuration.
	 *
	 * @return void
	 */
	public static function enqueue_assets(): void {
		if ( ! self::$product instanceof WC_Product ) {
			return;
		}

		wp_enqueue_style(
			'kbpyxd-frontend',
			KBPYXD_PLUGIN_URL . 'assets/css/frontend.css',
			[],
			KBPYXD_VERSION
		);

		wp_enqueue_script(
			'kbpyxd-frontend',
			KBPYXD_PLUGIN_URL . 'assets/js/frontend.js',
			[],
			KBPYXD_VERSION,
			true
		);

		$config = [
			'companyId'    => trim( (string) get_option( 'kbpyxd_company_id', '' ) ),
			'flexibleId'   => self::get_flexible_id( self::$product ),
			'hoverPreview' => 'yes' === get_option( 'kbpyxd_hover_preview', 'yes' ),
			'preload'      => 'yes' === get_option( 'kbpyxd_preload', 'yes' ),
			'sdkUrl'       => 'https://js.pyxmagic.com/build/draping.js',
			'i18n'         => [
				'loading'     => __( 'Loading…', 'pyxd-draping-for-woocommerce' ),
				'loadError'   => __( 'Unable to open the fabric visualizer. Please try again.', 'pyxd-draping-for-woocommerce' ),
				'unavailable' => __( 'Fabric options are not available for this product.', 'pyxd-draping-for-woocommerce' ),
				'selected'    => __( 'Selected option: %s', 'pyxd-draping-for-woocommerce' ),
			],
		];

		wp_add_inline_script(
			'kbpyxd-frontend',
			'window.kbPyxdDrapingConfig = ' . wp_json_encode( $config ) . ';',
			'before'
		);
	}

	/**
	 * Render the fabric visualizer button and status region.
	 *
	 * @return void
	 */
	public static function render_button(): void {
		if ( ! self::$product instanceof WC_Product ) {
			return;
		}

		$label = trim( (string) get_option( 'kbpyxd_button_label', '' ) );

		if ( '' === $label ) {
			$label = __( 'See Custom Fabric Options', 'pyxd-draping-for-woocommerce' );
		}

		printf(
			'<div class="kbpyxd-draping"><button type="button" class="button alt kbpyxd-draping__button" data-kbpyxd-open>%s</button><div class="kbpyxd-draping__status" data-kbpyxd-status role="status" aria-live="polite" hidden></div></div>',
			esc_html( $label )
		);
	}

	/**
	 * Get the product's Pyxd Flexible ID, falling back to its SKU.
	 *
	 * @param WC_Product $product Product object.
	 * @return string
	 */
	private static function get_flexible_id( WC_Product $product ): string {
		$flexible_id = trim( (string) $product->get_meta( '_kbpyxd_flexible_id', true ) );

		if ( '' !== $flexible_id ) {
			return $flexible_id;
		}

		return trim( (string) $product->get_sku() );
	}
}

