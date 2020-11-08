<?php
/**
 * InStockNotifier
 *
 * @author Govind Kumar
 * @version 1.0.0
 * @package InStockNotifier
 */

namespace InStockNotifier;

defined( 'ABSPATH' ) or die;

/**
 * In-Stock Notifier - WooCommerce Plugin
 * Copyright (C) 2017 Govind Kumar <gkprmr@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 3 as published
 * by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

if ( ! class_exists( 'WSN_Shortcode' ) ) {

	/**
	 * Class WSN_Shortcode
	 * @package InStockNotifier
	 *
	 * Generate the list of the product which is joined by logged user.
	 */
	class WSN_Shortcode {

		/**
		 * WSN_Shortcode constructor.
		 *
		 * @access public
		 * @since 1.0.0
		 */
		public function __construct() {

			// Show the list of products joined by the current logged user.
			add_shortcode( 'wsn_waiting_products', array( $this, 'wsn_display_waitlist' ) );
		}

		/**
		 * Generate the list of the product of logged user.
		 *
		 * @param array $atts Shortcode attributes.
		 */
		public function wsn_display_waitlist( $atts ) {

			global $wpdb, $user_ID;

			// Generating shortcode array.
			$atts = shortcode_atts( array(
				'user_id' => ( is_user_logged_in() ) ? $user_ID : 0,
			), $atts, 'wsn_waiting_products' );

			// Get the user data.
			$user = get_user_by( 'ID', $atts['user_id'] );

			if ( ! isset( $user->ID ) ) {
				echo esc_html__( 'Please login to view products list.', 'in-stock-notifier' );

				return false;
			}

			$inc = 1;

			if ( ! is_user_logged_in() && empty( $atts['user_id'] ) ) {

				$login_text = apply_filters( 'wsn_users_must_login_message_text', __( 'You must have to login or Pass the User ID to list out the products.', 'in-stock-notifier' ) );
				?>
                <li><?php echo esc_html( $login_text ); ?></li><?php

			} else {

				// Get the all waiting products.
				$products = $wpdb->get_results( $wpdb->prepare( "SELECT post_id,meta_value FROM $wpdb->postmeta WHERE meta_key='%s' ", WSN_USERS_META_KEY ) );

				echo sprintf( esc_attr__( 'Hi %s, here is the product list you are waiting for.', 'in-stock-notifier' ), esc_html( $user->display_name ) );
				?>
                <table><?php

				foreach ( $products as $key => $row ) {

					$product_waitlist_email = unserialize( $row->meta_value );

					if ( in_array( $user->user_email, $product_waitlist_email, true ) ) {

						$product_id = $row->post_id;

						if ( 'product_variation' === get_post_type( $product_id ) ) {
							/** @var \WC_Product_Variation $variation product */
							$variation = new \WC_Product_Variation( $product_id );
							$product_name = $variation->get_title() . ' - ' .implode( " / ", $variation->get_variation_attributes() );
							$product_url = $variation->get_permalink();
					    } else {
							/** @var \WC_Product $product product */
							$product = new \WC_Product( $row->post_id );
							$product_url = $product->get_permalink();
							$product_name = $product->get_formatted_name();
						}
						?>
                        <tr>
                            <td class="index_col"><?php echo intval( $inc ); ?></td>
                            <td>
                                <a href="<?php echo esc_url( $product_url ); ?>"><?php echo esc_html( $product_name ); ?></a>
                            </td>
                        </tr>
						<?php
						$inc ++;
						$counter ++;
					}
				}

				if ( 1 === $inc ) {
					?>
                    <tr>
                        <td colspan="2">
							<?php echo esc_html__( 'There is no product.', 'in-stock-notifier' ); ?>
                        </td>
                    </tr>
					<?php
				}
				?></table><?php
			}
		}
	}
}