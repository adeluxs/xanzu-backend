<?php

/**
 * Plugin Name: Xanzu BNPL Payment Gateway Plugin for WooCommerce
 * Plugin URI: https://tdevs.co
 * Description: Accept BNPL payments through Xanzu BNPL Payment Gateway. Integrates seamlessly with WooCommerce.
 * Version: 1.1
 * Author: Tdevs
 * Author URI: http://tdevs.co
 * Text Domain: xanzu-bnpl-payment
 * Domain Path: /languages
 * Requires Plugins: woocommerce
 * @package Xanzu_Payment
 */


// Exit if accessed directly
if (!defined('ABSPATH'))
    exit;

// Plugin Constant
define('XANZU_PAYMENT_VERSION', '1.0');
define('XANZU_PLUGIN_ID', 'xanzu_payment');
define('XANZU_PAYMENT_FILE', __FILE__);
define('XANZU_PAYMENT_DIR', plugin_dir_path(__FILE__));
define('XANZU_PAYMENT_URL', plugin_dir_url(__FILE__));

// Check if WooCommerce is active
function xanzu_check_woocommerce_active()
{
    if (!class_exists('WC_Payment_Gateway'))
        return;

    return true;
}

function xanzu_woocommerce_missing_notice()
{
    // Only show notice to users who can activate plugins
    if (!current_user_can('activate_plugins')) {
        return;
    }

    $message = sprintf(
        esc_html__('Xanzu Payment Gateway requires %s to be installed and active.', 'xanzu-payment'),
        '<strong>' . esc_html__('WooCommerce', 'xanzu-payment') . '</strong>'
    );
    echo '<div class="error"><p>' . wp_kses_post($message) . '</p></div>';
}

add_action('plugins_loaded', 'xanzu_payment_init', 0);

// Initialize the plugin
function xanzu_payment_init()
{
    // Check if WooCommerce is active
    xanzu_check_woocommerce_active();

    // Load plugin textdomain
    load_plugin_textdomain('xanzu-payment', false, XANZU_PAYMENT_DIR . 'languages/');

    // Include required files
    require_once XANZU_PAYMENT_DIR . 'includes/class-xanzu-payment-gateway.php';
    require_once XANZU_PAYMENT_DIR . 'includes/class-xanzu-api-handler.php';
    require_once XANZU_PAYMENT_DIR . 'includes/class-xanzu-ipn-handler.php';

    // Initialize IPN handler
    new Xanzu_IPN_Handler();

    // Add Xanzu Payment Gateway to WooCommerce
    add_filter('woocommerce_payment_gateways', 'xanzu_add_payment_gateway');
    add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'xanzu_plugin_settings_link');
}

function xanzu_plugin_settings_link($links)
{
    // Only show settings link to users who can manage WooCommerce
    if (!current_user_can('manage_woocommerce')) {
        return $links;
    }

    $settings_url = add_query_arg(
        array(
            'page' => 'wc-settings',
            'tab' => 'checkout',
            'section' => XANZU_PLUGIN_ID,
            'from' => 'WCADMIN_PAYMENT_SETTINGS',
        ),
        admin_url('admin.php')
    );
    $settings_link = '<a href="' . esc_url($settings_url) . '">' . esc_html__('Settings', 'xanzu-payment') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}

function xanzu_add_payment_gateway($gateways)
{
    $gateways[] = 'Xanzu_Payment_Gateway';
    return $gateways;
}

// Add block support for WooCommerce checkout blocks
add_action('woocommerce_blocks_loaded', 'xanzu_register_payment_method_block_support');

function xanzu_register_payment_method_block_support()
{
    // Check if the Block Registry class exists
    if (!class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
        error_log('Xanzu: WooCommerce Blocks not available');
        return;
    }

    // Include the block integration class
    require_once XANZU_PAYMENT_DIR . 'includes/class-xanzu-payment-blocks-support.php';

    // Register the block integration
    add_action('woocommerce_blocks_payment_method_type_registration', function ($registry) {
        $registry->register(new Xanzu_Payment_Blocks_Support());
    });
}

// Enqueue block assets
add_action('enqueue_block_assets', 'xanzu_enqueue_block_assets');

function xanzu_enqueue_block_assets()
{
    if (is_checkout() || has_block('woocommerce/checkout')) {
        wp_enqueue_style(
            'xanzu-payment-blocks-style',
            XANZU_PAYMENT_URL . 'assets/css/blocks-style.css',
            array(),
            XANZU_PAYMENT_VERSION
        );
    }
}
