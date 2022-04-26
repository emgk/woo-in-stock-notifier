<?php 
/**
 * InStockNotifier
 *
 * @author Govind Kumar
 * @version 1.0.0
 * @package InStockNotifier/Classes
 */

namespace InStockNotifier;

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

defined( 'ABSPATH' ) or die;

if ( ! class_exists( 'WSN_Bootstrap' ) ) {

	/**
	 * Class WSN_Bootstrap
	 * @package InStockNotifier
	 *
	 * @author Govind Kumar <gkprmr@gmail.com>
	 */
	final class WSN_Bootstrap {

		/**
		 * Class WSN_Bootstrap.
		 *
		 * @var $_instance
		 */
		protected static $_instance = null;

		/**
		 * Create Single instance
		 *
		 * @return null|WSN_Bootstrap.
		 * Create instance of the class
		 */
		public static function instance() {

			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * WSN_Bootstrap constructor.
		 */
		function __construct() {

			if(class_exists('WC_Product')){
				new WSN_Product();
				new WSN_Initialize();
				new WSN_Options();
				new WSN_ShortCode();
			}

		}

	}
}
