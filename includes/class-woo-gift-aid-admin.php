<?php
/**
 * WooCommerce Gift Aid Donations & Claims
 *
 * @package    WooCommerce\GiftAidDonations
 * @author     Diane Wallace <hello@dianewallace.co.uk>
 * @license    GPL-2.0+
 */

/**
 * Admin Section
 *
 * Functions and settings for the Gift Aid product.
 **/
if ( ! class_exists( 'Wooga_Admin' ) ) {

	class Wooga_Admin {

		public function __construct() {
			// Add Gift Aid section to Products tab.
			add_filter( 'woocommerce_get_sections_products', array( $this, 'wooga_add_section' ) );
			// Add claims table.
			add_action( 'woocommerce_admin_field_wooga_claims_table', array( $this, 'wooga_admin_field_wooga_claims_table' ) );
			// Add settings to Gift Aid tab.
			add_filter( 'woocommerce_get_settings_products', array( $this, 'wooga_all_settings' ), 10, 2 );
			// Export CSV.
			add_action( 'admin_post_print.csv', array( $this, 'wooga_print_csv' ) );
		}

		/**
		 * Create the Gift Aid section beneath the products tab.
		 *
		 * @since 1.0.0
		 *
		 * @param array $sections WooCommerce settings pages.
		 * @return array $sections WooCommerce settings pages, with additional Gift Aid settings page.
		 */
		public function wooga_add_section( $sections ) {

			$sections['wooga'] = __( 'Gift Aid', 'wooga' );
			return $sections;

		}

		/**
		 *  Add settings to the gift aid section.
		 *
		 * @since 1.0.0
		 *
		 * @param array $settings WooCommerce settings.
		 * @param string $current_section Settings page slug.
		 * @return array $settings_giftaid Gift Aid product settings.
		 */
		public function wooga_all_settings( $settings, $current_section ) {

			// Check the current section is what we want.
			if ( 'wooga' === $current_section ) {

				$settings_giftaid = array();

				// Add Title to the Gift Aid settings.
				$settings_giftaid[] = array( 'name' => __( 'Gift Aid Donation Settings', 'wooga' ), 'type' => 'title', 'desc' => __( 'The following options are used to configure the Simple Gift Aid Donations plugin.', 'wooga' ), 'id' => 'wooga' );

				// Define Donation Post.
				$settings_giftaid[] = array(

					'name'     => __( 'Donation Post ID', 'wooga' ),
					'desc_tip' => __( 'Any Woo Product with the donation category can be used', 'wooga' ),
					'id'       => 'wooga_donation_id',
					'type'     => 'text',
					'desc'     => __( 'Add the ID of your default donation', 'wooga' ),

				);

				// Define Charity Name.
				$settings_giftaid[] = array(

					'name'     => __( 'Charity Name', 'wooga' ),
					'desc_tip' => __( 'For use on the checkout form', 'wooga' ),
					'id'       => 'wooga_charity_id',
					'type'     => 'text',
					'desc'     => __( 'Add the Name of your charity ', 'wooga' ),

				);

				// Gift Aid Table.
				$settings_giftaid[] = array(

					'name'     => __( 'Gift Aid Claims', 'wooga' ),
					'id'       => 'wooga_claims',
					'type'     => 'wooga_claims_table',

				);

				$settings_giftaid[] = array( 'type' => 'sectionend', 'id' => 'wooga' );

				return $settings_giftaid;

				// If not, return the standard settings.

			} else {

				return $settings;

			}

		}

		/**
		 * Gift Aid Claims Admin Table.
		 *
		 * @since 1.0.0
		 *
		 * @param int $value Donation amount.
		 */
		public function wooga_admin_field_wooga_claims_table() {
			?>
			<table class="wooga_export_giftaid wc_input_table sortable widefat">
				<label for="wooga_export_giftaid" style="font-size: 14px; font-weight: 600; line-height: 40px;">Gift Aid Claims</label>
				<thead>
				<tr>
					<th><?php esc_attr_e( 'First Name', 'wooga' ); ?></th>
					<th><?php esc_attr_e( 'Last Name', 'wooga' ); ?></th>
					<th><?php esc_attr_e( 'House Name or Number', 'wooga' ); ?></th>
					<th><?php esc_attr_e( 'Post Code', 'wooga' ); ?></th>
					<th><?php esc_attr_e( 'Donation Date', 'wooga' ); ?></th>
					<th><?php esc_attr_e( 'Amount', 'wooga' ); ?></th>
				</tr>
				</thead>
				<tbody id="claims">
				<?php
				global $wpdb;

				$gift_aid   = 'gift_aid';
				$first_name = '_billing_first_name';
				$last_name  = '_billing_last_name';
				$house      = '_billing_address_1';
				$post_code  = '_billing_postcode';
				$date       = '_giftaid_donation_date';
				$amount     = '_order_total';

				$wooga_claims = $wpdb->get_results( $wpdb->prepare(
					"
						        SELECT      first_name.meta_value as first_name, last_name.meta_value as last_name, 
									        house.meta_value as house, post_code.meta_value as post_code,
									        amount.meta_value as amount, date.meta_value as date
						        FROM        $wpdb->postmeta gift_aid
						        INNER JOIN  $wpdb->postmeta first_name 
						                    on first_name.post_id = gift_aid.post_id
						                    and first_name.meta_key = %s
						        INNER JOIN  $wpdb->postmeta last_name 
						                    on last_name.post_id = gift_aid.post_id
						                    and last_name.meta_key = %s
						        INNER JOIN  $wpdb->postmeta house 
						                    on house.post_id = gift_aid.post_id
						                    and house.meta_key = %s
						        INNER JOIN  $wpdb->postmeta post_code
						                    on post_code.post_id = gift_aid.post_id
						                    and post_code.meta_key = %s
						        INNER JOIN  $wpdb->postmeta date
						                    on date.post_id = gift_aid.post_id
						                    and date.meta_key = %s
						        INNER JOIN  $wpdb->postmeta amount
						                    on amount.post_id = gift_aid.post_id
						                    and amount.meta_key = %s
						        WHERE       gift_aid.meta_key = %s
						                    and gift_aid.meta_value = 1
						        ORDER BY    date.meta_value
						        ",
					$first_name,
					$last_name,
					$house,
					$post_code,
					$date,
					$amount,
					$gift_aid
				) );

				foreach ( $wooga_claims as $data ) {
					?>
					<tr>
						<td>
							<input type="text" value="<?php echo esc_attr( $data->first_name ); ?>"  name="first_name" />
						</td>
						<td>
							<input type="text" value="<?php echo esc_attr( $data->last_name ); ?>"  name="last_name" />
						</td>
						<td>
							<input type="text" value="<?php echo esc_attr( $data->house ); ?>"  name="house" />
						</td>
						<td>
							<input type="text" value="<?php echo esc_attr( $data->post_code ); ?>"  name="post_code" />
						</td>
						<td>
							<input type="text" value="<?php echo esc_attr( date( 'd/m/y',strtotime( $data->date ) ) ); ?>"  name="date" />
						</td>
						<td>
							<input type="text" value="<?php echo esc_attr( $data->amount ); ?>"  name="amount" />
						</td>
					</tr>
					<?php
				}
				?>
				</tbody>
				<tfoot>
				<tr>
					<th colspan="10">
						<!-- <a href="#" class="button minus wooga_mark_claimed">Mark all as claimed</a> -->
						<a href=" <?php echo esc_url( admin_url( 'admin-post.php?action=print.csv' ) ) ?> " class="button export"><?php esc_attr_e( 'Export CSV', 'wooga' ); ?></a>
					</th>
				</tr>
				</tfoot>
			</table>

			<p>Once the table has been exported all donations will be marked as "claimed" and will no longer appear in this table.</p>
			<?php
		}

		/**
		 * Export Gift Aid Claims as CSV.
		 */
		public function wooga_print_csv() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			global $wpdb;

			$gift_aid   = 'gift_aid';
			$first_name = '_billing_first_name';
			$last_name  = '_billing_last_name';
			$house      = '_billing_address_1';
			$post_code  = '_billing_postcode';
			$date       = '_giftaid_donation_date';
			$amount     = '_order_total';

			$wooga_claims = $wpdb->get_results( $wpdb->prepare(
				"
		        SELECT      first_name.meta_value as first_name, last_name.meta_value as last_name, 
					        house.meta_value as house, post_code.meta_value as post_code,
					        amount.meta_value as amount, date.meta_value as date
		        FROM        $wpdb->postmeta gift_aid
		        INNER JOIN  $wpdb->postmeta first_name 
		                    on first_name.post_id = gift_aid.post_id
		                    and first_name.meta_key = %s
		        INNER JOIN  $wpdb->postmeta last_name 
		                    on last_name.post_id = gift_aid.post_id
		                    and last_name.meta_key = %s
		        INNER JOIN  $wpdb->postmeta house 
		                    on house.post_id = gift_aid.post_id
		                    and house.meta_key = %s
		        INNER JOIN  $wpdb->postmeta post_code
		                    on post_code.post_id = gift_aid.post_id
		                    and post_code.meta_key = %s
		        INNER JOIN  $wpdb->postmeta date
		                    on date.post_id = gift_aid.post_id
		                    and date.meta_key = %s
		        INNER JOIN  $wpdb->postmeta amount
		                    on amount.post_id = gift_aid.post_id
		                    and amount.meta_key = %s
		        WHERE       gift_aid.meta_key = %s
		                    and gift_aid.meta_value = 1
		        ORDER BY    date.meta_value
		        ",
				$first_name,
				$last_name,
				$house,
				$post_code,
				$date,
				$amount,
				$gift_aid
			) );

			$wooga_export = array();

			foreach ( $wooga_claims as $data ) {

				$data = array(
					__( 'Title', 'wooga' )                       => '',
					__( 'First Name', 'wooga' )                  => $data->first_name,
					__( 'Last Name', 'wooga' )                   => $data->last_name,
					__( 'House name or number', 'wooga' )        => $data->house,
					__( 'Postcode', 'wooga' )                    => $data->post_code,
					__( 'Aggregated donations', 'wooga' )        => '',
					__( 'Sponsored event (yes/blank)', 'wooga' ) => '',
					__( 'Donation date (DD/MM/YY)', 'wooga' )    => date( 'd/m/y',strtotime( $data->date ) ),
					__( 'Donation Amount', 'wooga' )             => $data->amount,
				);

				$wooga_export[] = $data;
			}

			header( 'Content-Type: application/csv' );
			header( 'Content-Disposition: attachment; filename=gift-aid.csv' );
			header( 'Pragma: no-cache' );

			if ( count( $wooga_export ) > 0 ) {
				echo esc_attr( $this->csv_formatted_line( array_keys( $wooga_export[0] ) ) );
				foreach ( $wooga_export as $data ) {
					echo esc_attr( $this->csv_formatted_line( $data ) );
				}
			} else {
				echo esc_attr__( 'There are no claims to export', 'wooga' );
			}

			//$wpdb->update( $wpdb->postmeta, array( 'meta_value' => 'claimed' ), array( 'meta_key' => 'gift_aid', 'meta_value' => 1 ) );

			exit();

		} //end print_csv

		/**
		 * Prepare Gift Aid data as CSV.
		 *
		 * @since 1.0.0
		 *
		 * @param array $data Gift Aid data.
		 * @return string Formatted csv.
		 */
		public function csv_formatted_line( $data ) {
			if ( is_array( $data ) ) {
				foreach ( $data as $key => $element ) {
					if ( ! is_string( $element ) ) {
						$element = (string) $element;
					}
					if ( strpos( $element, ',' ) !== false ) {
						if ( strpos( $element, '"' ) !== false ) {
							$element = str_replace( '"', '\"', $element );
						}
						$data[ $key ] = '"' . $element . '"';
					}
				}
				$data = implode( ',', $data );
			}

			return $data . "\n";
		} // End csv_formatted_line.

	} // End class Wooga_Admin.

	new Wooga_Admin;

}
