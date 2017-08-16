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

spl_autoload_register( __NAMESPACE__ . '\\_autoload_classes' );

function _autoload_classes( $class ) {

	if ( 0 !== strpos( $class, 'InStockNotifier\\', 0 ) )
		return;

	static $loaded = array();

	if ( isset( $loaded[ $class ] ) )
		return $loaded[ $class ];
	
	$path = WSN_CLASS_PATH;
	$extension = '.php';

	$_class = strtolower( str_replace( 'InStockNotifier\\', '', $class ) );
	$_class = str_replace( '_', '-', $_class );
	$_class = 'class-' . $_class;

	if ( file_exists( $path . $_class . $extension ) ) {
		return $loaded[ $class ] = (bool) require_once( $path . $_class . $extension );
	}
}