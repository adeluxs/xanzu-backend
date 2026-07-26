<?php

if (!defined('ABSPATH'))
    exit;

class Xanzu_Payment_Blocks_Support extends Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType
{

    protected $name = XANZU_PLUGIN_ID;
    private $gateway;

    public function initialize()
    {
        $this->settings = get_option("woocommerce_{$this->name}_settings", array());
        $this->gateway = new Xanzu_Payment_Gateway();
    }

    public function is_active()
    {
        return $this->gateway->is_available();
    }

    public function get_payment_method_script_handles()
    {
        wp_register_script(
            'xanzu-payment-blocks-integration',
            XANZU_PAYMENT_URL . 'assets/js/blocks-integration.js',
            array(
                'wc-blocks-registry',
                'wc-settings',
                'wp-element',
                'wp-html-entities',
            ),
            XANZU_PAYMENT_VERSION,
            true
        );

        return array('xanzu-payment-blocks-integration');
    }

    public function get_payment_method_data()
    {
        return array(
            'title' => $this->gateway->title,
            'description' => $this->gateway->description,
            'supports' => $this->get_supported_features(),
            'icon' => $this->gateway->icon,
        );
    }

    public function get_supported_features()
    {
        return $this->gateway->supports;
    }
}
