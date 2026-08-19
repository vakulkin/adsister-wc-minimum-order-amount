/**
 * Adsister WooCommerce Minimum Order Amount - Frontend JavaScript
 */
(function ($) {
	'use strict';

	var AdsisterWCMOA = {
		init: function () {
			this.params = window.adsister_wc_moa_params || {};

			if (this.params.is_checkout) {
				this.bindCheckoutEvents();
				this.applyInitialState();
			}
		},

		bindCheckoutEvents: function () {
			var self = this;

			// Listen to WooCommerce checkout AJAX updates
			$(document.body).on('updated_checkout', function () {
				self.handleCheckoutRecount();
			});

			// Prevent clicks on disabled checkout submit button
			$(document).on('click', '#place_order.adsister-wc-moa-btn-disabled', function (e) {
				e.preventDefault();
				e.stopPropagation();
				if (self.params.btn_disabled_title) {
					alert(self.params.btn_disabled_title);
				}
				return false;
			});
		},

		applyInitialState: function () {
			if (!this.params.status) {
				return;
			}
			this.togglePlaceOrderButton(this.params.status.is_minimum_met);
		},

		handleCheckoutRecount: function () {
			var self = this;

			// Quick check from newly rendered order review DOM
			var $orderNotice = $('.adsister-wc-moa-checkout-order-notice');
			if ($orderNotice.length && typeof $orderNotice.data('is-met') !== 'undefined') {
				var isMet = $orderNotice.data('is-met') == '1';
				self.togglePlaceOrderButton(isMet);
				return;
			}

			if (!self.params.ajax_url || !self.params.nonce) {
				return;
			}

			$.ajax({
				url: self.params.ajax_url,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'adsister_wc_moa_get_cart_status',
					nonce: self.params.nonce
				},
				success: function (response) {
					if (response && response.success && response.data) {
						self.togglePlaceOrderButton(response.data.is_minimum_met);
					}
				}
			});
		},

		togglePlaceOrderButton: function (isMet) {
			var $placeOrderBtn = $('#place_order, button#place_order, form.checkout #place_order');

			if (!$placeOrderBtn.length) {
				return;
			}

			if (isMet) {
				$placeOrderBtn.removeClass('adsister-wc-moa-btn-disabled')
					.removeAttr('disabled')
					.removeAttr('aria-disabled')
					.removeAttr('title');
			} else {
				$placeOrderBtn.addClass('adsister-wc-moa-btn-disabled')
					.attr('disabled', 'disabled')
					.attr('aria-disabled', 'true')
					.attr('title', this.params.btn_disabled_title || '');
			}
		}
	};

	$(document).ready(function () {
		AdsisterWCMOA.init();
	});

})(jQuery);
