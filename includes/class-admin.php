<?php
/**
 * WooCommerce administration integration.
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
 * Registers global settings and product-level fields.
 */
final class Admin {

	/**
	 * Settings section identifier.
	 *
	 * @var string
	 */
	private const SECTION_ID = 'pyxd_draping';

	/**
	 * Register WordPress and WooCommerce hooks.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_filter( 'woocommerce_get_sections_products', [ self::class, 'add_settings_section' ] );
		add_filter( 'woocommerce_get_settings_products', [ self::class, 'get_settings' ], 10, 2 );
		add_action( 'woocommerce_product_options_general_product_data', [ self::class, 'render_product_fields' ] );
		add_action( 'woocommerce_admin_process_product_object', [ self::class, 'save_product_fields' ] );
		add_filter( 'plugin_action_links_' . plugin_basename( KBPYXD_PLUGIN_FILE ), [ self::class, 'add_settings_link' ] );
	}

	/**
	 * Add the Pyxd section to WooCommerce product settings.
	 *
	 * @param array $sections Product settings sections.
	 * @return array
	 */
	public static function add_settings_section( array $sections ): array {
		$sections[ self::SECTION_ID ] = __( 'Pyxd Draping', 'pyxd-draping-for-woocommerce' );

		return $sections;
	}

	/**
	 * Return settings for the Pyxd section.
	 *
	 * @param array  $settings        Existing settings.
	 * @param string $current_section Current product settings section.
	 * @return array
	 */
	public static function get_settings( array $settings, string $current_section ): array {
		if ( self::SECTION_ID !== $current_section ) {
			return $settings;
		}

		return [
			[
				'title' => __( 'Pyxd Draping', 'pyxd-draping-for-woocommerce' ),
				'type'  => 'title',
				'desc'  => __( 'Configure the Pyxd Draping modal shown on eligible product pages.', 'pyxd-draping-for-woocommerce' ),
				'id'    => 'kbpyxd_settings',
			],
			[
				'title'    => __( 'Company ID', 'pyxd-draping-for-woocommerce' ),
				'desc'     => __( 'The Draping Client ID supplied by Pyxd.', 'pyxd-draping-for-woocommerce' ),
				'id'       => 'kbpyxd_company_id',
				'type'     => 'text',
				'css'      => 'min-width: 320px;',
				'desc_tip' => true,
			],
			[
				'title'   => __( 'Button label', 'pyxd-draping-for-woocommerce' ),
				'id'      => 'kbpyxd_button_label',
				'type'    => 'text',
				'default' => __( 'See Custom Fabric Options', 'pyxd-draping-for-woocommerce' ),
			],
			[
				'title'   => __( 'Button position', 'pyxd-draping-for-woocommerce' ),
				'id'      => 'kbpyxd_button_position',
				'type'    => 'select',
				'default' => 'after_form',
				'options' => [
					'before_form'  => __( 'Before add-to-cart form', 'pyxd-draping-for-woocommerce' ),
					'after_button' => __( 'After add-to-cart button', 'pyxd-draping-for-woocommerce' ),
					'after_form'   => __( 'After add-to-cart form', 'pyxd-draping-for-woocommerce' ),
				],
			],
			[
				'title'   => __( 'Hover preview', 'pyxd-draping-for-woocommerce' ),
				'desc'    => __( 'Allow Pyxd to preview swatches when a customer hovers over them.', 'pyxd-draping-for-woocommerce' ),
				'id'      => 'kbpyxd_hover_preview',
				'type'    => 'checkbox',
				'default' => 'yes',
			],
			[
				'title'   => __( 'Preload visualizer', 'pyxd-draping-for-woocommerce' ),
				'desc'    => __( 'Begin loading the product visualizer when the product page is ready.', 'pyxd-draping-for-woocommerce' ),
				'id'      => 'kbpyxd_preload',
				'type'    => 'checkbox',
				'default' => 'yes',
			],
			[
				'type' => 'sectionend',
				'id'   => 'kbpyxd_settings',
			],
		];
	}

	/**
	 * Render product-level Pyxd fields.
	 *
	 * @return void
	 */
	public static function render_product_fields(): void {
		echo '<div class="options_group">';

		woocommerce_wp_checkbox(
			[
				'id'          => '_kbpyxd_enabled',
				'label'       => __( 'Pyxd Draping', 'pyxd-draping-for-woocommerce' ),
				'description' => __( 'Show the fabric visualizer for this product.', 'pyxd-draping-for-woocommerce' ),
			]
		);

		woocommerce_wp_text_input(
			[
				'id'          => '_kbpyxd_flexible_id',
				'label'       => __( 'Pyxd Flexible ID', 'pyxd-draping-for-woocommerce' ),
				'desc_tip'    => true,
				'description' => __( 'Enter the Pyxd Frame ID or mapped product identifier. If empty, the WooCommerce SKU is used.', 'pyxd-draping-for-woocommerce' ),
			]
		);

		echo '</div>';
	}

	/**
	 * Save product-level Pyxd fields.
	 *
	 * @param WC_Product $product Product being saved.
	 * @return void
	 */
	public static function save_product_fields( WC_Product $product ): void {
		$enabled     = isset( $_POST['_kbpyxd_enabled'] ) ? 'yes' : 'no';
		$flexible_id = isset( $_POST['_kbpyxd_flexible_id'] )
			? sanitize_text_field( wp_unslash( $_POST['_kbpyxd_flexible_id'] ) )
			: '';

		$product->update_meta_data( '_kbpyxd_enabled', $enabled );
		$product->update_meta_data( '_kbpyxd_flexible_id', $flexible_id );
	}

	/**
	 * Add a settings shortcut to the Plugins screen.
	 *
	 * @param array $links Existing action links.
	 * @return array
	 */
	public static function add_settings_link( array $links ): array {
		$url = admin_url( 'admin.php?page=wc-settings&tab=products&section=' . self::SECTION_ID );

		array_unshift(
			$links,
			'<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'pyxd-draping-for-woocommerce' ) . '</a>'
		);

		return $links;
	}
}

