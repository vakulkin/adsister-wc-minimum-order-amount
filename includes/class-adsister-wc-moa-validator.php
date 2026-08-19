<?php
/**
 * Validator class for WooCommerce Minimum Order Amount.
 *
 * @package Adsister_WooCommerce_Minimum_Order_Amount
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Adsister_WC_MOA_Validator {

	/**
	 * Track if notice was already added in current request cycle.
	 *
	 * @var bool
	 */
	private static $notice_added = false;

	/**
	 * Initialize validation hooks.
	 */
	public static function init() {
		// Validate cart items ONLY on cart page (allow full access to checkout page without blocking form render)
		add_action( 'woocommerce_check_cart_items', array( __CLASS__, 'validate_cart_page_items' ), 20 );

		// Server-side validation during checkout submission (blocks placing the order)
		add_action( 'woocommerce_after_checkout_validation', array( __CLASS__, 'validate_checkout_submission' ), 20, 2 );
		add_action( 'woocommerce_checkout_process', array( __CLASS__, 'validate_checkout_process' ), 20 );

		// WooCommerce Store API validation (for Cart/Checkout Blocks)
		add_action( 'woocommerce_store_api_checkout_update_order_from_request', array( __CLASS__, 'validate_blocks_checkout' ), 10, 2 );
	}

	/**
	 * Check cart totals ONLY on the Cart page.
	 * Excludes Checkout page so WooCommerce doesn't hide the checkout form with the generic error.
	 */
	public static function validate_cart_page_items() {
		if ( ! Adsister_WC_MOA_Calculator::is_enabled() ) {
			return;
		}

		if ( ! function_exists( 'WC' ) || ! WC()->cart || WC()->cart->is_empty() ) {
			return;
		}

		// Avoid notices outside frontend
		if ( is_admin() && ! wp_doing_ajax() ) {
			return;
		}

		// DO NOT add notice in check_cart_items on checkout page so checkout form remains accessible
		if ( function_exists( 'is_checkout' ) && is_checkout() ) {
			return;
		}

		// Only display on real cart page
		if ( function_exists( 'is_cart' ) && ! is_cart() ) {
			return;
		}

		if ( ! Adsister_WC_MOA_Calculator::is_minimum_met() ) {
			$message = Adsister_WC_MOA_Calculator::get_message( 'cart' );

			if ( ! self::$notice_added && ! self::has_moa_notice( $message ) ) {
				wc_add_notice( $message, 'error' );
				self::$notice_added = true;
			}
		}
	}

	/**
	 * Check whether our notice is already in WooCommerce notice queue.
	 *
	 * @param string $message Notice message.
	 * @return bool
	 */
	private static function has_moa_notice( $message ) {
		if ( ! function_exists( 'wc_has_notice' ) ) {
			return false;
		}
		return wc_has_notice( $message, 'error' );
	}

	/**
	 * Validate during checkout submission.
	 *
	 * @param array    $data   Posted checkout data.
	 * @param WP_Error $errors Validation errors object.
	 */
	public static function validate_checkout_submission( $data, $errors ) {
		if ( ! Adsister_WC_MOA_Calculator::is_enabled() ) {
			return;
		}

		if ( ! Adsister_WC_MOA_Calculator::is_minimum_met() ) {
			$message = Adsister_WC_MOA_Calculator::get_message( 'checkout' );
			$errors->add( 'adsister_wc_moa_min_order_error', $message );
		}
	}

	/**
	 * Secondary fallback check during checkout processing.
	 */
	public static function validate_checkout_process() {
		if ( ! Adsister_WC_MOA_Calculator::is_enabled() ) {
			return;
		}

		if ( ! Adsister_WC_MOA_Calculator::is_minimum_met() ) {
			$message = Adsister_WC_MOA_Calculator::get_message( 'checkout' );
			if ( ! wc_has_notice( $message, 'error' ) ) {
				wc_add_notice( $message, 'error' );
			}
		}
	}

	/**
	 * Validate Store API Checkout (WooCommerce Blocks).
	 *
	 * @param \WC_Order        $order   Order object.
	 * @param \WP_REST_Request $request REST request object.
	 * @throws \Exception
	 */
	public static function validate_blocks_checkout( $order, $request ) {
		if ( ! Adsister_WC_MOA_Calculator::is_enabled() ) {
			return;
		}

		if ( ! Adsister_WC_MOA_Calculator::is_minimum_met() ) {
			$message = wp_strip_all_tags( Adsister_WC_MOA_Calculator::get_message( 'checkout' ) );
			throw new \Automattic\WooCommerce\StoreApi\Exceptions\RouteException(
				'adsister_woocommerce_rest_min_order_error',
				$message,
				400
			);
		}
	}
}
