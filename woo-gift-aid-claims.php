<?php
/**
 * WooCommerce Gift Aid Donations & Claims
 *
 * @package    WooCommerce\GiftAidDonations
 * @author     Diane Wallace <hello@dianewallace.co.uk>
 * @license    GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name:       WooCommerce Gift Aid Donations & Claims
 * Plugin URI:        https://woocommerce.com
 * Description:       A WooCommerce plugin that creates a donations product and allows donors to elect to reclaim Gift Aid at the checkout. Claims can then be exported as csv.
 * Version:           1.0.0
 * Author:            Diane Wallace
 * Author URI:        http://dianewallace.co.uk/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
// Constants.
$donate_id = get_option( 'wooga_donation_id' );
define( 'DONATE_ID', $donate_id );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require_once plugin_dir_path( __FILE__ ) . 'includes/class-woo-gift-aid-claims.php';

/**
 * Checks if WooCommerce is active and begins execution of the plugin.
 *
 * @since 1.0.0
 */
function run_wooga() {
	// Check WooCommerce is active.
	if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {
		Wooga_Donations::get_instance();
	} else {
		printf(
			'<div class="error"><p>%s</p></div>',
			esc_html__( 'Please activate WooCommerce plugin. it is required for this plugin to work properly!', 'cwr-woocommerce-cart' )
		);
	}
}
run_wooga();
