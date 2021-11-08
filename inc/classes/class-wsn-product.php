<?php
/**
 * InStockNotifier
 *
 * @author Govind Kumar
 * @version 1.0.0
 * @package InStockNotifier/Classes
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

if ( ! class_exists( 'WSN_Product' ) ) {

	/**
	 * Class WSN_Product
	 * @package InStockNotifier
	 *
	 * @since 1.0.0
	 */
	class WSN_Product {

		/**
		 * Current product object
		 *
		 * @var \WC_Product
		 */
		private $current_product;

		/**
		 * Current product type
		 *
		 * @var string product_type;
		 */
		private $product_type;

		/**
		 * List of allowed product type
		 *
		 * @var array allowed_product_types
		 */
		public $allowed_product_types = array(
			'simple',
			'variation'
		);

		/**
		 * WSN_Product constructor.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {

			global $product;

			// Set current product.
			$this->current_product = $product;

			// Add form to woo commerce stock html.
			add_action( 'woocommerce_before_main_content', array( $this, 'get_output_form' ) );
			add_action( 'wp', array( $this, 'wsn_handle_submit' ) );
		}

		/**
		 * Getting the output form for waitlist in front end.
		 *
		 * @method get_output_form
		 */
		public function get_output_form() {
			global $post;

			$post_id = $post->ID;

			if ( get_post_type( $post_id ) === 'product' && is_product() ) {

				// Get the Product.
				$this->current_product = wc_get_product( $post_id );

				// @todo Add grouped product support.
				if ( 'grouped' === $this->current_product->get_type() ) {
					return;
				}

				// Check if, plugin functionality is enabled.
				if ( get_option( 'is_enabled', true ) ) {

					// Set the number of argument according to product type.
					$args_num = ( 'variable' === $this->current_product->get_type() ) ? 3 : 2;
					add_action( 'woocommerce_get_stock_html', array( $this, 'output_form' ), 20, $args_num );
				}
			}
		}

		/**
		 * Append the form to woo commerce stock html.
		 *
		 * @param string $html content for the form.
		 * @param bool $availability - number of product available.
		 * @param bool $product product .
		 *
		 * @return string
		 */
		public function output_form( $html, $availability, $product = false ) {

			if ( ! $product ) {
				$product = $this->current_product;
			}

			// @Todo add front-end functionality for the variable product.
			if ( ! is_user_logged_in() && ! get_option( 'unregistered_can_join' ) || $product->is_in_stock() ) {
				return $html;
			}

			return $this->form_data( $product, $html );
		}


		/**
		 * Generate the waitlist form for different product type.
		 *
		 * @param \WC_Product $product Product Object.
		 * @param string $html HTML content.
		 *
		 * @return string
		 */
		public function form_data( $product, $html ) {

			// Check if product is in stock.
			if ( ! isset( $product ) || $product->is_in_stock() ) {
				return $html;
			}

			// Product type.
			$product_type = $product->get_type();

			// Product id.
			$product_id = $product->get_id();

			// Get the product url.
			$product_url = ( 'simple' === $product_type || 'grouped' === $product_type ) ? get_permalink( $product->get_id() ) : get_permalink( $product->get_parent_id() );

			$this->product_id = $product_id;

			// Add action waitlist nonce.
			$url = wp_nonce_url( $product_url, 'action_waitlist' );

			$url = add_query_arg( WSN_USERS_META_KEY, $product_id, $url );
			$url = add_query_arg( WSN_USERS_META_KEY . '-action', 'register', $url );
			$url = add_query_arg( 'var_id', $product_id, $url );

			// is used logged-in ?
			$is_loggedin = is_user_logged_in();

			ob_start();

			do_action( 'before_wsn_form', $product, $is_loggedin );

			// If user is not logged-in.
			if ( ! is_user_logged_in() ) {
				if ( get_option( 'unregistered_can_join', true ) && in_array( $product_type, $this->allowed_product_types, true ) ) {
					?>
                    <div class="wsn-form">
                        <div class="wsn-form__inner">
                            <div class="wsn-form__content">
                                <div class="wsn-form-message">
                                    <div
                                            class="wsn-form-message-item"><?php echo apply_filters( 'wsn-out-of-stock', esc_html__( 'Out of Stock', 'in-stock-notifier' ), $is_loggedin ) ?></div>
                                    <div
                                            class="wsn-form-message-item"><?php echo apply_filters( 'wsn-guide-desc', esc_html__( 'Provide your Email so we can email you when product comes in-stock.', 'in-stock-notifier' ), $is_loggedin ); ?></div>
                                </div>
                                <div class="wsn-form-field">
                                    <input
                                            type="text"
                                            placeholder="<?php echo __( 'Enter Your Email Address...', 'in-stock-notifier' ); ?>"
                                            id="wsn_waitlist_email"
                                            name="wsn_email"
                                            class="wsn-input-field wsn-waitlist-email-field"
                                    />
                                </div>
                            </div>
                            <div class="wsn-form__footer">
                                <a
                                        class="<?php echo apply_filters( 'join_btn_classes', 'button btn alt wsn-submit-form wsn-submit-form--disabled', $is_loggedin ); ?>"
                                        href="<?php echo esc_url( $url ); ?>"
                                ><?php echo get_option( 'join_btn_label', esc_attr__( 'Join waitlist', 'in-stock-notifier' ) ); ?></a>
                            </div>
                        </div>
                    </div>
					<?php
				}
			} else {

				// Logged user data.
				$user = wp_get_current_user();

				// Logged user email.
				$email = $user->user_email;

				// Get waitlist of product.
				$waitlist = wsn_get_waitlist( $product_id );

				// already joined ?
				$joined = wsn_check_register( $email, $waitlist );
				?>
                <div class="wsn-form">
                    <div class="wsn-form__inner">
                        <div class="wsn-form__content">
                            <div class="wsn-form-message">
                                <div class="wsn-form-message-item">
									<?php echo esc_html__( 'Out of Stock', 'in-stock-notifier' ); ?>
                                </div>
                                <div class="wsn-form-message-item">
									<?php echo $joined ?
										esc_html( apply_filters( 'wsn_leave_waitlist_message_text', esc_attr__( 'We\'ll notify you as soon as item is back in stock.', 'in-stock-notifier' ), $is_loggedin ) ) :
										esc_html( apply_filters( 'wsn_join_waitlist_message_text', esc_attr__( 'Join the waitlist, so we can notify you as soon as the item is back in stock.', 'in-stock-notifier' ), $is_loggedin ) );
									?>
                                </div>
                            </div>
                        </div>
                        <div class="wsn-form__footer">
							<?php if ( $joined ) {
								$url = add_query_arg( WSN_USERS_META_KEY . '-action', 'leave', $url );
								$url = add_query_arg( 'wsn_email', $email, $url );
								?>
                                <a class="button btn alt wsn_button <?php echo esc_html( apply_filters( 'wsn_leave_waitlist_button_classes', 'wsn_leaveclass', $is_loggedin ) ); ?>"
                                   href="<?php echo esc_url( esc_attr( $url ) ); ?>"><?php echo get_option( 'leave_btn_label', __( 'Leave waitlist', 'in-stock-notifier' ) ); ?>
                                </a>
							<?php } else {
								$url = add_query_arg( 'wsn_email', $email, $url );
								?>
                                <a
                                        class="button btn alt <?php echo apply_filters( 'wsn_join_waitlist_button_classes', 'wsn_join_btn_class', $is_loggedin ); ?>"
                                        href="<?php echo esc_url( $url ); ?>"
                                ><?php echo get_option( 'join_btn_label', esc_attr__( 'Join waitlist', 'in-stock-notifier' ) ); ?></a>
							<?php } ?>
                        </div>
                    </div>
                </div>
				<?php
				do_action( 'after_wsn_form', $product, is_user_logged_in() );
			}

			// Return Generated html.
			return ob_get_clean();
		}


		/**
		 * Add user to waitlist.
		 *
		 * @access public
		 */
		public function wsn_handle_submit() {

			if ( ! isset( $_REQUEST['_wpnonce'] ) || ! isset( $_REQUEST[ WSN_USERS_META_KEY . '-action' ] ) || ! isset( $_REQUEST['wsn_email'] ) ) {
				return;
			}


			// Get nonce .
			if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'action_waitlist' ) ) {
				return;
			}

			// Operation to do.
			$action = sanitize_text_field( wp_unslash( $_REQUEST[ WSN_USERS_META_KEY . '-action' ] ) );


			// Start user session and set cookies.
			if ( ! isset( $_COOKIE['woocommerce_items_in_cart'] ) ) {
				do_action( 'woocommerce_set_cart_cookies', true );
			}

			$email = sanitize_email( wp_unslash( $_REQUEST['wsn_email'] ) );

			// Product id.
			if ( isset( $_REQUEST['var_id'] ) ) {
				$product_id = intval( $_REQUEST['var_id'] );
			}

			if ( 'register' === $action ) {

				if ( empty( $email ) || ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {

					$msg      = apply_filters( 'wsn_invalid_email', __( 'Please enter valid email address.', 'in-stock-notifier' ) );
					$msg_type = 'error';

				} elseif ( ! wsn_register_user( $email, $product_id ) ) {

					// Assign error message.
					$msg      = apply_filters( 'wsn_email_exists', __( 'This email is already in the wait list for this product, please try any different email.', 'in-stock-notifier' ) );
					$msg_type = 'error';

				} else {

					// Success message.
					$msg      = apply_filters( 'wsn_joined_successfully', __( 'You have successfully joined waitlist.', 'in-stock-notifier' ) );
					$msg_type = 'success';

				}

			} elseif ( 'leave' === $action ) {

				// Remove user from waitlist.
				if ( wsn_leave_user( $email, $product_id ) ) {

					// Remove message.
					$msg      = apply_filters( 'wsn_removed_successfully', __( 'You have been removed from the waiting list for this product', 'in-stock-notifier' ) );
					$msg_type = 'success';
				}
			}

			// Add message to woo commerce notice.
			wc_add_notice( $msg, $msg_type );

			// Remove query arguments from url.
			$redirect_back = remove_query_arg( array(
				WSN_USERS_META_KEY,
				WSN_USERS_META_KEY . '-action',
				'_wpnonce',
				'wsn_email',
				'var_id',
			) );

			// Redirect back to product page.
			wp_safe_redirect( esc_url( $redirect_back ) );

			exit;
		}
	}
}
