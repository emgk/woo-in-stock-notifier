<?php
/**
 * WooCommerce In-Stock Notifier
 *
 * @author Govind Kumar <gkprmr@gmail.com>
 * @version 1.0.0
 * @package In-Stock
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
echo esc_attr( $email_heading ) . "\n\n";

echo esc_attr_x( 'Hi there,', 'Email greetings', 'tmsm-woocommerce-stocknotifier' ) . "\n\n";

echo sprintf( __( '%1$s is now back in stock at %2$s.', 'tmsm-woocommerce-stocknotifier' ), esc_html( $product_title ) , esc_html( get_bloginfo( 'name' ) ) );

echo __( 'You have been sent this email because your email address was registered in a waiting list for this product.', 'tmsm-woocommerce-stocknotifier' ) . "\n\n";
echo sprintf( __( 'If you want to purchase %1$s, please visit the following link: %2$s', 'tmsm-woocommerce-stocknotifier' ), esc_html( $product_title ), esc_url( $product_link ) ) . "\n\n";

echo esc_attr( apply_filters( 'woocommerce_email_footer_text' , get_option( 'woocommerce_email_footer_text' ) ) );
