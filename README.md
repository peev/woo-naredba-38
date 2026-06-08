# НАП Приложение 38 – WooCommerce XML Export

[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](https://www.gnu.org/licenses/gpl-2.0)

Open-source WordPress plugin that generates standardized XML audit files (Приложение №38) for the Bulgarian National Revenue Agency (НАП) from WooCommerce orders.

**Free forever** — GPLv2+, no premium tiers, no subscriptions.

- **Repository:** [github.com/peev/woo-naredba-38](https://github.com/peev/woo-naredba-38)
- **Requires:** WordPress 5.8+, WooCommerce 6.0+, PHP 7.4+

## Quick start

1. Copy this folder to `wp-content/plugins/nap-prilozhenie-38/`, or install the release ZIP via **Plugins → Add New → Upload**.
2. Activate the plugin (WooCommerce must be active).
3. Open **НАП Прил. 38 → Настройки** and enter your EIK and e-shop number from Приложение №33.
4. Open **НАП Прил. 38 → Генериране**, pick year/month, and download the XML.

Submit the file at [portal.nap.bg](https://portal.nap.bg) → Деклариране → Приложение №38.

## Building a release ZIP

```powershell
.\bin\package.ps1
```

Output: `dist/nap-prilozhenie-38-1.0.0.zip` — ready for WordPress upload or GitHub Releases.

## Developer hooks

```php
// Register a custom gateway as virtual POS (NAP code 2)
add_filter( 'nap38_virtual_pos_methods', function ( $methods ) {
    $methods[] = 'my_custom_gateway';
    return $methods;
} );

// Override payment code for a specific order
$order->update_meta_data( '_nap38_paym', '2' );
$order->save();
```

Available filters: `nap38_virtual_pos_methods`, `nap38_cod_methods`, `nap38_psp_methods`, `nap38_default_payment_code`, `nap38_refund_payment_code`.

## Contributing

Issues and pull requests are welcome. By contributing, you agree to license your work under GPL-2.0+.

## License

GPL-2.0 or later. See [LICENSE](LICENSE).
