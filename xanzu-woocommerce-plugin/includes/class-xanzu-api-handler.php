<?php

/**
 * Xanzu API Handler
 *
 * Handles all API communication with Xanzu Payment Gateway
 *
 * @package XanzuPaymentGateway
 */

if (!defined('ABSPATH')) {
    exit;
}

class Xanzu_API_Handler
{
    private const CHECKOUT_SESSION_ENDPOINT = '/bnpl/checkout-session';
    private const ORDER_STATUS_ENDPOINT = '/bnpl/order-status';

    /**
     * API Base URL
     *
     * @var string
     */
    private $api_url;

    /**
     * Sandbox mode
     *
     * @var bool
     */
    private $sandbox_mode;

    /**
     * Public Key
     *
     * @var string
     */
    private $public_key;

    /**
     * Secret Key
     *
     * @var string
     */
    private $secret_key;

    /**
     * Webhook Secret
     *
     * @var string
     */
    private $webhook_secret;

    /**
     * Access Token
     *
     * @var string
     */
    private $access_token;

    /**
     * Token Expiry
     *
     * @var int
     */
    private $token_expiry = 120; // 2 minutes

    /**
     * Request timeout in seconds.
     *
     * @var int
     */
    private $timeout = 30;

    /**
     * Constructor
     */
    public function __construct($settings)
    {
        // Safely get settings with defaults
        $this->api_url = isset($settings['api_url']) ? $settings['api_url'] : '';
        $this->sandbox_mode = isset($settings['sandbox_mode']) ? $settings['sandbox_mode'] : 'no';
        $this->public_key = isset($settings['public_key']) ? $settings['public_key'] : '';
        $this->secret_key = isset($settings['secret_key']) ? $settings['secret_key'] : '';
        $this->webhook_secret = isset($settings['webhook_secret']) ? $settings['webhook_secret'] : '';
        $this->timeout = isset($settings['timeout']) ? max(10, intval($settings['timeout'])) : 30;
    }

    /**
     * Get API endpoint URL
     *
     * @param string $endpoint Endpoint path
     * @return string
     */
    private function get_api_endpoint($endpoint)
    {
        $base_url = rtrim($this->api_url, '/');

        $prefix = $this->sandbox_mode !== 'no' ? '/sandbox' : '';
        return $base_url . $prefix . $endpoint;
    }

    /**
     * Build signed request arguments for merchant API requests.
     *
     * @param string $endpoint Endpoint path.
     * @param array|string $body Request body.
     * @param string $method HTTP method.
     * @return array
     */
    private function build_request_args($endpoint, $body, $method = 'POST')
    {
        $json_body = is_string($body) ? $body : wp_json_encode($body);
        $timestamp = gmdate('c');
        $nonce = wp_generate_password(16, false, false);
        $signature_payload = implode('|', array(
            strtoupper($method),
            $endpoint,
            $timestamp,
            $nonce,
            $json_body,
        ));

        return array(
            'timeout' => $this->timeout,
            'sslverify' => str_contains($this->api_url, '.test') || $this->sandbox_mode === 'yes' ? false : true,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'X-Qunzo-Public-Key' => $this->public_key,
                'X-Qunzo-Timestamp' => $timestamp,
                'X-Qunzo-Nonce' => $nonce,
                'X-Qunzo-Signature' => hash_hmac('sha256', $signature_payload, $this->secret_key),
            ),
            'body' => $json_body,
        );
    }

    /**
     * Ensure merchant credentials exist.
     *
     * @return true|WP_Error
     */
    public function validate_credentials()
    {
        if (empty($this->public_key)) {
            return new WP_Error('missing_public_key', esc_html__('Public key is not configured.', 'xanzu-payment'));
        }

        if (empty($this->secret_key)) {
            return new WP_Error('missing_secret_key', esc_html__('Secret key is not configured.', 'xanzu-payment'));
        }

        return true;
    }

    /**
     * Create BNPL checkout session.
     *
     * @param array $session_data Checkout session payload.
     * @param string $endpoint BNPL checkout endpoint.
     * @return array|WP_Error
     */
    public function create_checkout_session($session_data)
    {
        $credentials = $this->validate_credentials();
        if (is_wp_error($credentials)) {
            return $credentials;
        }

        $url = $this->get_api_endpoint(self::CHECKOUT_SESSION_ENDPOINT);
        $request_args = $this->build_request_args(self::CHECKOUT_SESSION_ENDPOINT, $session_data, 'POST');

        $response = wp_remote_post($url, $request_args);
        if (is_wp_error($response)) {
            return $response;
        }

        $data = $this->decode_api_response($response);
        if (is_wp_error($data)) {
            return $data;
        }

        $normalized = $this->normalize_checkout_session_response($data);
        if (empty($normalized['redirect_url'])) {
            return new WP_Error('missing_redirect_url', esc_html__('Checkout session response did not include a redirect URL.', 'xanzu-payment'));
        }

        return $normalized;
    }

    /**
     * Send WooCommerce order status update back to Xanzu.
     *
     * @param array $status_data
     * @return array|WP_Error
     */
    public function send_order_status_update($status_data)
    {
        $credentials = $this->validate_credentials();
        if (is_wp_error($credentials)) {
            return $credentials;
        }

        $url = $this->get_api_endpoint(self::ORDER_STATUS_ENDPOINT);
        $request_args = $this->build_request_args(self::ORDER_STATUS_ENDPOINT, $status_data, 'POST');

        $response = wp_remote_post($url, $request_args);
        if (is_wp_error($response)) {
            return $response;
        }

        return $this->decode_api_response($response);
    }

    /**
     * Get access token
     *
     * @return array|WP_Error
     */
    public function get_access_token()
    {
        // Check if we have a cached token that's still valid
        $cached_token = get_transient('xanzu_access_token');
        if ($cached_token && isset($cached_token['expires_at']) && time() < $cached_token['expires_at']) {
            $this->access_token = $cached_token['token'];
            return array('success' => true, 'token' => $this->access_token);
        }

        if (empty($this->public_key)) {
            return new WP_Error('missing_public_key', esc_html__('Public key is not configured.', 'xanzu-payment'));
        }

        $url = $this->get_api_endpoint('/access-token');

        $response = wp_remote_post($url, $this->build_request_args('/access-token', array(
            'public_key' => $this->public_key,
        )));

        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        $status_code = wp_remote_retrieve_response_code($response);

        if ($status_code !== 200 || !isset($data['status']) || $data['status'] !== 'success') {
            $message = isset($data['message']) ? $data['message'] : esc_html__('Failed to get access token.', 'xanzu-payment');
            return new WP_Error('api_error', $message);
        }

        // Cache the token
        $expires_at = isset($data['expires_in']) ? strtotime($data['expires_in']) : (time() + $this->token_expiry);
        set_transient('xanzu_access_token', array(
            'token' => $data['token'],
            'expires_at' => $expires_at,
        ), $this->token_expiry);

        $this->access_token = $data['token'];
        return array('success' => true, 'token' => $this->access_token);
    }

    /**
     * Create payment
     *
     * @param array $payment_data Payment data
     * @return array|WP_Error
     */
    public function create_payment($payment_data)
    {
        // Ensure we have a valid access token
        $token_result = $this->get_access_token();
        if (is_wp_error($token_result)) {
            return $token_result;
        }

        $url = $this->get_api_endpoint('/make-payment');

        $request_args = $this->build_request_args('/make-payment', $payment_data);
        $request_args['headers']['Authorization'] = 'Bearer ' . $this->access_token;

        $response = wp_remote_post($url, $request_args);

        if (is_wp_error($response)) {
            return $response;
        }

        $data = $this->decode_api_response($response);
        if (is_wp_error($data)) {
            return $data;
        }

        return $data;
    }

    /**
     * Verify callback signature using webhook secret first, then secret key fallback.
     *
     * @param string $signature Received signature.
     * @param array $payload Callback payload.
     * @param string $raw_payload Raw payload.
     * @return bool
     */
    public function verify_callback_signature($signature, $payload = array(), $raw_payload = '')
    {
        if (empty($signature)) {
            return false;
        }

        $payload_candidates = array();

        if (!empty($raw_payload)) {
            $payload_candidates[] = $raw_payload;
        }

        if (!empty($payload)) {
            ksort($payload);
            $payload_candidates[] = wp_json_encode($payload);
            $payload_candidates[] = urldecode(http_build_query($payload));
        }

        $secrets = array_filter(array($this->webhook_secret, $this->secret_key));

        foreach ($secrets as $secret) {
            foreach ($payload_candidates as $candidate) {
                $expected = hash_hmac('sha256', (string) $candidate, $secret);
                if (hash_equals($expected, $signature)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Verify IPN signature
     *
     * @param string $transaction_id Transaction ID
     * @param string $final_amount Final amount
     * @param string $signature Received signature
     * @return bool
     */
    public function verify_ipn_signature($transaction_id, $final_amount, $signature)
    {
        $expected_signatures = array();

        if (!empty($this->webhook_secret)) {
            $expected_signatures[] = hash_hmac('sha256', $transaction_id . '|' . $final_amount, $this->webhook_secret);
            $expected_signatures[] = hash_hmac('sha256', wp_json_encode(array(
                'transaction_id' => $transaction_id,
                'total_amount' => $final_amount,
            )), $this->webhook_secret);
        }

        if (!empty($this->secret_key)) {
            $expected_signatures[] = hash_hmac('sha256', $transaction_id . $final_amount, $this->secret_key);
        }

        foreach ($expected_signatures as $expected_signature) {
            if (hash_equals($expected_signature, $signature)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get payment URL
     *
     * @param string $payment_url Payment URL from API
     * @return string
     */
    public function get_payment_url($payment_url)
    {
        return $payment_url;
    }

    /**
     * Decode and validate standard API response envelope.
     *
     * @param array $response HTTP response from wp_remote_post.
     * @return array|WP_Error
     */
    private function decode_api_response($response)
    {
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        $status_code = wp_remote_retrieve_response_code($response);

        if (!is_array($data)) {
            return new WP_Error('invalid_response', esc_html__('Received an invalid response from Xanzu.', 'xanzu-payment'));
        }

        $is_success = isset($data['status'])
            ? in_array(strtolower((string) $data['status']), array('success', 'ok', 'created'), true)
            : ($status_code >= 200 && $status_code < 300);

        if ($status_code < 200 || $status_code >= 300 || !$is_success) {
            $message = isset($data['message']) ? $data['message'] : esc_html__('Xanzu API request failed.', 'xanzu-payment');
            return new WP_Error('api_error', $message);
        }

        return $data;
    }

    /**
     * Normalize possible checkout session response shapes.
     *
     * @param array $data API response.
     * @return array
     */
    private function normalize_checkout_session_response($data)
    {
        $nested = isset($data['data']) && is_array($data['data']) ? $data['data'] : array();

        return array(
            'status' => isset($data['status']) ? $data['status'] : 'success',
            'message' => isset($data['message']) ? $data['message'] : '',
            'redirect_url' => isset($data['redirect_url']) ? $data['redirect_url']
                : (isset($data['checkout_url']) ? $data['checkout_url']
                    : (isset($data['payment_url']) ? $data['payment_url']
                        : (isset($nested['redirect_url']) ? $nested['redirect_url']
                            : (isset($nested['checkout_url']) ? $nested['checkout_url']
                                : (isset($nested['payment_url']) ? $nested['payment_url'] : ''))))),
            'session_id' => isset($data['session_id']) ? $data['session_id']
                : (isset($data['checkout_session_id']) ? $data['checkout_session_id']
                    : (isset($nested['session_id']) ? $nested['session_id']
                        : (isset($nested['checkout_session_id']) ? $nested['checkout_session_id'] : null))),
            'raw' => $data,
        );
    }
}
