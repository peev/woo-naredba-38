<?php
defined( 'ABSPATH' ) || exit;

/**
 * Manages plugin settings stored per-site in wp_options.
 */
class NAP38_Settings {

    const OPTION_KEY = 'nap38_settings';

    public static function init() {
        add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
    }

    public static function register_settings() {
        register_setting(
            'nap38_settings_group',
            self::OPTION_KEY,
            [ __CLASS__, 'sanitize' ]
        );
    }

    /**
     * Returns all settings with defaults.
     */
    public static function get_all(): array {
        $defaults = [
            'eik'          => '',
            'eshop_n'      => '',
            'eshop_type'   => '1',
            'domain_name'  => home_url(),
            'order_status' => [ 'wc-completed' ],
            'eur_rate'     => '1.95583', // Fixed BGN/EUR rate
        ];

        $saved = get_option( self::OPTION_KEY, [] );

        return wp_parse_args( $saved, $defaults );
    }

    /**
     * Returns a single setting value.
     */
    public static function get( string $key, $default = '' ) {
        $all = self::get_all();
        return $all[ $key ] ?? $default;
    }

    /**
     * Sanitizes settings before saving.
     */
    public static function sanitize( $input ): array {
        $clean = [];
        $clean['eik']          = sanitize_text_field( $input['eik'] ?? '' );
        $clean['eshop_n']      = sanitize_text_field( $input['eshop_n'] ?? '' );
        $clean['eshop_type']   = in_array( $input['eshop_type'] ?? '1', [ '1', '2' ] ) ? $input['eshop_type'] : '1';
        $clean['domain_name']  = esc_url_raw( $input['domain_name'] ?? home_url() );
        $clean['eur_rate']     = preg_replace( '/[^0-9.]/', '', $input['eur_rate'] ?? '1.95583' );

        // Order statuses – stored as array of wc-* slugs
        if ( isset( $input['order_status'] ) && is_array( $input['order_status'] ) ) {
            $valid_statuses        = array_keys( wc_get_order_statuses() );
            $clean['order_status'] = array_intersect( $input['order_status'], $valid_statuses );
        } else {
            $clean['order_status'] = [ 'wc-completed' ];
        }

        return $clean;
    }
}
