<?php

if (!defined('ABSPATH'))
    exit;

class Xanzu_Payment_Gateway extends WC_Payment_Gateway
{
    /*
     * API Handler
     * 
     * @var Xanzu_API_Handler
     * 
     */
    private $api_handler;

    public function __construct()
    {
        $this->id = XANZU_PLUGIN_ID;
        $this->icon = esc_url(XANZU_PAYMENT_URL . 'assets/images/logo.svg');
        $this->has_fields = false;
        $this->method_title = esc_html__('Xanzu Payments', 'xanzu-payment');
        $this->method_description = esc_html__('Pay with Xanzu BNPL. Customers authenticate on Xanzu, choose split/installments, then return to complete the order.', 'xanzu-payment');
        $this->supports = array(
            'products'
        );

        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->enabled = $this->get_option('enabled');

        // For update Xanzu payment gateway settings
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
    }

    /**
     * Initialize Gateway Settings Form Fields
     */
    public function init_form_fields()
    {
        // Check if user has permission to manage WooCommerce settings
        if (!current_user_can('manage_woocommerce')) {
            return;
        }

        $this->form_fields = array(
            'enabled' => array(
                'title' => esc_html__('Enable/Disable', 'xanzu-payment'),
                'type' => 'checkbox',
                'label' => esc_html__('Enable Xanzu Payment Gateway', 'xanzu-payment'),
                'default' => 'no',
            ),
            'title' => array(
                'title' => esc_html__('Title', 'xanzu-payment'),
                'type' => 'text',
                'description' => esc_html__('This controls the title which the user sees during checkout.', 'xanzu-payment'),
                'default' => esc_html__('Xanzu Payment', 'xanzu-payment'),
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => esc_html__('Description', 'xanzu-payment'),
                'type' => 'textarea',
                'description' => esc_html__('This controls the description which the user sees during checkout.', 'xanzu-payment'),
                'default' => esc_html__('Pay securely through Xanzu Payment Gateway.', 'xanzu-payment'),
                'desc_tip' => true,
            ),
            'api_url' => array(
                'title' => esc_html__('API URL', 'xanzu-payment'),
                'type' => 'text',
                'description' => esc_html__('Enter your Xanzu Merchant API base URL (e.g., https://xanzu.com/api/merchant)', 'xanzu-payment'),
                'default' => '',
                'desc_tip' => true,
                'custom_attributes' => array(
                    'placeholder' => 'https://xanzu.com/api/merchant',
                ),
            ),
            'public_key' => array(
                'title' => esc_html__('Public Key', 'xanzu-payment'),
                'type' => 'text',
                'description' => esc_html__('Enter your Xanzu Public Key', 'xanzu-payment'),
                'default' => '',
                'desc_tip' => true,
            ),
            'secret_key' => array(
                'title' => esc_html__('Secret Key', 'xanzu-payment'),
                'type' => 'password',
                'description' => esc_html__('Enter your Xanzu Secret Key', 'xanzu-payment'),
                'default' => '',
                'desc_tip' => true,
            ),
            'webhook_secret' => array(
                'title' => esc_html__('Webhook Secret', 'xanzu-payment'),
                'type' => 'password',
                'description' => esc_html__('Enter your Xanzu Webhook Secret used to verify callbacks.', 'xanzu-payment'),
                'default' => '',
                'desc_tip' => true,
            ),
            'sandbox_mode' => array(
                'title' => esc_html__('Sandbox Mode', 'xanzu-payment'),
                'type' => 'checkbox',
                'label' => esc_html__('Enable Sandbox Mode', 'xanzu-payment'),
                'default' => 'no',
                'description' => esc_html__('Enable this to use sandbox/test mode for testing payments.', 'xanzu-payment'),
            ),
            'strict_callback_signature' => array(
                'title' => esc_html__('Strict Callback Signature', 'xanzu-payment'),
                'type' => 'checkbox',
                'label' => esc_html__('Require signed browser callback from Xanzu', 'xanzu-payment'),
                'default' => 'yes',
                'description' => esc_html__('Reject return callbacks when signature verification fails.', 'xanzu-payment'),
            ),
        );
    }

    /**
     * Process admin options with capability check
     *
     * @return bool
     */
    public function process_admin_options()
    {
        // Check if user has permission to manage WooCommerce settings
        if (!current_user_can('manage_woocommerce')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'xanzu-payment'));
        }

        return parent::process_admin_options();
    }

    /**
     * Check if the gateway is available for use
     *
     * @return bool
     */
    public function is_available()
    {
        // Check if gateway is enabled
        if ($this->enabled !== 'yes') {
            return false;
        }

        return true;
    }

    /**
     * Process the payment and return the result
     *
     * @param int $order_id Order ID
     * @return array
     */
    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);

        if (!$order) {
            throw new Exception(esc_html__('We could not start your BNPL checkout for this order. Please try again.', 'xanzu-payment'));
        }

        // Get gateway settings
        $api_url = $this->get_option('api_url');
        $public_key = $this->get_option('public_key');
        $secret_key = $this->get_option('secret_key');
        $webhook_secret = $this->get_option('webhook_secret');

        // Initialize API handler with gateway settings
        if (!$this->api_handler) {
            $this->api_handler = new Xanzu_API_Handler(array(
                'api_url' => $api_url,
                'public_key' => $public_key,
                'secret_key' => $secret_key,
                'webhook_secret' => $webhook_secret,
                'sandbox_mode' => $this->get_option('sandbox_mode'),
                'timeout' => 30,
            ));
        }

        $credentials = $this->api_handler->validate_credentials();
        if (is_wp_error($credentials)) {
            throw new Exception(esc_html($credentials->get_error_message()));
        }

        $session_data = $this->build_checkout_session_payload($order, $public_key, $secret_key);
        $result = $this->api_handler->create_checkout_session($session_data);

        if (is_wp_error($result)) {
            $error_message = is_array($result->get_error_message()) ? implode(', ', $result->get_error_message()) : $result->get_error_message();
            throw new Exception(esc_html($error_message));
        }

        // Check if redirect URL is available
        if (!isset($result['redirect_url']) || empty($result['redirect_url'])) {
            throw new Exception(esc_html__('We could not start the BNPL checkout right now. Please try again.', 'xanzu-payment'));
        }

        if (!empty($result['session_id'])) {
            $order->update_meta_data('_xanzu_checkout_session_id', sanitize_text_field($result['session_id']));
        }

        $order->update_meta_data('_xanzu_checkout_init_at', current_time('mysql'));
        $order->save();

        // Mark order as pending payment
        $order->update_status('pending', esc_html__('Awaiting Xanzu BNPL approval', 'xanzu-payment'));

        WC()->cart->empty_cart();

        // Return redirect to Xanzu checkout/auth page
        return array(
            'result' => 'success',
            'redirect' => esc_url_raw($result['redirect_url']),
        );
    }

    /**
     * Build BNPL checkout session payload from Woo order.
     *
     * @param WC_Order $order
     * @param string $public_key
     * @param string $secret_key
     * @return array
     */
    private function build_checkout_session_payload($order, $public_key, $secret_key)
    {
        $order_id = $order->get_id();
        $order_total = wc_format_decimal($order->get_total(), 2);
        $currency = $order->get_currency();
        $timestamp = time();

        $return_url = add_query_arg(
            array(
                'wc-api' => 'xanzu_return_handler',
                'order_id' => $order_id,
                'order_key' => $order->get_order_key(),
            ),
            home_url('/')
        );

        $items = array();
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            $items[] = array(
                'name' => $item->get_name(),
                'sku' => $product ? $product->get_sku() : '',
                'quantity' => (int) $item->get_quantity(),
                'unit_price' => wc_format_decimal($item->get_total() / max(1, $item->get_quantity()), 2),
                'line_total' => wc_format_decimal($item->get_total(), 2),
                'image' => $product && $product->get_image_id()
                    ? wp_get_attachment_image_url($product->get_image_id(), 'full')
                    : '',
            );
        }

        $signature_payload = implode('|', array(
            $public_key,
            $order_id,
            $order_total,
            $currency,
            $timestamp,
        ));

        return array(
            'merchant_public_key' => $public_key,
            'merchant_order_id' => (string) $order_id,
            'merchant_reference_id' => (string) $order->get_order_key(),
            'transaction_id' => (string) $order_id,
            'amount' => $order_total,
            'currency' => $currency,
            'timestamp' => $timestamp,
            'request_signature' => hash_hmac('sha256', $signature_payload, $secret_key),
            'customer' => array(
                'name' => trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()),
                'email' => $order->get_billing_email(),
                'phone' => $order->get_billing_phone(),
                'image' => $order->get_billing_email() ? 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($order->get_billing_email()))) . '?s=200&d=mm' : '',
            ),
            'items' => $items,
            'shipping_total' => wc_format_decimal($order->get_shipping_total(), 2),
            'tax_total' => wc_format_decimal($order->get_total_tax(), 2),
            'discount_total' => wc_format_decimal($order->get_discount_total(), 2),
            'success_url' => $return_url,
            'callback_url' => home_url('?wc-api=xanzu_ipn_handler'),
            'cancel_url' => $order->get_cancel_order_url_raw(),
            'webhook_url' => home_url('?wc-api=xanzu_ipn_handler'),
            'metadata' => array(
                'order_key' => $order->get_order_key(),
                'site_url' => home_url('/'),
                'platform' => 'wordpress-woocommerce',
                'checkout_source' => 'woocommerce_plugin',
                'sandbox_mode' => $this->get_option('sandbox_mode') === 'yes',
                'strict_callback_signature' => $this->get_option('strict_callback_signature', 'yes') === 'yes',
            ),
        );
    }

    /**
     * Check if the gateway supports block-based checkout
     *
     * @return bool
     */
    public function supports($feature)
    {
        $supports = array('products');

        // Add block support
        if ('tokenization' === $feature) {
            return false;
        }

        return in_array($feature, $supports);
    }

    /**
     * Admin options with capability check
     * Display admin options with proper capability verification
     */
    public function admin_options()
    {
        // Check if user has permission to manage WooCommerce settings
        if (!current_user_can('manage_woocommerce')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'xanzu-payment'));
        }

        parent::admin_options();
    }

    /**
     * Validate settings fields with capability check
     *
     * @return bool
     */
    public function validate_fields()
    {
        // For admin settings, check capability
        if (is_admin() && !current_user_can('manage_woocommerce')) {
            return false;
        }

        return parent::validate_fields();
    }
}
