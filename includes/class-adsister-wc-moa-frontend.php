<?php
/**
 * Frontend integration for WooCommerce Minimum Order Amount.
 *
 * @package Adsister_WooCommerce_Minimum_Order_Amount
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Adsister_WC_MOA_Frontend {

	/**
	 * Initialize frontend hooks.
	 */
	public static function init() {
		// Enqueue scripts & styles ONLY on cart and checkout pages
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );

		// Render notice on Checkout page (inside the order review directly above Place Order button, auto-refreshes on AJAX recount)
		add_action( 'woocommerce_review_order_before_submit', array( __CLASS__, 'render_checkout_notice' ), 10 );

		// AJAX recalculation endpoint
		add_action( 'wp_ajax_adsister_wc_moa_get_cart_status', array( __CLASS__, 'ajax_get_cart_status' ) );
		add_action( 'wp_ajax_nopriv_adsister_wc_moa_get_cart_status', array( __CLASS__, 'ajax_get_cart_status' ) );
	}

	/**
	 * Enqueue styles and scripts only on real cart and checkout pages.
	 */
	public static function enqueue_assets() {
		if ( ! Adsister_WC_MOA_Calculator::is_enabled() ) {
			return;
		}

		if ( ! function_exists( 'is_cart' ) || ! function_exists( 'is_checkout' ) ) {
			return;
		}

		// Only load assets on Cart and Checkout pages (excluding thank you / order received)
		$is_cart     = is_cart();
		$is_checkout = is_checkout() && ( ! function_exists( 'is_order_received_page' ) || ! is_order_received_page() );

		if ( ! $is_cart && ! $is_checkout ) {
			return;
		}

		wp_enqueue_style(
			'adsister-wc-moa-frontend',
			ADSISTER_WC_MOA_PLUGIN_URL . 'assets/css/adsister-wc-moa-frontend.css',
			array(),
			ADSISTER_WC_MOA_VERSION
		);

		wp_enqueue_script(
			'adsister-wc-moa-frontend',
			ADSISTER_WC_MOA_PLUGIN_URL . 'assets/js/adsister-wc-moa-frontend.js',
			array( 'jquery' ),
			ADSISTER_WC_MOA_VERSION,
			true
		);

		$status = Adsister_WC_MOA_Calculator::get_cart_status_data();

		wp_localize_script(
			'adsister-wc-moa-frontend',
			'adsister_wc_moa_params',
			array(
				'ajax_url'           => admin_url( 'admin-ajax.php' ),
				'nonce'              => wp_create_nonce( 'adsister_wc_moa_nonce' ),
				'status'             => $status,
				'is_cart'            => $is_cart,
				'is_checkout'        => $is_checkout,
				'btn_disabled_title' => __( 'Minimum order amount is not met. Please return to cart and add more items.', 'adsister-wc-minimum-order-amount' ),
			)
		);
	}

	/**
	 * Render inline notice right above the Place Order button (inside #order_review, refreshes on AJAX).
	 */
	public static function render_checkout_notice() {
		if ( ! Adsister_WC_MOA_Calculator::is_enabled() ) {
			return;
		}

		$is_met  = Adsister_WC_MOA_Calculator::is_minimum_met();
		$message = Adsister_WC_MOA_Calculator::get_message( 'checkout' );

		?>
		<div class="adsister-wc-moa-checkout-order-notice" data-is-met="<?php echo $is_met ? '1' : '0'; ?>" style="<?php echo $is_met ? 'display:none;' : ''; ?>">
			<ul class="woocommerce-error" role="alert" style="margin-bottom: 15px;">
				<li><?php echo wp_kses_post( $message ); ?></li>
			</ul>
		</div>
		<?php
	}

	/**
	 * AJAX endpoint to fetch real-time cart status data.
	 */
	public static function ajax_get_cart_status() {
		check_ajax_referer( 'adsister_wc_moa_nonce', 'nonce' );

		$status = Adsister_WC_MOA_Calculator::get_cart_status_data();

		wp_send_json_success( $status );
	}
}
