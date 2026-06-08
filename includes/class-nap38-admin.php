<?php
defined( 'ABSPATH' ) || exit;

/**
 * WordPress admin pages: Settings + Export.
 */
class NAP38_Admin {

    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'add_menu' ] );
        add_action( 'admin_post_nap38_export', [ __CLASS__, 'handle_export' ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_styles' ] );
    }

    public static function add_menu() {
        add_menu_page(
            'НАП Приложение 38',
            'НАП Прил. 38',
            'manage_woocommerce',
            'nap38',
            [ __CLASS__, 'render_export_page' ],
            'dashicons-media-code',
            58
        );

        add_submenu_page(
            'nap38',
            'Генериране на XML',
            'Генериране',
            'manage_woocommerce',
            'nap38',
            [ __CLASS__, 'render_export_page' ]
        );

        add_submenu_page(
            'nap38',
            'Настройки',
            'Настройки',
            'manage_options',
            'nap38-settings',
            [ __CLASS__, 'render_settings_page' ]
        );
    }

    public static function enqueue_styles( $hook ) {
        if ( strpos( $hook, 'nap38' ) === false ) {
            return;
        }
        // Inline styles – no external file needed
        wp_add_inline_style( 'wp-admin', self::inline_css() );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Settings page
    // ─────────────────────────────────────────────────────────────────────────

    public static function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Нямате достъп.' );
        }

        $settings       = NAP38_Settings::get_all();
        $all_statuses   = wc_get_order_statuses();
        $saved_statuses = (array) $settings['order_status'];
        ?>
        <div class="wrap nap38-wrap">
            <h1>⚙️ НАП Приложение 38 – Настройки</h1>

            <?php settings_errors( 'nap38_settings_group' ); ?>

            <form method="post" action="options.php">
                <?php settings_fields( 'nap38_settings_group' ); ?>

                <table class="form-table nap38-table">
                    <tr>
                        <th><label for="nap38_eik">ЕИК на задълженото лице <span class="required">*</span></label></th>
                        <td>
                            <input type="text" id="nap38_eik" name="nap38_settings[eik]"
                                   value="<?php echo esc_attr( $settings['eik'] ); ?>"
                                   class="regular-text" placeholder="123456789" required />
                            <p class="description">Вашият ЕИК / БУЛСТАТ.</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="nap38_eshop_n">Уникален № на е-магазина <span class="required">*</span></label></th>
                        <td>
                            <input type="text" id="nap38_eshop_n" name="nap38_settings[eshop_n]"
                                   value="<?php echo esc_attr( $settings['eshop_n'] ); ?>"
                                   class="regular-text" placeholder="BG-SHOP-12345" required />
                            <p class="description">Номерът от НАП, получен при подаване на Приложение №33.</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label>Тип магазин</label></th>
                        <td>
                            <label>
                                <input type="radio" name="nap38_settings[eshop_type]" value="1"
                                    <?php checked( $settings['eshop_type'], '1' ); ?> />
                                1 – Собствен/нает домейн
                            </label>
                            &nbsp;&nbsp;
                            <label>
                                <input type="radio" name="nap38_settings[eshop_type]" value="2"
                                    <?php checked( $settings['eshop_type'], '2' ); ?> />
                                2 – Онлайн платформа
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="nap38_domain">Уеб адрес на магазина <span class="required">*</span></label></th>
                        <td>
                            <input type="url" id="nap38_domain" name="nap38_settings[domain_name]"
                                   value="<?php echo esc_attr( $settings['domain_name'] ); ?>"
                                   class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th><label for="nap38_eur_rate">Курс BGN/EUR</label></th>
                        <td>
                            <input type="text" id="nap38_eur_rate" name="nap38_settings[eur_rate]"
                                   value="<?php echo esc_attr( $settings['eur_rate'] ); ?>"
                                   class="small-text" />
                            <p class="description">Фиксиран курс: 1 EUR = 1.95583 BGN. Променете само ако магазинът работи в EUR.</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label>Статуси на поръчките</label></th>
                        <td>
                            <?php foreach ( $all_statuses as $slug => $label ) : ?>
                                <label style="display:block; margin-bottom:4px;">
                                    <input type="checkbox" name="nap38_settings[order_status][]"
                                           value="<?php echo esc_attr( $slug ); ?>"
                                        <?php checked( in_array( $slug, $saved_statuses, true ) ); ?> />
                                    <?php echo esc_html( $label ); ?>
                                </label>
                            <?php endforeach; ?>
                            <p class="description">Поръчките с тези статуси ще се включат в отчета.</p>
                        </td>
                    </tr>
                </table>

                <div class="nap38-notice nap38-notice--info">
                    <strong>💡 Плащания:</strong> Plugin-ът автоматично разпознава Stripe, PayPal, наложен платеж и др.
                    За нестандартни gateway-и добавете <code>$order->update_meta_data('_nap38_paym', '2');</code>
                    или използвайте филтрите <code>nap38_virtual_pos_methods</code> / <code>nap38_default_payment_code</code>.
                </div>

                <?php submit_button( 'Запази настройките' ); ?>
            </form>
        </div>
        <?php
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Export page
    // ─────────────────────────────────────────────────────────────────────────

    public static function render_export_page() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( 'Нямате достъп.' );
        }

        $settings_ok = NAP38_Settings::get( 'eik' ) && NAP38_Settings::get( 'eshop_n' );

        // Default to previous month
        $last_month_ts = strtotime( 'first day of last month' );
        $default_year  = (int) wp_date( 'Y', $last_month_ts );
        $default_month = (int) wp_date( 'm', $last_month_ts );

        $year  = $default_year;
        $month = $default_month;

        if ( isset( $_GET['nap38_year'], $_GET['nap38_month'], $_GET['nap38_preview_nonce'] )
            && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nap38_preview_nonce'] ) ), 'nap38_preview' ) ) {
            $year  = (int) $_GET['nap38_year'];
            $month = (int) $_GET['nap38_month'];
        }
        ?>
        <div class="wrap nap38-wrap">
            <h1>📄 НАП Приложение 38 – Генериране на XML</h1>

            <?php if ( ! $settings_ok ) : ?>
                <div class="notice notice-warning">
                    <p>⚠️ Попълнете ЕИК и номера на е-магазина в <a href="<?php echo esc_url( admin_url( 'admin.php?page=nap38-settings' ) ); ?>">Настройки</a> преди да генерирате файл.</p>
                </div>
            <?php endif; ?>

            <div class="nap38-export-card">
                <form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" style="display:inline-flex; gap:12px; align-items:flex-end; flex-wrap:wrap;">
                    <input type="hidden" name="page" value="nap38" />
                    <?php wp_nonce_field( 'nap38_preview', 'nap38_preview_nonce' ); ?>

                    <div>
                        <label for="nap38_year"><strong>Година</strong></label><br/>
                        <select name="nap38_year" id="nap38_year">
                            <?php for ( $y = (int) wp_date( 'Y' ); $y >= 2024; $y-- ) : ?>
                                <option value="<?php echo esc_attr( (string) $y ); ?>" <?php selected( $y, $year ); ?>><?php echo esc_html( (string) $y ); ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div>
                        <label for="nap38_month"><strong>Месец</strong></label><br/>
                        <select name="nap38_month" id="nap38_month">
                            <?php
                            $bg_months = [ 1=>'Януари',2=>'Февруари',3=>'Март',4=>'Април',5=>'Май',6=>'Юни',
                                           7=>'Юли',8=>'Август',9=>'Септември',10=>'Октомври',11=>'Ноември',12=>'Декември' ];
                            foreach ( $bg_months as $num => $name ) :
                            ?>
                                <option value="<?php echo esc_attr( (string) $num ); ?>" <?php selected( $num, $month ); ?>>
                                    <?php echo esc_html( $name ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="button button-secondary">Преглед</button>
                </form>

                <?php
                // Show order count for selected period
                $statuses = NAP38_Settings::get( 'order_status', [ 'wc-completed' ] );
                $statuses_clean = array_map( fn( $s ) => str_replace( 'wc-', '', $s ), $statuses );
                $start = sprintf( '%04d-%02d-01 00:00:00', $year, $month );
                $end   = sprintf( '%04d-%02d-%02d 23:59:59', $year, $month, cal_days_in_month( CAL_GREGORIAN, $month, $year ) );
                $count = count( wc_get_orders( [
                    'status'       => $statuses_clean,
                    'date_created' => $start . '...' . $end,
                    'limit'        => -1,
                    'return'       => 'ids',
                ] ) );
                ?>

                <div class="nap38-stat">
                    Намерени <strong><?php echo esc_html( (string) $count ); ?> поръчки</strong> за
                    <?php echo esc_html( $bg_months[ $month ] . ' ' . $year ); ?>
                </div>

                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                    <?php wp_nonce_field( 'nap38_export_nonce', 'nap38_nonce' ); ?>
                    <input type="hidden" name="action"      value="nap38_export" />
                    <input type="hidden" name="nap38_year"  value="<?php echo esc_attr( $year ); ?>" />
                    <input type="hidden" name="nap38_month" value="<?php echo esc_attr( $month ); ?>" />

                    <button type="submit" class="button button-primary button-hero"
                        <?php disabled( ! $settings_ok ); ?>>
                        ⬇️ Изтегли XML файл
                    </button>
                </form>
            </div>

            <div class="nap38-notice nap38-notice--info" style="margin-top:24px;">
                <strong>ℹ️ Как да подадете файла:</strong>
                Влезте в <a href="https://portal.nap.bg" target="_blank">portal.nap.bg</a> →
                Деклариране → Приложение №38 → качете генерирания XML файл.
            </div>
        </div>
        <?php
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Handle export (file download)
    // ─────────────────────────────────────────────────────────────────────────

    public static function handle_export() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( 'Нямате достъп.' );
        }

        check_admin_referer( 'nap38_export_nonce', 'nap38_nonce' );

        $year  = isset( $_POST['nap38_year'] )  ? (int) $_POST['nap38_year']  : (int) wp_date( 'Y' );
        $month = isset( $_POST['nap38_month'] ) ? (int) $_POST['nap38_month'] : (int) wp_date( 'm' );

        if ( $year < 2020 || $year > 2100 || $month < 1 || $month > 12 ) {
            wp_die( 'Невалидна дата.' );
        }

        $eik    = NAP38_Settings::get( 'eik' );
        $eshop  = NAP38_Settings::get( 'eshop_n' );

        if ( ! $eik || ! $eshop ) {
            wp_die( 'Попълнете ЕИК и номер на е-магазина в Настройките.' );
        }

        $generator = new NAP38_Generator();
        $xml       = $generator->generate( $year, $month );

        $filename = sprintf(
            'NAP38_%s_%04d_%02d.xml',
            sanitize_file_name( $eik ),
            $year,
            $month
        );

        header( 'Content-Type: application/xml; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        header( 'Cache-Control: no-cache, no-store, must-revalidate' );
        header( 'Pragma: no-cache' );
        header( 'Expires: 0' );
        header( 'Content-Length: ' . strlen( $xml ) );

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted plugin-generated XML file download.
        echo $xml;
        exit;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Inline CSS
    // ─────────────────────────────────────────────────────────────────────────

    private static function inline_css(): string {
        return '
        .nap38-wrap { max-width: 860px; }
        .nap38-wrap h1 { margin-bottom: 24px; }
        .nap38-table th { width: 240px; }
        .nap38-table .required { color: #d63638; }
        .nap38-export-card {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 28px 32px;
            margin-bottom: 20px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .nap38-stat {
            font-size: 15px;
            color: #1d2327;
            background: #f0f6fc;
            border-left: 4px solid #0073aa;
            padding: 10px 16px;
            border-radius: 0 4px 4px 0;
        }
        .nap38-notice {
            padding: 14px 18px;
            border-radius: 6px;
            font-size: 13px;
            line-height: 1.6;
        }
        .nap38-notice--info {
            background: #f0f6fc;
            border: 1px solid #c3d9f0;
            color: #135e96;
        }
        .nap38-notice code {
            background: #dce9f5;
            padding: 1px 5px;
            border-radius: 3px;
        }
        ';
    }
}
