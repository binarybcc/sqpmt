<?php
/**
 * Payment Form Class
 *
 * Handles payment form generation and processing.
 *
 * @package Square_Payment_Service_Fee
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Payment Form class
 */
class SQPMT_Payment_Form {

    /**
     * The single instance of the class.
     */
    protected static $_instance = null;

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
        add_shortcode('square_payment_form', array($this, 'render_form'));
        add_action('wp_ajax_sqpmt_process_payment', array($this, 'ajax_process_payment'));
        add_action('wp_ajax_nopriv_sqpmt_process_payment', array($this, 'ajax_process_payment'));
    }

    /**
     * Render payment form
     *
     * @param array $atts Shortcode attributes
     * @return string Form HTML
     */
    public function render_form($atts) {
        // Check if API is configured
        $app_id = get_option('sqpmt_application_id', '');
        $location_id = get_option('sqpmt_location_id', '');

        if (empty($app_id) || empty($location_id)) {
            if (current_user_can('manage_options')) {
                return '<div class="sqpmt-error">' .
                    esc_html__('Square Payment plugin is not configured. Please configure it in Settings > Square Payment.', 'square-payment-service-fee') .
                    '</div>';
            }
            return '<div class="sqpmt-error">' .
                esc_html__('Payment system is currently unavailable. Please try again later.', 'square-payment-service-fee') .
                '</div>';
        }

        $validator = SQPMT_Validator::instance();
        $us_states = $validator->get_us_states();
        $sandbox_mode = get_option('sqpmt_sandbox_mode', 'yes');

        ob_start();
        ?>
        <div class="sqpmt-payment-form-wrapper" id="sqpmt-payment-form-wrapper">

            <?php if ($sandbox_mode === 'yes'): ?>
            <div class="sqpmt-sandbox-notice" role="alert" aria-live="polite">
                <strong><?php esc_html_e('TEST MODE', 'square-payment-service-fee'); ?></strong>
                <?php esc_html_e('This form is in sandbox mode. No real charges will be made.', 'square-payment-service-fee'); ?>
            </div>
            <?php endif; ?>

            <form id="sqpmt-payment-form" class="sqpmt-payment-form" method="post" aria-label="<?php esc_attr_e('Square Payment Form', 'square-payment-service-fee'); ?>">

                <!-- Payment Amount Section -->
                <div class="sqpmt-section">
                    <h3><?php esc_html_e('Payment Amount', 'square-payment-service-fee'); ?></h3>

                    <div class="sqpmt-form-group">
                        <label for="sqpmt-amount">
                            <?php esc_html_e('Amount Owed (US Dollars)', 'square-payment-service-fee'); ?> <span class="required" aria-label="<?php esc_attr_e('required', 'square-payment-service-fee'); ?>">*</span>
                        </label>
                        <div class="sqpmt-amount-input">
                            <input
                                type="number"
                                id="sqpmt-amount"
                                name="amount"
                                step="0.01"
                                min="1.00"
                                required
                                aria-required="true"
                                aria-describedby="sqpmt-amount-error"
                                placeholder="0.00"
                            >
                        </div>
                        <span class="sqpmt-error-message" id="sqpmt-amount-error" data-field="amount" role="alert" aria-live="polite"></span>
                    </div>

                    <!-- Calculation Breakdown -->
                    <div id="sqpmt-calculation-breakdown" class="sqpmt-calculation-breakdown" style="display: none;">
                        <div class="sqpmt-breakdown-row">
                            <span><?php esc_html_e('Original Amount:', 'square-payment-service-fee'); ?></span>
                            <span id="sqpmt-original-amount">$0.00</span>
                        </div>
                        <div class="sqpmt-breakdown-row">
                            <span><?php esc_html_e('Service Fee:', 'square-payment-service-fee'); ?> <span id="sqpmt-fee-percentage"></span></span>
                            <span id="sqpmt-service-fee">$0.00</span>
                        </div>
                        <div class="sqpmt-breakdown-row sqpmt-total">
                            <span><?php esc_html_e('Total Amount:', 'square-payment-service-fee'); ?></span>
                            <span id="sqpmt-total-amount">$0.00</span>
                        </div>
                    </div>
                </div>

                <!-- Customer Information Section -->
                <div class="sqpmt-section">
                    <h3><?php esc_html_e('Customer Information', 'square-payment-service-fee'); ?></h3>

                    <div class="sqpmt-form-group">
                        <label for="sqpmt-account-holder-name">
                            <?php esc_html_e('Account Holder\'s Name (if paying for someone else)', 'square-payment-service-fee'); ?> <span class="sqpmt-optional"><?php esc_html_e('(Optional)', 'square-payment-service-fee'); ?></span>
                        </label>
                        <input
                            type="text"
                            id="sqpmt-account-holder-name"
                            name="account_holder_name"
                            minlength="2"
                            maxlength="255"
                        >
                        <span class="sqpmt-error-message" data-field="account_holder_name"></span>
                    </div>

                    <div class="sqpmt-form-group">
                        <label for="sqpmt-name">
                            <?php esc_html_e('Cardholder\'s Name', 'square-payment-service-fee'); ?> <span class="required">*</span>
                        </label>
                        <input
                            type="text"
                            id="sqpmt-name"
                            name="name"
                            required
                            minlength="2"
                            maxlength="255"
                        >
                        <span class="sqpmt-error-message" data-field="name"></span>
                    </div>

                    <div class="sqpmt-form-row">
                        <div class="sqpmt-form-group">
                            <label for="sqpmt-email">
                                <?php esc_html_e('Cardholder\'s Email Address', 'square-payment-service-fee'); ?> <span class="required">*</span>
                            </label>
                            <input
                                type="email"
                                id="sqpmt-email"
                                name="email"
                                required
                            >
                            <span class="sqpmt-error-message" data-field="email"></span>
                        </div>

                        <div class="sqpmt-form-group">
                            <label for="sqpmt-phone">
                                <?php esc_html_e('Cardholder\'s Phone Number', 'square-payment-service-fee'); ?> <span class="required">*</span>
                            </label>
                            <input
                                type="tel"
                                id="sqpmt-phone"
                                name="phone"
                                required
                                placeholder="(555) 555-5555"
                            >
                            <span class="sqpmt-error-message" data-field="phone"></span>
                        </div>
                    </div>

                    <div class="sqpmt-form-group">
                        <label for="sqpmt-address-line1">
                            <?php esc_html_e('Cardholder\'s Street Address', 'square-payment-service-fee'); ?> <span class="required">*</span>
                        </label>
                        <input
                            type="text"
                            id="sqpmt-address-line1"
                            name="address_line1"
                            required
                            minlength="5"
                            maxlength="255"
                        >
                        <span class="sqpmt-error-message" data-field="address_line1"></span>
                    </div>

                    <div class="sqpmt-form-group">
                        <label for="sqpmt-address-line2">
                            <?php esc_html_e('Cardholder\'s Address Line 2', 'square-payment-service-fee'); ?> <span class="sqpmt-optional"><?php esc_html_e('(Optional)', 'square-payment-service-fee'); ?></span>
                        </label>
                        <input
                            type="text"
                            id="sqpmt-address-line2"
                            name="address_line2"
                            maxlength="255"
                        >
                    </div>

                    <div class="sqpmt-form-row sqpmt-form-row-3">
                        <div class="sqpmt-form-group">
                            <label for="sqpmt-city">
                                <?php esc_html_e('Cardholder\'s City', 'square-payment-service-fee'); ?> <span class="required">*</span>
                            </label>
                            <input
                                type="text"
                                id="sqpmt-city"
                                name="city"
                                required
                                minlength="2"
                                maxlength="100"
                            >
                            <span class="sqpmt-error-message" data-field="city"></span>
                        </div>

                        <div class="sqpmt-form-group">
                            <label for="sqpmt-state">
                                <?php esc_html_e('Cardholder\'s State', 'square-payment-service-fee'); ?> <span class="required">*</span>
                            </label>
                            <select id="sqpmt-state" name="state" required>
                                <option value=""><?php esc_html_e('Select State', 'square-payment-service-fee'); ?></option>
                                <?php foreach ($us_states as $code => $name): ?>
                                    <option value="<?php echo esc_attr($code); ?>"><?php echo esc_html($name); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <span class="sqpmt-error-message" data-field="state"></span>
                        </div>

                        <div class="sqpmt-form-group">
                            <label for="sqpmt-zip">
                                <?php esc_html_e('Cardholder\'s ZIP Code', 'square-payment-service-fee'); ?> <span class="required">*</span>
                            </label>
                            <input
                                type="text"
                                id="sqpmt-zip"
                                name="zip"
                                required
                                pattern="[0-9]{5}"
                                maxlength="5"
                                placeholder="12345"
                            >
                            <span class="sqpmt-error-message" data-field="zip"></span>
                        </div>
                    </div>
                </div>

                <!-- Payment Details Section -->
                <div class="sqpmt-section">
                    <h3><?php esc_html_e('Payment Details', 'square-payment-service-fee'); ?></h3>

                    <div class="sqpmt-form-group">
                        <label><?php esc_html_e('Card Information', 'square-payment-service-fee'); ?> <span class="required">*</span></label>
                        <div id="sqpmt-card-container"></div>
                        <span class="sqpmt-error-message" data-field="card"></span>
                    </div>
                </div>

                <!-- Messages -->
                <div id="sqpmt-messages" class="sqpmt-messages" role="status" aria-live="polite" aria-atomic="true"></div>

                <!-- Submit Button -->
                <div class="sqpmt-form-actions">
                    <button type="submit" id="sqpmt-submit-btn" class="sqpmt-submit-btn" disabled aria-label="<?php esc_attr_e('Submit payment', 'square-payment-service-fee'); ?>">
                        <span class="sqpmt-btn-text"><?php esc_html_e('Pay Now', 'square-payment-service-fee'); ?></span>
                        <span class="sqpmt-btn-spinner" style="display: none;" aria-hidden="true">
                            <span class="sqpmt-spinner" role="progressbar" aria-label="<?php esc_attr_e('Processing payment', 'square-payment-service-fee'); ?>"></span>
                            <?php esc_html_e('Processing...', 'square-payment-service-fee'); ?>
                        </span>
                    </button>
                </div>

                <?php wp_nonce_field('sqpmt_payment_nonce', 'sqpmt_nonce'); ?>
                <input type="hidden" id="sqpmt-payment-token" name="payment_token" value="">
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * AJAX: Process payment
     */
    public function ajax_process_payment() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'sqpmt_payment_nonce')) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'square-payment-service-fee')
            ));
        }

        // Sanitize and validate input
        $validator = SQPMT_Validator::instance();
        $data = $validator->sanitize_payment_data($_POST);

        // Validate data
        $validation = $validator->validate_payment_data($data);
        if (is_wp_error($validation)) {
            wp_send_json_error(array(
                'message' => $validation->get_error_message(),
                'errors' => $validation->get_error_data()
            ));
        }

        // Calculate amounts
        $calculator = SQPMT_Calculator::instance();
        $breakdown = $calculator->get_payment_breakdown($data['amount']);

        // Process payment through Square
        $api = SQPMT_Square_API::instance();
        $payment_result = $api->process_payment(
            $data['payment_token'],
            $breakdown['total_amount'],
            array(
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'address_line1' => $data['address_line1'],
                'address_line2' => $data['address_line2'],
                'city' => $data['city'],
                'state' => $data['state'],
                'zip' => $data['zip'],
            )
        );

        // Prepare transaction data
        $transaction_data = array(
            'transaction_id' => '',
            'amount' => $breakdown['original_amount'],
            'service_fee' => $breakdown['service_fee'],
            'total_amount' => $breakdown['total_amount'],
            'status' => 'FAILED',
            'account_holder_name' => $data['account_holder_name'],
            'customer_name' => $data['name'],
            'customer_email' => $data['email'],
            'customer_phone' => $data['phone'],
            'customer_address_line1' => $data['address_line1'],
            'customer_address_line2' => $data['address_line2'],
            'customer_city' => $data['city'],
            'customer_state' => $data['state'],
            'customer_zip' => $data['zip'],
        );

        if ($payment_result['success']) {
            // Payment successful
            $transaction_data['transaction_id'] = $payment_result['transaction_id'];
            $transaction_data['status'] = $payment_result['status'];

            // Log transaction
            $logger = SQPMT_Transaction_Logger::instance();
            $log_id = $logger->log_transaction($transaction_data);

            // Send emails
            $email = SQPMT_Email_Notifications::instance();
            $email->send_customer_receipt($transaction_data);
            $email->send_admin_notification($transaction_data);

            // Get success message
            $success_message = get_option('sqpmt_success_message', 'Thank you for your payment! Your transaction ID is: {transaction_id}');
            $success_message = str_replace('{transaction_id}', $payment_result['transaction_id'], $success_message);

            wp_send_json_success(array(
                'message' => $success_message,
                'transaction_id' => $payment_result['transaction_id']
            ));

        } else {
            // Payment failed
            $transaction_data['error_message'] = $payment_result['error'];

            // Log failed transaction
            $logger = SQPMT_Transaction_Logger::instance();
            $logger->log_transaction($transaction_data);

            // Get failure message
            $failure_message = get_option('sqpmt_failure_message', 'Payment failed. Please check your card details and try again.');

            wp_send_json_error(array(
                'message' => $failure_message,
                'error_details' => $payment_result['error']
            ));
        }
    }
}
