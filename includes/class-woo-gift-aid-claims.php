<?php
/**
 * WooCommerce Gift Aid Donations & Claims
 *
 * @package    WooCommerce\GiftAidDonations
 * @author     Diane Wallace <hello@dianewallace.co.uk>
 * @license    GPL-2.0+
 */

if ( ! class_exists( 'Wooga_Donations' ) ) {

	/**
	 * Main WooCommerce Gift Aid Donations & Claims Class.
	 */
	class Wooga_Donations {
		/**
		 * The unique identifier of this plugin.
		 *
		 * @since 1.0.0
		 * @access protected
		 * @var string $plugin_name The string used to uniquely identify this plugin.
		 */
		protected static $plugin_name = 'woo-giftaid-donations-and-claims';

		/**
		 * The current version of the plugin.
		 *
		 * @since 1.0.0
		 * @var string $version The current version of the plugin.
		 */
		const VERSION = '1.0.0';

		public function __construct() {
			// Add Donation product to product type drop down.
			add_filter( 'product_type_selector',  array( $this, 'wooga_product_type_selector' ) );
			// Hide all but the General and Advanced product data tabs for Donation products.
			add_filter( 'woocommerce_product_data_tabs', array( $this, 'wooga_product_data_tabs' ), 10, 1 );
			// Add fields to the General product data tab.
			add_filter( 'woocommerce_product_options_general_product_data', array( $this, 'wooga_product_options_general' ) );
			// Save donation product meta.
			add_action( 'woocommerce_process_product_meta_donation', array( $this, 'wooga_process_product_meta' ) );

			// Disable price display in frontend for Donation products.
			add_filter( 'woocommerce_get_price_html', array( $this, 'wooga_get_price_html' ), 10, 2 );
			// Add amount field before add to cart button.
			add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'wooga_before_add_to_cart_button' ) );
			// Use the Simple product type's add to cart button for Donation products.
			add_action( 'woocommerce_donation_add_to_cart', array( $this, 'wooga_add_to_cart_template' ) );
		}

		/**
		 * Include the files that make up the plugin.
		 *
		 * @since    1.0.0
		 * @access   private
		 */
		private function includes() {
			// Classes.
			require_once plugin_dir_path( __FILE__ ) . 'class-woo-product-donation.php';
			require_once plugin_dir_path( __FILE__ ) . 'class-woo-gift-aid-admin.php';
			require_once plugin_dir_path( __FILE__ ) . 'class-woo-gift-aid-cart.php';
		}

		/**
		 * Returns the instance.
		 *
		 * @since  1.0.0
		 * @access public
		 * @return object
		 */
		public static function get_instance() {
			static $instance = null;
			if ( is_null( $instance ) ) {
				$instance = new self;
				$instance->includes();
			}
			return $instance;
		}

		/**
		 * Add Donation product to product type drop down.
		 *
		 * @since 1.0.0
		 *
		 * @param array $product_types WooCommerce product types, such as Simple, Variable etc.
		 * @return array $product_types Product types array with Donation product appended.
		 */
		public function wooga_product_type_selector( $product_types ) {
			$product_types['donation'] = __( 'Donation', 'wooga-donations' );
			return $product_types;
		}

		/**
		 * Hide all but the General and Advanced product data tabs for Donation products.
		 *
		 * @since 1.0.0
		 *
		 * @param array $tabs Product settings Tabs.
		 * @return array $tabs
		 */
		public function wooga_product_data_tabs( $tabs ) {
			foreach ( $tabs as $tab_id => $tab_data ) {
				if ( 'general' !== $tab_id && 'advanced' !== $tab_id ) {
					$tabs[ $tab_id ]['class'][] = 'hide_if_donation';
				}
			}
			return $tabs;
		}

		/**
		 * Add fields to the General product data tab.
		 *
		 * @since 1.0.0
		 */
		public function wooga_product_options_general() {
			global $thepostid;
			?>
			<div class="options_group show_if_donation">
				<?php
				woocommerce_wp_text_input(
					array(
						'id' => 'donation_default_amount',
						'label' => __( 'Default amount', 'wooga-donations' ),
						'value' => get_post_meta( $thepostid, '_price', true ),
						'data_type' => 'price',
					)
				);
				$donation_increment = get_post_meta( $thepostid, '_donation_amount_increment', true );
				woocommerce_wp_text_input(
					array(
						'id' => 'donation_amount_increment',
						'label' => __( 'Amount increment', 'wooga-donations' ),
						'value' => ( empty( $donation_increment ) ? 0.01 : $donation_increment ),
						'data_type' => 'decimal',
					)
				);
				?>
			</div>
			<?php
		}

		/**
		 * Save donation product meta.
		 *
		 * @since 1.0.0
		 *
		 * @param int $product_id Product unique identifier.
		 */
		public function wooga_process_product_meta( $product_id ) {
			if ( empty( $_POST['woocommerce_meta_nonce'] ) || ! wp_verify_nonce( $_POST['woocommerce_meta_nonce'], 'woocommerce_save_data' ) ) {
				return;
			}

			$price = ( '' === $_POST['donation_default_amount'] ) ? '' : wc_format_decimal( $_POST['donation_default_amount'] );
			update_post_meta( $product_id, '_price', $price );
			update_post_meta( $product_id, '_regular_price', $price );
			update_post_meta( $product_id, '_donation_amount_increment', ( ! empty( $_POST['donation_amount_increment'] ) && is_numeric( $_POST['donation_amount_increment'] ) ? number_format( $_POST['donation_amount_increment'], 2, '.', '' ) : 0.01) );
		}

		/**
		 * Disable price display in frontend for Donation products.
		 *
		 * @since 1.0.0
		 *
		 * @param string $price   Price for Donation product.
		 * @param array  $product WooCommerce product data.
		 * @return string $price
		 */
		public function wooga_get_price_html( $price, $product ) {
			if ( $product->get_type() === 'donation' ) {
				return ( is_admin() ? 'Variable' : '' );
			} else {
				return $price;
			}
		}

		/**
		 * Add amount field before add to cart button.
		 *
		 * @since 1.0.0
		 */
		public function wooga_before_add_to_cart_button() {
			global $product;

			if ( $product->get_type() === 'donation' ) { ?>
				<div class="wc-donation-amount">
					<?php wp_nonce_field( 'woocommerce-cart' ); ?>
					<label for="donation_amount"><?php esc_attr_e( 'Amount', 'wooga-donations' ) ?>:</label>
					<input type="number" name="donation_amount" id="donation_amount" size="5" min="0" step="<?php esc_attr_e( $product->get_donation_amount_increment() ); ?>" value="<?php number_format_i18n( esc_attr_e( $product->get_price() ), 2 ); ?>" class="input-text text" />
					<input type="hidden" name="action" value="donation_amount" />
				</div>
			<?php }
		}

		/**
		 * Add to Cart.
		 *
		 * Use the Simple product type's add to cart button for Donation products.
		 *
		 * @since 1.0.0
		 */
		function wooga_add_to_cart_template() {
			do_action( 'woocommerce_simple_add_to_cart' );
		}

	} // End class Wooga_Donations.

}
