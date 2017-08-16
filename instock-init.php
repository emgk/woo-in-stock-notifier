<?php
/**
 * Plugin Name: Woo In-Stock Notifier
 * Version: 1.0.0
 * Plugin URI:http://blog.govindkumar.me
 * Author: Govind Kumar
 * Author URI:http://govindkumar.me
 * Description: Customers can build a waiting list of products those are out of stock. They will be notified automatically via email, when products come back in stock.
 * Text Domain:in-stock-notifier
 * Domain Path: /language/
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 **/

ini_set('display_errors', 1);
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

if ( ! function_exists( 'is_plugin_active' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
}

if ( ! function_exists( 'wp_get_current_user' ) ) {
	include( ABSPATH . 'wp-includes/pluggable.php' );
}

define( 'WSN_PATH', plugin_dir_path( __FILE__ ) );
define( 'WSN_INCLUDE_PATH', WSN_PATH . 'inc' . DIRECTORY_SEPARATOR );
define( 'WSN_ASSEST_PATH', plugin_dir_url( __FILE__ ) . 'assets' . DIRECTORY_SEPARATOR );
define( 'WSN_CLASS_PATH', WSN_PATH . 'inc' . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR );
define( 'WSN_EMAIL_TEMPLATE_PATH', WSN_PATH . 'templates' . DIRECTORY_SEPARATOR . 'email' . DIRECTORY_SEPARATOR );

define( 'WSN_USERS_META_KEY', 'wsn_waitlist_users' );
define( 'WSN_NUM_META', 'wsn_total_num_waitlist' );

if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {

	add_action( 'plugins_loaded', 'wsn_pre_load' );
	load_plugin_textdomain( 'in-stock-notifier', false, dirname( plugin_basename( __FILE__ ) ) . '/language/' );
} else {

	deactivate_plugins( plugin_basename( __FILE__ ) );
	add_action( 'admin_notices', 'wsn_woocommerce_dependecies_check' );
}

function wsn_woocommerce_dependecies_check() {
	?>
	<div class="error">
		<p><?php echo esc_attr_e( 'In-Stock Notifier can\'t active because it requires WooCommerce in order to work.', 'in-stock-notifier' ); ?></p>
	</div>
	<?php
}

function wsn_pre_load() {

	require( 'load.php' );

	// Include the all of the functions.
	include_once( WSN_INCLUDE_PATH . 'wsn-func.php' );

	// Making the wsn class global.
	$GLOBALS['instock_alert'] = new \InStockNotifier\WSN_Bootstrap();

}


