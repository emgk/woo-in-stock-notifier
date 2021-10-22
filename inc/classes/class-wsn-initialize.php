<?php
/**
 * InStockNotifier
 *
 * @author Govind Kumar
 * @version 1.0.0
 * @package InStockNotifier/Classes
 */

namespace InStockNotifier;

use WC_Product;
use WC_Product_Variation;
use WP_Post;

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

if ( ! class_exists( 'WSN_Initialize' ) ) {

	/**
	 * Class InStockNotifier/WSN_Initialize
	 *
	 * This class set up the backend for the plugin
	 *
	 * @since 1.0.0
	 * @author Govind Kumar
	 */
	class WSN_Initialize {

		/**
		 * WSN_Initialize constructor.
		 *
		 * @author Govind Kumar
		 * @access public
		 */
		public function __construct() {

			// Add plugin setting menu in back end .
			add_action( 'admin_menu', array( __CLASS__, 'in_stock_submenu' ) );

			// Add the waitlist user clumn in manage product page.
			add_filter( 'manage_edit-product_columns', array( $this, 'instockalert_add_column' ) );

			// Enqueue Scripts for admin.
			add_action( 'admin_enqueue_scripts', array( $this, 'instockalert_product_setup' ) );

			// This function will fire when any product stock status change.
			add_action( 'admin_init', array( $this, 'send_in_stock_email' ) );

			// Update the number of users in waitlist.
			add_action( 'admin_init', array( $this, 'wsn_recount_num' ) );

			// Make waitlist user column sortable.
			add_filter( 'manage_edit-product_sortable_columns', array( $this, 'wsn_sortable_tab' ) );

			// Enqueue scripts of plugin.
			add_action( 'wp_enqueue_scripts', array( $this, 'wsn_enqueue_assets' ) );

			// Register plugin setting fields.
			add_action( 'init', array( $this, 'wsn_register_settings' ) );

			// Adding the data inside the waitlist column in manage product page.
			add_action( 'manage_product_posts_custom_column', array( $this, 'wsn_col_func' ), 10, 1 );

			add_action( 'request', array( $this, 'sort_column_by_waitlist' ) );
		}

		/**
		 * Set meta key to sort the waitlist column.
		 *
		 * @param array $vars Pass the query args.
		 *
		 * @return array
		 */
		public function sort_column_by_waitlist( $vars ) {

			if (
				isset( $vars['orderby'] )
				&& 'wsn_waitlist' === $vars['orderby']
			) {

				$vars = array_merge( $vars, array(
					'meta_key' => 'total_num_waitlist',
					'orderby'  => 'meta_value_num',
				) );
			}

			return $vars;
		}

		/**
		 * Passing the html to add column in manage product page.
		 *
		 * @param array $columns Pass all product column.
		 *
		 * @return array
		 */
		public function instockalert_add_column( $columns ) {

			$waitlist_col = array(
				'wsn_waitlist' => sprintf(
					'<span class="parent-tips" data-tip="%s$1"><i class="fa fa-clock-o"></i></span>',
					__( 'Waitlist', 'tmsm-woocommerce-stocknotifier' )
				),
			);

			return wp_parse_args( $waitlist_col, $columns );
		}

		/**
		 * Make Waitlist column sortable
		 *
		 * @param array $columns Product column.
		 *
		 * @return mixed
		 */
		public function wsn_sortable_tab( $columns ) {

			// Add column to product list table.
			$columns['wsn_waitlist'] = 'wsn_waitlist';

			return $columns;
		}


		/**
		 * Get the number of the user in waitlist for product and show inside the waitlist column.
		 *
		 * @param array $column pass column.
		 */
		public function wsn_col_func( $column ) {

			global $post;

			if ( 'wsn_waitlist' === $column ) {
				echo 0 === wsn_total_waitlist( intval( $post->ID ) ) ? '-' : absint( wsn_total_waitlist( intval( $post->ID ) ) );
			}
		}

		/**
		 * Update the number of waitlist.
		 *
		 * @access public
		 */
		public function wsn_recount_num() {
			global $wpdb;

			// Get the current user ID.
			$userid = get_current_user_id();

			// Get the user data.
			$user = get_userdata( $userid );

			// Get the recount num from wp cache.
			$products = wp_cache_get( $user->ID, 'wsn_recount_num' );

			// If cache is empty.
			if ( ! $products ) {

				// Fetching row from the sql query.
				$products = $wpdb->get_results(
					$wpdb->prepare( "SELECT * FROM $wpdb->posts WHERE post_type='%s' OR post_type='%s' AND post_status='%s' ", 'product_variation', 'product', 'publish' ) );

				// Store result to the wp cache.
				wp_cache_add( $user->ID, 'wsn_recount_num' );
			}

			foreach ( $products as $key => $row ) {

				if ( 0 === $row->post_parent ) {

					// Get the product id .
					$product_id = $row->ID;

					// Get list of waitlist.
					$array_waitlist = wsn_get_waitlist( $product_id );

					// Get the number of waiting list.
					$waitlist_num = is_array( $array_waitlist ) ? count( array_filter( $array_waitlist ) ) : 0;

					// Update the number of waitlist of particular product.
					wsn_udpate_num( $waitlist_num, $product_id );

				} elseif ( 0 !== $row->post_parent ) {
					if ( 'product_variation' === $row->post_type ) {

						/** @var array|int $array_waitlist Get the waiting list in array if available */
						$array_waitlist = wsn_get_waitlist( $row->ID );

						// Add the number of waitlist to previous number.
						$total_waitlist_num = is_array( $array_waitlist ) ? count( $array_waitlist ) : 0;

						// Update waitlist number.
						wsn_udpate_num( $total_waitlist_num, $row->post_parent );
					}
				}
			}
		}

		/**
		 * This is function will automatic fire when product stock status change.
		 *
		 * @method send_in_stock_email
		 * @access public
		 */
		public function send_in_stock_email() {
			global $wpdb;

			// Getting the filter value of the auto mail sending.
			$stop_auto_sending = apply_filters( 'wsn_automatic_mailouts_are_disabled', false );

			if ( ! $stop_auto_sending ) {

				// Fetching row from the sql query.
				$products = $wpdb->get_results( $wpdb->prepare( "SELECT post_id,meta_value FROM $wpdb->postmeta WHERE meta_key = '%s' ", WSN_USERS_META_KEY ) );

				foreach ( $products as $key => $row ) {

					$p_id = $row->post_id;

					/** @var WP_Post $post */
					$post = get_post( $p_id );

					if ( empty( get_post( $p_id ) ) || ! in_array( $post->post_type, array(
							'product',
							'product_variation'
						), true ) ) {
						continue;
					}

					if ( 'product_variation' === $post->post_type ) {
						/** @var WC_Product_Variation $variation product */
						$product = new WC_Product_Variation( $p_id );
					} else {
						$product = new WC_Product( $p_id );
					}

					// Check if product is in stock.
					if ( $product->is_in_stock() ) {

						// Get the all user from waitlist.
						$waitlist = wsn_get_waitlist( $p_id );

						if ( ! empty( $waitlist ) ) {

							// Load woo commerce mailer function.
							$mailer = WC()->mailer();

							// Send the email to all waitlist user .
							do_action( 'send_wsn_email_mailout', $waitlist, $p_id );

							// Fetch the value of archive option.
							$is_archive = get_option( 'archive', true );

							// Check if option in true or not.
							if ( $is_archive ) {

								foreach ( $waitlist as $arc ) {
									// Store email to archive.
									wsn_store_email_into_archive( $arc, $p_id );
								}
							}
							$response = apply_filters( 'wsn_email_send_response', false );
							$do_not_remove = apply_filters( 'wsn_persistent_waitlists_are_disabled', false );

							if ( $response ) {
								if ( ! $do_not_remove ) {

									// Remove all user from waitlist.
									wsn_waitlist_empty( $p_id );
								}
							}
						}
					}
				}
			}
		}

		/**
		 * Adding the js and css into the backend.
		 *
		 * @method instockalert_product_setup
		 * @access public
		 */
		public function instockalert_product_setup() {

			// Registering the admin js scrip.
			wp_enqueue_script( 'wsn_admin_scripts', WSN_ASSEST_PATH . 'js/admin.min.js' );
			wp_enqueue_style( 'wsn_admin_styles', WSN_ASSEST_PATH . 'css/admin.min.css' );

			// Localize the ajax url for form submit.
			wp_localize_script( 'jquery', '_wsn_waitlist', array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
			) );
		}

		/**
		 *
		 * Register the js and css to front end.
		 *
		 * @method wsn_enqueue_assets
		 * @access public
		 */
		public function wsn_enqueue_assets() {

			// Enqueuing built-in jquery .
			wp_enqueue_script( 'jquery' );

			// Add the plugin style file.
			wp_enqueue_style( 'wsn_styles', WSN_ASSEST_PATH . 'css/front.min.css' );

			// Add plugin js script.
			wp_enqueue_script( 'wsn_scripts', WSN_ASSEST_PATH . 'js/front.min.js' );
		}

		/**
		 * Defining the setting fields for the plugin.
		 *
		 * @method wsn_register_settings
		 * @access public
		 */
		public function wsn_register_settings() {

			// Register plugin setting fields.
			register_setting( 'wsn_setting_fields', 'is_enabled' );
			register_setting( 'wsn_setting_fields', 'notification_text' );
			register_setting( 'wsn_setting_fields', 'join_btn_label' );
			register_setting( 'wsn_setting_fields', 'leave_btn_label' );
			register_setting( 'wsn_setting_fields', 'remove_after_email' );
			register_setting( 'wsn_setting_fields', 'sent_auto_mail' );
			register_setting( 'wsn_setting_fields', 'unregistered_can_join' );
			register_setting( 'wsn_setting_fields', 'archive' );
		}

		/**
		 * Add the waitlist setting sub menu inside the woo commerce
		 */
		public static function in_stock_submenu() {

			// Add sub menu in woo commerce menu.
			add_submenu_page(
				'woocommerce',
				__( 'In-Stock Notifier', 'tmsm-woocommerce-stocknotifier' ),
				__( 'In-Stock Notifier', 'tmsm-woocommerce-stocknotifier' ),
				'manage_options',
				'in-stock-notifier-option',
				array( __CLASS__, 'wsn_waitlist_option_page' ),
				59
			);
		}

		/**
		 * Generate the setting page of the plugin.
		 *
		 * @access public
		 */
		public static function wsn_waitlist_option_page() {
			?>
            <div class="wrap">

                <h2><?php echo esc_attr__( 'In-Stock Notifier', 'tmsm-woocommerce-stocknotifier' ); ?></h2>
                <hr/>
                <form method="post" action="options.php">

					<?php settings_fields( 'wsn_setting_fields' ); ?>

					<?php do_settings_sections( 'wsn_setting' ); ?>
                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row"><?php echo esc_attr__( 'Enable Waitlist', 'tmsm-woocommerce-stocknotifier' ); ?></th>
                            <td><input type="checkbox" name="is_enabled"
                                       value="1" <?php checked( 1, get_option( 'is_enabled', true ), true ); ?> /></td>
                        </tr>

                        <tr valign="top">
                            <th scope="row"><?php echo esc_attr__( 'Join Button label', 'tmsm-woocommerce-stocknotifier' ); ?></th>
                            <td><input type="text" name="join_btn_label"
                                       value="<?php echo get_option( 'join_btn_label' ) ? esc_attr( get_option( 'join_btn_label' ) ) : esc_attr__( 'Join Waitlist', 'tmsm-woocommerce-stocknotifier' ); ?>"/>
                            </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row"><?php echo esc_attr__( 'Leave Button label', 'tmsm-woocommerce-stocknotifier' ); ?></th>
                            <td><input type="text" name="leave_btn_label"
                                       value="<?php echo get_option( 'leave_btn_label' ) ? esc_attr( get_option( 'leave_btn_label' ) ) : esc_attr__( 'Leave Waitlist', 'tmsm-woocommerce-stocknotifier' ); ?>"/>
                            </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row"><?php echo esc_attr__( 'Additional Options', 'tmsm-woocommerce-stocknotifier' ); ?></th>
                            <td><input type="checkbox" name="remove_after_email"
                                       value="1" <?php checked( 1, get_option( 'remove_after_email' ), true ); ?> /> <?php echo esc_attr__( 'Remove user after email sent.', 'tmsm-woocommerce-stocknotifier' ); ?>
                            </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row"></th>
                            <td><input type="checkbox" name="unregistered_can_join"
                                       value="1" <?php checked( 1, get_option( 'unregistered_can_join', true ), true ); ?> />
								<?php echo esc_attr__( 'Allow guest to join.', 'tmsm-woocommerce-stocknotifier' ); ?>
                            </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row"></th>
                            <td><input title="Archived user " type="checkbox" name="archive"
                                       value="1" <?php checked( 1, get_option( 'archive', true ), true ); ?> /> <?php echo esc_attr__( 'Archive user after email sent.', 'tmsm-woocommerce-stocknotifier' ); ?>
                            </td>
                        </tr>

                    </table>
					<?php submit_button(); ?>
                </form>
            </div>
			<?php
		}
	}
}