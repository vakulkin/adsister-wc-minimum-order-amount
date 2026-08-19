<?php
/**
 * Calculator class for WooCommerce Minimum Order Amount.
 *
 * @package Adsister_WooCommerce_Minimum_Order_Amount
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Adsister_WC_MOA_Calculator {

	/**
	 * Check if the minimum order limit is enabled.
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		$enabled    = get_option( 'adsister_wc_moa_enabled', 'yes' ) === 'yes';
		$min_amount = self::get_min_amount();

		return $enabled && ( $min_amount > 0 );
	}

	/**
	 * Get the minimum order amount threshold from settings.
	 *
	 * @return float
	 */
	public static function get_min_amount() {
		$amount = get_option( 'adsister_wc_moa_min_amount', '0' );
		return (float) apply_filters( 'adsister_wc_moa_min_amount', (float) wc_format_decimal( $amount ) );
	}

	/**
	 * Calculate the qualifying order total (with taxes, without shipping).
	 *
	 * @return float
	 */
	public static function get_qualifying_total() {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return 0.0;
		}

		$cart = WC()->cart;

		if ( $cart->is_empty() ) {
			return 0.0;
		}

		// Total order amount including items, fees, taxes and shipping
		$total_raw = (float) $cart->get_total( 'edit' );

		// Shipping amount and shipping taxes to exclude
		$shipping_total = (float) $cart->get_shipping_total();
		$shipping_tax   = (float) $cart->get_shipping_tax();
		$shipping_cost  = $shipping_total + $shipping_tax;

		// Calculate total with taxes, excluding shipping
		$qualifying_total = $total_raw - $shipping_cost;

		// Check if fees should be excluded
		$include_fees = get_option( 'adsister_wc_moa_include_fees', 'yes' ) === 'yes';
		if ( ! $include_fees ) {
			$fee_total = (float) $cart->get_fee_total();
			$fee_tax   = (float) $cart->get_fee_tax();
			$qualifying_total -= ( $fee_total + $fee_tax );
		}

		$qualifying_total = max( 0.0, round( $qualifying_total, wc_get_price_decimals() ) );

		return (float) apply_filters( 'adsister_wc_moa_qualifying_total', $qualifying_total, $cart );
	}

	/**
	 * Check if the current cart satisfies the minimum order requirement.
	 *
	 * @return bool
	 */
	public static function is_minimum_met() {
		if ( ! self::is_enabled() ) {
			return true;
		}

		if ( ! function_exists( 'WC' ) || ! WC()->cart || WC()->cart->is_empty() ) {
			return true;
		}

		$min_amount       = self::get_min_amount();
		$qualifying_total = self::get_qualifying_total();

		return $qualifying_total >= $min_amount;
	}

	/**
	 * Get the remaining shortfall amount needed to meet the minimum.
	 *
	 * @return float
	 */
	public static function get_shortfall() {
		$min_amount       = self::get_min_amount();
		$qualifying_total = self::get_qualifying_total();

		$shortfall = max( 0.0, $min_amount - $qualifying_total );
		return (float) round( $shortfall, wc_get_price_decimals() );
	}

	/**
	 * Generate formatted notification message with placeholders replaced.
	 *
	 * @param string $context Context ('cart', 'checkout').
	 * @return string
	 */
	public static function get_message( $context = 'cart' ) {
		$min_amount       = self::get_min_amount();
		$qualifying_total = self::get_qualifying_total();
		$shortfall        = self::get_shortfall();

		$default_message = __( 'Minimum order amount is {min_amount} (including taxes, excluding shipping). Your current qualifying total is {current_amount}. Please add {remaining_amount} more to proceed.', 'adsister-wc-minimum-order-amount' );
		
		$template = get_option( 'adsister_wc_moa_cart_message', '' );
		if ( empty( trim( $template ) ) ) {
			$template = $default_message;
		}

		$formatted_min       = wc_price( $min_amount );
		$formatted_current   = wc_price( $qualifying_total );
		$formatted_remaining = wc_price( $shortfall );

		$replacements = array(
			'{min_amount}'       => $formatted_min,
			'{current_amount}'   => $formatted_current,
			'{remaining_amount}' => $formatted_remaining,
			'{min_total}'        => $formatted_min,
			'{current_total}'    => $formatted_current,
			'{remaining_total}'  => $formatted_remaining,
		);

		$message = str_replace( array_keys( $replacements ), array_values( $replacements ), $template );

		return apply_filters( 'adsister_wc_moa_formatted_message', $message, $context, $qualifying_total, $min_amount, $shortfall );
	}

	/**
	 * Get current state array for AJAX / JSON responses.
	 *
	 * @return array
	 */
	public static function get_cart_status_data() {
		$is_enabled       = self::is_enabled();
		$min_amount       = self::get_min_amount();
		$qualifying_total = self::get_qualifying_total();
		$is_met           = self::is_minimum_met();
		$shortfall        = self::get_shortfall();

		return array(
			'enabled'          => $is_enabled,
			'is_minimum_met'   => $is_met,
			'min_amount'       => $min_amount,
			'min_amount_html'  => wc_price( $min_amount ),
			'qualifying_total' => $qualifying_total,
			'current_html'     => wc_price( $qualifying_total ),
			'shortfall'        => $shortfall,
			'shortfall_html'   => wc_price( $shortfall ),
			'message'          => $is_met ? '' : self::get_message(),
		);
	}
}
