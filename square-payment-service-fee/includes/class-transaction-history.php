<?php
/**
 * Transaction History Class
 *
 * Handles transaction history admin page.
 *
 * @package Square_Payment_Service_Fee
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Transaction History class
 */
class SQPMT_Transaction_History {

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
		add_action( 'wp_ajax_sqpmt_export_transactions', array( $this, 'ajax_export_transactions' ) );
	}

	/**
	 * Add admin menu
	 */
	public function add_admin_menu() {
		add_submenu_page(
			'options-general.php',
			__( 'Square Payment History', 'square-payment-service-fee' ),
			__( 'Payment History', 'square-payment-service-fee' ),
			'manage_options',
			'square-payment-history',
			array( $this, 'history_page' )
		);
	}

	/**
	 * History page HTML
	 */
	public function history_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$logger = SQPMT_Transaction_Logger::instance();

		// Get filter parameters
		$search     = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';
		$start_date = isset( $_GET['start_date'] ) ? sanitize_text_field( $_GET['start_date'] ) : '';
		$end_date   = isset( $_GET['end_date'] ) ? sanitize_text_field( $_GET['end_date'] ) : '';
		$status     = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '';

		// Pagination
		$per_page     = 20;
		$current_page = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
		$offset       = ( $current_page - 1 ) * $per_page;

		// Get transactions
		$filters = array();
		if ( ! empty( $start_date ) && ! empty( $end_date ) ) {
			$filters['start_date'] = $start_date;
			$filters['end_date']   = $end_date;
		}
		if ( ! empty( $status ) ) {
			$filters['status'] = $status;
		}
		if ( ! empty( $search ) ) {
			$filters['search'] = $search;
		}

		// Get transactions based on filters
		if ( ! empty( $search ) ) {
			$transactions = $logger->search_transactions( $search, $per_page, $offset );
		} elseif ( ! empty( $start_date ) && ! empty( $end_date ) ) {
			$transactions = $logger->get_transactions_by_date_range( $start_date, $end_date, $per_page, $offset );
		} else {
			$transactions = $logger->get_recent_transactions( $per_page, $offset );
		}

		// Get total count for pagination
		$total_items = $logger->get_transaction_count( $filters );
		$total_pages = ceil( $total_items / $per_page );

		// Get statistics
		$stats = $logger->get_statistics( 'all' );

		$calculator = SQPMT_Calculator::instance();
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<!-- Statistics -->
			<div class="sqpmt-stats-grid">
				<div class="sqpmt-stat-box">
					<div class="sqpmt-stat-label"><?php esc_html_e( 'Total Transactions', 'square-payment-service-fee' ); ?></div>
					<div class="sqpmt-stat-value"><?php echo esc_html( number_format( $stats['total_transactions'] ) ); ?></div>
				</div>
				<div class="sqpmt-stat-box">
					<div class="sqpmt-stat-label"><?php esc_html_e( 'Successful', 'square-payment-service-fee' ); ?></div>
					<div class="sqpmt-stat-value sqpmt-stat-success"><?php echo esc_html( number_format( $stats['successful_transactions'] ) ); ?></div>
				</div>
				<div class="sqpmt-stat-box">
					<div class="sqpmt-stat-label"><?php esc_html_e( 'Total Revenue', 'square-payment-service-fee' ); ?></div>
					<div class="sqpmt-stat-value"><?php echo esc_html( $calculator->format_amount( $stats['total_revenue'] ) ); ?></div>
				</div>
				<div class="sqpmt-stat-box">
					<div class="sqpmt-stat-label"><?php esc_html_e( 'Total Fees Collected', 'square-payment-service-fee' ); ?></div>
					<div class="sqpmt-stat-value"><?php echo esc_html( $calculator->format_amount( $stats['total_fees'] ) ); ?></div>
				</div>
			</div>

			<!-- Filters -->
			<form method="get" class="sqpmt-filters">
				<input type="hidden" name="page" value="square-payment-history">

				<div class="sqpmt-filter-row">
					<input
						type="text"
						name="s"
						placeholder="<?php esc_attr_e( 'Search by name, email, or transaction ID...', 'square-payment-service-fee' ); ?>"
						value="<?php echo esc_attr( $search ); ?>"
						class="sqpmt-search-input"
					>

					<input
						type="date"
						name="start_date"
						placeholder="<?php esc_attr_e( 'Start Date', 'square-payment-service-fee' ); ?>"
						value="<?php echo esc_attr( $start_date ); ?>"
					>

					<input
						type="date"
						name="end_date"
						placeholder="<?php esc_attr_e( 'End Date', 'square-payment-service-fee' ); ?>"
						value="<?php echo esc_attr( $end_date ); ?>"
					>

					<select name="status">
						<option value=""><?php esc_html_e( 'All Statuses', 'square-payment-service-fee' ); ?></option>
						<option value="COMPLETED" <?php selected( $status, 'COMPLETED' ); ?>><?php esc_html_e( 'Completed', 'square-payment-service-fee' ); ?></option>
						<option value="FAILED" <?php selected( $status, 'FAILED' ); ?>><?php esc_html_e( 'Failed', 'square-payment-service-fee' ); ?></option>
					</select>

					<button type="submit" class="button"><?php esc_html_e( 'Filter', 'square-payment-service-fee' ); ?></button>

					<?php if ( ! empty( $search ) || ! empty( $start_date ) || ! empty( $end_date ) || ! empty( $status ) ) : ?>
						<a href="?page=square-payment-history" class="button"><?php esc_html_e( 'Clear', 'square-payment-service-fee' ); ?></a>
					<?php endif; ?>

					<button type="button" id="sqpmt-export-btn" class="button button-secondary" style="margin-left: auto;">
						<?php esc_html_e( 'Export to CSV', 'square-payment-service-fee' ); ?>
					</button>
				</div>
			</form>

			<!-- Transactions Table -->
			<?php if ( empty( $transactions ) ) : ?>
				<div class="notice notice-info">
					<p><?php esc_html_e( 'No transactions found.', 'square-payment-service-fee' ); ?></p>
				</div>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped sqpmt-transactions-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Date', 'square-payment-service-fee' ); ?></th>
							<th><?php esc_html_e( 'Transaction ID', 'square-payment-service-fee' ); ?></th>
							<th><?php esc_html_e( 'Customer', 'square-payment-service-fee' ); ?></th>
							<th><?php esc_html_e( 'Amount', 'square-payment-service-fee' ); ?></th>
							<th><?php esc_html_e( 'Service Fee', 'square-payment-service-fee' ); ?></th>
							<th><?php esc_html_e( 'Total', 'square-payment-service-fee' ); ?></th>
							<th><?php esc_html_e( 'Status', 'square-payment-service-fee' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'square-payment-service-fee' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $transactions as $transaction ) : ?>
							<tr>
								<td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $transaction->created_at ) ) ); ?></td>
								<td><code><?php echo esc_html( $transaction->transaction_id ); ?></code></td>
								<td>
									<strong><?php echo esc_html( $transaction->customer_name ); ?></strong><br>
									<small><?php echo esc_html( $transaction->customer_email ); ?></small>
								</td>
								<td><?php echo esc_html( $calculator->format_amount( $transaction->amount ) ); ?></td>
								<td><?php echo esc_html( $calculator->format_amount( $transaction->service_fee ) ); ?></td>
								<td><strong><?php echo esc_html( $calculator->format_amount( $transaction->total_amount ) ); ?></strong></td>
								<td>
									<?php if ( $transaction->status === 'COMPLETED' ) : ?>
										<span class="sqpmt-status sqpmt-status-success"><?php esc_html_e( 'Completed', 'square-payment-service-fee' ); ?></span>
									<?php else : ?>
										<span class="sqpmt-status sqpmt-status-failed"><?php esc_html_e( 'Failed', 'square-payment-service-fee' ); ?></span>
									<?php endif; ?>
								</td>
								<td>
									<button
										type="button"
										class="button button-small sqpmt-view-details"
										data-transaction-id="<?php echo esc_attr( $transaction->id ); ?>"
									>
										<?php esc_html_e( 'View Details', 'square-payment-service-fee' ); ?>
									</button>
								</td>
							</tr>
							<tr class="sqpmt-details-row" id="sqpmt-details-<?php echo esc_attr( $transaction->id ); ?>" style="display: none;">
								<td colspan="8">
									<div class="sqpmt-transaction-details">
										<h3><?php esc_html_e( 'Transaction Details', 'square-payment-service-fee' ); ?></h3>
										<div class="sqpmt-details-grid">
											<div class="sqpmt-detail-group">
												<h4><?php esc_html_e( 'Customer Information', 'square-payment-service-fee' ); ?></h4>
												<p><strong><?php esc_html_e( 'Phone:', 'square-payment-service-fee' ); ?></strong> <?php echo esc_html( $transaction->customer_phone ); ?></p>
												<p><strong><?php esc_html_e( 'Address:', 'square-payment-service-fee' ); ?></strong><br>
													<?php
													echo esc_html( $transaction->customer_address_line1 );
													if ( ! empty( $transaction->customer_address_line2 ) ) {
														echo '<br>' . esc_html( $transaction->customer_address_line2 );
													}
													echo '<br>' . esc_html( $transaction->customer_city ) . ', ';
													echo esc_html( $transaction->customer_state ) . ' ';
													echo esc_html( $transaction->customer_zip );
													?>
												</p>
											</div>
											<?php if ( ! empty( $transaction->error_message ) ) : ?>
												<div class="sqpmt-detail-group">
													<h4><?php esc_html_e( 'Error Message', 'square-payment-service-fee' ); ?></h4>
													<p class="sqpmt-error-text"><?php echo esc_html( $transaction->error_message ); ?></p>
												</div>
											<?php endif; ?>
										</div>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<!-- Pagination -->
				<?php if ( $total_pages > 1 ) : ?>
					<div class="tablenav">
						<div class="tablenav-pages">
							<span class="displaying-num">
								<?php
								printf(
									esc_html( _n( '%s item', '%s items', $total_items, 'square-payment-service-fee' ) ),
									number_format_i18n( $total_items )
								);
								?>
							</span>
							<?php
							echo paginate_links(
								array(
									'base'      => add_query_arg( 'paged', '%#%' ),
									'format'    => '',
									'prev_text' => __( '&laquo;', 'square-payment-service-fee' ),
									'next_text' => __( '&raquo;', 'square-payment-service-fee' ),
									'total'     => $total_pages,
									'current'   => $current_page,
								)
							);
							?>
						</div>
					</div>
				<?php endif; ?>
			<?php endif; ?>
		</div>

		<style>
		.sqpmt-stats-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
			gap: 20px;
			margin: 20px 0;
		}
		.sqpmt-stat-box {
			background: #fff;
			border: 1px solid #ccd0d4;
			border-radius: 4px;
			padding: 20px;
		}
		.sqpmt-stat-label {
			font-size: 13px;
			color: #666;
			margin-bottom: 8px;
		}
		.sqpmt-stat-value {
			font-size: 28px;
			font-weight: bold;
			color: #2271b1;
		}
		.sqpmt-stat-success {
			color: #4caf50;
		}
		.sqpmt-filters {
			background: #fff;
			border: 1px solid #ccd0d4;
			border-radius: 4px;
			padding: 15px;
			margin: 20px 0;
		}
		.sqpmt-filter-row {
			display: flex;
			gap: 10px;
			align-items: center;
			flex-wrap: wrap;
		}
		.sqpmt-search-input {
			flex: 1;
			min-width: 200px;
		}
		.sqpmt-transactions-table {
			margin-top: 20px;
		}
		.sqpmt-status {
			display: inline-block;
			padding: 4px 12px;
			border-radius: 12px;
			font-size: 12px;
			font-weight: bold;
		}
		.sqpmt-status-success {
			background: #d4edda;
			color: #155724;
		}
		.sqpmt-status-failed {
			background: #f8d7da;
			color: #721c24;
		}
		.sqpmt-transaction-details {
			background: #f9f9f9;
			padding: 20px;
			border-radius: 4px;
		}
		.sqpmt-details-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
			gap: 20px;
			margin-top: 15px;
		}
		.sqpmt-detail-group h4 {
			margin-top: 0;
			margin-bottom: 10px;
		}
		.sqpmt-error-text {
			color: #d63638;
		}
		</style>

		<script>
		jQuery(document).ready(function($) {
			// Toggle details
			$('.sqpmt-view-details').on('click', function() {
				var transactionId = $(this).data('transaction-id');
				$('#sqpmt-details-' + transactionId).toggle();
			});

			// Export to CSV
			$('#sqpmt-export-btn').on('click', function() {
				var params = new URLSearchParams(window.location.search);
				params.set('action', 'sqpmt_export_transactions');
				params.set('nonce', '<?php echo wp_create_nonce( 'sqpmt_export_transactions' ); ?>');

				window.location.href = ajaxurl + '?' + params.toString();
			});
		});
		</script>
		<?php
	}

	/**
	 * AJAX: Export transactions to CSV
	 */
	public function ajax_export_transactions() {
		check_ajax_referer( 'sqpmt_export_transactions', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Unauthorized.', 'square-payment-service-fee' ) );
		}

		$logger = SQPMT_Transaction_Logger::instance();

		// Get filters
		$filters = array();
		if ( ! empty( $_GET['start_date'] ) && ! empty( $_GET['end_date'] ) ) {
			$filters['start_date'] = sanitize_text_field( $_GET['start_date'] );
			$filters['end_date']   = sanitize_text_field( $_GET['end_date'] );
			$transactions          = $logger->get_transactions_by_date_range( $filters['start_date'], $filters['end_date'], 10000, 0 );
		} elseif ( ! empty( $_GET['s'] ) ) {
			$search       = sanitize_text_field( $_GET['s'] );
			$transactions = $logger->search_transactions( $search, 10000, 0 );
		} else {
			$transactions = $logger->get_recent_transactions( 10000, 0 );
		}

		// Set headers for CSV download
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=square-payment-transactions-' . date( 'Y-m-d' ) . '.csv' );

		// Create output stream
		$output = fopen( 'php://output', 'w' );

		// Add BOM for Excel UTF-8 support
		fprintf( $output, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );

		// CSV headers
		fputcsv(
			$output,
			array(
				'Date',
				'Transaction ID',
				'Customer Name',
				'Customer Email',
				'Customer Phone',
				'Address',
				'City',
				'State',
				'ZIP',
				'Amount',
				'Service Fee',
				'Total',
				'Status',
				'Error Message',
			)
		);

		// CSV rows
		foreach ( $transactions as $transaction ) {
			$address = $transaction->customer_address_line1;
			if ( ! empty( $transaction->customer_address_line2 ) ) {
				$address .= ', ' . $transaction->customer_address_line2;
			}

			fputcsv(
				$output,
				array(
					date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $transaction->created_at ) ),
					$transaction->transaction_id,
					$transaction->customer_name,
					$transaction->customer_email,
					$transaction->customer_phone,
					$address,
					$transaction->customer_city,
					$transaction->customer_state,
					$transaction->customer_zip,
					number_format( $transaction->amount, 2 ),
					number_format( $transaction->service_fee, 2 ),
					number_format( $transaction->total_amount, 2 ),
					$transaction->status,
					$transaction->error_message ?? '',
				)
			);
		}

		fclose( $output );
		exit;
	}
}
