<?php

/**
 * Xanzu IPN Handler
 *
 * Handles Instant Payment Notifications from Xanzu
 *
 * @package Xanzu_Payment
 */

if (!defined('ABSPATH')) {
    exit;
}

class Xanzu_IPN_Handler
{

    /**
     * API Handler instance
     *
     * @var Xanzu_API_Handler
     */
    private $api_handler;

    /**
     * Constructor
     */
    public function __construct()
    {
        // Initialize API handler
        add_action('woocommerce_api_xanzu_ipn_handler', array($this, 'handle_ipn'));
        add_action('woocommerce_api_xanzu_return_handler', array($this, 'handle_return'));
        add_action('woocommerce_order_status_changed', array($this, 'handle_order_status_changed'), 10, 4);
        add_filter('woocommerce_thankyou_order_received_text', array($this, 'filter_order_received_text'), 10, 2);
    }

    public function init()
    {
        $settings = get_option('woocommerce_' . XANZU_PLUGIN_ID . '_settings', array());

        // Prepare settings array for API handler
        $api_settings = array(
            'api_url' => isset($settings['api_url']) ? $settings['api_url'] : '',
            'public_key' => isset($settings['public_key']) ? $settings['public_key'] : '',
            'secret_key' => isset($settings['secret_key']) ? $settings['secret_key'] : '',
            'webhook_secret' => isset($settings['webhook_secret']) ? $settings['webhook_secret'] : '',
            'sandbox_mode' => isset($settings['sandbox_mode']) ? $settings['sandbox_mode'] : 'no',
        );

        // Initialize API handler
        $this->api_handler = new Xanzu_API_Handler($api_settings);
    }

    /**
     * Handle IPN request
     */
    public function handle_ipn()
    {
        $this->init();
        $raw_input = file_get_contents('php://input');
        $json_payload = json_decode($raw_input, true);
        $request_payload = is_array($json_payload) ? $json_payload : wp_unslash($_POST);

        // Get request data
        $status = isset($request_payload['status']) ? sanitize_text_field($request_payload['status']) : '';
        $signature = isset($request_payload['signature']) ? sanitize_text_field($request_payload['signature']) : '';
        $data = isset($request_payload['data']) && is_array($request_payload['data'])
            ? array_map('sanitize_text_field', $request_payload['data'])
            : array();

        // Log IPN request
        $this->log_ipn('IPN received', array(
            'status' => $status,
            'signature' => $signature,
            'data' => $data,
            'get_params' => $_POST,
        ));

        // Validate signature
        if (empty($signature) || empty($data)) {
            $this->log_ipn('IPN validation failed: Missing data');
            status_header(400);
            exit;
        }

        $transaction_id = isset($data['transaction_id']) ? sanitize_text_field($data['transaction_id']) : '';
        $total_amount = isset($data['total_amount']) ? sanitize_text_field($data['total_amount']) : '';

        if (empty($transaction_id) || empty($total_amount)) {
            $this->log_ipn('IPN validation failed: Missing transaction data');
            status_header(400);
            exit;
        }

        $expected_payload = array(
            'status' => $status,
            'transaction_id' => $transaction_id,
            'total_amount' => $total_amount,
            'data' => $data,
        );

        // Verify signature
        if (!$this->api_handler->verify_ipn_signature($transaction_id, $total_amount, $signature) && !$this->verify_webhook_signature($signature, $expected_payload, $raw_input)) {
            $this->log_ipn('IPN validation failed: Invalid signature', array(
                'transaction_id' => $transaction_id,
                'total_amount' => $total_amount,
            ));
            status_header(400);
            exit;
        }

        // Find order by transaction ID
        $order = wc_get_order($transaction_id);

        if (empty($order)) {
            $this->log_ipn('IPN validation failed: Order not found', array(
                'transaction_id' => $transaction_id,
            ));
            status_header(404);
            exit;
        }

        // Check if payment was successful
        if ($status === 'success') {
            $this->process_successful_payment($order, $data);
        } else {
            $this->log_ipn('IPN received with non-success status', array(
                'order_id' => $order->get_id(),
                'status' => $status,
            ));
        }

        // Return success response
        status_header(200);
        echo 'OK';
        exit;
    }

    /**
     * Handle browser return callback from Xanzu hosted checkout.
     */
    public function handle_return()
    {
        $this->init();

        $payload = wp_unslash($_GET);
        $raw_order_id = isset($payload['order_id']) ? sanitize_text_field($payload['order_id']) : '';
        $transaction_id = isset($payload['transaction_id']) ? sanitize_text_field($payload['transaction_id']) : '';
        $order_id = $raw_order_id !== '' ? absint($raw_order_id) : 0;
        $status = isset($payload['status']) ? sanitize_text_field($payload['status']) : '';
        $signature = isset($payload['signature']) ? sanitize_text_field($payload['signature']) : '';

        if ($order_id <= 0 && $transaction_id !== '') {
            $order_id = absint($transaction_id);
        }

        if ($order_id <= 0) {
            wp_safe_redirect(wc_get_checkout_url());
            exit;
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            wp_safe_redirect(wc_get_checkout_url());
            exit;
        }

        $settings = get_option('woocommerce_' . XANZU_PLUGIN_ID . '_settings', array());
        $require_signature = !isset($settings['strict_callback_signature']) || $settings['strict_callback_signature'] === 'yes';

        $verify_payload = array(
            // Preserve string values exactly as Xanzu signed them before resolving the local order.
            'order_id' => $raw_order_id !== '' ? $raw_order_id : ($transaction_id !== '' ? $transaction_id : (string) $order_id),
            'status' => $status,
            'transaction_id' => $transaction_id,
            'session_id' => isset($payload['session_id']) ? sanitize_text_field($payload['session_id']) : '',
            'timestamp' => isset($payload['timestamp']) ? sanitize_text_field($payload['timestamp']) : '',
        );

        if ($require_signature && !$this->api_handler->verify_callback_signature($signature, $verify_payload, urldecode(http_build_query($payload)))) {
            $this->log_ipn('Return validation failed: Invalid signature', $verify_payload);
            $order->add_order_note(esc_html__('Xanzu return callback failed signature verification.', 'xanzu-payment'));
            wc_add_notice(esc_html__('Unable to verify payment callback. Please contact support if money was deducted.', 'xanzu-payment'), 'error');
            wp_safe_redirect($order->get_cancel_order_url_raw());
            exit;
        }

        $normalized_status = strtolower($status);
        $success_states = array('success', 'approved', 'completed', 'paid');
        $cancel_states = array('cancel', 'cancelled', 'canceled', 'failed', 'declined');

        if (in_array($normalized_status, $success_states, true)) {
            // Persist sandbox hint so the thank-you page can reflect sandbox/test payments.
            if ($this->is_gateway_sandbox_enabled()) {
                $order->update_meta_data('_xanzu_sandbox_mode', 'yes');
            }

            if (!$order->has_status(array('processing', 'completed'))) {
                $order->payment_complete();
                $order->add_order_note(esc_html__('Xanzu BNPL checkout approved on hosted session return.', 'xanzu-payment'));
                $order->update_meta_data('_xanzu_return_status', $normalized_status);
                $order->save();
            }

            wp_safe_redirect($order->get_checkout_order_received_url());
            exit;
        }

        if (in_array($normalized_status, $cancel_states, true)) {
            if (!$order->has_status(array('cancelled', 'failed'))) {
                $order->update_status('cancelled', esc_html__('Xanzu BNPL checkout was cancelled by customer or provider.', 'xanzu-payment'));
                $order->update_meta_data('_xanzu_return_status', $normalized_status);
                $order->save();
            }

            wp_safe_redirect($order->get_cancel_order_url_raw());
            exit;
        }

        wp_safe_redirect($order->get_checkout_order_received_url());
        exit;
    }

    /**
     * Sync WooCommerce order status changes back to Xanzu BNPL.
     *
     * @param int $order_id
     * @param string $old_status
     * @param string $new_status
     * @param WC_Order $order
     */
    public function handle_order_status_changed($order_id, $old_status, $new_status, $order)
    {
        if (!$order instanceof WC_Order) {
            $order = wc_get_order($order_id);
        }

        if (!$order || $order->get_payment_method() !== XANZU_PLUGIN_ID) {
            return;
        }

        $this->init();

        $payload = array(
            'session_id' => sanitize_text_field((string) $order->get_meta('_xanzu_checkout_session_id')),
            'merchant_order_id' => (string) $order->get_id(),
            'woocommerce_status' => sanitize_key($new_status),
            'previous_status' => sanitize_key($old_status),
            'order_key' => $order->get_order_key(),
            'total_amount' => wc_format_decimal($order->get_total(), 2),
            'currency' => $order->get_currency(),
            'updated_at' => gmdate('c'),
        );

        $result = $this->api_handler->send_order_status_update($payload);
        if (is_wp_error($result)) {
            $this->log_ipn('Order status sync failed', array(
                'order_id' => $order->get_id(),
                'status' => $new_status,
                'message' => $result->get_error_message(),
            ));
        }
    }

    /**
     * Verify webhook signature with the configured webhook secret.
     *
     * @param string $signature Received signature.
     * @param array $payload Normalized payload.
     * @param string $raw_input Raw request body.
     * @return bool
     */
    private function verify_webhook_signature($signature, $payload, $raw_input = '')
    {
        $settings = get_option('woocommerce_' . XANZU_PLUGIN_ID . '_settings', array());
        $webhook_secret = isset($settings['webhook_secret']) ? $settings['webhook_secret'] : '';

        if (empty($webhook_secret)) {
            return false;
        }

        $payload_candidates = array();

        if (!empty($raw_input)) {
            $payload_candidates[] = $raw_input;
        }

        $payload_candidates[] = wp_json_encode($payload);
        $payload_candidates[] = implode('|', array(
            isset($payload['status']) ? $payload['status'] : '',
            isset($payload['transaction_id']) ? $payload['transaction_id'] : '',
            isset($payload['total_amount']) ? $payload['total_amount'] : '',
        ));

        foreach ($payload_candidates as $candidate) {
            $expected_signature = hash_hmac('sha256', $candidate, $webhook_secret);
            if (hash_equals($expected_signature, $signature)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Process successful payment
     *
     * @param WC_Order $order Order object
     * @param array $data Payment data
     */
    private function process_successful_payment($order, $data)
    {
        // Check if order is already processed
        if ($order->has_status(array('processing', 'completed'))) {
            $this->log_ipn('Order already processed', array(
                'order_id' => $order->get_id(),
            ));
            return;
        }

        // Validate amount
        $received_amount = isset($data['total_amount']) ? floatval($data['total_amount']) : 0;
        $order_amount = floatval($order->get_total());

        // Update order status
        if ($order->get_status() === 'checkout-draft') {
            $order->set_status('pending');
            $order->save();
        }

        $order->payment_complete();
        $order->add_order_note(sprintf(esc_html__('Payment received via Xanzu Payment Gateway. Transaction ID: %s', 'xanzu-payment'), sanitize_text_field($data['transaction_id'])));

        // Store payment details
        $order->update_meta_data('_xanzu_payment_status', 'completed');
        $order->update_meta_data('_xanzu_payment_amount', $received_amount);
        $order->update_meta_data('_xanzu_payment_currency', isset($data['currency']) ? $data['currency'] : '');
        $order->update_meta_data('_xanzu_sandbox_mode', $this->resolve_sandbox_meta_value($data));
        $order->save();

        // Reduce stock levels
        wc_reduce_stock_levels($order->get_id());

        // Clear any cached data
        wc_delete_shop_order_transients($order->get_id());

        $this->log_ipn('Payment processed successfully', array(
            'order_id' => $order->get_id(),
            'transaction_id' => $data['transaction_id'],
            'amount' => $received_amount,
        ));
    }

    /**
     * Customize WooCommerce order-received message for sandbox orders.
     *
     * @param string $text
     * @param WC_Order|false $order
     * @return string
     */
    public function filter_order_received_text($text, $order)
    {
        if (!$order instanceof WC_Order) {
            return $text;
        }

        if ($order->get_payment_method() !== XANZU_PLUGIN_ID) {
            return $text;
        }

        $sandboxMeta = $order->get_meta('_xanzu_sandbox_mode', true);
        if ((string) $sandboxMeta !== 'yes') {
            return $text;
        }

        return sprintf(
            '[%s] %s',
            esc_html__('Sandbox', 'xanzu-payment'),
            $text
        );
    }

    /**
     * Resolve and normalize sandbox mode from callback payload.
     *
     * @param array $data
     * @return string
     */
    private function resolve_sandbox_meta_value($data)
    {
        if (!is_array($data)) {
            return $this->is_gateway_sandbox_enabled() ? 'yes' : 'no';
        }

        if (!array_key_exists('sandbox_mode', $data)) {
            return $this->is_gateway_sandbox_enabled() ? 'yes' : 'no';
        }

        $raw = strtolower(trim((string) $data['sandbox_mode']));
        if (in_array($raw, array('1', 'true', 'yes', 'on'), true)) {
            return 'yes';
        }

        return 'no';
    }

    /**
     * Determine whether gateway sandbox mode is enabled in plugin settings.
     *
     * @return bool
     */
    private function is_gateway_sandbox_enabled()
    {
        $settings = get_option('woocommerce_' . XANZU_PLUGIN_ID . '_settings', array());

        return isset($settings['sandbox_mode']) && $settings['sandbox_mode'] === 'yes';
    }

    /**
     * Log IPN events
     *
     * @param string $message Log message
     * @param array $context Additional context
     */
    private function log_ipn($message, $context = array())
    {
        if (defined('WP_DEBUG') && WP_DEBUG && function_exists('wc_get_logger')) {
            $logger = wc_get_logger();
            $logger->info($message, array(
                'source' => 'xanzu-ipn',
                'context' => $context,
            ));
        }
    }
}
