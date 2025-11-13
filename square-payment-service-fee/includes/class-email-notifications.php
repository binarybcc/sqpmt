<?php
/**
 * Email Notifications Class
 *
 * Handles sending email notifications for transactions.
 *
 * @package Square_Payment_Service_Fee
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Email Notifications class
 */
class SQPMT_Email_Notifications {

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
     * Send customer receipt email
     *
     * @param array $transaction_data Transaction data
     * @return bool True if email sent successfully
     */
    public function send_customer_receipt($transaction_data) {
        // Check if customer emails are enabled
        if (get_option('sqpmt_customer_email_enabled', 'yes') !== 'yes') {
            return false;
        }

        $to = $transaction_data['customer_email'];
        $subject = sprintf(
            __('Payment Receipt - %s', 'square-payment-service-fee'),
            get_bloginfo('name')
        );

        // Build email content
        $message = $this->get_customer_email_template($transaction_data);

        // Email headers
        $site_domain = str_replace('www.', '', parse_url(get_site_url(), PHP_URL_HOST));
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <admin@' . $site_domain . '>'
        );

        // Send email
        return wp_mail($to, $subject, $message, $headers);
    }

    /**
     * Send admin notification email
     *
     * @param array $transaction_data Transaction data
     * @return bool True if email sent successfully
     */
    public function send_admin_notification($transaction_data) {
        // Get admin email address from settings
        $to = get_option('sqpmt_admin_email_address', get_option('admin_email'));

        // If email address is empty, don't send notification
        if (empty($to)) {
            return false;
        }
        $subject = sprintf(
            __('New Payment Received - %s', 'square-payment-service-fee'),
            SQPMT_Calculator::instance()->format_amount($transaction_data['total_amount'])
        );

        // Build email content
        $message = $this->get_admin_email_template($transaction_data);

        // Email headers
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
        );

        // Send email
        return wp_mail($to, $subject, $message, $headers);
    }

    /**
     * Get customer email template
     *
     * @param array $transaction_data Transaction data
     * @return string HTML email content
     */
    private function get_customer_email_template($transaction_data) {
        $calculator = SQPMT_Calculator::instance();

        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php esc_html_e('Payment Receipt', 'square-payment-service-fee'); ?></title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    line-height: 1.6;
                    color: #333;
                    max-width: 600px;
                    margin: 0 auto;
                    padding: 20px;
                }
                .header {
                    background-color: #4CAF50;
                    color: white;
                    padding: 20px;
                    text-align: center;
                    border-radius: 5px 5px 0 0;
                }
                .content {
                    background-color: #f9f9f9;
                    padding: 30px;
                    border: 1px solid #ddd;
                    border-radius: 0 0 5px 5px;
                }
                .transaction-details {
                    background-color: white;
                    padding: 20px;
                    margin: 20px 0;
                    border-radius: 5px;
                    border: 1px solid #e0e0e0;
                }
                .detail-row {
                    display: flex;
                    justify-content: space-between;
                    padding: 10px 0;
                    border-bottom: 1px solid #f0f0f0;
                }
                .detail-row:last-child {
                    border-bottom: none;
                }
                .detail-label {
                    font-weight: bold;
                    color: #666;
                }
                .detail-value {
                    color: #333;
                }
                .total-row {
                    font-size: 1.2em;
                    font-weight: bold;
                    margin-top: 10px;
                    padding-top: 10px;
                    border-top: 2px solid #4CAF50;
                }
                .footer {
                    margin-top: 30px;
                    padding-top: 20px;
                    border-top: 1px solid #ddd;
                    text-align: center;
                    color: #666;
                    font-size: 0.9em;
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1><?php esc_html_e('Payment Received', 'square-payment-service-fee'); ?></h1>
            </div>
            <div class="content">
                <p><?php printf(
                    esc_html__('Dear %s,', 'square-payment-service-fee'),
                    esc_html($transaction_data['customer_name'])
                ); ?></p>

                <p><?php esc_html_e('Thank you for your payment! This email confirms that we have successfully received your payment.', 'square-payment-service-fee'); ?></p>

                <div class="transaction-details">
                    <h2><?php esc_html_e('Payment Details', 'square-payment-service-fee'); ?></h2>

                    <div class="detail-row">
                        <span class="detail-label"><?php esc_html_e('Transaction ID:', 'square-payment-service-fee'); ?></span>
                        <span class="detail-value"><?php echo esc_html($transaction_data['transaction_id']); ?></span>
                    </div>

                    <div class="detail-row">
                        <span class="detail-label"><?php esc_html_e('Date:', 'square-payment-service-fee'); ?></span>
                        <span class="detail-value"><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'))); ?></span>
                    </div>

                    <div class="detail-row">
                        <span class="detail-label"><?php esc_html_e('Original Amount:', 'square-payment-service-fee'); ?></span>
                        <span class="detail-value"><?php echo esc_html($calculator->format_amount($transaction_data['amount'])); ?></span>
                    </div>

                    <div class="detail-row">
                        <span class="detail-label"><?php esc_html_e('Service Fee:', 'square-payment-service-fee'); ?></span>
                        <span class="detail-value"><?php echo esc_html($calculator->format_amount($transaction_data['service_fee'])); ?></span>
                    </div>

                    <div class="detail-row total-row">
                        <span class="detail-label"><?php esc_html_e('Total Paid:', 'square-payment-service-fee'); ?></span>
                        <span class="detail-value"><?php echo esc_html($calculator->format_amount($transaction_data['total_amount'])); ?></span>
                    </div>
                </div>

                <div class="transaction-details">
                    <h3><?php esc_html_e('Billing Information', 'square-payment-service-fee'); ?></h3>

                    <div class="detail-row">
                        <span class="detail-label"><?php esc_html_e('Name:', 'square-payment-service-fee'); ?></span>
                        <span class="detail-value"><?php echo esc_html($transaction_data['customer_name']); ?></span>
                    </div>

                    <div class="detail-row">
                        <span class="detail-label"><?php esc_html_e('Email:', 'square-payment-service-fee'); ?></span>
                        <span class="detail-value"><?php echo esc_html($transaction_data['customer_email']); ?></span>
                    </div>

                    <div class="detail-row">
                        <span class="detail-label"><?php esc_html_e('Phone:', 'square-payment-service-fee'); ?></span>
                        <span class="detail-value"><?php echo esc_html($transaction_data['customer_phone']); ?></span>
                    </div>

                    <div class="detail-row">
                        <span class="detail-label"><?php esc_html_e('Address:', 'square-payment-service-fee'); ?></span>
                        <span class="detail-value">
                            <?php
                            echo esc_html($transaction_data['customer_address_line1']);
                            if (!empty($transaction_data['customer_address_line2'])) {
                                echo '<br>' . esc_html($transaction_data['customer_address_line2']);
                            }
                            echo '<br>' . esc_html($transaction_data['customer_city']) . ', ';
                            echo esc_html($transaction_data['customer_state']) . ' ';
                            echo esc_html($transaction_data['customer_zip']);
                            ?>
                        </span>
                    </div>
                </div>

                <p><?php esc_html_e('Please keep this email for your records.', 'square-payment-service-fee'); ?></p>

                <p><?php esc_html_e('If you have any questions about this payment, please contact us.', 'square-payment-service-fee'); ?></p>

                <div class="footer">
                    <p><?php echo esc_html(get_bloginfo('name')); ?></p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Get admin email template
     *
     * @param array $transaction_data Transaction data
     * @return string HTML email content
     */
    private function get_admin_email_template($transaction_data) {
        $calculator = SQPMT_Calculator::instance();

        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php esc_html_e('New Payment Received', 'square-payment-service-fee'); ?></title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    line-height: 1.6;
                    color: #333;
                    max-width: 600px;
                    margin: 0 auto;
                    padding: 20px;
                }
                .header {
                    background-color: #2196F3;
                    color: white;
                    padding: 20px;
                    text-align: center;
                    border-radius: 5px 5px 0 0;
                }
                .content {
                    background-color: #f9f9f9;
                    padding: 30px;
                    border: 1px solid #ddd;
                    border-radius: 0 0 5px 5px;
                }
                .transaction-details {
                    background-color: white;
                    padding: 20px;
                    margin: 20px 0;
                    border-radius: 5px;
                    border: 1px solid #e0e0e0;
                }
                .detail-row {
                    padding: 8px 0;
                    border-bottom: 1px solid #f0f0f0;
                }
                .detail-row:last-child {
                    border-bottom: none;
                }
                .detail-label {
                    font-weight: bold;
                    color: #666;
                    display: inline-block;
                    width: 150px;
                }
                .total-amount {
                    font-size: 1.5em;
                    color: #4CAF50;
                    font-weight: bold;
                    text-align: center;
                    margin: 20px 0;
                    padding: 15px;
                    background-color: #f0f8f0;
                    border-radius: 5px;
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1><?php esc_html_e('New Payment Received', 'square-payment-service-fee'); ?></h1>
            </div>
            <div class="content">
                <div class="total-amount">
                    <?php echo esc_html($calculator->format_amount($transaction_data['total_amount'])); ?>
                </div>

                <div class="transaction-details">
                    <h3><?php esc_html_e('Transaction Information', 'square-payment-service-fee'); ?></h3>

                    <div class="detail-row">
                        <span class="detail-label"><?php esc_html_e('Transaction ID:', 'square-payment-service-fee'); ?></span>
                        <?php echo esc_html($transaction_data['transaction_id']); ?>
                    </div>

                    <div class="detail-row">
                        <span class="detail-label"><?php esc_html_e('Status:', 'square-payment-service-fee'); ?></span>
                        <?php echo esc_html($transaction_data['status']); ?>
                    </div>

                    <div class="detail-row">
                        <span class="detail-label"><?php esc_html_e('Date/Time:', 'square-payment-service-fee'); ?></span>
                        <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'))); ?>
                    </div>

                    <div class="detail-row">
                        <span class="detail-label"><?php esc_html_e('Original Amount:', 'square-payment-service-fee'); ?></span>
                        <?php echo esc_html($calculator->format_amount($transaction_data['amount'])); ?>
                    </div>

                    <div class="detail-row">
                        <span class="detail-label"><?php esc_html_e('Service Fee:', 'square-payment-service-fee'); ?></span>
                        <?php echo esc_html($calculator->format_amount($transaction_data['service_fee'])); ?>
                    </div>

                    <div class="detail-row">
                        <span class="detail-label"><?php esc_html_e('Total:', 'square-payment-service-fee'); ?></span>
                        <strong><?php echo esc_html($calculator->format_amount($transaction_data['total_amount'])); ?></strong>
                    </div>
                </div>

                <div class="transaction-details">
                    <h3><?php esc_html_e('Customer Information', 'square-payment-service-fee'); ?></h3>

                    <div class="detail-row">
                        <span class="detail-label"><?php esc_html_e('Name:', 'square-payment-service-fee'); ?></span>
                        <?php echo esc_html($transaction_data['customer_name']); ?>
                    </div>

                    <div class="detail-row">
                        <span class="detail-label"><?php esc_html_e('Email:', 'square-payment-service-fee'); ?></span>
                        <a href="mailto:<?php echo esc_attr($transaction_data['customer_email']); ?>">
                            <?php echo esc_html($transaction_data['customer_email']); ?>
                        </a>
                    </div>

                    <div class="detail-row">
                        <span class="detail-label"><?php esc_html_e('Phone:', 'square-payment-service-fee'); ?></span>
                        <?php echo esc_html($transaction_data['customer_phone']); ?>
                    </div>

                    <div class="detail-row">
                        <span class="detail-label"><?php esc_html_e('Address:', 'square-payment-service-fee'); ?></span>
                        <?php
                        echo esc_html($transaction_data['customer_address_line1']);
                        if (!empty($transaction_data['customer_address_line2'])) {
                            echo '<br>' . esc_html($transaction_data['customer_address_line2']);
                        }
                        echo '<br>' . esc_html($transaction_data['customer_city']) . ', ';
                        echo esc_html($transaction_data['customer_state']) . ' ';
                        echo esc_html($transaction_data['customer_zip']);
                        ?>
                    </div>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
}
