<?php
/**
 * Square API Handler
 *
 * Handles all communication with Square's API.
 *
 * @package Square_Payment_Service_Fee
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Square API class
 */
class SQPMT_Square_API {

    /**
     * The single instance of the class.
     */
    protected static $_instance = null;

    /**
     * Square API base URL
     */
    private $api_url;

    /**
     * Access token
     */
    private $access_token;

    /**
     * Location ID
     */
    private $location_id;

    /**
     * Main Instance.
     */
    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Constructor.
     */
    public function __construct() {
        $sandbox_mode = get_option('sqpmt_sandbox_mode', 'yes');

        // Set API URL based on mode
        if ($sandbox_mode === 'yes') {
            $this->api_url = 'https://connect.squareupsandbox.com/v2';
        } else {
            $this->api_url = 'https://connect.squareup.com/v2';
        }

        // Get encrypted access token
        $this->access_token = $this->get_decrypted_access_token();
        $this->location_id = get_option('sqpmt_location_id', '');
    }

    /**
     * Get decrypted access token
     */
    private function get_decrypted_access_token() {
        $encrypted_token = get_option('sqpmt_access_token', '');

        if (empty($encrypted_token)) {
            return '';
        }

        // Decrypt using AES-256-GCM
        return $this->decrypt_token($encrypted_token);
    }

    /**
     * Encrypt access token for storage using AES-256-GCM
     *
     * @param string $token The token to encrypt
     * @return string Encrypted token (base64 encoded)
     */
    public static function encrypt_access_token($token) {
        if (empty($token)) {
            return '';
        }

        // Check if OpenSSL is available
        if (!function_exists('openssl_encrypt')) {
            error_log('[Square Payment Service Fee] OpenSSL not available. Token will not be encrypted properly.');
            // Fallback to base64 encoding (not secure, but better than plaintext)
            return base64_encode($token);
        }

        $cipher = 'aes-256-gcm';
        $key = self::get_encryption_key();

        // Generate a random IV (Initialization Vector)
        $ivlen = openssl_cipher_iv_length($cipher);
        $iv = openssl_random_pseudo_bytes($ivlen);

        // Encrypt the token
        $tag = '';
        $ciphertext = openssl_encrypt(
            $token,
            $cipher,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            16  // Tag length for GCM
        );

        if ($ciphertext === false) {
            error_log('[Square Payment Service Fee] Encryption failed: ' . openssl_error_string());
            return base64_encode($token); // Fallback
        }

        // Combine IV + Tag + Ciphertext and encode
        $encrypted = base64_encode($iv . $tag . $ciphertext);

        return $encrypted;
    }

    /**
     * Decrypt access token using AES-256-GCM
     *
     * @param string $encrypted_token The encrypted token
     * @return string Decrypted token
     */
    private function decrypt_token($encrypted_token) {
        if (empty($encrypted_token)) {
            return '';
        }

        // Check if OpenSSL is available
        if (!function_exists('openssl_decrypt')) {
            error_log('[Square Payment Service Fee] OpenSSL not available for decryption.');
            // Try base64 decode as fallback
            return base64_decode($encrypted_token);
        }

        $cipher = 'aes-256-gcm';
        $key = self::get_encryption_key();

        // Decode from base64
        $data = base64_decode($encrypted_token);

        if ($data === false) {
            error_log('[Square Payment Service Fee] Invalid encrypted token format.');
            return '';
        }

        // Extract IV length
        $ivlen = openssl_cipher_iv_length($cipher);
        $tag_length = 16; // GCM tag length

        // Check if data is long enough
        if (strlen($data) < $ivlen + $tag_length) {
            error_log('[Square Payment Service Fee] Encrypted token too short. May be legacy format.');
            // Attempt legacy XOR decryption for backward compatibility
            return $this->decrypt_legacy_xor($encrypted_token);
        }

        // Extract IV, tag, and ciphertext
        $iv = substr($data, 0, $ivlen);
        $tag = substr($data, $ivlen, $tag_length);
        $ciphertext = substr($data, $ivlen + $tag_length);

        // Decrypt
        $plaintext = openssl_decrypt(
            $ciphertext,
            $cipher,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($plaintext === false) {
            error_log('[Square Payment Service Fee] Decryption failed: ' . openssl_error_string());
            // Try legacy XOR decryption
            return $this->decrypt_legacy_xor($encrypted_token);
        }

        return $plaintext;
    }

    /**
     * Decrypt legacy XOR-encrypted tokens (for backward compatibility)
     *
     * @param string $encrypted_token The XOR-encrypted token
     * @return string Decrypted token
     */
    private function decrypt_legacy_xor($encrypted_token) {
        // Legacy XOR decryption for tokens encrypted with old method
        $key = md5(AUTH_KEY . SECURE_AUTH_KEY);
        $result = '';
        $string_length = strlen($encrypted_token);
        $key_length = strlen($key);

        for ($i = 0; $i < $string_length; $i++) {
            $result .= $encrypted_token[$i] ^ $key[$i % $key_length];
        }

        // If decrypted successfully, re-encrypt with AES and save
        if (!empty($result) && strpos($result, 'EAAA') === 0) {
            // Looks like a valid Square token, re-encrypt and update
            $new_encrypted = self::encrypt_access_token($result);
            update_option('sqpmt_access_token', $new_encrypted);
            error_log('[Square Payment Service Fee] Migrated legacy XOR token to AES-256-GCM encryption.');
        }

        return $result;
    }

    /**
     * Get encryption key based on WordPress salts (32 bytes for AES-256)
     *
     * @return string 32-byte encryption key
     */
    private static function get_encryption_key() {
        // Use WordPress salts to create a 32-byte key for AES-256
        $key_material = AUTH_KEY . SECURE_AUTH_KEY . NONCE_KEY;
        return hash('sha256', $key_material, true);
    }

    /**
     * Process payment through Square
     *
     * @param string $source_id Payment source token from Square SDK
     * @param float $amount Total amount to charge (in dollars)
     * @param array $customer_data Customer information
     * @return array Response with success status and data
     */
    public function process_payment($source_id, $amount, $customer_data) {
        // Validate inputs
        if (empty($source_id) || empty($amount)) {
            return array(
                'success' => false,
                'error' => __('Missing payment information.', 'square-payment-service-fee')
            );
        }

        if (empty($this->access_token) || empty($this->location_id)) {
            return array(
                'success' => false,
                'error' => __('Square API credentials not configured.', 'square-payment-service-fee')
            );
        }

        // Convert amount to cents (Square uses smallest currency unit)
        $amount_in_cents = round($amount * 100);

        // Generate idempotency key (prevents duplicate charges)
        $idempotency_key = $this->generate_idempotency_key();

        // Prepare request body
        $body = array(
            'source_id' => $source_id,
            'idempotency_key' => $idempotency_key,
            'amount_money' => array(
                'amount' => $amount_in_cents,
                'currency' => 'USD'
            ),
            'location_id' => $this->location_id,
            'autocomplete' => true,
        );

        // Add billing address if provided
        if (!empty($customer_data)) {
            $body['billing_address'] = array(
                'address_line_1' => sanitize_text_field($customer_data['address_line1']),
                'locality' => sanitize_text_field($customer_data['city']),
                'administrative_district_level_1' => sanitize_text_field($customer_data['state']),
                'postal_code' => sanitize_text_field($customer_data['zip']),
                'country' => 'US'
            );

            if (!empty($customer_data['address_line2'])) {
                $body['billing_address']['address_line_2'] = sanitize_text_field($customer_data['address_line2']);
            }

            // Add buyer email address
            if (!empty($customer_data['email'])) {
                $body['buyer_email_address'] = sanitize_email($customer_data['email']);
            }

            // Add note with customer information
            $body['note'] = sprintf(
                'Payment from %s (Phone: %s)',
                sanitize_text_field($customer_data['name']),
                sanitize_text_field($customer_data['phone'])
            );
        }

        // Make API request
        $response = $this->make_request('POST', '/payments', $body);

        // Process response
        if ($response['success']) {
            $payment_data = $response['data'];

            return array(
                'success' => true,
                'transaction_id' => $payment_data['payment']['id'],
                'status' => $payment_data['payment']['status'],
                'receipt_url' => isset($payment_data['payment']['receipt_url']) ? $payment_data['payment']['receipt_url'] : '',
                'amount' => $amount,
                'created_at' => $payment_data['payment']['created_at']
            );
        } else {
            return array(
                'success' => false,
                'error' => $response['error']
            );
        }
    }

    /**
     * Test API connection
     *
     * @return array Response with success status
     */
    public function test_connection() {
        if (empty($this->access_token)) {
            return array(
                'success' => false,
                'error' => __('Access token is not configured.', 'square-payment-service-fee')
            );
        }

        // Try to retrieve location details
        $response = $this->make_request('GET', '/locations/' . $this->location_id);

        if ($response['success']) {
            return array(
                'success' => true,
                'message' => __('Connection successful!', 'square-payment-service-fee'),
                'location' => $response['data']['location']
            );
        } else {
            return array(
                'success' => false,
                'error' => $response['error']
            );
        }
    }

    /**
     * Make API request to Square
     *
     * @param string $method HTTP method (GET, POST, etc.)
     * @param string $endpoint API endpoint
     * @param array $body Request body (optional)
     * @return array Response array
     */
    private function make_request($method, $endpoint, $body = null) {
        $url = $this->api_url . $endpoint;

        $args = array(
            'method' => $method,
            'headers' => array(
                'Square-Version' => '2024-10-17',
                'Authorization' => 'Bearer ' . $this->access_token,
                'Content-Type' => 'application/json',
            ),
            'timeout' => 30,
        );

        if ($body !== null && in_array($method, array('POST', 'PUT', 'PATCH'))) {
            $args['body'] = wp_json_encode($body);
        }

        // Make request using WordPress HTTP API
        $response = wp_remote_request($url, $args);

        // Check for errors
        if (is_wp_error($response)) {
            $this->log_error('API Request Failed', $response->get_error_message());
            return array(
                'success' => false,
                'error' => $response->get_error_message()
            );
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $data = json_decode($response_body, true);

        // Handle response codes
        if ($response_code >= 200 && $response_code < 300) {
            return array(
                'success' => true,
                'data' => $data
            );
        } else {
            // Extract error message from Square API response
            $error_message = __('Unknown error occurred.', 'square-payment-service-fee');

            if (isset($data['errors']) && is_array($data['errors']) && count($data['errors']) > 0) {
                $error_message = $data['errors'][0]['detail'];
                if (isset($data['errors'][0]['field'])) {
                    $error_message = $data['errors'][0]['field'] . ': ' . $error_message;
                }
            }

            $this->log_error('API Error', $error_message, array(
                'response_code' => $response_code,
                'response_body' => $response_body
            ));

            return array(
                'success' => false,
                'error' => $error_message,
                'response_code' => $response_code
            );
        }
    }

    /**
     * Generate unique idempotency key
     *
     * @return string
     */
    private function generate_idempotency_key() {
        return uniqid('sqpmt_', true) . '_' . time();
    }

    /**
     * Log error to WordPress error log
     *
     * @param string $title Error title
     * @param string $message Error message
     * @param array $context Additional context
     */
    private function log_error($title, $message, $context = array()) {
        if (defined('WP_DEBUG') && WP_DEBUG === true) {
            error_log(sprintf(
                '[Square Payment Service Fee] %s: %s | Context: %s',
                $title,
                $message,
                wp_json_encode($context)
            ));
        }
    }
}
