<?php
/**
 * WooCommerce Gift Aid Donations & Claims
 *
 * @package    WooCommerce\GiftAidDonations
 * @author     Diane Wallace <hello@dianewallace.co.uk>
 * @license    GPL-2.0+
 */

if ( ! class_exists( 'Wooga_Cart' ) ) {

	class Wooga_Cart {

		public function __construct() {
			// Set Donation product price when loading the cart.
			add_filter( 'woocommerce_get_cart_item_from_session', array( $this, 'wooga_get_cart_item_from_session' ) );
			// Add the donation amount field to the cart display.
			add_filter( 'woocommerce_cart_item_price', array( $this, 'wooga_cart_item_price' ), 10, 3 );
			// Process donation amount fields in cart updates.
			add_filter( 'woocommerce_update_cart_action_cart_updated', array( $this, 'wooga_update_cart' ) );

			// Add Donation field to shopping cart.
			add_action( 'woocommerce_cart_contents', array( $this, 'wooga_woocommerce_after_cart_table' ) );
			// Capture form data and add basket item.
			add_action( 'init', array( $this, 'wooga_process_donation' ) );
			// Change price of donation product.
			add_filter( 'woocommerce_get_price', array( $this, 'wooga_get_price' ), 10, 2 );
			// Add checkbox field to the checkout.
			add_action( 'woocommerce_after_order_notes', array( $this, 'wooga_checkout_field' ) );
			// Update the order meta with field value.
			add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'wooga_checkout_field_update_order_meta' ) );
		}

		/**
		 * Set Donation product price when loading the cart.
		 *
		 * @since 1.0.0
		 *
		 * @param array $session_data WooCommerce data for users interactions with store.
		 * @return array $session_data
		 */
		function wooga_get_cart_item_from_session( $session_data ) {

			if ( $session_data['data']->get_type() === 'donation' && isset( $session_data['donation_amount'] ) ) {
				$session_data['data']->set_price( $session_data['donation_amount'] );
			}
			return $session_data;
		}

		/**
		 * Add the donation amount field to the cart display.
		 *
		 * @since 1.0.0
		 *
		 * @param string $price_html        Donation product price markup.
		 * @param array  $cart_item     The cart item.
		 * @param string $cart_item_key The cart item key.
		 * @return string Donation input field.
		 */
		function wooga_cart_item_price( $price_html, $cart_item, $cart_item_key ) {

			if ( $cart_item['data']->get_type() === 'donation' ) {
				$price_html = '<input type="number" name="donation_amount_' . $cart_item_key . '" size="5" min="0" step="' . $cart_item['data']->get_donation_amount_increment() . '" value="' . $cart_item['data']->get_price() . '" />';
			}

			return $price_html;
		}

		/**
		 * Process donation amount fields in cart updates.
		 *
		 * @since 1.0.0
		 *
		 * @param $cart_updated
		 * @return bool Has the cart been updated?
		 */
		function wooga_update_cart( $cart_updated ) {
			global $woocommerce;
			foreach ( $woocommerce->cart->get_cart() as $key => $cart_item ) {

				if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'woocommerce-cart' ) ) {
					return;
				}

				if ( $cart_item['data']->get_type() === 'donation' && isset( $_POST[ 'donation_amount_' . $key ] )
				     && is_numeric( $_POST[ 'donation_amount_' . $key ] ) && $_POST[ 'donation_amount_' . $key ] > 0 && $_POST[ 'donation_amount_' . $key ] !== $cart_item['data']->get_price() ) {
					$cart_item['donation_amount'] = $_POST[ 'donation_amount_' . $key ] * 1;
					$woocommerce->session->donation_amount = $cart_item['donation_amount'];
					$woocommerce->cart->cart_contents[ $key ] = $cart_item;
					$cart_updated = true;
				}
			}
			return $cart_updated;
		}

		/**
		 * Add Donation field to shopping cart.
		 *
		 * @since 1.0.0
		 *
		 * @return bool Is the donations product in the cart?
		 */
		public function wooga_donation_exists() {

			global $woocommerce;

			if ( count( $woocommerce->cart->get_cart() ) > 0 ) {

				foreach ( $woocommerce->cart->get_cart() as $cart_item_key => $values ) {

					$_product = $values['data'];

					if ( 'donation' === $_product->product_type ) {
						return true;
					}
				}
			}
			return false;
		}

		/**
		 * If no donation product, add a donation field to cart.
		 *
		 * @since 1.0.0
		 */
		public function wooga_woocommerce_after_cart_table() {

			global $woocommerce;
			$donate = isset( $woocommerce->session->donation_amount ) ? floatval( $woocommerce->session->donation_amount ) : 0;

			if ( ! $this->wooga_donation_exists() ) {
				unset( $woocommerce->session->donation_amount );
			}

			if ( ! $this->wooga_donation_exists() ) {
				?>
				<tr class="donation-block">
					<td colspan="6">
						<div class="donation">
							<p class="message"><strong>Add a donation to your order:</strong></p>
							<div class="input text">
								<label>Donation (&pound;):</label>
								<input type="text" name="donation_amount" value="<?php echo esc_attr( $donate ); ?>"/>
							</div>
							<div class="submit donate-btn">
								<?php wp_nonce_field( 'woocommerce-cart' ); ?>
								<input type="submit" class="woocommerce-Button button" name="add_donation" value="<?php esc_attr_e( 'Add Donation', 'wooga' ); ?>" />
								<input type="hidden" name="action" value="add_donation" />
							</div>
						</div>
					</td>
				</tr>
				<?php
			}
		}

		/**
		 * Capture form data and add basket item.
		 *
		 * @since 1.0.0
		 */
		public function wooga_process_donation() {

			global $woocommerce;

			if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'woocommerce-cart' ) ) {
				return;
			}

			$donation = isset( $_POST['donation_amount'] ) && ! empty( $_POST['donation_amount'] ) ? floatval( $_POST['donation_amount'] ) : false;

			if ( $donation && isset( $_POST['add_donation'] ) ) {

				// Add item to basket.
				$found = false;

				// Add to session.
				if ( $donation >= 0 ) {
					$woocommerce->session->donation_amount = $donation;

					//check if product already in cart
					if ( count( $woocommerce->cart->get_cart() ) > 0 ) {

						foreach ( $woocommerce->cart->get_cart() as $cart_item_key => $values ) {

							$_product = $values['data'];

							if ( 'donation' === $_product->product_type ) {
								$found = true;
							}
						}

						// if product not found, add it
						if ( ! $found ) {
							$woocommerce->cart->add_to_cart( DONATE_ID );
						}
					} else {
						// if no products in cart, add it
						$woocommerce->cart->add_to_cart( DONATE_ID );
					}
				}
			}
		}

		/**
		 * Get Price of Donation Product.
		 *
		 * @since 1.0.0
		 *
		 * @param int   $price Price of product.
		 * @param array $product Product data.
		 * @return float|int
		 */
		public function wooga_get_price( $price, $product ) {

			global $woocommerce;

			if ( $product->get_type() === 'donation' ) {

				return isset( $woocommerce->session->donation_amount ) ? floatval( $woocommerce->session->donation_amount ) : 0;

			}
			return $price;
		}

		/**
		 * Add Gift Aid checkbox field to the checkout.
		 *
		 * @since 1.0.0
		 *
		 * @param array $checkout WooCommerce checkout fields.
		 */
		public function wooga_checkout_field( $checkout ) {

			$charity = get_option( 'wooga_charity_id' );
			$label   = sprintf( wp_kses( __( '<strong>Gift Aid Declaration:</strong> Boost your donation by 25p of Gift Aid for every £1 you donate. Gift aid is reclaimed by the charity from the tax you pay for the current year. Your address is needed to identify you as a current UK taxpayer.<br /><br />
        I am a UK tax payer. Please treat all gifts I have made to %1$s in the last four years and all donations I make hereafter as Gift Aid donations. I understand that if I pay less Income Tax and/or Capital Gains Tax than the amount of Gift Aid claimed on all my donations in that tax year it is my responsibility to pay any difference.<br /><br />
        I understand that I can cancel this declaration at any time by notifying %1$s and I will inform them if I change my name or home address. Or If I no longer pay sufficient tax on my income or capital gains to reclaim gift aid.', 'wooga' ), array( 'br' => array(), 'strong' => array() ) ), $charity );

			if ( $this->wooga_donation_exists() ) {

				echo '<div id="gift-aid-field"><h3>' . esc_attr__( 'Gift Aid: ' ) . '</h3>';

				woocommerce_form_field( 'giftaid_checkbox', array(
					'type'     => 'checkbox',
					'class'    => array( 'input-checkbox' ),
					'label'    => $label,
					'required' => false,
				), $checkout->get_value( 'giftaid_checkbox' ));

				echo '</div>';
			}
		}

		/**
		 * Update the order meta with gift-aid field values.
		 *
		 * @since 1.0.0
		 *
		 * @param int $order_id WooCommerce unique order id.
		 */
		public function wooga_checkout_field_update_order_meta( $order_id ) {
			if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'woocommerce-process_checkout' ) ) {
				return;
			}

			if ( $_POST['giftaid_checkbox'] ) { update_post_meta( $order_id, 'gift_aid', esc_attr( $_POST['giftaid_checkbox'] ) );
			}

			// Post Data
			$order = new WC_Order( $order_id );
			$date      = $order->order_date;
			$first_name = get_post_meta( $order_id, '_billing_first_name', true );
			$last_name  = get_post_meta( $order_id, '_billing_last_name', true );
			$house_no   = get_post_meta( $order_id, '_billing_address_1', true );
			$post_code  = get_post_meta( $order_id, '_billing_postcode', true );
			$amount     = get_post_meta( $order_id, '_order_total', true );

			$donation_data = array(
				'first_name' => $first_name,
				'last_name'  => $last_name,
				'house_no'   => $house_no,
				'post_code'  => $post_code,
				'date'      => $date,
				'amount'    => $amount,
			);

			update_post_meta( $order_id, '_giftaid_donation_data', $donation_data );
			update_post_meta( $order_id, '_giftaid_donation_date', $date );
		}

	} // End class Wooga_Cart.

	new Wooga_Cart;

}
