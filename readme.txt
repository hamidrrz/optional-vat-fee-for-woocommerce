=== Optional VAT Fee for WooCommerce ===
Contributors: hamidreza
Tags: woocommerce, checkout, fee, percentage
Requires at least: 6.0
Tested up to: 6.5
WC requires at least: 7.0
WC tested up to: 8.5
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Optional VAT fee at checkout with dynamic totals and customer opt-in.

== Description ==
Add an optional VAT percentage on the WooCommerce checkout page. Customers can opt in via a checkbox and totals update instantly.

Notes:
- This plugin targets the classic (shortcode) checkout. If the block-based checkout is used, the plugin shows a notice and does not add the field.
- GitHub: https://github.com/hamidrrz/optional-vat-fee-for-woocommerce

== Installation ==
1. Upload the `optional-vat-fee-for-woocommerce` folder to `/wp-content/plugins/`.
2. Activate the plugin through the Plugins screen.
3. Go to WooCommerce > Settings > VAT Fee to configure options.

== Settings ==
- Enable: Turns the checkout checkbox on or off.
- Percentage: Fee percentage (0-100). Decimals allowed.
- Include shipping: If enabled, shipping total is included in the fee base.
- Fee label: Base label shown in order totals (percentage is appended).

== Fee Calculation ==
- Base amount is the cart contents total after discounts and coupons, excluding tax.
- When "Include shipping" is enabled, the shipping total (excluding tax) is added to the base.
- The fee itself is non-taxable and no taxes are added to it.

== HPOS Compatibility ==
- Declares compatibility with WooCommerce High-Performance Order Storage (custom order tables).
- Uses WooCommerce CRUD methods for order meta.

== Hooks & Filters ==
- `wccpf_checkout_label` (string $label, float $percentage): Filter the checkout checkbox label.
- `wccpf_checkout_description` (string $description): Filter the optional description under the checkbox.
- `wccpf_fee_label` (string $label, float $percentage): Filter the fee line item label.
- `wccpf_percentage` (float $percentage): Filter the percentage used for calculations.
- `wccpf_is_enabled` (bool $enabled): Control whether the feature is enabled.
- `wccpf_include_shipping` (bool $include): Control whether shipping is included in the base.
- `wccpf_settings` (array $settings): Filter the WooCommerce settings array.

== Test Checklist ==
1. Checkbox unchecked -> no fee.
2. Checkbox checked -> fee applied once, totals correct.
3. Toggle checkbox -> totals update live.
4. Apply coupon -> fee recalculates correctly.
5. Include shipping ON/OFF -> fee changes accordingly.
6. Taxes enabled -> behavior matches the fee calculation rules above.
7. Order created -> meta saved and visible in admin, My Account, and emails.
8. HPOS enabled -> everything works.
9. Performance: no assets on non-checkout pages.
