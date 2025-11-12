<?php
/**
 * Validator Class
 *
 * Handles validation of all user inputs.
 *
 * @package Square_Payment_Service_Fee
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Validator class
 */
class SQPMT_Validator {

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
     * Validate payment form data
     *
     * @param array $data Form data to validate
     * @return true|WP_Error True if valid, WP_Error if invalid
     */
    public function validate_payment_data($data) {
        $errors = array();

        // Validate amount
        if (empty($data['amount'])) {
            $errors['amount'] = __('Amount is required.', 'square-payment-service-fee');
        } else {
            $calculator = SQPMT_Calculator::instance();
            $amount_validation = $calculator->validate_amount($data['amount']);
            if (is_wp_error($amount_validation)) {
                $errors['amount'] = $amount_validation->get_error_message();
            }
        }

        // Validate name
        $name_validation = $this->validate_name($data['name'] ?? '');
        if (is_wp_error($name_validation)) {
            $errors['name'] = $name_validation->get_error_message();
        }

        // Validate email
        $email_validation = $this->validate_email($data['email'] ?? '');
        if (is_wp_error($email_validation)) {
            $errors['email'] = $email_validation->get_error_message();
        }

        // Validate phone
        $phone_validation = $this->validate_phone($data['phone'] ?? '');
        if (is_wp_error($phone_validation)) {
            $errors['phone'] = $phone_validation->get_error_message();
        }

        // Validate address
        $address_validation = $this->validate_address($data['address_line1'] ?? '');
        if (is_wp_error($address_validation)) {
            $errors['address_line1'] = $address_validation->get_error_message();
        }

        // Validate city
        $city_validation = $this->validate_city($data['city'] ?? '');
        if (is_wp_error($city_validation)) {
            $errors['city'] = $city_validation->get_error_message();
        }

        // Validate state
        $state_validation = $this->validate_state($data['state'] ?? '');
        if (is_wp_error($state_validation)) {
            $errors['state'] = $state_validation->get_error_message();
        }

        // Validate ZIP code
        $zip_validation = $this->validate_zip($data['zip'] ?? '');
        if (is_wp_error($zip_validation)) {
            $errors['zip'] = $zip_validation->get_error_message();
        }

        // Validate payment token
        if (empty($data['payment_token'])) {
            $errors['payment_token'] = __('Payment information is required.', 'square-payment-service-fee');
        }

        // Return errors if any
        if (!empty($errors)) {
            return new WP_Error('validation_failed', __('Validation failed.', 'square-payment-service-fee'), $errors);
        }

        return true;
    }

    /**
     * Validate name
     *
     * @param string $name Name to validate
     * @return true|WP_Error
     */
    public function validate_name($name) {
        $name = trim($name);

        if (empty($name)) {
            return new WP_Error('invalid_name', __('Name is required.', 'square-payment-service-fee'));
        }

        if (strlen($name) < 2) {
            return new WP_Error('invalid_name', __('Name must be at least 2 characters.', 'square-payment-service-fee'));
        }

        if (strlen($name) > 255) {
            return new WP_Error('invalid_name', __('Name is too long.', 'square-payment-service-fee'));
        }

        return true;
    }

    /**
     * Validate email
     *
     * @param string $email Email to validate
     * @return true|WP_Error
     */
    public function validate_email($email) {
        $email = trim($email);

        if (empty($email)) {
            return new WP_Error('invalid_email', __('Email is required.', 'square-payment-service-fee'));
        }

        if (!is_email($email)) {
            return new WP_Error('invalid_email', __('Please enter a valid email address.', 'square-payment-service-fee'));
        }

        return true;
    }

    /**
     * Validate phone number
     *
     * @param string $phone Phone number to validate
     * @return true|WP_Error
     */
    public function validate_phone($phone) {
        $phone = trim($phone);

        if (empty($phone)) {
            return new WP_Error('invalid_phone', __('Phone number is required.', 'square-payment-service-fee'));
        }

        // Remove all non-numeric characters for validation
        $phone_digits = preg_replace('/[^0-9]/', '', $phone);

        // Check if we have exactly 10 digits (US phone number)
        if (strlen($phone_digits) !== 10) {
            return new WP_Error('invalid_phone', __('Please enter a valid 10-digit phone number.', 'square-payment-service-fee'));
        }

        return true;
    }

    /**
     * Validate address
     *
     * @param string $address Address to validate
     * @return true|WP_Error
     */
    public function validate_address($address) {
        $address = trim($address);

        if (empty($address)) {
            return new WP_Error('invalid_address', __('Address is required.', 'square-payment-service-fee'));
        }

        if (strlen($address) < 5) {
            return new WP_Error('invalid_address', __('Address must be at least 5 characters.', 'square-payment-service-fee'));
        }

        if (strlen($address) > 255) {
            return new WP_Error('invalid_address', __('Address is too long.', 'square-payment-service-fee'));
        }

        return true;
    }

    /**
     * Validate city
     *
     * @param string $city City to validate
     * @return true|WP_Error
     */
    public function validate_city($city) {
        $city = trim($city);

        if (empty($city)) {
            return new WP_Error('invalid_city', __('City is required.', 'square-payment-service-fee'));
        }

        if (strlen($city) < 2) {
            return new WP_Error('invalid_city', __('City must be at least 2 characters.', 'square-payment-service-fee'));
        }

        if (strlen($city) > 100) {
            return new WP_Error('invalid_city', __('City name is too long.', 'square-payment-service-fee'));
        }

        return true;
    }

    /**
     * Validate state
     *
     * @param string $state State to validate
     * @return true|WP_Error
     */
    public function validate_state($state) {
        $state = trim($state);

        if (empty($state)) {
            return new WP_Error('invalid_state', __('State is required.', 'square-payment-service-fee'));
        }

        $valid_states = $this->get_us_states();

        if (!array_key_exists($state, $valid_states)) {
            return new WP_Error('invalid_state', __('Please select a valid state.', 'square-payment-service-fee'));
        }

        return true;
    }

    /**
     * Validate ZIP code
     *
     * @param string $zip ZIP code to validate
     * @return true|WP_Error
     */
    public function validate_zip($zip) {
        $zip = trim($zip);

        if (empty($zip)) {
            return new WP_Error('invalid_zip', __('ZIP code is required.', 'square-payment-service-fee'));
        }

        // Check if ZIP is exactly 5 digits
        if (!preg_match('/^\d{5}$/', $zip)) {
            return new WP_Error('invalid_zip', __('ZIP code must be 5 digits.', 'square-payment-service-fee'));
        }

        return true;
    }

    /**
     * Get list of US states
     *
     * @return array
     */
    public function get_us_states() {
        return array(
            'AL' => 'Alabama',
            'AK' => 'Alaska',
            'AZ' => 'Arizona',
            'AR' => 'Arkansas',
            'CA' => 'California',
            'CO' => 'Colorado',
            'CT' => 'Connecticut',
            'DE' => 'Delaware',
            'FL' => 'Florida',
            'GA' => 'Georgia',
            'HI' => 'Hawaii',
            'ID' => 'Idaho',
            'IL' => 'Illinois',
            'IN' => 'Indiana',
            'IA' => 'Iowa',
            'KS' => 'Kansas',
            'KY' => 'Kentucky',
            'LA' => 'Louisiana',
            'ME' => 'Maine',
            'MD' => 'Maryland',
            'MA' => 'Massachusetts',
            'MI' => 'Michigan',
            'MN' => 'Minnesota',
            'MS' => 'Mississippi',
            'MO' => 'Missouri',
            'MT' => 'Montana',
            'NE' => 'Nebraska',
            'NV' => 'Nevada',
            'NH' => 'New Hampshire',
            'NJ' => 'New Jersey',
            'NM' => 'New Mexico',
            'NY' => 'New York',
            'NC' => 'North Carolina',
            'ND' => 'North Dakota',
            'OH' => 'Ohio',
            'OK' => 'Oklahoma',
            'OR' => 'Oregon',
            'PA' => 'Pennsylvania',
            'RI' => 'Rhode Island',
            'SC' => 'South Carolina',
            'SD' => 'South Dakota',
            'TN' => 'Tennessee',
            'TX' => 'Texas',
            'UT' => 'Utah',
            'VT' => 'Vermont',
            'VA' => 'Virginia',
            'WA' => 'Washington',
            'WV' => 'West Virginia',
            'WI' => 'Wisconsin',
            'WY' => 'Wyoming',
            'DC' => 'District of Columbia'
        );
    }

    /**
     * Sanitize payment data
     *
     * @param array $data Data to sanitize
     * @return array Sanitized data
     */
    public function sanitize_payment_data($data) {
        return array(
            'amount' => floatval($data['amount'] ?? 0),
            'name' => sanitize_text_field($data['name'] ?? ''),
            'email' => sanitize_email($data['email'] ?? ''),
            'phone' => sanitize_text_field($data['phone'] ?? ''),
            'address_line1' => sanitize_text_field($data['address_line1'] ?? ''),
            'address_line2' => sanitize_text_field($data['address_line2'] ?? ''),
            'city' => sanitize_text_field($data['city'] ?? ''),
            'state' => sanitize_text_field($data['state'] ?? ''),
            'zip' => sanitize_text_field($data['zip'] ?? ''),
            'payment_token' => sanitize_text_field($data['payment_token'] ?? ''),
        );
    }
}
