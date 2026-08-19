<?php
/**
 * Settings integration for WooCommerce Minimum Order Amount.
 *
 * @package Adsister_WooCommerce_Minimum_Order_Amount
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Adsister_WC_MOA_Settings {

	/**
	 * Initialize settings hooks.
	 */
	public static function init() {
		add_filter( 'woocommerce_get_settings_pages', array( __CLASS__, 'add_settings_page' ) );
	}

	/**
	 * Register the settings page class.
	 *
	 * @param array $settings List of settings pages.
	 * @return array
	 */
	public static function add_settings_page( $settings ) {
		if ( ! class_exists( 'Adsister_WC_Settings_MOA' ) ) {
			require_once ADSISTER_WC_MOA_PLUGIN_DIR . 'includes/admin/class-adsister-wc-settings-moa.php';
		}
		$settings[] = new Adsister_WC_Settings_MOA();
		return $settings;
	}
}
