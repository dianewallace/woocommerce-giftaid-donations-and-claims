<?php
/**
 * WooCommerce Gift Aid Donations & Claims
 *
 * @package    WooCommerce\GiftAidDonations
 * @author     Diane Wallace <hello@dianewallace.co.uk>
 * @license    GPL-2.0+
 */

add_action( 'init', 'wooga_donation_product_type' );
/**
 * Register the donation product type after init.
 */
function wooga_donation_product_type() {

	if ( ! class_exists( 'WC_Product_Simple' ) ) {
		return;
	}

	class WC_Product_Donation extends WC_Product_Simple {

		/**
		 * WC_Product_Donation constructor.
		 *
		 * @param mixed $product
		 */
		public function __construct( $product ) {
			parent::__construct( $product );
			$this->product_type = 'donation';
		}

		/**
		 * Get product type.
		 *
		 * @return string Donation Product.
		 */
		public function get_type() {
			return 'donation';
		}

		/**
		 * Get donation amount increment.
		 *
		 * @return float $donation_increment
		 */
		public function get_donation_amount_increment() {
			if ( ! isset( $this->donation_increment ) ) {
				$this->donation_increment = get_post_meta( $this->get_id(), '_donation_amount_increment', true );
				if ( empty( $this->donation_increment ) ) {
					$this->donation_increment = 0.01;
				}
			}

			return $this->donation_increment;
		}

		/**
		 * Returns true if donation product can be sold individually.
		 *
		 * @return bool
		 */
		public function is_sold_individually() {
			return true;
		}

		/**
		 * Returns false if donation product is not taxable.
		 *
		 * @return bool
		 */
		public function is_taxable() {
			return false;
		}

		/**
		 * Returns false if donation product does not need shipping.
		 *
		 * @return bool
		 */
		public function needs_shipping() {
			return false;
		}

		/**
		 * Returns true if donation product is virtual.
		 *
		 * @return bool
		 */
		public function is_virtual() {
			return true;
		}

		/**
		 * Get the add to cart button text.
		 *
		 * @return string
		 */
		public function add_to_cart_text() {
			return __( 'Donate', 'wooga-donations' );
		}

		/**
		 * Get the single add to cart button text.
		 *
		 * @return string
		 */
		public function single_add_to_cart_text() {
			return __( 'Donate', 'wooga-donations' );
		}

		/**
		 * Get the add to cart url.
		 *
		 * @return string
		 */
		public function add_to_cart_url() {
			return get_permalink( $this->id );
		}
	}
}

