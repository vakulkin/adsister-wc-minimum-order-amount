<?php
/**
 * WooCommerce Settings Page implementation for Minimum Order Amount.
 *
 * @package Adsister_WooCommerce_Minimum_Order_Amount
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Settings_Page', false ) && defined( 'WC_ABSPATH' ) ) {
	include_once WC_ABSPATH . 'includes/admin/settings/class-wc-settings-page.php';
}

/**
 * Adsister_WC_Settings_MOA class.
 */
class Adsister_WC_Settings_MOA extends WC_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'adsister_wc_moa_settings';
		$this->label = __( 'Minimum Order Amount', 'adsister-wc-minimum-order-amount' );

		parent::__construct();
	}

	/**
	 * Get settings array.
	 *
	 * @return array
	 */
	public function get_settings() {
		$currency_symbol = function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '$';

		$settings = array(
			array(
				'title' => __( 'Minimum Order Amount Configuration', 'adsister-wc-minimum-order-amount' ),
				'type'  => 'title',
				'desc'  => __( 'Configure the minimum order threshold required for customers to place orders. The calculation includes taxes and discounts, but excludes shipping costs.', 'adsister-wc-minimum-order-amount' ),
				'id'    => 'adsister_wc_moa_section_general',
			),
			array(
				'title'   => __( 'Enable Minimum Order Limit', 'adsister-wc-minimum-order-amount' ),
				'desc'    => __( 'Enforce a minimum order requirement before customers can proceed to checkout.', 'adsister-wc-minimum-order-amount' ),
				'id'      => 'adsister_wc_moa_enabled',
				'default' => 'yes',
				'type'    => 'checkbox',
			),
			array(
				'title'             => __( 'Minimum Order Amount', 'adsister-wc-minimum-order-amount' ),
				'desc'              => sprintf(
					/* translators: %s: Currency symbol */
					__( 'Minimum order subtotal + taxes (excluding shipping) in %s. Set to 0 to disable.', 'adsister-wc-minimum-order-amount' ),
					$currency_symbol
				),
				'id'                => 'adsister_wc_moa_min_amount',
				'default'           => '0',
				'type'              => 'number',
				'custom_attributes' => array(
					'min'  => '0',
					'step' => 'any',
				),
				'css'               => 'min-width: 150px;',
			),
			array(
				'title'   => __( 'Include Fees in Total', 'adsister-wc-minimum-order-amount' ),
				'desc'    => __( 'Include custom cart/order fees (with their taxes) in the qualifying minimum order total.', 'adsister-wc-minimum-order-amount' ),
				'id'      => 'adsister_wc_moa_include_fees',
				'default' => 'yes',
				'type'    => 'checkbox',
			),
			array(
				'title'    => __( 'Notification Message', 'adsister-wc-minimum-order-amount' ),
				'desc'     => __( 'Available dynamic placeholders:<br><code>{min_amount}</code> — Required minimum threshold<br><code>{current_amount}</code> — Current qualifying order total<br><code>{remaining_amount}</code> — Amount remaining to reach minimum', 'adsister-wc-minimum-order-amount' ),
				'id'       => 'adsister_wc_moa_cart_message',
				'default'  => __( 'Minimum order amount is {min_amount} (including taxes, excluding shipping). Your current qualifying total is {current_amount}. Please add {remaining_amount} more to proceed.', 'adsister-wc-minimum-order-amount' ),
				'type'     => 'textarea',
				'css'      => 'min-width: 450px; min-height: 90px;',
				'desc_tip' => false,
			),
			array(
				'type' => 'sectionend',
				'id'   => 'adsister_wc_moa_section_general',
			),
		);

		return apply_filters( 'adsister_wc_moa_settings', $settings );
	}
}
