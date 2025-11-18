<?php
/**
 * Settings Class
 *
 * Handles plugin settings and admin interface.
 *
 * @package Square_Payment_Service_Fee
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Settings class
 */
class SQPMT_Settings {

	/**
	 * The single instance of the class.
	 */
	protected static $_instance = null;

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
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'wp_ajax_sqpmt_test_connection', array( $this, 'ajax_test_connection' ) );
	}

	/**
	 * Add admin menu
	 */
	public function add_admin_menu() {
		add_options_page(
			__( 'Square Payment Settings', 'square-payment-service-fee' ),
			__( 'Square Payment', 'square-payment-service-fee' ),
			'manage_options',
			'square-payment-settings',
			array( $this, 'settings_page' )
		);
	}

	/**
	 * Register settings
	 */
	public function register_settings() {
		// API Settings Section
		add_settings_section(
			'sqpmt_api_section',
			__( 'Square API Configuration', 'square-payment-service-fee' ),
			array( $this, 'api_section_callback' ),
			'sqpmt_settings'
		);

		// Application ID
		register_setting(
			'sqpmt_settings',
			'sqpmt_application_id',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);

		add_settings_field(
			'sqpmt_application_id',
			__( 'Application ID', 'square-payment-service-fee' ),
			array( $this, 'application_id_field' ),
			'sqpmt_settings',
			'sqpmt_api_section'
		);

		// Access Token
		register_setting(
			'sqpmt_settings',
			'sqpmt_access_token',
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_access_token' ),
			)
		);

		add_settings_field(
			'sqpmt_access_token',
			__( 'Access Token', 'square-payment-service-fee' ),
			array( $this, 'access_token_field' ),
			'sqpmt_settings',
			'sqpmt_api_section'
		);

		// Location ID
		register_setting(
			'sqpmt_settings',
			'sqpmt_location_id',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);

		add_settings_field(
			'sqpmt_location_id',
			__( 'Location ID', 'square-payment-service-fee' ),
			array( $this, 'location_id_field' ),
			'sqpmt_settings',
			'sqpmt_api_section'
		);

		// Sandbox Mode
		register_setting(
			'sqpmt_settings',
			'sqpmt_sandbox_mode',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => 'yes',
			)
		);

		add_settings_field(
			'sqpmt_sandbox_mode',
			__( 'Sandbox Mode', 'square-payment-service-fee' ),
			array( $this, 'sandbox_mode_field' ),
			'sqpmt_settings',
			'sqpmt_api_section'
		);

		// Payment Settings Section
		add_settings_section(
			'sqpmt_payment_section',
			__( 'Payment Settings', 'square-payment-service-fee' ),
			array( $this, 'payment_section_callback' ),
			'sqpmt_settings'
		);

		// Service Fee Percentage
		register_setting(
			'sqpmt_settings',
			'sqpmt_service_fee',
			array(
				'type'              => 'number',
				'sanitize_callback' => array( $this, 'sanitize_percentage' ),
				'default'           => '3.5',
			)
		);

		add_settings_field(
			'sqpmt_service_fee',
			__( 'Service Fee Percentage', 'square-payment-service-fee' ),
			array( $this, 'service_fee_field' ),
			'sqpmt_settings',
			'sqpmt_payment_section'
		);

		// Minimum Amount
		register_setting(
			'sqpmt_settings',
			'sqpmt_minimum_amount',
			array(
				'type'              => 'number',
				'sanitize_callback' => 'floatval',
				'default'           => '1.00',
			)
		);

		add_settings_field(
			'sqpmt_minimum_amount',
			__( 'Minimum Payment Amount', 'square-payment-service-fee' ),
			array( $this, 'minimum_amount_field' ),
			'sqpmt_settings',
			'sqpmt_payment_section'
		);

		// Messages Section
		add_settings_section(
			'sqpmt_messages_section',
			__( 'Messages', 'square-payment-service-fee' ),
			array( $this, 'messages_section_callback' ),
			'sqpmt_settings'
		);

		// Success Message
		register_setting(
			'sqpmt_settings',
			'sqpmt_success_message',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'wp_kses_post',
			)
		);

		add_settings_field(
			'sqpmt_success_message',
			__( 'Success Message', 'square-payment-service-fee' ),
			array( $this, 'success_message_field' ),
			'sqpmt_settings',
			'sqpmt_messages_section'
		);

		// Failure Message
		register_setting(
			'sqpmt_settings',
			'sqpmt_failure_message',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'wp_kses_post',
			)
		);

		add_settings_field(
			'sqpmt_failure_message',
			__( 'Failure Message', 'square-payment-service-fee' ),
			array( $this, 'failure_message_field' ),
			'sqpmt_settings',
			'sqpmt_messages_section'
		);

		// Email Settings Section
		add_settings_section(
			'sqpmt_email_section',
			__( 'Email Notifications', 'square-payment-service-fee' ),
			array( $this, 'email_section_callback' ),
			'sqpmt_settings'
		);

		// Customer Email Enabled
		register_setting(
			'sqpmt_settings',
			'sqpmt_customer_email_enabled',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => 'yes',
			)
		);

		add_settings_field(
			'sqpmt_customer_email_enabled',
			__( 'Send Customer Receipt', 'square-payment-service-fee' ),
			array( $this, 'customer_email_enabled_field' ),
			'sqpmt_settings',
			'sqpmt_email_section'
		);

		// Admin Email Address
		register_setting(
			'sqpmt_settings',
			'sqpmt_admin_email_address',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_email',
				'default'           => get_option( 'admin_email' ),
			)
		);

		add_settings_field(
			'sqpmt_admin_email_address',
			__( 'Admin Notification Email', 'square-payment-service-fee' ),
			array( $this, 'admin_email_address_field' ),
			'sqpmt_settings',
			'sqpmt_email_section'
		);

		// Security Settings Section
		add_settings_section(
			'sqpmt_security_section',
			__( 'Security & Rate Limiting', 'square-payment-service-fee' ),
			array( $this, 'security_section_callback' ),
			'sqpmt_settings'
		);

		// Rate Limiting Enabled
		register_setting(
			'sqpmt_settings',
			'sqpmt_rate_limit_enabled',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => 'yes',
			)
		);

		add_settings_field(
			'sqpmt_rate_limit_enabled',
			__( 'Enable Rate Limiting', 'square-payment-service-fee' ),
			array( $this, 'rate_limit_enabled_field' ),
			'sqpmt_settings',
			'sqpmt_security_section'
		);

		// Payment Rate Limit Max
		register_setting(
			'sqpmt_settings',
			'sqpmt_rate_limit_payment_max',
			array(
				'type'              => 'number',
				'sanitize_callback' => 'absint',
				'default'           => 5,
			)
		);

		add_settings_field(
			'sqpmt_rate_limit_payment_max',
			__( 'Payment Attempts Limit', 'square-payment-service-fee' ),
			array( $this, 'rate_limit_payment_max_field' ),
			'sqpmt_settings',
			'sqpmt_security_section'
		);

		// Payment Rate Limit Window
		register_setting(
			'sqpmt_settings',
			'sqpmt_rate_limit_payment_window',
			array(
				'type'              => 'number',
				'sanitize_callback' => 'absint',
				'default'           => 300,
			)
		);

		add_settings_field(
			'sqpmt_rate_limit_payment_window',
			__( 'Payment Time Window (seconds)', 'square-payment-service-fee' ),
			array( $this, 'rate_limit_payment_window_field' ),
			'sqpmt_settings',
			'sqpmt_security_section'
		);
	}

	/**
	 * Settings page HTML
	 */
	public function settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Check if settings were saved
		if ( isset( $_GET['settings-updated'] ) ) {
			add_settings_error(
				'sqpmt_messages',
				'sqpmt_message',
				__( 'Settings saved successfully.', 'square-payment-service-fee' ),
				'updated'
			);
		}

		settings_errors( 'sqpmt_messages' );
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<div class="notice notice-info">
				<p>
					<strong><?php esc_html_e( 'Shortcode:', 'square-payment-service-fee' ); ?></strong>
					<code>[square_payment_form]</code>
					<br>
					<?php esc_html_e( 'Use this shortcode on any page or post to display the payment form.', 'square-payment-service-fee' ); ?>
				</p>
			</div>

			<form action="options.php" method="post">
				<?php
				settings_fields( 'sqpmt_settings' );
				do_settings_sections( 'sqpmt_settings' );
				submit_button( __( 'Save Settings', 'square-payment-service-fee' ) );
				?>
			</form>

			<hr>

			<h2><?php esc_html_e( 'Test Connection', 'square-payment-service-fee' ); ?></h2>
			<p><?php esc_html_e( 'Click the button below to test your Square API connection.', 'square-payment-service-fee' ); ?></p>
			<button type="button" class="button button-secondary" id="sqpmt-test-connection">
				<?php esc_html_e( 'Test Connection', 'square-payment-service-fee' ); ?>
			</button>
			<div id="sqpmt-test-result" style="margin-top: 15px;"></div>
		</div>

		<script>
		jQuery(document).ready(function($) {
			$('#sqpmt-test-connection').on('click', function() {
				var button = $(this);
				var resultDiv = $('#sqpmt-test-result');

				button.prop('disabled', true).text('<?php esc_html_e( 'Testing...', 'square-payment-service-fee' ); ?>');
				resultDiv.html('');

				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'sqpmt_test_connection',
						nonce: '<?php echo wp_create_nonce( 'sqpmt_test_connection' ); ?>'
					},
					success: function(response) {
						if (response.success) {
							resultDiv.html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
						} else {
							resultDiv.html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>');
						}
					},
					error: function() {
						resultDiv.html('<div class="notice notice-error"><p><?php esc_html_e( 'Connection test failed.', 'square-payment-service-fee' ); ?></p></div>');
					},
					complete: function() {
						button.prop('disabled', false).text('<?php esc_html_e( 'Test Connection', 'square-payment-service-fee' ); ?>');
					}
				});
			});
		});
		</script>
		<?php
	}

	/**
	 * Section callbacks
	 */
	public function api_section_callback() {
		echo '<p>' . esc_html__( 'Configure your Square API credentials. You can get these from your Square Developer Dashboard.', 'square-payment-service-fee' ) . '</p>';
		echo '<p><a href="https://developer.squareup.com/apps" target="_blank">' . esc_html__( 'Visit Square Developer Dashboard', 'square-payment-service-fee' ) . '</a></p>';
	}

	public function payment_section_callback() {
		echo '<p>' . esc_html__( 'Configure payment-related settings.', 'square-payment-service-fee' ) . '</p>';
	}

	public function messages_section_callback() {
		echo '<p>' . esc_html__( 'Customize the messages shown to users. Use {transaction_id} as a placeholder for the transaction ID.', 'square-payment-service-fee' ) . '</p>';
	}

	public function email_section_callback() {
		echo '<p>' . esc_html__( 'Configure email notification settings.', 'square-payment-service-fee' ) . '</p>';
	}

	public function security_section_callback() {
		echo '<p>' . esc_html__( 'Configure security and rate limiting options to protect against brute force attacks.', 'square-payment-service-fee' ) . '</p>';
	}

	/**
	 * Field callbacks
	 */
	public function application_id_field() {
		$value = get_option( 'sqpmt_application_id', '' );
		echo '<input type="text" name="sqpmt_application_id" value="' . esc_attr( $value ) . '" class="regular-text" required>';
		echo '<p class="description">' . esc_html__( 'Your Square Application ID from the Developer Dashboard.', 'square-payment-service-fee' ) . '</p>';
	}

	public function access_token_field() {
		$value = get_option( 'sqpmt_access_token', '' );
		// Don't display the actual token for security
		$display_value = ! empty( $value ) ? '••••••••••••••••' : '';
		echo '<input type="password" name="sqpmt_access_token" value="" class="regular-text" placeholder="' . esc_attr( $display_value ) . '">';
		echo '<p class="description">' . esc_html__( 'Your Square Access Token. This will be stored encrypted.', 'square-payment-service-fee' );
		if ( ! empty( $value ) ) {
			echo ' ' . esc_html__( '(Currently set - leave blank to keep existing)', 'square-payment-service-fee' );
		}
		echo '</p>';
	}

	public function location_id_field() {
		$value = get_option( 'sqpmt_location_id', '' );
		echo '<input type="text" name="sqpmt_location_id" value="' . esc_attr( $value ) . '" class="regular-text" required>';
		echo '<p class="description">' . esc_html__( 'Your Square Location ID.', 'square-payment-service-fee' ) . '</p>';
	}

	public function sandbox_mode_field() {
		$value = get_option( 'sqpmt_sandbox_mode', 'yes' );
		?>
		<label>
			<input type="radio" name="sqpmt_sandbox_mode" value="yes" <?php checked( $value, 'yes' ); ?>>
			<?php esc_html_e( 'Sandbox (Testing)', 'square-payment-service-fee' ); ?>
		</label>
		<br>
		<label>
			<input type="radio" name="sqpmt_sandbox_mode" value="no" <?php checked( $value, 'no' ); ?>>
			<?php esc_html_e( 'Production (Live)', 'square-payment-service-fee' ); ?>
		</label>
		<p class="description"><?php esc_html_e( 'Use Sandbox mode for testing. Switch to Production when ready to accept real payments.', 'square-payment-service-fee' ); ?></p>
		<?php
	}

	public function service_fee_field() {
		$value = get_option( 'sqpmt_service_fee', '3.5' );
		echo '<input type="number" name="sqpmt_service_fee" value="' . esc_attr( $value ) . '" step="0.01" min="0" max="100" class="small-text"> %';
		echo '<p class="description">' . esc_html__( 'The percentage fee to add to each payment.', 'square-payment-service-fee' ) . '</p>';
	}

	public function minimum_amount_field() {
		$value = get_option( 'sqpmt_minimum_amount', '1.00' );
		echo '$<input type="number" name="sqpmt_minimum_amount" value="' . esc_attr( $value ) . '" step="0.01" min="0.50" class="small-text">';
		echo '<p class="description">' . esc_html__( 'Minimum payment amount allowed.', 'square-payment-service-fee' ) . '</p>';
	}

	public function success_message_field() {
		$value = get_option( 'sqpmt_success_message', 'Thank you for your payment! Your transaction ID is: {transaction_id}' );
		echo '<textarea name="sqpmt_success_message" rows="3" class="large-text">' . esc_textarea( $value ) . '</textarea>';
		echo '<p class="description">' . esc_html__( 'Message shown after successful payment. Use {transaction_id} to display the transaction ID.', 'square-payment-service-fee' ) . '</p>';
	}

	public function failure_message_field() {
		$value = get_option( 'sqpmt_failure_message', 'Payment failed. Please check your card details and try again.' );
		echo '<textarea name="sqpmt_failure_message" rows="3" class="large-text">' . esc_textarea( $value ) . '</textarea>';
		echo '<p class="description">' . esc_html__( 'Message shown when payment fails.', 'square-payment-service-fee' ) . '</p>';
	}

	public function customer_email_enabled_field() {
		$value = get_option( 'sqpmt_customer_email_enabled', 'yes' );
		?>
		<label>
			<input type="checkbox" name="sqpmt_customer_email_enabled" value="yes" <?php checked( $value, 'yes' ); ?>>
			<?php esc_html_e( 'Send receipt email to customer after successful payment', 'square-payment-service-fee' ); ?>
		</label>
		<?php
	}

	public function admin_email_address_field() {
		$value = get_option( 'sqpmt_admin_email_address', get_option( 'admin_email' ) );
		?>
		<input type="email" name="sqpmt_admin_email_address" value="<?php echo esc_attr( $value ); ?>" class="regular-text" placeholder="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>">
		<p class="description">
			<?php esc_html_e( 'Enter the email address where admin notifications should be sent. Leave blank to disable admin notifications.', 'square-payment-service-fee' ); ?>
		</p>
		<?php
	}

	public function rate_limit_enabled_field() {
		$value = get_option( 'sqpmt_rate_limit_enabled', 'yes' );
		?>
		<label>
			<input type="checkbox" name="sqpmt_rate_limit_enabled" value="yes" <?php checked( $value, 'yes' ); ?>>
			<?php esc_html_e( 'Enable rate limiting to prevent brute force attacks', 'square-payment-service-fee' ); ?>
		</label>
		<p class="description">
			<?php esc_html_e( 'Recommended: Limits the number of payment attempts from the same user/IP address within a time window.', 'square-payment-service-fee' ); ?>
		</p>
		<?php
	}

	public function rate_limit_payment_max_field() {
		$value = get_option( 'sqpmt_rate_limit_payment_max', 5 );
		echo '<input type="number" name="sqpmt_rate_limit_payment_max" value="' . esc_attr( $value ) . '" min="1" max="100" class="small-text"> ' . esc_html__( 'attempts', 'square-payment-service-fee' );
		echo '<p class="description">' . esc_html__( 'Maximum number of payment attempts allowed per time window. Default: 5 attempts.', 'square-payment-service-fee' ) . '</p>';
	}

	public function rate_limit_payment_window_field() {
		$value = get_option( 'sqpmt_rate_limit_payment_window', 300 );
		echo '<input type="number" name="sqpmt_rate_limit_payment_window" value="' . esc_attr( $value ) . '" min="60" max="3600" class="small-text"> ' . esc_html__( 'seconds', 'square-payment-service-fee' );
		echo '<p class="description">' . esc_html__( 'Time window for rate limiting (in seconds). Default: 300 seconds (5 minutes).', 'square-payment-service-fee' ) . '</p>';
	}

	/**
	 * Sanitize access token (encrypt it)
	 */
	public function sanitize_access_token( $value ) {
		// If empty, keep the existing value
		if ( empty( $value ) ) {
			return get_option( 'sqpmt_access_token', '' );
		}

		// Encrypt the token
		return SQPMT_Square_API::encrypt_access_token( $value );
	}

	/**
	 * Sanitize percentage
	 */
	public function sanitize_percentage( $value ) {
		$value = floatval( $value );
		if ( $value < 0 ) {
			$value = 0;
		}
		if ( $value > 100 ) {
			$value = 100;
		}
		return $value;
	}

	/**
	 * AJAX: Test Square API connection
	 */
	public function ajax_test_connection() {
		check_ajax_referer( 'sqpmt_test_connection', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Unauthorized.', 'square-payment-service-fee' ),
				)
			);
		}

		// Check rate limiting (if enabled)
		if ( get_option( 'sqpmt_rate_limit_enabled', 'yes' ) === 'yes' ) {
			$rate_limiter = SQPMT_Rate_Limiter::instance();
			$identifier   = SQPMT_Rate_Limiter::get_request_identifier();

			$max_attempts = intval( get_option( 'sqpmt_rate_limit_test_max', 10 ) );
			$time_window  = intval( get_option( 'sqpmt_rate_limit_test_window', 60 ) );

			$rate_limit_check = $rate_limiter->check_rate_limit( 'test_connection', $identifier, $max_attempts, $time_window );

			if ( $rate_limit_check !== false && isset( $rate_limit_check['limited'] ) ) {
				wp_send_json_error(
					array(
						'message' => $rate_limit_check['message'],
					)
				);
			}
		}

		$api    = SQPMT_Square_API::instance();
		$result = $api->test_connection();

		if ( $result['success'] ) {
			wp_send_json_success(
				array(
					'message' => $result['message'],
				)
			);
		} else {
			wp_send_json_error(
				array(
					'message' => $result['error'],
				)
			);
		}
	}
}
