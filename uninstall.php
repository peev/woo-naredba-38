<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package NAP38
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'nap38_settings' );
