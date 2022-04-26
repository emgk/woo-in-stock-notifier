<?php
/**
 * Plugin Name: Woo In-Stock Notifier
 * Version: 1.0.5
 * Plugin URI: http://govind.js.org/
 * Author: Govind Kumar
 * Author URI: http://govind.js.org/
 * Description: Customers can build a waiting list of products those are out of stock. They will be notified automatically via email, when products come back in stock.
 * Text Domain:in-stock-notifier
 * Domain Path: /languages/
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * WC requires at least: 2.5.0
 * WC tested up to: 5.8.0
 **/

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

// defines
define( 'WSN_PATH', plugin_dir_path( __FILE__ ) );
define( 'WSN_INCLUDE_PATH', WSN_PATH . 'inc' . DIRECTORY_SEPARATOR );
define( 'WSN_ASSEST_PATH', plugin_dir_url( __FILE__ ) . 'assets' . DIRECTORY_SEPARATOR );
define( 'WSN_CLASS_PATH', WSN_PATH . 'inc' . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR );
define( 'WSN_EMAIL_TEMPLATE_PATH', WSN_PATH . 'templates' . DIRECTORY_SEPARATOR . 'email' . DIRECTORY_SEPARATOR );

define( 'WSN_USERS_META_KEY', 'wsn_waitlist_users' );
define( 'WSN_NUM_META', 'wsn_total_num_waitlist' );

// Deactivate the plugin of woocommerce isn't activated.
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {
	add_action( 'plugins_loaded', 'wsn_pre_load' );
	load_plugin_textdomain( 'in-stock-notifier', false, dirname( plugin_basename( __FILE__ ) ) . '/language/' );
} else {
	deactivate_plugins( plugin_basename( __FILE__ ) );
	add_action( 'admin_notices', 'wsc_woo_requires' );
}

/**
 * Translate plugins
 *
 * @since 1.0.4
 */
add_action( 'plugins_loaded', 'wsn_localization_plugin' );

/**
 * Load plugin's language file
 */
function wsn_localization_plugin() {
    load_plugin_textdomain( 'in-stock-notifier', false, WSN_PATH . 'languages/' );
}

/**
 * If WooCommerce isn't activated then show the admin the notice to activate
 * the WooCommerce plugin order to make this plugin runnable.
 *
 * @since 1.0
 */
function wsc_woo_requires() {
	?>
    <div class="error">
        <p>
			<?php echo esc_htmk( 'In-Stock Notifier can\'t active because it requires WooCommerce in order to work.', 'in-stock-notifier' ); ?>
        </p>
    </div>
	<?php
}

/**
 * Make the plugin's main class globally accessible.
 *
 * @since 1.0
 */
function wsn_pre_load() {

	// Loader files.
	require( 'load.php' );

	// Include the all of the functions.
	include_once( WSN_INCLUDE_PATH . 'wsn-func.php' );

	// Making the wsn class global.
	$GLOBALS['instock_alert'] = new \InStockNotifier\WSN_Bootstrap();

}
