<?php
/**
 * Calculator Class
 *
 * Handles all service fee calculations.
 *
 * @package Square_Payment_Service_Fee
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Calculator class
 */
class SQPMT_Calculator {

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
	 * Calculate service fee
	 *
	 * @param float $amount Original amount
	 * @return float Service fee amount
	 */
	public function calculate_service_fee( $amount ) {
		$amount = floatval( $amount );

		if ( $amount <= 0 ) {
			return 0;
		}

		$service_fee_percentage = floatval( get_option( 'sqpmt_service_fee', '3.5' ) );
		$service_fee            = ( $amount * $service_fee_percentage ) / 100;

		// Round to 2 decimal places
		return round( $service_fee, 2 );
	}

	/**
	 * Calculate total amount (original + service fee)
	 *
	 * @param float $amount Original amount
	 * @return float Total amount
	 */
	public function calculate_total( $amount ) {
		$amount      = floatval( $amount );
		$service_fee = $this->calculate_service_fee( $amount );

		return round( $amount + $service_fee, 2 );
	}

	/**
	 * Get breakdown of payment amounts
	 *
	 * @param float $amount Original amount
	 * @return array Array with original, fee, and total amounts
	 */
	public function get_payment_breakdown( $amount ) {
		$amount      = floatval( $amount );
		$service_fee = $this->calculate_service_fee( $amount );
		$total       = $this->calculate_total( $amount );

		return array(
			'original_amount'        => round( $amount, 2 ),
			'service_fee'            => $service_fee,
			'total_amount'           => $total,
			'service_fee_percentage' => floatval( get_option( 'sqpmt_service_fee', '3.5' ) ),
		);
	}

	/**
	 * Format amount for display
	 *
	 * @param float $amount Amount to format
	 * @param bool  $include_currency Include currency symbol
	 * @return string Formatted amount
	 */
	public function format_amount( $amount, $include_currency = true ) {
		$formatted = number_format( floatval( $amount ), 2, '.', ',' );

		if ( $include_currency ) {
			return '$' . $formatted;
		}

		return $formatted;
	}

	/**
	 * Validate amount
	 *
	 * @param mixed $amount Amount to validate
	 * @return bool|WP_Error True if valid, WP_Error if invalid
	 */
	public function validate_amount( $amount ) {
		// Check if amount is numeric
		if ( ! is_numeric( $amount ) ) {
			return new WP_Error(
				'invalid_amount',
				__( 'Amount must be a number.', 'square-payment-service-fee' )
			);
		}

		$amount = floatval( $amount );

		// Check if amount is positive
		if ( $amount <= 0 ) {
			return new WP_Error(
				'invalid_amount',
				__( 'Amount must be greater than zero.', 'square-payment-service-fee' )
			);
		}

		// Check minimum amount
		$min_amount = floatval( get_option( 'sqpmt_minimum_amount', '1.00' ) );
		if ( $amount < $min_amount ) {
			return new WP_Error(
				'invalid_amount',
				sprintf(
					__( 'Amount must be at least %s.', 'square-payment-service-fee' ),
					$this->format_amount( $min_amount )
				)
			);
		}

		// Check maximum amount (if set)
		$max_amount = floatval( get_option( 'sqpmt_maximum_amount', '0' ) );
		if ( $max_amount > 0 && $amount > $max_amount ) {
			return new WP_Error(
				'invalid_amount',
				sprintf(
					__( 'Amount cannot exceed %s.', 'square-payment-service-fee' ),
					$this->format_amount( $max_amount )
				)
			);
		}

		return true;
	}
}
