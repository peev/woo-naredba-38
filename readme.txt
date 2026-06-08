=== NAP Appendix 38 XML Export for WooCommerce ===
Contributors: peev
Tags: woocommerce, nap, bulgaria, xml, export, tax, ecommerce
Requires at least: 5.8
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Requires Plugins: woocommerce

Generate standardized XML audit files (Bulgarian NAP Appendix 38) from WooCommerce orders. Free and open source forever.

== Description ==

This plugin generates XML reports required by the Bulgarian National Revenue Agency (NAP) under Appendix 38 from your WooCommerce store orders. It is built for Bulgarian online merchants who must submit monthly reports via [portal.nap.bg](https://portal.nap.bg).

**Free forever** — GPLv2+ open source, with no paid add-ons or subscriptions.

This is an unofficial third-party tool and is not affiliated with or endorsed by NAP.

= Features =

* Generate XML for a selected year and month
* Per-site settings (company ID, e-shop number, store type, domain)
* Automatic payment method mapping (Stripe, PayPal, cash on delivery, and more)
* Refund support in the export
* WooCommerce HPOS (High-Performance Order Storage) compatible
* Paginated order queries for large stores

= Requirements =

* WordPress 5.8+
* WooCommerce 6.0+
* PHP 7.4+
* NAP Appendix 33 data (company ID and e-shop number)

= NAP payment codes =

* 2 – virtual POS (Stripe, PayPal, myPOS, BORICA, ePay, Fibank)
* 3 – cash on delivery
* 4 – payment service provider (Braintree, Mollie, Klarna)
* 5 – other

Extend mapping with the `nap38_virtual_pos_methods`, `nap38_cod_methods`, `nap38_psp_methods` filters, or the `_nap38_paym` order meta field.

== Installation ==

1. Upload the `nap-prilozhenie-38` folder to `/wp-content/plugins/`, or install the ZIP via **Plugins → Add New → Upload Plugin**.
2. Activate the plugin from **Plugins → Installed Plugins**.
3. Ensure WooCommerce is active.
4. Go to **NAP App. 38 → Settings** and enter your company ID (EIK) and e-shop number.
5. Generate XML from **NAP App. 38 → Export**.

== Frequently Asked Questions ==

= Is this plugin free? =

Yes. It is open source under GPL-2.0+ and will remain free forever.

= Where do I submit the generated file? =

Log in to portal.nap.bg → Declarations → Appendix 38 and upload the XML file.

= Does it work with HPOS? =

Yes. The plugin declares compatibility with WooCommerce High-Performance Order Storage.

= How do I add a custom payment gateway? =

Use the `nap38_virtual_pos_methods` filter or set the `_nap38_paym` meta field on an order.

== Screenshots ==

1. XML export page
2. Settings page

== Changelog ==

= 1.0.0 =
* Initial public release
* Appendix 38 XML generation (effective 01.01.2026)
* Per-site settings, payment mapping, and HPOS support

== Upgrade Notice ==

= 1.0.0 =
Initial public release.
