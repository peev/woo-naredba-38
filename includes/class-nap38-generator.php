<?php
defined( 'ABSPATH' ) || exit;

/**
 * Generates the XML file per Приложение №38 schema.
 * Uses paginated queries (50 orders at a time) to avoid memory exhaustion.
 */
class NAP38_Generator {

    const BATCH_SIZE = 50;

    private float  $eur_rate;
    private string $eik;
    private string $eshop_n;
    private string $eshop_type;
    private string $domain_name;

    public function __construct() {
        $this->eur_rate    = (float) NAP38_Settings::get( 'eur_rate', '1.95583' );
        $this->eik         = NAP38_Settings::get( 'eik' );
        $this->eshop_n     = NAP38_Settings::get( 'eshop_n' );
        $this->eshop_type  = NAP38_Settings::get( 'eshop_type', '1' );
        $this->domain_name = NAP38_Settings::get( 'domain_name', home_url() );
    }

    /**
     * Main entry point. Returns generated XML string.
     */
    public function generate( int $year, int $month ): string {
        // Raise memory limit for this request only
        @ini_set( 'memory_limit', '512M' );

        $xml = new DOMDocument( '1.0', 'UTF-8' );
        $xml->formatOutput = true;

        $root = $xml->createElement( 'REPORT' );
        $xml->appendChild( $root );

        // ── Header ───────────────────────────────────────────────────────────
        $this->add_text_node( $xml, $root, 'EIK',           $this->eik );
        $this->add_text_node( $xml, $root, 'E_SHOP_N',      $this->eshop_n );
        $this->add_text_node( $xml, $root, 'E_SHOP_TYPE',   $this->eshop_type );
        $this->add_text_node( $xml, $root, 'DOMAIN_NAME',   $this->domain_name );
        $this->add_text_node( $xml, $root, 'CREATION_DATE', date( 'Y-m-d' ) );
        $this->add_text_node( $xml, $root, 'MON',           str_pad( (string) $month, 2, '0', STR_PAD_LEFT ) );
        $this->add_text_node( $xml, $root, 'GOD',           (string) $year );

        // ── Orders (paginated) ───────────────────────────────────────────────
        $orders_node = $xml->createElement( 'ORDERS' );
        $root->appendChild( $orders_node );

        $page = 1;
        do {
            $batch      = $this->get_orders_batch( $year, $month, $page );
            $batch_size = count( $batch );

            foreach ( $batch as $order ) {
                $this->append_order_node( $xml, $orders_node, $order );
            }

            unset( $batch );
            $this->maybe_flush_wpdb_cache();
            $page++;
        } while ( $batch_size === self::BATCH_SIZE );

        // ── Refunds (paginated) ──────────────────────────────────────────────
        $refunds_node = $xml->createElement( 'REFUNDS' );
        $root->appendChild( $refunds_node );

        $refund_rows = $this->get_refunded_order_ids( $year, $month );
        $r_total     = 0.0;

        foreach ( $refund_rows as $row ) {
            $order = wc_get_order( $row->order_id );
            if ( ! $order ) continue;

            $refund_date   = $row->refund_date ?: date( 'Y-m-d' );
            $refund_amount = (float) $order->get_total_refunded();
            $r_amount      = $this->to_eur( $refund_amount );
            $r_total      += $r_amount;

            $refund_node = $xml->createElement( 'REFUND' );
            $refunds_node->appendChild( $refund_node );

            $this->add_text_node( $xml, $refund_node, 'R_ORD_N',  (string) $order->get_order_number() );
            $this->add_text_node( $xml, $refund_node, 'R_AMOUNT', $this->fmt( $r_amount ) );
            $this->add_text_node( $xml, $refund_node, 'R_DATE',   $refund_date );
            $this->add_text_node( $xml, $refund_node, 'R_PAYM',   $this->map_refund_payment( $order ) );

            unset( $order );
        }

        // Prepend R_ORD count before refund rows
        $r_ord_node = $xml->createElement( 'R_ORD', (string) count( $refund_rows ) );
        $refunds_node->insertBefore( $r_ord_node, $refunds_node->firstChild );

        $this->add_text_node( $xml, $refunds_node, 'R_TOTAL', $this->fmt( $r_total ) );

        return $xml->saveXML();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Order building
    // ─────────────────────────────────────────────────────────────────────────

    private function append_order_node( DOMDocument $xml, DOMNode $orders_node, WC_Order $order ): void {
        $order_node = $xml->createElement( 'ORDER' );
        $orders_node->appendChild( $order_node );

        $this->add_text_node( $xml, $order_node, 'ORD_N', (string) $order->get_order_number() );
        $this->add_text_node( $xml, $order_node, 'ORD_D', $order->get_date_created()->format( 'Y-m-d' ) );

        $invoice_n    = $order->get_meta( '_invoice_number' ) ?: $order->get_meta( '_wcf_invoice_number' ) ?: '';
        $invoice_date = $order->get_meta( '_invoice_date' )   ?: $order->get_meta( '_wcf_invoice_date' )   ?: '';

        if ( $invoice_n ) {
            $this->add_text_node( $xml, $order_node, 'DOC_N',    $invoice_n );
            $this->add_text_node( $xml, $order_node, 'DOC_DATE', $invoice_date ?: $order->get_date_created()->format( 'Y-m-d' ) );
        } else {
            $this->add_text_node( $xml, $order_node, 'DOC_DATE', $order->get_date_created()->format( 'Y-m-d' ) );
        }

        // ── Line items ───────────────────────────────────────────────────────
        $items_node   = $xml->createElement( 'ITEMS' );
        $order_node->appendChild( $items_node );

        $subtotal_all     = 0.0;
        $subtotal_tax_all = 0.0;

        foreach ( $order->get_items() as $item ) {
            /** @var WC_Order_Item_Product $item */
            $item_node = $xml->createElement( 'ITEM' );
            $items_node->appendChild( $item_node );

            $qty          = (float) $item->get_quantity();
            $subtotal     = (float) $item->get_subtotal();
            $subtotal_tax = (float) $item->get_subtotal_tax();

            $subtotal_all     += $subtotal;
            $subtotal_tax_all += $subtotal_tax;

            $unit_price = $qty > 0 ? $subtotal / $qty : 0;

            // Derive VAT rate
            $vat_rate = 0;
            if ( $subtotal > 0 && $subtotal_tax > 0 ) {
                $vat_rate = (int) round( ( $subtotal_tax / $subtotal ) * 100 );
            } else {
                foreach ( $item->get_taxes()['total'] as $tax_id => $tax_total ) {
                    $rate_obj = WC_Tax::_get_tax_rate( $tax_id );
                    if ( $rate_obj ) {
                        $vat_rate = (int) $rate_obj['tax_rate'];
                        break;
                    }
                }
                if ( ! $vat_rate ) $vat_rate = 20;
            }

            $this->add_text_node( $xml, $item_node, 'ART_NAME',     $item->get_name() );
            $this->add_text_node( $xml, $item_node, 'ART_QUANT',    $this->fmt( $qty ) );
            $this->add_text_node( $xml, $item_node, 'ART_PRICE',    $this->fmt( $this->to_eur( $unit_price ) ) );
            $this->add_text_node( $xml, $item_node, 'ART_VAT_RATE', str_pad( (string) $vat_rate, 2, '0', STR_PAD_LEFT ) );
            $this->add_text_node( $xml, $item_node, 'ART_VAT',      $this->fmt( $this->to_eur( $subtotal_tax ) ) );
            $this->add_text_node( $xml, $item_node, 'ART_SUM',      $this->fmt( $this->to_eur( $subtotal + $subtotal_tax ) ) );
        }

        // ── Order totals ─────────────────────────────────────────────────────
        $discount    = (float) $order->get_discount_total();
        $total_tax   = (float) $order->get_total_tax();
        $grand_total = (float) $order->get_total();

        $this->add_text_node( $xml, $order_node, 'ORD_TOTAL1', $this->fmt( $this->to_eur( $subtotal_all ) ) );
        $this->add_text_node( $xml, $order_node, 'ORD_DISC',   $this->fmt( $this->to_eur( $discount ) ) );
        $this->add_text_node( $xml, $order_node, 'ORD_VAT',    $this->fmt( $this->to_eur( $total_tax ) ) );
        $this->add_text_node( $xml, $order_node, 'ORD_TOTAL2', $this->fmt( $this->to_eur( $grand_total ) ) );

        $this->add_text_node( $xml, $order_node, 'PAYM', $this->map_payment_method( $order ) );

        $trans_n = $order->get_transaction_id();
        if ( $trans_n ) {
            $this->add_text_node( $xml, $order_node, 'TRANS_N', $trans_n );
        }

        $proc_id = $order->get_meta( '_payment_processor_id' ) ?: '';
        if ( $proc_id ) {
            $this->add_text_node( $xml, $order_node, 'PROC_ID', $proc_id );
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Paginated DB queries
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Fetch one page of orders for the given month.
     */
    private function get_orders_batch( int $year, int $month, int $page ): array {
        $statuses       = NAP38_Settings::get( 'order_status', [ 'wc-completed' ] );
        $statuses_clean = array_map( fn( $s ) => str_replace( 'wc-', '', $s ), $statuses );

        $start = sprintf( '%04d-%02d-01 00:00:00', $year, $month );
        $end   = sprintf( '%04d-%02d-%02d 23:59:59', $year, $month, cal_days_in_month( CAL_GREGORIAN, $month, $year ) );

        return wc_get_orders( [
            'status'       => $statuses_clean,
            'date_created' => $start . '...' . $end,
            'limit'        => self::BATCH_SIZE,
            'paged'        => $page,
            'orderby'      => 'date',
            'order'        => 'ASC',
            'type'         => 'shop_order',
        ] );
    }

    /**
     * Fetch refunded order IDs using a direct DB query — avoids loading full
     * WC_Order objects just to filter by refund date.
     *
     * Returns array of stdObjects with ->order_id and ->refund_date (Y-m-d).
     */
    private function get_refunded_order_ids( int $year, int $month ): array {
        global $wpdb;

        $start = sprintf( '%04d-%02d-01', $year, $month );
        $end   = sprintf( '%04d-%02d-%02d', $year, $month, cal_days_in_month( CAL_GREGORIAN, $month, $year ) );

        // HPOS-compatible: try wc_orders table first, fall back to posts
        $hpos_table = $wpdb->prefix . 'wc_orders';
        $use_hpos   = $wpdb->get_var( "SHOW TABLES LIKE '{$hpos_table}'" ) === $hpos_table;

        if ( $use_hpos ) {
            // In HPOS, refunds are child orders with type=shop_order_refund
            $rows = $wpdb->get_results( $wpdb->prepare(
                "SELECT parent_order_id AS order_id,
                        DATE(date_created_gmt) AS refund_date
                 FROM {$hpos_table}
                 WHERE type = 'shop_order_refund'
                   AND DATE(date_created_gmt) BETWEEN %s AND %s
                 GROUP BY parent_order_id
                 ORDER BY parent_order_id ASC",
                $start, $end
            ) );
        } else {
            // Legacy posts table
            $rows = $wpdb->get_results( $wpdb->prepare(
                "SELECT post_parent AS order_id,
                        DATE(post_date) AS refund_date
                 FROM {$wpdb->posts}
                 WHERE post_type   = 'shop_order_refund'
                   AND post_status = 'completed'
                   AND DATE(post_date) BETWEEN %s AND %s
                 GROUP BY post_parent
                 ORDER BY post_parent ASC",
                $start, $end
            ) );
        }

        return $rows ?: [];
    }

    /**
     * Clear wpdb query log and WP object cache between batches to prevent
     * memory accumulation from WP's internal query logging.
     */
    private function maybe_flush_wpdb_cache(): void {
        global $wpdb;
        // Stop accumulating query log (only populated when SAVEQUERIES=true)
        if ( defined( 'SAVEQUERIES' ) && SAVEQUERIES ) {
            $wpdb->queries = [];
        }
        // Clear WP object cache for orders to prevent stale objects stacking up
        wp_cache_flush_group( 'posts' );
        wp_cache_flush_group( 'post_meta' );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Payment method mapping
    // ─────────────────────────────────────────────────────────────────────────

    private function map_payment_method( WC_Order $order ): string {
        $method = $order->get_payment_method();

        $virtual_pos = apply_filters( 'nap38_virtual_pos_methods', [
            'stripe', 'stripe_cc', 'stripe_ideal', 'woocommerce_payments',
            'paypal', 'paypalexpress', 'ppec_paypal',
            'mypos_virtual', 'borica', 'epay', 'fibank',
        ] );
        $cod_methods = apply_filters( 'nap38_cod_methods', [ 'cod' ] );
        $psp_methods = apply_filters( 'nap38_psp_methods', [ 'braintree', 'mollie', 'klarna', 'afterpay' ] );

        if ( in_array( $method, $virtual_pos, true ) ) return '2';
        if ( in_array( $method, $cod_methods, true ) ) return '3';
        if ( in_array( $method, $psp_methods, true ) ) return '4';

        $override = $order->get_meta( '_nap38_paym' );
        if ( $override ) return $override;

        return apply_filters( 'nap38_default_payment_code', '5', $order );
    }

    private function map_refund_payment( WC_Order $order ): string {
        $method = $order->get_payment_method();
        $card   = [ 'stripe', 'stripe_cc', 'woocommerce_payments', 'paypal', 'mypos_virtual', 'borica', 'epay', 'fibank' ];
        if ( in_array( $method, $card, true ) ) return '2';
        if ( $method === 'cod' ) return '3';
        return apply_filters( 'nap38_refund_payment_code', '1', $order );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Utilities
    // ─────────────────────────────────────────────────────────────────────────

    private function to_eur( float $bgn ): float {
        if ( $this->eur_rate <= 0 ) return $bgn;
        if ( get_woocommerce_currency() === 'EUR' ) return $bgn;
        return round( $bgn / $this->eur_rate, 2 );
    }

    private function fmt( float $n ): string {
        return number_format( $n, 2, '.', '' );
    }

    private function add_text_node( DOMDocument $doc, DOMNode $parent, string $tag, string $value ): void {
        $node = $doc->createElement( $tag );
        $node->appendChild( $doc->createTextNode( $value ) );
        $parent->appendChild( $node );
    }
}
