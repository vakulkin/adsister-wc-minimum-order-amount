<?php

/**
 * Plugin Name:       WooCommerce Minimum Order Amount
 * Description:       Enforces a minimum order amount (calculated with taxes, excluding shipping) with dynamic cart, checkout, and mini-cart notifications and AJAX recalculation support.
 * Version:           1.0.0
 * Text Domain:       adsister-wc-minimum-order-amount
 * Domain Path:       /languages
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * WC requires at least: 5.0
 * WC tested up to:   10.7
 *
 * @package Adsister_WooCommerce_Minimum_Order_Amount
 */

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

// Define plugin constants
define('ADSISTER_WC_MOA_VERSION', '1.0.0');
define('ADSISTER_WC_MOA_PLUGIN_FILE', __FILE__);
define('ADSISTER_WC_MOA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ADSISTER_WC_MOA_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ADSISTER_WC_MOA_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Declare HPOS and Cart/Checkout Blocks compatibility.
 */
add_action('before_woocommerce_init', function () {
	if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
	}
});

/**
 * Main Plugin Class.
 */
final class Adsister_WC_Minimum_Order_Amount
{

	/**
	 * Single instance of the plugin.
	 *
	 * @var Adsister_WC_Minimum_Order_Amount|null
	 */
	private static $instance = null;

	/**
	 * Get active instance.
	 *
	 * @return Adsister_WC_Minimum_Order_Amount
	 */
	public static function instance()
	{
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct()
	{
		$this->includes();
		$this->init_hooks();
	}

	/**
	 * Include required files.
	 */
	private function includes()
	{
		require_once ADSISTER_WC_MOA_PLUGIN_DIR . 'includes/class-adsister-wc-moa-calculator.php';
		require_once ADSISTER_WC_MOA_PLUGIN_DIR . 'includes/class-adsister-wc-moa-settings.php';
		require_once ADSISTER_WC_MOA_PLUGIN_DIR . 'includes/class-adsister-wc-moa-validator.php';
		require_once ADSISTER_WC_MOA_PLUGIN_DIR . 'includes/class-adsister-wc-moa-frontend.php';
	}

	/**
	 * Initialize plugin hooks.
	 */
	private function init_hooks()
	{
		add_action('plugins_loaded', array($this, 'load_textdomain'));

		// Initialize components
		Adsister_WC_MOA_Settings::init();
		Adsister_WC_MOA_Validator::init();
		Adsister_WC_MOA_Frontend::init();

		// Add Settings link on plugins page
		add_filter('plugin_action_links_' . ADSISTER_WC_MOA_PLUGIN_BASENAME, array($this, 'add_settings_link'));
	}

	/**
	 * Load plugin localization files.
	 */
	public function load_textdomain()
	{
		load_plugin_textdomain(
			'adsister-wc-minimum-order-amount',
			false,
			dirname(ADSISTER_WC_MOA_PLUGIN_BASENAME) . '/languages'
		);
	}

	/**
	 * Add link to settings page in plugin actions.
	 *
	 * @param array $links Plugin links.
	 * @return array
	 */
	public function add_settings_link($links)
	{
		$settings_url = admin_url('admin.php?page=wc-settings&tab=adsister_wc_moa_settings');
		$settings_link = '<a href="' . esc_url($settings_url) . '">' . esc_html__('Settings', 'adsister-wc-minimum-order-amount') . '</a>';
		array_unshift($links, $settings_link);
		return $links;
	}
}

/**
 * Bootstrap the plugin after WooCommerce is loaded.
 */
function adsister_wc_moa_init()
{
	// Ensure WooCommerce is active
	if (! class_exists('WooCommerce')) {
		add_action('admin_notices', function () {
			echo '<div class="error"><p>' .
				esc_html__('WooCommerce Minimum Order Amount requires WooCommerce to be installed and active.', 'adsister-wc-minimum-order-amount') .
				'</p></div>';
		});
		return;
	}

	Adsister_WC_Minimum_Order_Amount::instance();
}
add_action('plugins_loaded', 'adsister_wc_moa_init', 20);
