<?php
/**
 * WooCommerce In-Stock Notifier
 *
 * @author Govind Kumar <gkprmr@gmail.com>
 * @version 1.0.0
 * @package In-Stock
 */

do_action( 'woocommerce_email_header', $email_heading );
?>
<p>
	<?php esc_html_x( 'Hi There,', 'Email salutation', 'in-stock-notifier' ); ?>
</p>
<p>
	<?php
	echo sprintf( __( '%1$s is now back in stock at %2$s.', 'in-stock-notifier' ), '<strong>' . $product_title . '</strong>', '<strong>' . get_bloginfo( 'name' ) . '</strong>' ) . ' ';
	echo __( 'You have been sent this email because your email address was registered on a waiting list for this product.', 'in-stock-notifier' );
	?>
</p>
<p>
	<?php echo sprintf( __( 'If you would like to purchase %1$s, please visit the following <a href="%2$s">link</a>', 'in-stock-notifier' ),  '<strong>' . $product_title . '</strong>', esc_url( $product_link ) ); ?>
</p>
<?php do_action( 'woocommerce_email_footer' ); ?>
