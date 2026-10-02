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
		add_shortcode( 'pyxd_draping', [ self::class, 'render_shortcode' ] );
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

		if ( ! $product instanceof WC_Product || self::is_product_disabled( $product ) ) {
			return;
		}

		$company_id = trim( (string) get_option( 'kbpyxd_company_id', '' ) );
		$flexible_id = self::get_flexible_id( $product );
		$position    = (string) get_option( 'kbpyxd_button_position', 'after_form' );

		if ( '' === $company_id || ( 'shortcode' !== $position && '' === $flexible_id ) ) {
			return;
		}

		self::$product = $product;

		add_action( 'wp_enqueue_scripts', [ self::class, 'enqueue_assets' ] );

		if ( 'shortcode' === $position ) {
			return;
		}

		$hooks    = [
			'before_form'                  => 'woocommerce_before_add_to_cart_form',
			'after_button'                 => 'woocommerce_after_add_to_cart_button',
			'after_form'                   => 'woocommerce_after_add_to_cart_form',
			'single_product_summary'       => 'woocommerce_single_product_summary',
			'product_meta_start'           => 'woocommerce_product_meta_start',
			'product_meta_end'             => 'woocommerce_product_meta_end',
			'product_thumbnails'           => 'woocommerce_product_thumbnails',
			'after_single_product_summary' => 'woocommerce_after_single_product_summary',
		];
		$hook     = isset( $hooks[ $position ] ) ? $hooks[ $position ] : $hooks['after_form'];
		$priority = (int) get_option( 'kbpyxd_hook_priority', 20 );

		add_action( $hook, [ self::class, 'render_button' ], $priority );
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
				'loading'   => __( 'Loading…', 'pyxd-draping-for-woocommerce' ),
				'loadError' => __( 'Unable to open the fabric visualizer. Please try again.', 'pyxd-draping-for-woocommerce' ),
				'selected'  => __( 'Selected option: %s', 'pyxd-draping-for-woocommerce' ),
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

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markup is escaped when constructed.
		echo self::get_button_markup(
			self::get_flexible_id( self::$product ),
			self::get_button_label()
		);
	}

	/**
	 * Render the visualizer button from a product-page shortcode.
	 *
	 * The current WooCommerce product SKU is used when the shortcode does not
	 * provide a Flexible ID or SKU override.
	 *
	 * @param array|string $attributes Shortcode attributes.
	 * @return string
	 */
	public static function render_shortcode( $attributes = [] ): string {
		$product = self::get_current_product();

		if ( ! $product instanceof WC_Product || self::is_product_disabled( $product ) ) {
			return '';
		}

		$company_id = trim( (string) get_option( 'kbpyxd_company_id', '' ) );

		if ( '' === $company_id ) {
			return '';
		}

		$attributes = shortcode_atts(
			[
				'flexible_id' => '',
				'sku'         => '',
				'label'       => '',
			],
			$attributes,
			'pyxd_draping'
		);

		$flexible_id = trim( sanitize_text_field( (string) $attributes['flexible_id'] ) );

		if ( '' === $flexible_id ) {
			$flexible_id = trim( sanitize_text_field( (string) $attributes['sku'] ) );
		}

		if ( '' === $flexible_id ) {
			$flexible_id = trim( (string) $product->get_sku() );
		}

		if ( '' === $flexible_id ) {
			return '';
		}

		$label = trim( sanitize_text_field( (string) $attributes['label'] ) );

		if ( '' === $label ) {
			$label = self::get_button_label();
		}

		return self::get_button_markup( $flexible_id, $label );
	}

	/**
	 * Get the configured button label.
	 *
	 * @return string
	 */
	private static function get_button_label(): string {
		$label = trim( (string) get_option( 'kbpyxd_button_label', '' ) );

		if ( '' === $label ) {
			$label = __( 'See Custom Fabric Options', 'pyxd-draping-for-woocommerce' );
		}

		return $label;
	}

	/**
	 * Build escaped visualizer button markup.
	 *
	 * @param string $flexible_id Pyxd Flexible ID or product SKU.
	 * @param string $label       Customer-facing button label.
	 * @return string
	 */
	private static function get_button_markup( string $flexible_id, string $label ): string {
		return sprintf(
			'<div class="kbpyxd-draping"><button type="button" class="button alt kbpyxd-draping__button" data-kbpyxd-open data-kbpyxd-flexible-id="%1$s">%2$s</button><div class="kbpyxd-draping__status" data-kbpyxd-status role="status" aria-live="polite" hidden></div></div>',
			esc_attr( $flexible_id ),
			esc_html( $label )
		);
	}

	/**
	 * Get the current WooCommerce product object.
	 *
	 * @return WC_Product|null
	 */
	private static function get_current_product(): ?WC_Product {
		global $product;

		if ( is_product() ) {
			$current_product = wc_get_product( get_queried_object_id() );

			if ( $current_product instanceof WC_Product ) {
				return $current_product;
			}
		}

		if ( $product instanceof WC_Product ) {
			return $product;
		}

		return null;
	}

	/**
	 * Determine whether Pyxd Draping is disabled for a product.
	 *
	 * Products display the visualizer by default and must be explicitly opted
	 * out with the product-level disable setting.
	 *
	 * @param WC_Product $product Product object.
	 * @return bool
	 */
	private static function is_product_disabled( WC_Product $product ): bool {
		return 'yes' === $product->get_meta( '_kbpyxd_disabled', true );
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
