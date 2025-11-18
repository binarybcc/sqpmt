<?php
/**
 * Rate Limiter Class
 *
 * Prevents brute force attacks and API abuse by rate limiting requests.
 *
 * @package Square_Payment_Service_Fee
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Rate Limiter class
 */
class SQPMT_Rate_Limiter {

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
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'sqpmt_rate_limits';
	}

	/**
	 * Check if an action is rate limited
	 *
	 * @param string $action The action being rate limited (e.g., 'payment', 'test_connection')
	 * @param string $identifier Unique identifier (IP address, user ID, etc.)
	 * @param int    $max_attempts Maximum attempts allowed
	 * @param int    $time_window Time window in seconds
	 * @return bool|array False if allowed, error array if rate limited
	 */
	public function check_rate_limit( $action, $identifier, $max_attempts, $time_window ) {
		global $wpdb;

		// Clean up old entries first
		$this->cleanup_old_entries( $time_window );

		// Get current timestamp
		$current_time   = current_time( 'mysql' );
		$time_threshold = date( 'Y-m-d H:i:s', strtotime( $current_time ) - $time_window );

		// Count attempts in the time window
		$attempts = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->table_name}
            WHERE action = %s
            AND identifier = %s
            AND attempted_at > %s",
				$action,
				$identifier,
				$time_threshold
			)
		);

		// Check if rate limit exceeded
		if ( $attempts >= $max_attempts ) {
			// Get the oldest attempt in the window to calculate reset time
			$oldest_attempt = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT attempted_at FROM {$this->table_name}
                WHERE action = %s
                AND identifier = %s
                AND attempted_at > %s
                ORDER BY attempted_at ASC
                LIMIT 1",
					$action,
					$identifier,
					$time_threshold
				)
			);

			$reset_time  = strtotime( $oldest_attempt ) + $time_window;
			$retry_after = max( 1, $reset_time - time() );

			return array(
				'limited'      => true,
				'message'      => sprintf(
					__( 'Too many attempts. Please try again in %d seconds.', 'square-payment-service-fee' ),
					$retry_after
				),
				'retry_after'  => $retry_after,
				'attempts'     => $attempts,
				'max_attempts' => $max_attempts,
			);
		}

		// Log this attempt
		$this->log_attempt( $action, $identifier );

		return false; // Not rate limited
	}

	/**
	 * Log an attempt
	 *
	 * @param string $action The action being performed
	 * @param string $identifier Unique identifier
	 * @return bool Success status
	 */
	private function log_attempt( $action, $identifier ) {
		global $wpdb;

		$result = $wpdb->insert(
			$this->table_name,
			array(
				'action'       => $action,
				'identifier'   => $identifier,
				'attempted_at' => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%s' )
		);

		return $result !== false;
	}

	/**
	 * Clean up old rate limit entries
	 *
	 * @param int $time_window Keep entries within this time window (in seconds)
	 * @return int Number of rows deleted
	 */
	private function cleanup_old_entries( $time_window = 3600 ) {
		global $wpdb;

		$time_threshold = date( 'Y-m-d H:i:s', time() - $time_window );

		$deleted = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$this->table_name} WHERE attempted_at < %s",
				$time_threshold
			)
		);

		return $deleted !== false ? $deleted : 0;
	}

	/**
	 * Clear rate limit for a specific action and identifier
	 *
	 * @param string $action The action
	 * @param string $identifier Unique identifier
	 * @return bool Success status
	 */
	public function clear_rate_limit( $action, $identifier ) {
		global $wpdb;

		$result = $wpdb->delete(
			$this->table_name,
			array(
				'action'     => $action,
				'identifier' => $identifier,
			),
			array( '%s', '%s' )
		);

		return $result !== false;
	}

	/**
	 * Get identifier for current request
	 *
	 * Uses IP address and user agent for anonymous users,
	 * or user ID for logged-in users.
	 *
	 * @return string Unique identifier
	 */
	public static function get_request_identifier() {
		// Use user ID if logged in
		if ( is_user_logged_in() ) {
			return 'user_' . get_current_user_id();
		}

		// Otherwise use IP + user agent hash
		$ip         = self::get_client_ip();
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '';

		return 'ip_' . md5( $ip . $user_agent );
	}

	/**
	 * Get client IP address
	 *
	 * @return string IP address
	 */
	public static function get_client_ip() {
		$ip = '';

		// Check for various proxy headers
		$headers = array(
			'HTTP_CF_CONNECTING_IP',  // Cloudflare
			'HTTP_X_FORWARDED_FOR',   // Proxy/Load balancer
			'HTTP_X_REAL_IP',         // Nginx proxy
			'HTTP_CLIENT_IP',         // Proxy
			'REMOTE_ADDR',             // Direct connection
		);

		foreach ( $headers as $header ) {
			if ( ! empty( $_SERVER[ $header ] ) ) {
				$ip = $_SERVER[ $header ];

				// Handle comma-separated list (X-Forwarded-For can have multiple IPs)
				if ( strpos( $ip, ',' ) !== false ) {
					$ips = explode( ',', $ip );
					$ip  = trim( $ips[0] );
				}

				// Validate IP
				if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
					break;
				}
			}
		}

		// Fallback to REMOTE_ADDR if no valid IP found
		if ( empty( $ip ) && ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = $_SERVER['REMOTE_ADDR'];
		}

		return $ip;
	}

	/**
	 * Get rate limit statistics
	 *
	 * @param string $action Optional action to filter by
	 * @return array Statistics
	 */
	public function get_statistics( $action = null ) {
		global $wpdb;

		$where  = '';
		$params = array();

		if ( $action !== null ) {
			$where    = 'WHERE action = %s';
			$params[] = $action;
		}

		$total_attempts = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->table_name} {$where}",
				$params
			)
		);

		$unique_identifiers = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT identifier) FROM {$this->table_name} {$where}",
				$params
			)
		);

		$recent_attempts = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->table_name}
                {$where} " . ( ! empty( $where ) ? 'AND' : 'WHERE' ) . ' attempted_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)',
				$params
			)
		);

		return array(
			'total_attempts'     => intval( $total_attempts ),
			'unique_identifiers' => intval( $unique_identifiers ),
			'recent_attempts_1h' => intval( $recent_attempts ),
		);
	}

	/**
	 * Create rate limit table
	 */
	public static function create_table() {
		global $wpdb;

		$table_name      = $wpdb->prefix . 'sqpmt_rate_limits';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            action varchar(50) NOT NULL,
            identifier varchar(255) NOT NULL,
            attempted_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY action_identifier (action, identifier),
            KEY attempted_at (attempted_at)
        ) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}
}
