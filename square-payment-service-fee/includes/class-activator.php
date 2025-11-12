<?php
/**
 * Fired during plugin activation.
 *
 * @package Square_Payment_Service_Fee
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Activator class
 */
class SQPMT_Activator {

    /**
     * Activate the plugin.
     */
    public static function activate() {
        global $wpdb;

        // Create transactions table
        $table_name = $wpdb->prefix . 'sqpmt_transactions';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            transaction_id varchar(255) NOT NULL,
            amount decimal(10,2) NOT NULL,
            service_fee decimal(10,2) NOT NULL,
            total_amount decimal(10,2) NOT NULL,
            status varchar(50) NOT NULL,
            customer_name varchar(255) NOT NULL,
            customer_email varchar(255) NOT NULL,
            customer_phone varchar(50) NOT NULL,
            customer_address_line1 varchar(255) NOT NULL,
            customer_address_line2 varchar(255) DEFAULT NULL,
            customer_city varchar(100) NOT NULL,
            customer_state varchar(50) NOT NULL,
            customer_zip varchar(20) NOT NULL,
            customer_country varchar(50) DEFAULT 'US',
            error_message text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY transaction_id (transaction_id),
            KEY customer_email (customer_email),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Set default options if they don't exist
        if (get_option('sqpmt_service_fee') === false) {
            add_option('sqpmt_service_fee', '3.5');
        }

        if (get_option('sqpmt_sandbox_mode') === false) {
            add_option('sqpmt_sandbox_mode', 'yes');
        }

        if (get_option('sqpmt_success_message') === false) {
            add_option('sqpmt_success_message', 'Thank you for your payment! Your transaction ID is: {transaction_id}');
        }

        if (get_option('sqpmt_failure_message') === false) {
            add_option('sqpmt_failure_message', 'Payment failed. Please check your card details and try again.');
        }

        if (get_option('sqpmt_admin_email_enabled') === false) {
            add_option('sqpmt_admin_email_enabled', 'yes');
        }

        if (get_option('sqpmt_customer_email_enabled') === false) {
            add_option('sqpmt_customer_email_enabled', 'yes');
        }

        // Flush rewrite rules
        flush_rewrite_rules();
    }
}
