=== Pyxd Draping for WooCommerce ===
Contributors: kevinbrent
Tags: woocommerce, furniture, fabric, visualizer, pyxd
Requires at least: 6.4
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.1.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adds the Pyxd Draping fabric visualizer to eligible WooCommerce products.

== Description ==

Pyxd Draping for WooCommerce adds Pyxd's modal fabric visualizer to eligible WooCommerce product pages through standard WooCommerce hooks or a product-aware shortcode. Products display the visualizer by default and can be individually disabled. It is a standalone plugin and requires only WordPress, WooCommerce, and an available Pyxd Draping account or sample configuration.

= Requirements =

* WordPress 6.4 or newer
* WooCommerce 8.0 or newer
* PHP 7.4 or newer
* A Pyxd Draping Company ID
* A Pyxd Frame ID, SKU, or other Flexible ID for each product that should display the visualizer

== Installation ==

1. Upload the `pyxd-draping-for-woocommerce` directory to `wp-content/plugins/`, or install the plugin ZIP through Plugins > Add Plugin > Upload Plugin.
2. Activate Pyxd Draping for WooCommerce.
3. Open WooCommerce > Settings > Products > Pyxd Draping.
4. Enter the Company ID supplied by Pyxd and configure the global display settings.
5. Edit products that need a Flexible ID override or should have Pyxd Draping disabled.

WooCommerce must be installed and active. The plugin displays an administrator notice and does not initialize its integration when WooCommerce is unavailable.

== Global Settings ==

Open WooCommerce > Settings > Products > Pyxd Draping.

= Company ID =

Enter the Draping Client ID supplied by Pyxd. The plugin uses this value as the `data-company-id` attribute when it loads the Pyxd SDK from `https://js.pyxmagic.com/build/draping.js`.

The visualizer is not rendered when the Company ID is empty.

= Button label =

Controls the customer-facing text of the visualizer button. The default label is "See Custom Fabric Options."

= Button position =

Selects the WooCommerce action hook used to render the button.

* Before add-to-cart form: `woocommerce_before_add_to_cart_form`
* After add-to-cart button: `woocommerce_after_add_to_cart_button`
* After add-to-cart form: `woocommerce_after_add_to_cart_form`
* Single product summary: `woocommerce_single_product_summary`
* Product meta start: `woocommerce_product_meta_start`
* Product meta end: `woocommerce_product_meta_end`
* Product thumbnails: `woocommerce_product_thumbnails`
* After single product summary: `woocommerce_after_single_product_summary`
* Shortcode only (no automatic output): `[pyxd_draping]`

The default position is After add-to-cart form. A theme must execute the selected WooCommerce hook for the button to appear. Theme overrides can change the visual result of a hook, especially the product-thumbnail and after-summary positions.

Select Shortcode only (no automatic output) when placing the button manually with the shortcode. This prevents the plugin from also adding the button through a WooCommerce action hook.

= Action hook priority =

Controls when the plugin's button callback runs relative to other callbacks registered on the selected WooCommerce action hook.

* Default: `20`
* Minimum: `-9999`
* Maximum: `9999`
* Whole numbers only

A lower number runs earlier. A higher number runs later. Callbacks registered on the same hook with the same priority run in registration order.

Priority does not affect shortcode output.

= Hover preview =

Controls Pyxd's `hoverPreview` modal option. When enabled, Pyxd can preview a swatch while the customer points to it. This setting is enabled by default.

= Preload visualizer =

When enabled, the plugin begins looking up and preloading the current product's Pyxd assets after the product page is ready. This reduces the time between clicking the button and opening the modal. The plugin also attempts to preload when the customer first points to or focuses the button.

Preloading is enabled by default. Disabling it delays SDK and product loading until customer interaction.

== Product Setup ==

1. Open Products in WordPress administration.
2. Edit the product.
3. In Product data > General, enter a Pyxd Flexible ID when the WooCommerce SKU is not the identifier mapped by Pyxd.
4. Leave Disable Pyxd Draping unchecked to show the visualizer, or check it to hide the visualizer for this product.
5. Update the product.

The Flexible ID may be a Pyxd Frame ID or another product identifier mapped by Pyxd. If the field is empty, the plugin uses the WooCommerce product SKU. The visualizer is not rendered when both the Flexible ID and product SKU are empty.

The visualizer is enabled by default. A configured global Company ID and a product Flexible ID or SKU are required before the button appears. The product-level Disable Pyxd Draping checkbox is an explicit opt-out.

== Shortcode ==

Add the following shortcode to content rendered on a WooCommerce product page that has not been disabled:

`[pyxd_draping]`

With no attributes, the shortcode gets the SKU directly from the current WooCommerce product object and uses the globally configured button label.

The shortcode supports these optional attributes:

* `flexible_id`: Explicit Pyxd Flexible ID. This takes precedence over every other identifier.
* `sku`: Explicit SKU or other SKU-based Pyxd identifier. Used when `flexible_id` is empty.
* `label`: Button label for this shortcode instance. Falls back to the global Button label setting.

Examples:

* `[pyxd_draping label="View Fabric Options"]`
* `[pyxd_draping sku="CHAIR-100"]`
* `[pyxd_draping flexible_id="ytYfMXKY9UJd9" label="Customize This Product"]`

Identifier precedence is `flexible_id`, then `sku`, then the current product object's SKU. The product-level Pyxd Flexible ID field is used by automatically hooked buttons but is intentionally not the shortcode's default.

The shortcode returns no markup unless it is rendered with a current WooCommerce product, Pyxd Draping has not been disabled on that product, a global Company ID is configured, and an identifier can be resolved.

== Testing with Pyxd Sample Data ==

The Pyxd integration guide provides the following values specifically for testing:

* Sample Company ID: `EOwcft9LfSuxN`
* Sample Frame/Flexible ID: `ytYfMXKY9UJd9`

Enter the sample Company ID in the global settings and the sample Frame ID on a product that has not been disabled. Test from a normal HTTP or HTTPS WordPress environment; the Pyxd API cannot be tested directly from a `file://` URL.

Production stores should use the Company ID and product mappings supplied for that store by Pyxd.

== Storefront Behavior ==

On an eligible product page, the plugin:

1. Loads its local JavaScript and CSS only for that product.
2. Loads the Pyxd SDK over HTTPS.
3. Checks that the Flexible ID is available with Pyxd when the SDK supports lookup.
4. Preloads the configured frame when enabled or requested by customer interaction.
5. Opens Pyxd's self-contained modal when the customer clicks the button.
6. Displays the returned output string beneath the button when Pyxd provides one.
7. Dispatches a browser event containing the complete Pyxd result.

The plugin does not change the WooCommerce product, variation, SKU, price, cart item, or order when a customer selects a swatch.

== JavaScript Selection Event ==

After the Pyxd modal closes successfully, the plugin dispatches `kbpyxdDrapingSelection` on `document`. The event's `detail` property contains the result returned by `window.pyxdDraping.showModal()`, including the frame ID, output string when available, and configuration object.

    document.addEventListener( 'kbpyxdDrapingSelection', function ( event ) {
        console.log( event.detail );
    } );

Custom cart or order behavior should listen for this event and implement the store's approved SKU, pricing, and configuration rules. Do not assume that every Pyxd output string maps to a WooCommerce product or variation.

== Frequently Asked Questions ==

= The button does not appear. =

Verify all of the following:

* WooCommerce is active.
* The global Company ID is not empty.
* Disable Pyxd Draping is not selected on the product.
* The product has a Flexible ID or a WooCommerce SKU.
* The active theme executes the selected WooCommerce action hook.
* The product page is a standard WooCommerce single-product request.

= The visualizer cannot find the product. =

Confirm that the product's Flexible ID or fallback SKU exists in the Pyxd customer data and is mapped to the configured Company ID.

= The SDK does not load. =

Confirm that the site and its security policy allow HTTPS requests to:

* `https://js.pyxmagic.com/`
* `https://draping.pyxmagic.com/`

The plugin refuses to reuse a Pyxd SDK script that declares a different Company ID because a page can have only one active Pyxd client configuration.

= The position or priority has no visible effect. =

WooCommerce hooks are controlled by the active theme's product templates. Confirm that the selected hook exists in the theme override and check whether theme CSS moves, hides, or restyles content in that area. Priority only controls order among callbacks on the same hook; it does not move content between hooks.

== Uninstall ==

Uninstalling the plugin deletes its global WooCommerce settings. Product-level disable state and Flexible ID metadata are retained so products do not need to be remapped after a reinstall.

== Additional Documentation ==

* [Pyxd Draping Integration Guide](https://docs.google.com/document/d/15ko7qpLgOac2HcH2VDtqavZorJR7lwMged2hGTPItnI/edit)

== Changelog ==

= 1.1.1 =
* Changed product behavior so the visualizer displays by default and can be explicitly disabled.

= 1.1.0 =
* Added additional WooCommerce action hook positions.
* Added configurable action hook priority.
* Added the `[pyxd_draping]` product-page shortcode.
* Added shortcode-specific Flexible ID, SKU, and button label overrides.
* Added comprehensive installation, configuration, integration, and troubleshooting documentation.

= 1.0.0 =
* Added the Pyxd modal visualizer to enabled WooCommerce products.
* Added global display, hover-preview, and preload settings.
* Added product-level enablement and Flexible ID fields with SKU fallback.
* Added the `kbpyxdDrapingSelection` browser event.
