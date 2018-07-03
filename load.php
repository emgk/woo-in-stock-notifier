<?php
/**
 * InStockNotifier
 *
 * @author Govind Kumar <gkprmr@gmail.com>
 * @version 1.0.0
 * @package InStockNotifier
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

/**
 * Auto load the plugin's classes.
 */
spl_autoload_register( __NAMESPACE__ . '\\wsn_autoload_classes' );

/**
 * Auto load the plugin classes.
 *
 * @since 1.0
 *
 * @param string $class Name of the class.
 *
 * @return bool|mixed
 */
function wsn_autoload_classes( $class ) {

	// Get the name of the namespace and check if it is what we are looking for?
	if ( 0 !== strpos( $class, 'InStockNotifier\\', 0 ) )
		return false;

	static $loaded = array();

	if ( isset( $loaded[ $class ] ) )
		return $loaded[ $class ];
	
	$extension = '.php';

	$_class = strtolower( str_replace( 'InStockNotifier\\', '', $class ) );
	$_class = str_replace( '_', '-', $_class );
	$_class = 'class-' . $_class;

	if ( file_exists( WSN_CLASS_PATH . $_class . $extension ) ) {
		return $loaded[ $class ] = (bool) require_once( WSN_CLASS_PATH . $_class . $extension );
	}
}