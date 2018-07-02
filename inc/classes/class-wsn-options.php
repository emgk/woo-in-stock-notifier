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

if ( ! class_exists( 'WSN_Options' ) ) {

	/**
	 * Class WSN_Options
	 * @package InStockNotifier
	 */
	class WSN_Options {

		/**
		 * WSN_Options constructor.
		 */
		function __construct() {

			// Woo commerce email initialization.
			add_action( 'woocommerce_init', array( $this, 'load_wc_mailer' ) );
			add_filter( 'woocommerce_email_classes', array( $this, 'add_woocommerce_emails' ) );

			// Ajax form for plugin backend.
			add_action( 'admin_footer', array( $this, 'add_admin_script' ) );

			// Add Ajax form for add new user in waitlist.
			add_action( 'wp_ajax_addNewUser', array( $this, 'add_new_user_ajax' ) );

			// Add ajax form for remove user from the waitlist.
			add_action( 'wp_ajax_removeUser', array( $this, 'remove_user_ajax' ) );

			// Add ajax email sent action form.
			add_action( 'wp_ajax_wsn_waitlist_send_mail', array( $this, 'wsn_waitlist_send_mail_ajax' ) );

			// Ajax action form for retrieve all archived user.
			add_action( 'wp_ajax_archive_function', array( $this, 'wsn_archive_function' ) );

			// Adding css and js in backend.
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

			// Add email action to woo commerce.
			add_action( 'woocommerce_email_actions', array( $this, 'kia_ajax_email_action' ) );

			// Add meta box.
			add_action( 'add_meta_boxes', array( $this, 'wsn_product_tab' ) );
		}

		/**
		 * Add meta box in backend.
		 *
		 * @access public
		 */
		public function wsn_product_tab() {

			// Add meta box.
			add_meta_box( 'wsn_product',  // Meta box id.
				esc_attr__( 'In-Stock Notifier', 'in-stock-notifier' ), // Title of the meta box.
				array( $this, 'wsn_product_tab_callback' ), // Content for inside the meta box.
				'product',      // Post type.
				'normal', 'high',         // Priority.
				null );
		}

		/**
		 * Get all the user email from the archived
		 *
		 * @access public
		 */
		public function wsn_archive_function() {

			// Getting the product type.
			if ( isset( $_REQUEST['type'] ) ) {
				$action_type = sanitize_text_field( wp_unslash( $_REQUEST['type'] ) );
			}

			if ( ! isset( $_REQUEST['product'] ) ) {
				return;
			}
			// Product id .
			$product_id = intval( $_REQUEST['product'] );

			if ( isset( $_REQUEST['user_id'] ) ) {

				// Get the user's email.
				$user_email = sanitize_email( wp_unslash( $_REQUEST['user_id'] ) );
			}

			if ( '_show' === $action_type ) {

				// Retrieve all user email from archived list.
				$users = wsn_get_archived_users( $product_id );
				wp_die( wp_json_encode( $users ) );

			} elseif ( '_remove' === $action_type ) {

				// Remove user from the archive.
				if ( wsn_remove_form_archive( $user_email, $product_id ) ) {
					wp_die( wp_json_encode( array( 'remove' => true ) ) );
				}
			} elseif ( '_restore' === $action_type ) {

				if ( wsn_register_user( $user_email, $product_id ) ) {

					if ( wsn_remove_form_archive( $user_email, $product_id ) ) {
						wp_die( wp_json_encode( array( 'remove' => true ) ) );
					}
				}
			}
			wp_die();
		}

		/**
		 * Send email ajax
		 *
		 * @param array $actions Woo commerce emails.
		 *
		 * @return array
		 */
		public function kia_ajax_email_action( $actions ) {

			$actions[] = 'send_wsn_email_mailout';

			return $actions;
		}

		/**
		 * Ajax form action for send email.
		 *
		 * @access public
		 */
		public function wsn_waitlist_send_mail_ajax() {

			if ( ! isset( $_REQUEST['product'] ) && ! isset( $_REQUEST['type'] ) || ! isset( $_REQUEST['email'] ) ) {
				wp_die();
			}

			// Get the product ID.
			$product_id = sanitize_text_field( wp_unslash( $_REQUEST['product'] ) );

			// Get the type.
			$type = sanitize_text_field( wp_unslash( $_REQUEST['type'] ) );

			// Is we need to empty the list after email sent?
			$do_empty = get_option( 'remove_after_email' );

			// Load woo commerce mailer class.
			$mailer = WC()->mailer();

			if ( 'all' === $type ) {
				// Get all user from waitlist.
				$users = wsn_get_waitlist( $product_id );
			} else {
				$users = (array) sanitize_email( wp_unslash( $_REQUEST['email'] ) );
			}

			if ( ! empty( $users ) ) {

				// Send email to all wait listed user .
				do_action( 'send_wsn_email_mailout', $users, $product_id );

				// Get the value of the archive setting field.
				$is_archived = get_option( 'archive' );

				// Getting the email of user.
				$user_email = sanitize_email( wp_unslash( $_REQUEST['email'] ) );

				if ( $is_archived ) {

					// Store email into archived after email sent.
					wsn_store_email_into_archive( $user_email, $product_id );
				}
			}

			$response = apply_filters( 'wsn_email_send_response', false );

			// Check response.
			if ( $response ) {

				if ( 'all' === $type ) {
					$msg = '<span class="dashicons dashicons-yes"></span> ' . esc_attr__( 'Message successfully sent to all user', 'in-stock-notifier' );
				} else {
					$msg = '<span class="dashicons dashicons-yes"></span>';
				}
				$send = true;
			} else {
				$msg  = '<span class="dashicons dashicons-no"></span>';
				$send = false;
			}

			if ( $do_empty && $send ) {

				if ( 'all' === $type ) {

					// Remove all user from waitlist.
					wsn_waitlist_empty( $product_id );
				} else {

					// Remove single user from the waitlist.
					wsn_leave_user( $user_email, $product_id );
				}
			}
			// Pass param to js.
			echo wp_json_encode( array(
				'msg'    => $msg,
				'remove' => $do_empty,
				'send'   => $send,
				'id'     => $product_id,
			) );
			wp_die();
		}

		/**
		 * Adding the js file in wp admin.
		 *
		 * @access public
		 */
		public function enqueue_scripts() {

			// Add metabox.js in backend.
			wp_enqueue_script( 'wsn-waitlist-metabox', WSN_ASSEST_PATH . 'js/metabox.js', array( 'jquery' ) );

			// Localize ajax script in backend.
			wp_localize_script( 'wsn_waitlist_meta', 'wsn_waitlist_meta', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
		}

		/**
		 * Remove user from the waitlist.
		 *
		 * @access public
		 * @method remove_user_ajax
		 */
		public function remove_user_ajax() {

			if ( ! isset( $_REQUEST['wp_action'] ) || ! isset( $_REQUEST['wsn_email'] ) || ! isset( $_REQUEST['p_id'] ) ) {
				die;
			}

			if ( check_ajax_referer( 'action_waitlist', 'security' ) ) {

				$action = sanitize_text_field( wp_unslash( $_REQUEST['wp_action'] ) );

				if ( 'leave' === $action ) {

					$email      = sanitize_email( wp_unslash( $_REQUEST['wsn_email'] ) );
					$product_id = intval( $_REQUEST['p_id'] );

					if ( wsn_leave_user( $email, $product_id ) ) {
						echo wp_json_encode( array( 'success' => 'true' ) );
					}
				}
			}
		}

		/**
		 * Generate send email url.
		 *
		 * @param string $email User email id.
		 * @param integer $id Product id.
		 * @param string $type email type single/all.
		 *
		 * @return string return the generated html.
		 */
		public function button_to_send_mail( $email, $id, $type = 'single' ) {

			ob_start();
			?>

            <a class="wsn_waitlist_send_mail_btn short" href="javascript:void(0);"
               data-type="<?php echo esc_html( $type ); ?>" data-user_email="<?php echo $email; ?>"
               data-product_id="<?php echo intval( $id ); ?>"
               title="<?php echo esc_attr__( 'Send Email', 'in-stock-notifier' ); ?>"><?php

			if ( 'single' !== $type ) {
				?><span class="dashicons dashicons-email-alt"></span>
				<?php echo esc_attr__( ' Send Email to all users', 'in-stock-notifier' );
			} else {
				?><span class="dashicons dashicons-email-alt"></span><?php
			}
			?></a><?php

			return ob_get_clean();
		}

		/**
		 * Add wsn_email template to woo commerce.
		 *
		 * @param array $emails pass email object.
		 *
		 * @return mixed
		 */
		public function add_woocommerce_emails( $emails ) {

			// Include the InStockNotifier\Email Class.
			$emails['wsn_email'] = new Email();

			return $emails;
		}

		/**
		 * Store user email into waitlist.
		 */
		public function add_new_user_ajax() {

			if ( ! isset( $_POST['p_id'] ) || ! isset( $_POST['email'] ) || ! isset( $_POST['security'] ) ) {
				die;
			}

			if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['security'] ) ), 'add_new_user_js' ) ) {

				$pid = intval( $_POST['p_id'] );

				$email = sanitize_email( wp_unslash( $_POST['email'] ) );

				if ( ! isset( $_POST['inc'] ) ) {
					die;
				}
				$inc = intval( $_POST['inc'] );

				$inc += 1;

				$remove = '<a data-product_id="' . $pid . '" data-wp_nonce="' . wp_create_nonce( 'action_waitlist' ) . '" data-total="' . ( $inc - 1 ) . '" data-uid="' . $inc . '" data-email="' . $email . '" data-action="leave" href="javascript:void(0);" title="Remove User" class="removeUser"> <span class="dashicons dashicons-no"></span></a>';

				$em = $this->button_to_send_mail( $email, $pid );

				if ( wsn_register_user( $email, $pid ) ) {
					wp_die( wp_json_encode( array(
						'email'      => $email,
						'status'     => 'success',
						'currentId'  => $inc,
						'removeLink' => $remove,
						'emailLink'  => $em,
					) ) );
				} else {
					wp_die( wp_json_encode( array( 'status' => 'exists', ) ) );
				}
			}
		}

		/**
		 * Add admin js to backend.
		 */
		public function add_admin_script() {
			wp_enqueue_script( 'wsn_scripts', wsn_ASSETS_URL . '/js/admin_js.js' );
		}

		/**
		 * Load wc email action .
		 *
		 * @access public
		 */
		public function load_wc_mailer() {
			add_action( 'send_wsn_email_mailout', array( 'WC_Emails', 'send_transactional_email' ), 10, 2 );
		}

		/**
		 * Meta tab in admin panel
		 *
		 * @access public
		 */
		public function wsn_product_tab_callback() {

			global $post;

			$new_user_nonce = wp_create_nonce( 'add_new_user_js' ); ?>

            <div id="wsn_callback" class="panel wc-metaboxes-wrapper woocommerce_options_panel wsn_waitlist_box">
            <div class="options_group"><span class="wrap">

                    <?php

                    // Get product by id.
                    $wsn_product = wc_get_product( $post->ID );

                    // Get the product type.
                    $product_type = $wsn_product->product_type;

                    // Get the parent id.
                    $parent_id = $wsn_product->get_id();

                    // If the product type is variable.
                    if ( 'variable' === $product_type ) {

	                    // Get all variations.
	                    $variations = $wsn_product->get_available_variations();

	                    for ( $i = 0; $i < count( $variations ); $i ++ ) {

		                    $pid               = intval( $variations[ $i ]['variation_id'] );
		                    $variation_product = wc_get_product( $pid );
		                    ?>
                            <div class="woocommerce_variation wc-metabox closed">
                            <h3>
                                <div class="handlediv"
                                     title="<?php esc_attr_e( 'Click to toggle', 'woocommerce' ); ?>"></div>
						        <strong><?php echo sprintf( esc_attr__( 'Waitlist for %s', 'in-stock-notifier' ), $variation_product->get_formatted_name() ); ?> </strong>
					        </h3>

					<div class="wc-metabox-content woocommerce_variable_attributes ">
						<div class="wsn_product_container" id="<?php echo intval( $pid ); ?>">
							<div class="data">

                                <?php
                                $waitlist = wsn_get_waitlist( $pid );
                                ?>
                                <div class="waitlist_data" id="<?php echo intval( $pid ); ?>">
                                <?php
                                if ( ! $variation_product->is_in_stock() ) {
                                ?>
                                    <table id="waitlists<?php echo intval( $pid ); ?>" class="wsn-usertable">
                                        <tr><td class="wsn-user-email-col"><b><?php echo esc_attr__( 'User Email', 'in-stock-notifier' ); ?></b>
                                            <td class="wsn-email-col"><b><?php echo esc_attr__( 'Send Email', 'in-stock-notifier' ); ?></b></td>
                                            <td class="wsn-action-col"><b><?php echo esc_attr__( 'Remove', 'wsn_waitlis' ); ?></b></td>
                                        </tr>
										<?php
										if ( ! empty( $waitlist ) ) {
											?>
                                            <div class="wsn_title">
                                        <?php echo apply_filters( 'wsn_waitlist_introduction', esc_attr__( 'The following users are currently on the waiting list for this product.', 'in-stock-notifier' ) ); ?>
                                    </div>

									<?php

											$inc = 1;

											foreach ( $waitlist as $data ) {

												$product_id          = $variations[ $i ]['variation_id'];
												$total_waitlist_user = count( get_post_meta( $product_id, WSN_USERS_META_KEY, true ) );

												?>
                                                <tr class="old"
                                                    id="row-<?php echo intval( $inc ) . '-' . intval( $pid ); ?>">
										<td><?php echo $data; ?></td>
										<td class="wsn-email-col">
                                            <?php
                                            echo $this->button_to_send_mail( $data, $product_id );
                                            ?>
                                        </td>
                                            <td class="wsn-action-col">
                                                <a data-product_id="<?php echo intval( $product_id ); ?>"
                                                   data-wp_nonce="<?php echo esc_attr( wp_create_nonce( 'action_waitlist' ) ); ?>"
                                                   data-uid="<?php echo intval( $inc ); ?>"
                                                   data-total="<?php echo esc_attr( $total_waitlist_user ); ?>"
                                                   data-email="<?php echo esc_attr( $data ); ?>" data-action="leave"
                                                   href="javascript:void(0);"
                                                   title="<?php echo esc_attr__( 'Remove User', 'in-stock-notifier' ); ?>"
                                                   class="removeUser">
										            <span class="dashicons dashicons-no"></span>
                                                </a>
                                            </td>
                                        </tr>
												<?php
												$inc ++;
											}
										} else {
										?>
                                        <tr class="no_user" id="<?php echo intval( $pid ); ?>">
                                        <td colspan="3" align="center">
                                            <?php echo apply_filters( 'wsn_empty_waitlist_introduction', esc_attr__( 'No one joined wait list', 'in-stock-notifier' ) ); ?>
                                        </td>
											<?php
											}
											?>
                                    </table>
									<?php
									$total_waitlist_user = count( get_post_meta( $pid, WSN_USERS_META_KEY, true ) );

									?>
                                    <p class="add_new_user_form" id="form<?php intval( $pid ); ?>"
                                       style="display:none;">
                                        <input type="text"
                                               placeholder="<?php echo esc_attr__( 'Enter user\'s email address..', 'in-stock-notifier' ); ?>"
                                               class="usrEmail" id="<?php echo intval( $pid ); ?>"
                                               placeholder="<?php echo esc_attr__( 'Enter user\'s email...', 'in-stock-notifier' ); ?>"
                                               name="usr_email" class="short"/>
                                        <button id="wsn_add_btn" data-nonce="<?php echo $new_user_nonce; ?>"
                                                data-parent_id="<?php echo intval( $parent_id ); ?>"
                                                data-product_id="<?php echo intval( $pid ); ?>"
                                                data-total="<?php echo $total_waitlist_user; ?>" name="wsn_add_btn"
                                                class="button button-primary add_user_btn">

                                            <?php echo esc_attr( 'Add User', 'in-stock-notifier' ); ?>
                                        </button>
                                    </p>
                                    <a href="javascript:void(0);" id="wsn_add_new_user"
                                       data-nonce="<?php echo esc_attr( $new_user_nonce ); ?>"
                                       data-product_id="<?php echo intval( $pid ); ?>"><?php echo esc_attr__( 'Add new user', 'in-stock-notifier' ); ?></a>
									<?php
									if ( $waitlist ) {
										?><span><a href="javascript:void(0);">
                                            <?php echo $this->button_to_send_mail( $data, $product_id, 'all' ); ?>
                                    </a> &nbsp; &nbsp;</span><?php
									}

									?>
                                    <a href="javascript:void(0);" id="show_archived"
                                       data-product_id="<?php echo intval( $pid ); ?>"> <span
                                                class="dashicons dashicons-editor-alignleft"></span> <?php echo esc_attr__( 'View Archived Users', 'in-stock-notifier' ); ?></a></div>
                                <div class="archived_data_panel" id="<?php echo intval( $pid ); ?>">
									<a class="close_archived" id="<?php echo intval( $pid ); ?>"
                                       href="javascript:void(0);"><span class="dashicons dashicons-dismiss"></span></a>
									<div class="archive_container">
										<div class="archived_head_text"><?php echo esc_attr__( 'Archived Wait List', 'in-stock-notifier' ); ?></div>

										<div class="archived_data" id="<?php echo intval( $pid ) ?>">
											<table class="_archive_userlist" id="table_<?php echo intval( $pid ) ?>">

											</table>
										</div>
									</div>
	                                <?php }
	                                ?>
								</div>
							</div>
						</div>
					</div></div><?php
	                    }
                    } elseif ( 'simple' === $product_type ) {

	                    $pid = intval( $wsn_product->get_id() );

	                    ?>

                        <div class=" woocommerce_variable_attributes wc-metaboxes-wrapper">

					<div class="data">

						<?php if ( ! $wsn_product->is_in_stock() ) { ?>

                            <div class="waitlist_data" id="<?php echo intval( $pid ); ?>">

									<div class=" wc-metabox wc-metabox-content">

									<?php
									$waitlist = wsn_get_waitlist( $pid );
									if ( ! empty( $waitlist ) ) {
										?>
                                        <div class="wsn_title">
                                            <?php echo apply_filters( 'wsn_waitlist_introduction', esc_attr__( 'The following users are currently on the waiting list for this product.', 'in-stock-notifier' ) ); ?>
                                        </div>
										<?php
									}
									?>
                                        <table id="waitlists<?php echo intval( $pid ); ?>" class="wsn-usertable">
                                            <tr>
                                                <td class="wsn-user-email-col">
                                                    <b><?php echo esc_attr__( 'User Email', 'in-stock-notifier' ); ?> </b>
                                                <td class="wsn-email-col">
                                                    <b><?php echo esc_attr__( 'Send Email', 'in-stock-notifier' ); ?></b>
                                                </td>
                                                <td class="wsn-action-col">
                                                    <b><?php echo esc_attr__( 'Remove', 'wsn_waitlis' ); ?></b>
                                                </td>
                                            </tr>
											<?php

											if ( ! empty( $waitlist ) ) {

												$inc = 1;

												foreach ( $waitlist as $data ) {

													$total_waitlist_user = count( get_post_meta( $pid, WSN_USERS_META_KEY, true ) );

													?>
                                                <tr class="old"
                                                    id="row-<?php echo intval( $inc ) . '-' . intval( $pid ); ?>">
                                                    <td>
                                                    <?php echo $data; ?>
                                                </td>
                                                <td class="wsn-email-col">
                                                    <?php echo $this->button_to_send_mail( $data, $pid ); ?>
											    </td>
											    <td class="wsn-action-col">
                                                    <a data-product_id="<?php echo intval( $pid ); ?>"
                                                       data-wp_nonce="<?php echo wp_create_nonce( 'action_waitlist' ); ?>"
                                                       data-uid="<?php echo intval( $inc ); ?>"
                                                       data-total="<?php echo $total_waitlist_user; ?>"
                                                       data-email="<?php $data; ?>" data-action="leave"
                                                       href="javascript:void(0);"
                                                       title="<?php echo __( 'Remove User', 'wsn_waitilist' ); ?>"
                                                       class="removeUser"><span
                                                                class="dashicons dashicons-no"></span></a>
                                                </td>
                                                    </tr><?php
													$inc ++;
												}
											} else {
												?>
                                            <tr class="no_user" id="<?php echo intval( $pid ); ?>">
                                                <td colspan="3" align="center">
												<?php echo apply_filters( 'wsn_empty_waitlist_introduction', esc_attr__( 'No one joined wait list', 'in-stock-notifier' ) ); ?>
                                                </td><?php
											}
											?></table><?php
										$total_waitlist_user = count( get_post_meta( $pid, WSN_USERS_META_KEY, true ) );
										?>
                                        <p class="add_new_user_form" id="form<?php echo intval( $pid ); ?>"
                                           style="display:none;">
                                            <input type="text"
                                                   placeholder="<?php echo esc_attr__( 'Enter user\'s email address..', 'in-stock-notifier' ); ?>"
                                                   class="usrEmail" id="<?php echo intval( $pid ); ?>"
                                                   placeholder="<?php echo esc_attr__( 'Enter user\'s email...', 'in-stock-notifier' ); ?> "
                                                   name="usr_email" class="short"/>
                                            <button id="wsn_add_btn" data-nonce="<?php echo $new_user_nonce; ?>"
                                                    data-product_id="<?php echo intval( $pid ); ?>"
                                                    data-total="<?php echo $total_waitlist_user; ?>" name="wsn_add_btn"
                                                    class="button button-primary">
                                                <?php echo esc_attr__( 'Add User', 'in-stock-notifier' ); ?>
                                            </button>
                                        </p>
                                        <a href="javascript:void(0);" data-nonce="<?php echo $new_user_nonce; ?>"
                                           id="wsn_add_new_user" data-product_id="<?php echo intval( $pid ); ?>"><span
                                                    class="dashicons dashicons-admin-users"></span> <?php echo esc_attr__( 'Add new user', 'in-stock-notifier' ); ?></a>
										<?php

										if ( $waitlist ) {
											?>
                                            <span>
                                                <a href="javascript:void(0);">
                                                    <?php echo $this->button_to_send_mail( $data, intval( $pid ), 'all' ); ?>
                                                </a> &nbsp; &nbsp;
                                            </span>
											<?php
										}
										?>
                                        <a href="javascript:void(0);" id="show_archived"
                                           data-product_id="<?php echo intval( $pid ); ?>"><span
                                                    class="dashicons dashicons-editor-alignleft"></span> <?php echo esc_attr__( 'View Archived Users', 'in-stock-notifier' ); ?></a><?php
										?>
								</div>
							</div>
                        <div class="archived_data_panel  wc-metabox wc-metabox-content"
                             id="<?php echo intval( $pid ) ?>">
                            <a class="close_archived" id="<?php echo intval( $pid ) ?>" href="javascript:void(0);">
                                <span class="dashicons dashicons-no"></span>
                            </a>
							<div class="archive_container">
								<div class="archived_head_text"><?php echo esc_attr__( 'Archived Wait List', 'in-stock-notifier' ); ?></div>

								<div class="archived_data" id="<?php echo intval( $pid ) ?>">
									<table class="_archive_userlist" id="table_<?php echo intval( $pid ) ?>">

									</table>
								</div>
							</div></div><?php
						} elseif ( 'auto-draft' === $post->post_status ) {
							echo esc_attr__( 'Product is not published yet.', 'in-stock-notifier' );
						} else {
							echo esc_attr__( 'Product is already available for sale ', 'in-stock-notifier' );
						}
						?></div>
				</div>
                    <?php }
                    ?>
            </div></div><?php
		}
	}
}
