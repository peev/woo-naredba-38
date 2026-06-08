=== НАП Приложение 38 – WooCommerce XML Export ===
Contributors: peev
Tags: woocommerce, nap, bulgaria, xml, export, tax, ecommerce
Requires at least: 5.8
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Requires Plugins: woocommerce

Генерира стандартизиран XML одиторски файл (Приложение №38) за НАП от WooCommerce поръчки. Безплатен завинаги.

== Description ==

Този плъгин автоматично генерира XML файл по изискванията на НАП (Приложение №38) от поръчките във вашия WooCommerce магазин. Подходящ за български онлайн търговци, които трябва да подават месечни отчети през [portal.nap.bg](https://portal.nap.bg).

**Безплатен завинаги** — отворен код (GPL-2.0+), без платени разширения или абонаменти.

= Основни функции =

* Генериране на XML за избрана година и месец
* Настройки per-site (ЕИК, номер на е-магазин, тип магазин, домейн)
* Автоматично разпознаване на методи на плащане (Stripe, PayPal, наложен платеж и др.)
* Поддръжка на възстановени суми (refunds)
* Съвместимост с WooCommerce HPOS (High-Performance Order Storage)
* Пагинирани заявки за големи обеми поръчки

= Изисквания =

* WordPress 5.8+
* WooCommerce 6.0+
* PHP 7.4+
* Попълнени данни от Приложение №33 (ЕИК и номер на е-магазин)

= Плащания – кодове НАП =

* 2 – виртуален ПОС (Stripe, PayPal, myPOS, BORICA, ePay, Fibank)
* 3 – наложен платеж
* 4 – ДПУ (Braintree, Mollie, Klarna)
* 5 – друг

Разширете разпознаването чрез филтрите `nap38_virtual_pos_methods`, `nap38_cod_methods`, `nap38_psp_methods` или мета поле `_nap38_paym` на поръчката.

== Installation ==

1. Качете папката `nap-prilozhenie-38` в `/wp-content/plugins/` или инсталирайте ZIP файла през **Plugins → Add New → Upload Plugin**.
2. Активирайте плъгина от **Plugins → Installed Plugins**.
3. Уверете се, че WooCommerce е активен.
4. Отидете на **НАП Прил. 38 → Настройки** и попълнете ЕИК и номера на е-магазина.
5. Генерирайте XML от **НАП Прил. 38 → Генериране**.

== Frequently Asked Questions ==

= Плъгинът безплатен ли е? =

Да. Плъгинът е с отворен код под GPL-2.0+ и ще остане безплатен завинаги.

= Къде подавам генерирания файл? =

Влезте в portal.nap.bg → Деклариране → Приложение №38 и качете XML файла.

= Работи ли с HPOS? =

Да. Плъгинът декларира съвместимост с WooCommerce High-Performance Order Storage.

= Как да добавя custom payment gateway? =

Използвайте филтъра `nap38_virtual_pos_methods` или задайте `_nap38_paym` мета поле на поръчката.

== Screenshots ==

1. Страница за генериране на XML
2. Страница с настройки

== Changelog ==

= 1.0.0 =
* Първоначална публична версия
* Генериране на XML по Приложение №38 (ДВ, бр. 42/2025, в сила от 01.01.2026)
* Настройки per-site, автоматично разпознаване на плащания, HPOS поддръжка

== Upgrade Notice ==

= 1.0.0 =
Първо публично издание.
