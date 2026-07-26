<?php
/**
 * Xanzu Payment Gateway Uninstall
 *
 * Uninstalling Xanzu Payment Gateway deletes options and transients.
 *
 * @package Xanzu_Payment
 * @version 1.0
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete plugin options
delete_option('woocommerce_xanzu_payment_settings');
delete_option('woocommerce_xanzu_payment_settings');

// Delete transients
delete_transient('xanzu_access_token');
delete_transient('xanzu_access_token');
