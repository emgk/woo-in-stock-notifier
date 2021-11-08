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

			// Add Ajax form for add new user in waitlist.
			add_action( 'wp_ajax_addNewUser', array( $this, 'add_new_user_ajax' ) );

			// Add ajax form for remove user from the waitlist.
			add_action( 'wp_ajax_removeUser', array( $this, 'remove_user_ajax' ) );

			// Add ajax email sent action form.
			add_action( 'wp_ajax_wsn_waitlist_send_mail', array( $this, 'wsn_waitlist_send_mail_ajax' ) );

			// Ajax action form for retrieve all archived user.
			add_action( 'wp_ajax_archive_function', array( $this, 'wsn_archive_function' ) );

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
				'product', // Post type.
				'normal', 'high', // Priority.
				null );
		}

		/**
		 * Get all the user email from the archived
		 *
		 * @access public
		 */
		public function wsn_archive_function() {

			if ( ! isset( $_REQUEST['product'], $_REQUEST['type'], $_REQUEST['user_id'] ) ) {
				return;
			}

			// Getting the product type.
			$action_type = sanitize_text_field( wp_unslash( $_REQUEST['type'] ) );

			// Product id .
			$product_id = absint( $_REQUEST['product'] );

			// Get the user's email.
			$user_email = sanitize_email( wp_unslash( $_REQUEST['user_id'] ) );

			switch ( $action_type ) {
				case '_remove' :
					// Remove user from the archive.
					if ( wsn_remove_form_archive( $user_email, $product_id ) ) {
						wp_die( wp_json_encode( array( 'remove' => true ) ) );
					}
					break;
				case '_restore':
					// Restore user email archive
					if ( wsn_register_user( $user_email, $product_id ) && wsn_remove_form_archive( $user_email, $product_id ) ) {
						wp_die( wp_json_encode( array( 'remove' => true ) ) );
					}
					break;
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

			if ( ! isset( $_REQUEST['product'] ) && ! isset( $_REQUEST['type'] ) ) {
				wp_die();
			}

			// Get the product ID.
			$product_id = sanitize_text_field( wp_unslash( $_REQUEST['product'] ) );

			// Get the type.
			$type = sanitize_text_field( wp_unslash( $_REQUEST['type'] ) );

			if ( 'all' !== $type && ! isset( $_REQUEST['email'] ) ) {
				wp_die();
			}

			// Is we need to empty the list after email sent?
			$do_empty = get_option( 'remove_after_email' );

			$is_archived = false;

			// Load woo commerce mailer class.
			WC()->mailer();

			if ( 'all' === $type ) {
				// Get all user from waitlist.
				$users = wsn_get_waitlist( $product_id );
			} else {
				$users = (array) sanitize_email( wp_unslash( $_REQUEST['email'] ) );
			}

			if ( ! empty( $users ) ) {

				/**
				 * Send email to all wait listed user.
				 *
				 * @param {array|email} $users
				 */
				do_action( 'send_wsn_email_mailout', $users, $product_id );

				// Get the value of the archive setting field.
				$is_archived = get_option( 'archive' );

				if ( $is_archived ) {
					foreach ( $users as $user_email ) {
						// remove user from the list after archive
						wsn_leave_user( $user_email, $product_id );

						// Store email into archived after email sent.
						if ( ! empty( $user_email ) ) {
							wsn_store_email_into_archive( $user_email, $product_id );
						}
					}
				}
			}

			$response = apply_filters( 'wsn_email_send_response', false );


			switch ( $type ) {
				case 'all':
					ob_start();
					if ( $response ) {
						$this->render_notice( __( 'Email sent!', 'in-stock-notifier' ), __( 'Email successfully sent to all users.', 'in-stock-notifier' ), 'dashicons-yes' );
					} else {
						$this->render_notice( __( 'Failed!', 'in-stock-notifier' ), __( 'Failed to send email to all users.', 'in-stock-notifier' ), 'dashicons-no' );
					}
					$msg = ob_get_clean();
					break;
				default:
					if ( $response ) {
						$msg = '<span class="dashicons dashicons-yes"></span>';
					} else {
						$msg = '<span class="dashicons dashicons-no"></span>';
					}
			}

			$send = (bool) $response;

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
				'msg'      => $msg,
				'remove'   => (int) $do_empty,
				'archived' => (int) $is_archived,
				'send'     => $send,
				'id'       => $product_id,
			) );
			wp_die();
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

			wp_die();
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
			$emails['wsn_email'] = new WSN_Email();

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

					// remove user from the archive list
					wsn_remove_form_archive( $email, $pid );

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
		 * Load wc email action .
		 *
		 * @access public
		 */
		public function load_wc_mailer() {
			add_action( 'send_wsn_email_mailout', array( 'WC_Emails', 'send_transactional_email' ), 10, 2 );
		}

		/**
		 * list out all users in table view
		 *
		 * @param integer $pid Product ID
		 * @param \WC_Product $product Product object
		 */
		public function list_users_for_product( $pid, $product ) {

			// get the list of users
			$waitlist = wsn_get_waitlist( $pid );
			?>
            <div id="wsn-users-tab" class="wsn-tabs__content wsn-tabs__content--current">
                <div class="wsn-tab-section">
                    <div class="wsn-tab-section__header">
                        <h3><?php echo __( 'Users waitlist', 'in-stock-notifier' ); ?></h3>
						<?php
						echo print_r( $waitlist, true );
						?>
						<?php if ( ! empty( $waitlist ) ) { ?>
                            <div class="wsn-tab-section-desc">
								<?php echo apply_filters( 'wsn_waitlist_introduction', esc_attr__( 'The following users are currently on the waiting list for this product.', 'in-stock-notifier' ) ); ?>
                            </div>
						<?php } ?>
                    </div>
                    <div class="wsn-tab-section__body" id="waitlists<?php echo intval( $pid ); ?>">
                        <div class="wsn-tab-table">
                            <div class="wsn-tab-table-header">
                                <div class="wsn-tab-table-list-col"><?php echo __( 'Email', 'in-stock-notifier' ); ?></div>
                                <div class="wsn-tab-table-list-col"><?php echo __( 'Action', 'in-stock-notifier' ); ?></div>
                            </div>
                            <div class="wsn-tab-table-body">
								<?php
								$inc = 1;
								if ( ! empty( $waitlist ) ) {

									foreach ( $waitlist as $data ) {

										$total_waitlist_user = count( get_post_meta( $pid, WSN_USERS_META_KEY, true ) );
										?>
                                        <div class="wsn-tab-table-item"
                                             id="row-<?php echo intval( $inc ) . '-' . intval( $pid ); ?>">
                                            <div class="wsn-tab-table-item-col">
												<?php echo $data; ?>
                                            </div>
                                            <div class="wsn-tab-table-item-col">
                                                <div class="wsn-tab-table-item-col-actions">
                                                    <div class="wsn-tab-table-item-col-action">
														<?php echo $this->button_to_send_mail( $data, $pid ); ?>
                                                    </div>
                                                    <div class="wsn-tab-table-item-col-action">
                                                        <a data-product_id="<?php echo intval( $pid ); ?>"
                                                           data-wp_nonce="<?php echo wp_create_nonce( 'action_waitlist' ); ?>"
                                                           data-uid="<?php echo intval( $inc ); ?>"
                                                           data-total="<?php echo $total_waitlist_user; ?>"
                                                           data-email="<?php echo esc_attr( $data ); ?>"
                                                           data-action="leave"
                                                           href="javascript:void(0);"
                                                           title="<?php echo __( 'Remove User', 'in-stock-notifier' ); ?>"
                                                           class="removeUser"><span
                                                                    class="dashicons dashicons-no"></span></a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
										<?php
									}
								}
								?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
			<?php
		}

		public function waitlist_box( $pid, $product ) {
			$new_user_nonce = wp_create_nonce( 'add_new_user_js' );
			$waitlist       = wsn_get_waitlist( $pid );

			$total_waitlist_user = count( get_post_meta( $pid, WSN_USERS_META_KEY, true ) );

			?>
            <div class="wsn-wrapper" id="<?php echo intval( $pid ); ?>">
                <div class="wsn-splash" id="<?php echo 'wsn-add-user-' . $pid; ?>">
                    <div class="wsn-splash__overlay"></div>
                    <div class="wsn-splash__inner">
                        <div class="wsn-splash__body">
                            <div class="wsn-form">
                                <h5><label
                                            for="<?php echo "user-email-field-$pid"; ?>"><?php echo __( 'Add new user', 'in-stock-notifier' ); ?></label>
                                </h5>
                                <div class="wsn-form-field">
                                    <input type="text" class="wsn-input-field"
                                           id="<?php echo "user-email-field-$pid"; ?>"
                                           placeholder="<?php echo __( 'Enter email address', 'in-stock-notifier' ); ?>"/>
                                </div>
                                <div class="wsn-form-field">
                                    <button id="wsn_add_btn" data-nonce="<?php echo $new_user_nonce; ?>"
                                            data-product_id="<?php echo intval( $pid ); ?>"
                                            data-total="<?php echo $total_waitlist_user; ?>" name="wsn_add_btn"
                                            class="button button-primary">
										<?php echo esc_attr__( 'Add User', 'in-stock-notifier' ); ?>
                                    </button>
                                    <a
                                            href="javascript:void(0);"
                                            id="wsn_hide_add_new_user"
                                            data-nonce="<?php echo esc_attr( $new_user_nonce ); ?>"
                                            data-product_id="<?php echo intval( $pid ); ?>"
                                    >
										<?php echo esc_attr__( 'Cancel', 'in-stock-notifier' ); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="wsn-tabs" id="<?php echo 'wsn-add-tabs-' . $pid; ?>">
                    <div class="wsn-tabs__inner">
                        <div class="wsn-tabs__header">
                            <div class="wsn-tabs__menu">
                                <ul class="wsn-tabs-nav" id="<?php echo "wsn-tabs-nav-$pid"; ?>">
                                    <li
                                            class="wsn-tabs-nav-item wsn-tabs-nav-item--current"
                                            data-tab="<?php echo "wsn-users-tab-$pid"; ?>"
                                            data-type="users"
                                    >
										<?php echo __( 'Users', 'in-stock-notifier' ); ?>
                                    </li>
                                    <li
                                            class="wsn-tabs-nav-item"
                                            data-tab="<?php echo "wsn-archived-tab-$pid"; ?>"
                                            data-type="archived"
                                    >
										<?php echo __( 'Archived users', 'in-stock-notifier' ); ?>
                                    </li>
                                </ul>
                            </div>
                            <div class="wsn-tabs__action">
                                <div class="wsn-tabs__action-item">
                                    <a
                                            href="javascript:void(0);"
                                            id="wsn_add_new_user"
                                            data-nonce="<?php echo esc_attr( $new_user_nonce ); ?>"
                                            data-product_id="<?php echo intval( $pid ); ?>"
                                    >
                                        <i class="dashicons dashicons-admin-users"></i>
										<?php echo esc_attr__( 'Add new user', 'in-stock-notifier' ); ?>
                                    </a>
                                </div>
                                <div class="wsn-tabs__action-item">
                                    <a
                                            class="wsn-send-email-all-users"
                                            href="javascript:void(0);"
                                            data-type="all"
                                            data-product_id="<?php echo intval( $pid ); ?>"
                                    >
                                        <i class="dashicons dashicons-email-alt" aria-hidden="true"></i>
										<?php echo __( 'Send email to all users', 'in-stock-notifier' ); ?>
                                    </a>
                                </div>
                                <div class="wsn-tabs__action-item">
                                    <a
                                            href="https://wordpress.org/support/plugin/woo-in-stock-notifier/reviews/"
                                            target="_blank"
                                    >
                                        <i class="dashicons dashicons-star-half" aria-hidden="true"></i>
										<?php echo __( 'Feedback', 'in-stock-notifier' ); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="wsn-tabs__body">
                            <div id="<?php echo "wsn-users-tab-$pid"; ?>"
                                 class="wsn-tabs__content wsn-tabs__content--current">
                                <div class="wsn-tab-section">
                                    <div class="wsn-tab-section__header">
                                        <h5><?php echo __( 'Users waitlist', 'in-stock-notifier' ); ?></h5>
                                        <div class="wsn-tab-section-desc">
											<?php echo apply_filters( 'wsn_waitlist_introduction', esc_attr__( 'The following users are currently on the waiting list for this product.', 'in-stock-notifier' ) ); ?>
                                        </div>
                                    </div>
                                    <div
                                            class="wsn-tab-section__body"
                                            id="waitlists-<?php echo intval( $pid ); ?>"
                                    >
                                        <div class="wsn-tab-table" id="<?php echo "wsn-tab-table-$pid"; ?>">
                                            <div class="wsn-tab-table-header">
                                                <div
                                                        class="wsn-tab-table-list-col"><?php echo __( 'Email', 'in-stock-notifier' ); ?></div>
                                                <div
                                                        class="wsn-tab-table-list-col"><?php echo __( 'Action', 'in-stock-notifier' ); ?></div>
                                            </div>
                                            <div class="wsn-tab-table-body">
												<?php if ( ! empty( $waitlist ) ) {
													foreach ( $waitlist as $key => $data ) {
														// get the total user waitlist
														$total_waitlist_user = count( get_post_meta( $pid, WSN_USERS_META_KEY, true ) );
														?>
                                                        <div class="wsn-tab-table-item"
                                                             id="row-<?php echo absint( $key ) . '-' . absint( $pid ); ?>">
                                                            <div class="wsn-tab-table-item-col">
																<?php echo $data; ?>
                                                            </div>
                                                            <div class="wsn-tab-table-item-col">
                                                                <div class="wsn-tab-table-item-col-actions">
                                                                    <div class="wsn-tab-table-item-col-action">
																		<?php echo $this->button_to_send_mail( $data, $pid ); ?>
                                                                    </div>
                                                                    <div class="wsn-tab-table-item-col-action">
                                                                        <a data-product_id="<?php echo absint( $pid ); ?>"
                                                                           data-wp_nonce="<?php echo wp_create_nonce( 'action_waitlist' ); ?>"
                                                                           data-uid="<?php echo absint( $key ); ?>"
                                                                           data-total="<?php echo $total_waitlist_user; ?>"
                                                                           data-email="<?php echo esc_attr( $data ); ?>"
                                                                           data-action="leave"
                                                                           href="javascript:void(0);"
                                                                           title="<?php echo __( 'Remove User', 'in-stock-notifier' ); ?>"
                                                                           class="removeUser"><span
                                                                                    class="dashicons dashicons-no"></span></a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
													<?php }
												}

												$this->render_notice(
													__( 'No users', 'in-stock-notifier' ),
													apply_filters( 'wsn_waitlist_no_users', __( 'Currently there are no users waiting for this product.<br/>Click on "Add new user" to add new user manually.', 'in-stock-notifier' ) ),
													'dashicons-warning',
													! empty( $waitlist )
												);
												?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div id="<?php echo "wsn-archived-tab-$pid"; ?>" class="wsn-tabs__content wsn-archived-list"
                                 data-productid="<?php echo intval( $pid ); ?>">
                                <div class="wsn-tab-section">
                                    <div class="wsn-tab-section__header">
                                        <h5>Archived Wait List</h5>
                                        <div class="wsn-tab-section-desc">
											<?php echo apply_filters( 'wsn_waitlist_introduction', esc_attr__( 'The following users are archived after sending the email.', 'in-stock-notifier' ) ); ?>
                                        </div>
                                    </div>
                                    <div class="wsn-tab-section__body">
                                        <div class="wsn-tab-table">
                                            <div class="wsn-tab-table-header">
                                                <div class="wsn-tab-table-list-col"><?php echo __( 'Email', 'in-stock-notifier' ); ?></div>
                                                <div class="wsn-tab-table-list-col"><?php echo __( 'Action', 'in-stock-notifier' ); ?></div>
                                            </div>
                                            <div class="wsn-tab-table-body">
												<?php
												// get archived users
												$archived_users = wsn_get_archived_users( $pid );

												$inc = 1;
												if ( ! empty( $archived_users ) ) {
													foreach ( $archived_users as $data ) {
														?>
                                                        <div class="wsn-tab-table-item"
                                                             id="row-<?php echo intval( $inc ) . '-' . intval( $pid ); ?>">
                                                            <div class="wsn-tab-table-item-col">
																<?php echo $data; ?>
                                                            </div>
                                                            <div class="wsn-tab-table-item-col">
                                                                <div class="wsn-tab-table-item-col-actions">
                                                                    <div class="wsn-tab-table-item-col-action">
                                                                        <a href="javascript:void( 0 );"
                                                                           class="restoreEmail"
                                                                           data-uid="<?php echo $data; ?>"
                                                                           data-pid="<?php echo intval( $pid ); ?>"><span
                                                                                    class="dashicons dashicons-image-rotate"></span></a>
                                                                    </div>
                                                                    <div class="wsn-tab-table-item-col-action">
                                                                        <a href="javascript:void( 0 );"
                                                                           class="removeArchivedUser"
                                                                           data-uid="<?php echo $data; ?>"
                                                                           data-pid="<?php echo intval( $pid ); ?>"><span
                                                                                    class="dashicons dashicons-no"></span></a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
														<?php
													}
												}

												$this->render_notice(
													__( 'No archived users', 'in-stock-notifier' ),
													apply_filters( 'wsn_waitlist_no_archived_users', __( 'User will be added to archived list once the email is sent. <br/> To enable this feature go to "WooCommerce > In-Stock Notifier".', 'in-stock-notifier' ) ),
													'dashicons-warning',
													! empty( $archived_users )
												);
												?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
			<?php
		}

		/**
		 * Render notice
		 *
		 * @param string $title Title
		 * @param string $desc Description
		 * @param string $icon Notice Icon
		 * @param bool $is_hidden is hidden
		 */
		public function render_notice( $title = '', $desc = '', $icon = 'dashicons-warning', $is_hidden = false ) {
			?>
            <div class="wsn-notice<?php echo $is_hidden ? ' wsn-hidden' : ''; ?>">
                <div class="wsn-notice__inner">
                    <div class="wsn-notice__icon">
                        <span><i class="dashicons <?php echo esc_attr( $icon ); ?>" aria-hidden="true"></i></span>
                    </div>
                    <div class="wsn-notice__main">
                        <h5><?php echo esc_attr( $title ); ?></h5>
                        <div class="wsn-notice-desc">
							<?php echo $desc; ?>
                        </div>
                    </div>
                </div>
            </div>
			<?php
		}

		/**
		 * Meta tab in admin panel
		 *
		 * @access public
		 */
		public function wsn_product_tab_callback() {
			global $post;

			/** @var \WC_Product $wsn_product */
			$wsn_product = wc_get_product( $post->ID );

			// Get the product type.
			$product_type = $wsn_product->product_type;

			$pid = intval( $wsn_product->get_id() );

			// Product isn't live yet
			if ( 'auto-draft' === $post->post_status ) {
				$this->render_notice( __( 'Not published' ), __( 'Product is not published yet.', 'in-stock-notifier' ) );

				return;
			}

			switch ( $product_type ) {
				case 'simple':
					if ( $wsn_product->is_in_stock() ) {
						$this->render_notice( __( 'In-stock' ), __( 'Product is already available for sale.', 'in-stock-notifier' ), 'dashicons-smiley' );
					} else {
						$this->waitlist_box( $pid, $wsn_product );
					}
					break;
				case 'variable';
					// Get all variations.
					$variations = $wsn_product->get_available_variations();

					for ( $i = 0; $i < count( $variations ); $i ++ ) {
						$pid = intval( $variations[ $i ]['variation_id'] );

						/** @var \WC_Product $variation_product */
						$variation_product = wc_get_product( $pid );
						?>
                        <div id="wsn_callback" class="wc-metaboxes-wrapper wsn-product-variation-head">
                            <div class="woocommerce_variation wc-metabox closed">
                                <h3>
                                    <div
                                            class="handlediv"
                                            title="<?php esc_attr_e( 'Click to toggle', 'woocommerce' ); ?>"
                                    ></div>
                                    <strong><?php echo sprintf( esc_attr__( 'Waitlist for %s', 'in-stock-notifier' ), $variation_product->get_formatted_name() ); ?></strong>
                                </h3>

                                <div class="wc-metabox-content woocommerce_variable_attributes ">
                                    <div class="wsn_product_container" id="<?php echo intval( $pid ); ?>">
                                        <div class="waitlist_data" id="<?php echo intval( $pid ); ?>">
											<?php
											if ( $variation_product->is_in_stock() ) {
												$this->render_notice( __( 'In-stock' ), __( 'Product is already available for sale.', 'in-stock-notifier' ), 'dashicons-smiley' );
											} else {
												$this->waitlist_box( $pid, $variation_product );
											}
											?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
						<?php
					}
					break;
			}
		}
	}
}
