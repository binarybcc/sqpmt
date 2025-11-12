<?php
/**
 * Transaction Logger Class
 *
 * Handles logging of all payment transactions.
 *
 * @package Square_Payment_Service_Fee
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Transaction Logger class
 */
class SQPMT_Transaction_Logger {

    /**
     * The single instance of the class.
     */
    protected static $_instance = null;

    /**
     * Database table name
     */
    private $table_name;

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
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'sqpmt_transactions';
    }

    /**
     * Log a successful transaction
     *
     * @param array $transaction_data Transaction data
     * @return int|false Transaction log ID or false on failure
     */
    public function log_transaction($transaction_data) {
        global $wpdb;

        // Prepare data for insertion
        $data = array(
            'transaction_id' => sanitize_text_field($transaction_data['transaction_id']),
            'amount' => floatval($transaction_data['amount']),
            'service_fee' => floatval($transaction_data['service_fee']),
            'total_amount' => floatval($transaction_data['total_amount']),
            'status' => sanitize_text_field($transaction_data['status']),
            'customer_name' => sanitize_text_field($transaction_data['customer_name']),
            'customer_email' => sanitize_email($transaction_data['customer_email']),
            'customer_phone' => sanitize_text_field($transaction_data['customer_phone']),
            'customer_address_line1' => sanitize_text_field($transaction_data['customer_address_line1']),
            'customer_city' => sanitize_text_field($transaction_data['customer_city']),
            'customer_state' => sanitize_text_field($transaction_data['customer_state']),
            'customer_zip' => sanitize_text_field($transaction_data['customer_zip']),
            'customer_country' => 'US',
            'error_message' => isset($transaction_data['error_message']) ? sanitize_textarea_field($transaction_data['error_message']) : null,
        );

        // Add optional address line 2
        if (!empty($transaction_data['customer_address_line2'])) {
            $data['customer_address_line2'] = sanitize_text_field($transaction_data['customer_address_line2']);
        }

        // Insert into database
        $result = $wpdb->insert(
            $this->table_name,
            $data,
            array(
                '%s', // transaction_id
                '%f', // amount
                '%f', // service_fee
                '%f', // total_amount
                '%s', // status
                '%s', // customer_name
                '%s', // customer_email
                '%s', // customer_phone
                '%s', // customer_address_line1
                '%s', // customer_city
                '%s', // customer_state
                '%s', // customer_zip
                '%s', // customer_country
                '%s', // error_message
            )
        );

        if ($result === false) {
            error_log('[Square Payment Service Fee] Failed to log transaction: ' . $wpdb->last_error);
            return false;
        }

        return $wpdb->insert_id;
    }

    /**
     * Get transaction by ID
     *
     * @param int $id Transaction log ID
     * @return object|null Transaction object or null
     */
    public function get_transaction($id) {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $id
        ));
    }

    /**
     * Get transaction by Square transaction ID
     *
     * @param string $transaction_id Square transaction ID
     * @return object|null Transaction object or null
     */
    public function get_transaction_by_square_id($transaction_id) {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE transaction_id = %s",
            $transaction_id
        ));
    }

    /**
     * Get recent transactions
     *
     * @param int $limit Number of transactions to retrieve
     * @param int $offset Offset for pagination
     * @return array Array of transaction objects
     */
    public function get_recent_transactions($limit = 20, $offset = 0) {
        global $wpdb;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_name} ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $limit,
            $offset
        ));
    }

    /**
     * Get transactions by date range
     *
     * @param string $start_date Start date (Y-m-d format)
     * @param string $end_date End date (Y-m-d format)
     * @param int $limit Limit results
     * @param int $offset Offset for pagination
     * @return array Array of transaction objects
     */
    public function get_transactions_by_date_range($start_date, $end_date, $limit = 100, $offset = 0) {
        global $wpdb;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_name}
            WHERE DATE(created_at) BETWEEN %s AND %s
            ORDER BY created_at DESC
            LIMIT %d OFFSET %d",
            $start_date,
            $end_date,
            $limit,
            $offset
        ));
    }

    /**
     * Search transactions
     *
     * @param string $search_term Search term
     * @param int $limit Limit results
     * @param int $offset Offset for pagination
     * @return array Array of transaction objects
     */
    public function search_transactions($search_term, $limit = 20, $offset = 0) {
        global $wpdb;

        $search_term = '%' . $wpdb->esc_like($search_term) . '%';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_name}
            WHERE customer_name LIKE %s
            OR customer_email LIKE %s
            OR transaction_id LIKE %s
            ORDER BY created_at DESC
            LIMIT %d OFFSET %d",
            $search_term,
            $search_term,
            $search_term,
            $limit,
            $offset
        ));
    }

    /**
     * Get total transaction count
     *
     * @param array $filters Optional filters
     * @return int Transaction count
     */
    public function get_transaction_count($filters = array()) {
        global $wpdb;

        $where = array('1=1');
        $values = array();

        // Filter by date range
        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $where[] = 'DATE(created_at) BETWEEN %s AND %s';
            $values[] = $filters['start_date'];
            $values[] = $filters['end_date'];
        }

        // Filter by status
        if (!empty($filters['status'])) {
            $where[] = 'status = %s';
            $values[] = $filters['status'];
        }

        // Filter by search term
        if (!empty($filters['search'])) {
            $search_term = '%' . $wpdb->esc_like($filters['search']) . '%';
            $where[] = '(customer_name LIKE %s OR customer_email LIKE %s OR transaction_id LIKE %s)';
            $values[] = $search_term;
            $values[] = $search_term;
            $values[] = $search_term;
        }

        $where_clause = implode(' AND ', $where);

        if (!empty($values)) {
            $query = $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_name} WHERE {$where_clause}",
                $values
            );
        } else {
            $query = "SELECT COUNT(*) FROM {$this->table_name} WHERE {$where_clause}";
        }

        return intval($wpdb->get_var($query));
    }

    /**
     * Get transaction statistics
     *
     * @param string $period Period for stats (today, week, month, year, all)
     * @return array Statistics array
     */
    public function get_statistics($period = 'all') {
        global $wpdb;

        $where = '';

        switch ($period) {
            case 'today':
                $where = 'WHERE DATE(created_at) = CURDATE()';
                break;
            case 'week':
                $where = 'WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)';
                break;
            case 'month':
                $where = 'WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)';
                break;
            case 'year':
                $where = 'WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)';
                break;
            default:
                $where = '';
        }

        $stats = $wpdb->get_row(
            "SELECT
                COUNT(*) as total_transactions,
                COUNT(CASE WHEN status = 'COMPLETED' THEN 1 END) as successful_transactions,
                COUNT(CASE WHEN status != 'COMPLETED' THEN 1 END) as failed_transactions,
                COALESCE(SUM(CASE WHEN status = 'COMPLETED' THEN total_amount ELSE 0 END), 0) as total_revenue,
                COALESCE(SUM(CASE WHEN status = 'COMPLETED' THEN service_fee ELSE 0 END), 0) as total_fees,
                COALESCE(AVG(CASE WHEN status = 'COMPLETED' THEN total_amount ELSE NULL END), 0) as avg_transaction_amount
            FROM {$this->table_name} {$where}",
            ARRAY_A
        );

        return $stats;
    }

    /**
     * Delete old transactions
     *
     * @param int $days Delete transactions older than this many days
     * @return int|false Number of rows deleted or false on failure
     */
    public function delete_old_transactions($days = 90) {
        global $wpdb;

        return $wpdb->query($wpdb->prepare(
            "DELETE FROM {$this->table_name} WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $days
        ));
    }
}
